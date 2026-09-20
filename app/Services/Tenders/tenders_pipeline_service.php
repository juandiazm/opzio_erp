<?php

namespace App\Services\Tenders;

use App\Models\tenders_feedback_event;
use App\Models\tenders_opportunity;
use App\Models\tenders_outbox_event;
use App\Models\tenders_pipeline_entry;
use App\Models\tenders_pipeline_item;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class tenders_pipeline_service
{
    private const WEIGHTS = [
        'interested' => 1,
        'not_interested' => -1,
        'saved' => 2,
        'preparing' => 3,
        'submitted' => 4,
        'won' => 5,
        'lost' => 0,
        'dismissed' => -3,
    ];

    public function feedback(string $tenantId, string $publicId, string|int|null $actorId, array $payload, string $idempotencyKey): array
    {
        $opportunity = $this->findOrFail($publicId);
        $existing = tenders_feedback_event::query()->where('tenant_id', $tenantId)->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return ['feedback_id' => $existing->id, 'duplicate' => true, 'feedback_state' => $this->feedbackState($existing->event_type)];
        }
        $event = DB::transaction(function () use ($tenantId, $publicId, $actorId, $payload, $idempotencyKey, $opportunity): tenders_feedback_event {
            $event = tenders_feedback_event::create([
                'tenant_id' => $tenantId,
                'tenders_opportunity_id' => $opportunity->id,
                'actor_id' => (string) $actorId,
                'event_type' => $payload['event_type'],
                'reason_code' => $payload['reason_code'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'weight' => self::WEIGHTS[$payload['event_type']] ?? 0,
                'idempotency_key' => $idempotencyKey,
            ]);
            $this->outbox($tenantId, 'feedback.created', $publicId, ['feedback_id' => $event->id, 'event_type' => $event->event_type]);
            return $event;
        });

        return ['feedback_id' => $event->id, 'duplicate' => false, 'feedback_state' => $this->feedbackState($event->event_type)];
    }

    public function save(string $tenantId, string $publicId, string|int|null $actorId): array
    {
        $opportunity = $this->findOrFail($publicId);
        $result = DB::transaction(function () use ($tenantId, $publicId, $actorId, $opportunity): array {
            $item = tenders_pipeline_item::firstOrCreate(
                ['tenant_id' => $tenantId, 'tenders_opportunity_id' => $opportunity->id],
                ['stage' => 'saved', 'owner_id' => (string) $actorId],
            );
            $created = $item->wasRecentlyCreated;
            $this->outbox($tenantId, 'pipeline.opportunity_saved', $publicId, ['owner_id' => $actorId]);
            return [$created, $item->fresh()];
        });

        return ['created' => $result[0], 'data' => $this->summary($result[1], $opportunity)];
    }

    public function pipeline(string $tenantId, string $publicId, string|int|null $actorId, array $payload): array
    {
        $opportunity = $this->findOrFail($publicId);
        $item = DB::transaction(function () use ($tenantId, $publicId, $actorId, $payload, $opportunity): tenders_pipeline_item {
            $item = tenders_pipeline_item::firstOrCreate(
                ['tenant_id' => $tenantId, 'tenders_opportunity_id' => $opportunity->id],
                ['stage' => $payload['stage']],
            );
            $entry = tenders_pipeline_entry::create([
                'tenant_id' => $tenantId,
                'tenders_opportunity_id' => $opportunity->id,
                'actor_id' => (string) $actorId,
                'stage' => $payload['stage'],
                'due_at' => $payload['due_at'] ?? null,
                'outcome' => $payload['outcome'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ]);
            $item->fill([
                'stage' => $entry->stage,
                'owner_id' => (string) $actorId,
                'due_at' => $entry->due_at,
                'outcome' => $entry->outcome,
                'notes' => $entry->notes,
            ])->save();
            $this->outbox($tenantId, 'pipeline.entry_created', $publicId, ['entry_id' => $entry->id, 'stage' => $entry->stage]);
            return $item->fresh();
        });

        return $this->summary($item, $opportunity);
    }

    public function applications(string $tenantId, ?string $stage, ?string $search, int $page, int $perPage): array
    {
        $query = tenders_pipeline_item::query()->with('opportunity')->where('tenant_id', $tenantId);
        if ($stage) $query->where('stage', $stage);
        if ($search) {
            $query->whereHas('opportunity', function ($builder) use ($search): void {
                $pattern = '%'.trim($search).'%';
                $builder->where('title', 'like', $pattern)->orWhere('entity', 'like', $pattern)->orWhere('reference', 'like', $pattern)->orWhere('source_id', 'like', $pattern);
            });
        }
        $paginator = $query->latest('updated_at')->paginate(max(1, min(50, $perPage)), ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(fn ($item): array => $this->summary($item, $item->opportunity))->all(),
            'meta' => [
                'tenant_id' => $tenantId,
                'stage' => $stage,
                'search' => $search,
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];
    }

    public function history(string $tenantId, string $publicId, int $page, int $perPage): ?array
    {
        $opportunity = $this->find($publicId);
        if (! $opportunity) return null;
        $paginator = tenders_pipeline_entry::query()->where('tenant_id', $tenantId)->where('tenders_opportunity_id', $opportunity->id)->whereNull('deleted_at')->latest('created_at')->paginate(max(1, min(100, $perPage)), ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(fn ($entry): array => $this->entry($entry, $opportunity))->all(),
            'meta' => [
                'tenant_id' => $tenantId,
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];
    }

    public function updateEntry(string $tenantId, string $publicId, int $entryId, string|int|null $actorId, array $payload): ?array
    {
        $opportunity = $this->find($publicId);
        if (! $opportunity) return null;
        $entry = tenders_pipeline_entry::query()->whereKey($entryId)->where('tenant_id', $tenantId)->where('tenders_opportunity_id', $opportunity->id)->whereNull('deleted_at')->first();
        if (! $entry) return null;
        DB::transaction(function () use ($entry, $payload, $tenantId, $publicId, $actorId, $opportunity): void {
            $entry->fill(['stage' => $payload['stage'], 'due_at' => $payload['due_at'] ?? null, 'outcome' => $payload['outcome'] ?? null, 'notes' => $payload['notes'] ?? null])->save();
            $this->refreshItem($tenantId, $opportunity);
            $this->outbox($tenantId, 'pipeline.entry_updated', $publicId, ['entry_id' => $entry->id, 'actor_id' => $actorId, 'stage' => $entry->stage]);
        });

        return $this->entry($entry->fresh(), $opportunity);
    }

    public function deleteEntry(string $tenantId, string $publicId, int $entryId, string|int|null $actorId): ?bool
    {
        $opportunity = $this->find($publicId);
        if (! $opportunity) return null;
        $entry = tenders_pipeline_entry::query()->whereKey($entryId)->where('tenant_id', $tenantId)->where('tenders_opportunity_id', $opportunity->id)->whereNull('deleted_at')->first();
        if (! $entry) return false;
        DB::transaction(function () use ($entry, $tenantId, $publicId, $actorId, $opportunity): void {
            $entry->deleted_at = now();
            $entry->save();
            $this->refreshItem($tenantId, $opportunity);
            $this->outbox($tenantId, 'pipeline.entry_deleted', $publicId, ['entry_id' => $entry->id, 'actor_id' => $actorId]);
        });

        return true;
    }

    private function refreshItem(string $tenantId, tenders_opportunity $opportunity): void
    {
        $latest = tenders_pipeline_entry::query()->where('tenant_id', $tenantId)->where('tenders_opportunity_id', $opportunity->id)->whereNull('deleted_at')->latest('created_at')->latest('id')->first();
        $item = tenders_pipeline_item::query()->firstOrCreate(['tenant_id' => $tenantId, 'tenders_opportunity_id' => $opportunity->id]);
        if (! $latest) {
            $item->fill(['stage' => 'saved', 'due_at' => null, 'outcome' => null, 'notes' => null])->save();
            return;
        }
        $item->fill(['stage' => $latest->stage, 'owner_id' => $latest->actor_id, 'due_at' => $latest->due_at, 'outcome' => $latest->outcome, 'notes' => $latest->notes])->save();
    }

    private function outbox(string $tenantId, string $type, string $aggregateId, array $payload): void
    {
        tenders_outbox_event::create(['tenant_id' => $tenantId, 'event_type' => $type, 'aggregate_type' => 'opportunity', 'aggregate_id' => $aggregateId, 'payload' => $payload, 'available_at' => now()]);
    }

    private function find(?string $publicId): ?tenders_opportunity
    {
        [$source, $sourceId] = array_pad(explode(':', (string) $publicId, 2), 2, null);
        return $source && $sourceId ? tenders_opportunity::query()->where('source', $source)->where('source_id', $sourceId)->first() : null;
    }

    private function findOrFail(string $publicId): tenders_opportunity
    {
        return $this->find($publicId) ?? throw new RuntimeException('Oportunidad no encontrada.');
    }

    private function feedbackState(string $eventType): ?string
    {
        return in_array($eventType, ['interested', 'not_interested'], true) ? $eventType : null;
    }

    private function summary(tenders_pipeline_item $item, tenders_opportunity $opportunity): array
    {
        return [
            'opportunity_id' => $opportunity->source.':'.$opportunity->source_id,
            'title' => $opportunity->title,
            'entity' => $opportunity->entity,
            'reference' => $opportunity->reference,
            'source' => $opportunity->source,
            'source_url' => $opportunity->source_url,
            'amount' => $opportunity->amount === null ? null : (float) $opportunity->amount,
            'deadline' => $opportunity->deadline_at?->toIso8601String(),
            'stage' => $item->stage,
            'owner_id' => $item->owner_id,
            'due_at' => $item->due_at?->toIso8601String(),
            'outcome' => $item->outcome,
            'notes' => $item->notes,
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private function entry(tenders_pipeline_entry $entry, tenders_opportunity $opportunity): array
    {
        return [
            'id' => $entry->id,
            'opportunity_id' => $opportunity->source.':'.$opportunity->source_id,
            'actor_id' => $entry->actor_id,
            'stage' => $entry->stage,
            'due_at' => $entry->due_at?->toIso8601String(),
            'outcome' => $entry->outcome,
            'notes' => $entry->notes,
            'created_at' => $entry->created_at?->toIso8601String(),
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }
}
