<?php

namespace App\Console\Commands;

use App\Models\tenders_opportunity;
use App\Models\tenders_context;
use App\Models\tenders_document;
use App\Models\tenders_feedback_event;
use App\Models\tenders_pipeline_entry;
use App\Models\tenders_pipeline_item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class tenders_import_python extends Command
{
    protected $signature = 'tenders:import-python {path : Ruta a un directorio con archivos JSONL} {--dry-run : Valida y cuenta sin escribir} {--chunk=500 : Registros por transaccion}';

    protected $description = 'Importa un paquete JSONL de Licitaciones generado por el servicio anterior';

    public function handle(): int
    {
        $path = rtrim((string) $this->argument('path'), '\\/');
        if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'db') {
            return $this->importSqlite($path);
        }
        if (! is_dir($path)) {
            $this->error('El directorio de importacion no existe.');
            return self::INVALID;
        }
        $file = $this->findFile($path, ['opportunities.jsonl', 'tenders_opportunities.jsonl']);
        if ($file === null) {
            $this->error('No existe opportunities.jsonl en el paquete.');
            return self::INVALID;
        }
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, min(5000, (int) $this->option('chunk')));
        $counts = ['seen' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'rejected' => 0];
        $batch = [];
        $handle = fopen($file, 'rb');
        if ($handle === false) throw new RuntimeException('No fue posible abrir el archivo de oportunidades.');
        try {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) === '') continue;
                $counts['seen']++;
                $payload = json_decode($line, true);
                if (! is_array($payload) || ! filled($payload['source'] ?? null) || ! filled($payload['source_id'] ?? null)) {
                    $counts['rejected']++;
                    continue;
                }
                $batch[] = $payload;
                if (count($batch) >= $chunkSize) {
                    $this->importBatch($batch, $counts, $dryRun);
                    $batch = [];
                }
            }
        } finally {
            fclose($handle);
        }
        if ($batch !== []) $this->importBatch($batch, $counts, $dryRun);
        $this->line(json_encode(['dry_run' => $dryRun, 'opportunities' => $counts], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function importSqlite(string $path): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, min(5000, (int) $this->option('chunk')));
        $database = new \SQLite3($path, SQLITE3_OPEN_READONLY);
        $table = $database->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='opportunities'");
        if ($table !== 'opportunities') {
            $this->error('La base SQLite no tiene la tabla opportunities.');
            return self::INVALID;
        }
        $counts = ['seen' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'rejected' => 0];
        $batch = [];
        $result = $database->query('SELECT * FROM opportunities ORDER BY id');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $counts['seen']++;
            $raw = json_decode((string) ($row['raw_payload'] ?? ''), true);
            $payload = array_merge($row, [
                'raw_payload' => is_array($raw) ? $raw : [],
                'embedding' => json_decode((string) ($row['embedding'] ?? ''), true),
            ]);
            if (! filled($payload['source'] ?? null) || ! filled($payload['source_id'] ?? null)) {
                $counts['rejected']++;
                continue;
            }
            $batch[] = $payload;
            if (count($batch) >= $chunkSize) {
                $this->importBatch($batch, $counts, $dryRun);
                $batch = [];
            }
        }
        if ($batch !== []) $this->importBatch($batch, $counts, $dryRun);
        if (! $dryRun) {
            $related = $this->importRelated($database);
            $counts['related'] = $related;
        }
        $database->close();
        $this->line(json_encode(['source' => $path, 'dry_run' => $dryRun, 'opportunities' => $counts], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function importRelated(\SQLite3 $database): array
    {
        $counts = ['contexts' => 0, 'documents' => 0, 'chunks' => 0, 'feedback' => 0, 'pipeline_items' => 0, 'pipeline_entries' => 0];
        DB::transaction(function () use ($database, &$counts): void {
            $contextResult = $database->query('SELECT * FROM company_contexts');
            while ($row = $contextResult->fetchArray(\SQLITE3_ASSOC)) {
                tenders_context::updateOrCreate(
                    ['tenant_id' => (string) $row['tenant_id']],
                    [
                        'company_name' => (string) ($row['company_name'] ?? 'OPZIO S.A.S.'),
                        'identification' => $row['identification'] ?? null,
                        'description' => $row['description'] ?? null,
                        'services' => $this->jsonArray($row['services'] ?? null),
                        'technologies' => $this->jsonArray($row['technologies'] ?? null),
                        'sectors' => $this->jsonArray($row['sectors'] ?? null),
                        'geography' => $this->jsonArray($row['geography'] ?? null),
                        'excluded_terms' => $this->jsonArray($row['excluded_terms'] ?? null),
                        'min_contract_value' => $row['min_contract_value'] ?? null,
                        'max_contract_value' => $row['max_contract_value'] ?? null,
                        'version' => max(1, (int) ($row['profile_version'] ?? 1)),
                        'updated_at' => now(),
                    ],
                );
                $counts['contexts']++;
            }

            $documentIds = [];
            $documentResult = $database->query('SELECT d.*, o.source AS opportunity_source, o.source_id AS opportunity_source_id FROM documents d JOIN opportunities o ON o.id = d.opportunity_id');
            while ($row = $documentResult->fetchArray(\SQLITE3_ASSOC)) {
                $opportunity = $this->findOpportunity((string) $row['opportunity_source'], (string) $row['opportunity_source_id']);
                if (! $opportunity) continue;
                $document = tenders_document::updateOrCreate(
                    ['source' => (string) $row['source'], 'source_document_id' => (string) $row['source_document_id']],
                    [
                        'tenders_opportunity_id' => $opportunity->id,
                        'process_id' => $row['process_id'] ?? null,
                        'filename' => (string) ($row['filename'] ?? 'document'),
                        'extension' => $row['extension'] ?? null,
                        'description' => $row['description'] ?? null,
                        'source_url' => (string) ($row['source_url'] ?? ''),
                        'source_uploaded_at' => $row['source_uploaded_at'] ?? null,
                        'size_bytes' => $row['size_bytes'] ?? null,
                        'mime_type' => $row['mime_type'] ?? null,
                        'sha256' => $row['sha256'] ?? null,
                        'status' => $row['status'] ?? 'discovered',
                        'storage_path' => $row['storage_path'] ?? null,
                        'extracted_text' => $row['extracted_text'] ?? null,
                        'error' => $row['error'] ?? null,
                    ],
                );
                $documentIds[(int) $row['id']] = $document->id;
                $counts['documents']++;
            }

            $chunkResult = $database->query('SELECT * FROM document_chunks');
            while ($row = $chunkResult->fetchArray(\SQLITE3_ASSOC)) {
                $documentId = $documentIds[(int) $row['document_id']] ?? null;
                if (! $documentId) continue;
                tenders_document::query()->find($documentId)?->chunks()->updateOrCreate(
                    ['chunk_index' => (int) $row['chunk_index']],
                    ['page_ref' => $row['page_ref'] ?? null, 'text' => (string) ($row['text'] ?? ''), 'text_hash' => (string) ($row['text_hash'] ?? hash('sha256', (string) ($row['text'] ?? '')))],
                );
                $counts['chunks']++;
            }

            $feedbackResult = $database->query('SELECT f.*, o.source, o.source_id FROM feedback_events f JOIN opportunities o ON o.id = f.opportunity_id');
            while ($row = $feedbackResult->fetchArray(\SQLITE3_ASSOC)) {
                $opportunity = $this->findOpportunity((string) $row['source'], (string) $row['source_id']);
                if (! $opportunity) continue;
                tenders_feedback_event::firstOrCreate(
                    ['tenant_id' => (string) $row['tenant_id'], 'idempotency_key' => (string) $row['idempotency_key']],
                    ['tenders_opportunity_id' => $opportunity->id, 'actor_id' => $row['actor_id'] ?? null, 'event_type' => (string) $row['event_type'], 'reason_code' => $row['reason_code'] ?? null, 'notes' => $row['notes'] ?? null, 'weight' => $row['weight'] ?? 0, 'created_at' => $row['created_at'] ?? now(), 'updated_at' => $row['created_at'] ?? now()],
                );
                $counts['feedback']++;
            }

            $pipelineItemResult = $database->query('SELECT p.*, o.source, o.source_id FROM pipeline_items p JOIN opportunities o ON o.id = p.opportunity_id');
            while ($row = $pipelineItemResult->fetchArray(\SQLITE3_ASSOC)) {
                $opportunity = $this->findOpportunity((string) $row['source'], (string) $row['source_id']);
                if (! $opportunity) continue;
                tenders_pipeline_item::updateOrCreate(
                    ['tenant_id' => (string) $row['tenant_id'], 'tenders_opportunity_id' => $opportunity->id],
                    ['stage' => $row['stage'] ?? 'saved', 'owner_id' => $row['owner_id'] ?? null, 'due_at' => $row['due_at'] ?? null, 'outcome' => $row['outcome'] ?? null, 'notes' => $row['notes'] ?? null, 'created_at' => $row['created_at'] ?? now(), 'updated_at' => $row['updated_at'] ?? now()],
                );
                $counts['pipeline_items']++;
            }

            $pipelineEntryResult = $database->query('SELECT p.*, o.source, o.source_id FROM pipeline_entries p JOIN opportunities o ON o.id = p.opportunity_id');
            while ($row = $pipelineEntryResult->fetchArray(\SQLITE3_ASSOC)) {
                $opportunity = $this->findOpportunity((string) $row['source'], (string) $row['source_id']);
                if (! $opportunity) continue;
                tenders_pipeline_entry::updateOrCreate(
                    ['id' => (int) $row['id']],
                    ['tenant_id' => (string) $row['tenant_id'], 'tenders_opportunity_id' => $opportunity->id, 'actor_id' => $row['actor_id'] ?? null, 'stage' => $row['stage'] ?? 'saved', 'due_at' => $row['due_at'] ?? null, 'outcome' => $row['outcome'] ?? null, 'notes' => $row['notes'] ?? null, 'deleted_at' => $row['deleted_at'] ?? null, 'created_at' => $row['created_at'] ?? now(), 'updated_at' => $row['updated_at'] ?? now()],
                );
                $counts['pipeline_entries']++;
            }
        });

        return $counts;
    }

    private function findOpportunity(string $source, string $sourceId): ?tenders_opportunity
    {
        return tenders_opportunity::query()->where('source', $source)->where('source_id', $sourceId)->first();
    }

    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function importBatch(array $batch, array &$counts, bool $dryRun): void
    {
        if ($dryRun) {
            foreach ($batch as $payload) $this->classify($payload, $counts, true);
            return;
        }
        DB::transaction(function () use ($batch, &$counts): void {
            foreach ($batch as $payload) $this->classify($payload, $counts, false);
        });
    }

    private function classify(array $payload, array &$counts, bool $dryRun): void
    {
        $values = $this->values($payload);
        if ($dryRun) {
            $counts['created']++;
            return;
        }
        $record = tenders_opportunity::query()->where('source', $values['source'])->where('source_id', $values['source_id'])->first();
        if ($record === null) {
            if (! $dryRun) {
                $record = tenders_opportunity::create($values);
                $record->versions()->create([
                    'payload_hash' => $values['payload_hash'],
                    'changed_fields' => array_keys($values),
                    'raw_payload' => $values['raw_payload'],
                    'observed_at' => now(),
                ]);
            }
            $counts['created']++;
            return;
        }
        if ($record->payload_hash === $values['payload_hash']) {
            $counts['unchanged']++;
            return;
        }
        if (! $dryRun) {
            $record->fill($values)->save();
            $record->versions()->create([
                'payload_hash' => $values['payload_hash'],
                'changed_fields' => array_keys($values),
                'raw_payload' => $values['raw_payload'],
                'observed_at' => now(),
            ]);
        }
        $counts['updated']++;
    }

    private function values(array $payload): array
    {
        $raw = is_array($payload['raw_payload'] ?? null) ? $payload['raw_payload'] : $payload;
        $payloadHash = (string) ($payload['payload_hash'] ?? hash('sha256', json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        $allowed = [
            'source', 'source_id', 'source_process_id', 'reference', 'title', 'description', 'entity', 'entity_nit',
            'department', 'city', 'amount', 'currency', 'phase', 'procurement_method', 'contract_type', 'category_code',
            'category_text', 'status', 'source_status', 'opening_status', 'source_url', 'published_at', 'last_published_at',
            'deadline_at', 'embedding', 'embedding_model', 'embedding_hash',
        ];
        $values = array_intersect_key($payload, array_flip($allowed));
        $values['source'] = (string) ($values['source'] ?? '');
        $values['source_id'] = (string) ($values['source_id'] ?? '');
        $values['title'] = (string) ($values['title'] ?? 'Oportunidad sin titulo');
        $values['entity'] = (string) ($values['entity'] ?? 'Entidad no identificada');
        $values['status'] = (string) ($values['status'] ?? 'closed');
        $values['payload_hash'] = $payloadHash;
        $values['raw_payload'] = $raw;
        $values['updated_at'] = now();
        $values['created_at'] = $values['created_at'] ?? now();

        if ($values['source'] === '' || $values['source_id'] === '') throw new RuntimeException('Registro JSONL sin source/source_id.');
        return $values;
    }

    private function findFile(string $path, array $names): ?string
    {
        foreach ($names as $name) {
            $candidate = $path.DIRECTORY_SEPARATOR.$name;
            if (is_file($candidate)) return $candidate;
        }
        return null;
    }
}
