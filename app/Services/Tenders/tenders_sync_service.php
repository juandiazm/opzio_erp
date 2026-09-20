<?php

namespace App\Services\Tenders;

use App\Models\tenders_connection;
use App\Models\tenders_opportunity;
use App\Models\tenders_opportunity_version;
use App\Models\tenders_sync_run;
use App\Models\tenders_sync_state;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class tenders_sync_service
{
    public function __construct(
        private readonly tenders_secop_client $client,
        private readonly tenders_normalization_service $normalizer,
    ) {
    }

    public function syncBatch(
        tenders_connection $connection,
        string $source,
        int $days = 7,
        int $pageSize = 250,
        ?string $cursorAt = null,
        ?string $cursorId = null,
        string $mode = 'incremental',
        bool $resetCursor = false,
    ): array {
        if (! in_array($source, ['secop1', 'secop2'], true)) {
            throw new RuntimeException('La sincronizacion requiere una fuente SECOP valida.');
        }
        $settings = app(tenders_configuration_service::class)->settings($connection);
        $sourceSettings = $settings['sources'][$source] ?? null;
        if (! is_array($sourceSettings) || ! ($sourceSettings['enabled'] ?? false)) {
            throw new RuntimeException('La fuente SECOP '.$source.' no esta activa.');
        }
        $days = max(1, min(3650, $days));
        $pageSize = max(1, min(250, $pageSize));
        $state = tenders_sync_state::query()->firstOrCreate([
            'tenders_connection_id' => $connection->id,
            'source' => $source,
        ]);
        $storedCursorAt = $state->cursor_at;
        $storedCursorId = $state->cursor_id;
        if ($resetCursor || $mode === 'full') {
            $storedCursorAt = null;
            $storedCursorId = null;
        }
        $from = $this->parseDate($cursorAt)
            ?: $storedCursorAt
            ?: now()->subDays($days);
        $effectiveCursorAt = $this->parseDate($cursorAt) ?: ($resetCursor || $mode === 'full' ? null : $storedCursorAt);
        $effectiveCursorId = $cursorId ?: ($resetCursor || $mode === 'full' ? null : $storedCursorId);
        $run = tenders_sync_run::create([
            'tenders_connection_id' => $connection->id,
            'source' => $source,
            'mode' => $mode,
            'status' => 'running',
            'cursor_from' => $from,
            'cursor_to' => now(),
            'parameters' => compact('days', 'pageSize', 'cursorAt', 'cursorId', 'mode', 'resetCursor'),
            'started_at' => now(),
        ]);
        $stats = [
            'pages' => 1,
            'rows_seen' => 0,
            'rows_created' => 0,
            'rows_updated' => 0,
            'rows_unchanged' => 0,
            'rows_rejected' => 0,
        ];

        try {
            $rows = $this->client->forConnection($connection)->fetchPage(
                $source,
                $from,
                $effectiveCursorAt,
                $effectiveCursorId,
                $pageSize,
            );
            $nextCursorAt = $effectiveCursorAt;
            $nextCursorId = $effectiveCursorId;
            foreach ($rows as $row) {
                $stats['rows_seen']++;
                $normalized = $this->normalizer->normalize($source, $row);
                if ($normalized === null) {
                    $stats['rows_rejected']++;
                    continue;
                }
                $hash = $this->hashPayload($row);
                $outcome = $this->upsertOpportunity($normalized, $row, $hash);
                $stats['rows_'.$outcome]++;
                $rowCursorAt = $this->normalizer->parseDate($row[$sourceSettings['timestamp_field']] ?? null);
                $rowCursorId = (string) ($row[$sourceSettings['id_field']] ?? '');
                if ($rowCursorAt !== null && $rowCursorId !== '') {
                    $nextCursorAt = $rowCursorAt;
                    $nextCursorId = $rowCursorId;
                }
            }
            $hasMore = count($rows) >= $pageSize;
            if ($nextCursorAt !== null && $nextCursorId !== null) {
                $state->cursor_at = $nextCursorAt;
                $state->cursor_id = $nextCursorId;
            }
            if (! $hasMore) {
                $state->last_success_at = now();
                $connection->update(['status' => 'active', 'last_sync_at' => now(), 'last_error' => null]);
            }
            $state->save();
            $run->update(array_merge($stats, [
                'status' => 'succeeded',
                'finished_at' => now(),
            ]));

            return [
                'ok' => true,
                'complete' => ! $hasMore,
                'has_more' => $hasMore,
                'source' => $source,
                'next_cursor_at' => $nextCursorAt?->toIso8601String(),
                'next_cursor_id' => $nextCursorId,
                'run_id' => $run->id,
                'message' => $hasMore
                    ? 'Lote de '.$source.' sincronizado. Continuando...'
                    : 'Sincronizacion de '.$source.' completada.',
                'stats' => $stats,
            ];
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $connection->update(['status' => 'error', 'last_error' => $message]);
            $run->update(array_merge($stats, [
                'status' => 'failed',
                'error_message' => $message,
                'finished_at' => now(),
            ]));
            throw $exception;
        }
    }

    public function syncAll(
        tenders_connection $connection,
        string $mode = 'incremental',
        int $days = 7,
        int $pageSize = 250,
        int $maxPages = 20,
        bool $resetCursor = false,
    ): array {
        $settings = app(tenders_configuration_service::class)->settings($connection);
        $sources = collect($settings['sources'])
            ->filter(fn (array $source): bool => (bool) ($source['enabled'] ?? false))
            ->keys()
            ->all();
        $results = [];
        foreach ($sources as $source) {
            $cursorAt = null;
            $cursorId = null;
            $sourceResults = [];
            for ($page = 0; $page < max(1, $maxPages); $page++) {
                $result = $this->syncBatch($connection, $source, $days, $pageSize, $cursorAt, $cursorId, $mode, $resetCursor && $page === 0);
                $sourceResults[] = $result;
                if (! $result['has_more']) break;
                $cursorAt = $result['next_cursor_at'];
                $cursorId = $result['next_cursor_id'];
            }
            $results[$source] = $sourceResults;
        }

        return ['ok' => true, 'sources' => $results];
    }

    public function status(?string $source = null, int $limit = 20): array
    {
        return tenders_sync_run::query()
            ->when($source, fn ($query) => $query->where('source', $source))
            ->latest('started_at')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->map(fn (tenders_sync_run $run): array => [
                'id' => $run->id,
                'source' => $run->source,
                'mode' => $run->mode,
                'status' => $run->status,
                'started_at' => $run->started_at?->toIso8601String(),
                'finished_at' => $run->finished_at?->toIso8601String(),
                'pages' => $run->pages,
                'rows_seen' => $run->rows_seen,
                'rows_created' => $run->rows_created,
                'rows_updated' => $run->rows_updated,
                'rows_unchanged' => $run->rows_unchanged,
                'rows_rejected' => $run->rows_rejected,
                'error' => $run->error_message,
                'parameters' => $run->parameters ?? [],
            ])
            ->all();
    }

    private function upsertOpportunity(array $normalized, array $rawPayload, string $payloadHash): string
    {
        return DB::transaction(function () use ($normalized, $rawPayload, $payloadHash): string {
            $record = tenders_opportunity::query()
                ->where('source', $normalized['source'])
                ->where('source_id', $normalized['source_id'])
                ->first();
            if ($record === null) {
                $record = tenders_opportunity::create(array_merge($normalized, [
                    'payload_hash' => $payloadHash,
                    'raw_payload' => $rawPayload,
                ]));
                $record->versions()->create([
                    'payload_hash' => $payloadHash,
                    'changed_fields' => array_keys($normalized),
                    'raw_payload' => $rawPayload,
                    'observed_at' => now(),
                ]);
                return 'created';
            }
            if ($record->payload_hash === $payloadHash) return 'unchanged';
            $changedFields = [];
            foreach ($normalized as $field => $value) {
                if (! $this->sameValue($record->{$field}, $value)) $changedFields[] = $field;
            }
            $record->fill(array_merge($normalized, [
                'payload_hash' => $payloadHash,
                'raw_payload' => $rawPayload,
            ]));
            if ($record->status === 'open' && $normalized['status'] === 'open') $record->status = 'updated';
            $record->save();
            $record->versions()->create([
                'payload_hash' => $payloadHash,
                'changed_fields' => $changedFields,
                'raw_payload' => $rawPayload,
                'observed_at' => now(),
            ]);
            return 'updated';
        });
    }

    private function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') return null;
        try { return Carbon::parse($value)->utc(); } catch (\Throwable) { return null; }
    }

    private function sameValue(mixed $left, mixed $right): bool
    {
        if ($left instanceof Carbon && $right instanceof Carbon) return $left->equalTo($right);
        if ($left instanceof \DateTimeInterface && $right instanceof \DateTimeInterface) return $left->getTimestamp() === $right->getTimestamp();
        return $left == $right;
    }
}
