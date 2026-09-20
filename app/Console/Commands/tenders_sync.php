<?php

namespace App\Console\Commands;

use App\Services\Tenders\tenders_configuration_service;
use App\Models\tenders_opportunity;
use App\Services\Tenders\tenders_document_service;
use App\Services\Tenders\tenders_embedding_service;
use App\Services\Tenders\tenders_sync_service;
use Illuminate\Console\Command;
use Throwable;

class tenders_sync extends Command
{
    protected $signature = 'tenders:sync
        {--source=all : Fuente SECOP: all, secop1 o secop2}
        {--mode=incremental : Modo incremental o full}
        {--lookback-days= : Dias de ventana}
        {--page-size= : Filas por lote}
        {--max-pages= : Paginas maximas por fuente}
        {--documents : Descubre y procesa documentos de oportunidades recientes}
        {--embeddings : Genera embeddings si estan activos en la configuracion}
        {--reset-cursor : Reinicia el cursor de la fuente}';

    protected $description = 'Sincroniza el catalogo SECOP local de Licitaciones';

    public function handle(
        tenders_configuration_service $configuration,
        tenders_sync_service $service,
        tenders_document_service $documentService,
        tenders_embedding_service $embeddingService,
    ): int
    {
        $source = (string) $this->option('source');
        $mode = (string) $this->option('mode');
        if (! in_array($source, ['all', 'secop1', 'secop2'], true)) {
            $this->error('La fuente debe ser all, secop1 o secop2.');
            return self::INVALID;
        }
        if (! in_array($mode, ['incremental', 'full'], true)) {
            $this->error('El modo debe ser incremental o full.');
            return self::INVALID;
        }
        $connection = $configuration->get();
        try {
            $result = $source === 'all'
                ? $service->syncAll(
                    $connection,
                    $mode,
                    (int) ($this->option('lookback-days') ?: data_get($configuration->settings($connection), 'transport.lookback_days', 7)),
                    (int) ($this->option('page-size') ?: data_get($configuration->settings($connection), 'transport.page_size', 250)),
                    (int) ($this->option('max-pages') ?: data_get($configuration->settings($connection), 'transport.max_pages', 20)),
                    (bool) $this->option('reset-cursor'),
                )
                : $this->syncSource($service, $connection, $source, $mode, $configuration);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        if ($this->option('documents')) {
            $result['documents'] = $this->processDocuments($documentService);
        }
        if ($this->option('embeddings')) {
            $result['embeddings'] = $embeddingService->process();
        }
        $this->info('Sincronizacion SECOP completada.');
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function processDocuments(tenders_document_service $service): array
    {
        $result = ['opportunities' => 0, 'discovered' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'processed' => 0, 'failed' => 0];
        $opportunities = tenders_opportunity::query()
            ->whereIn('status', ['open', 'updated'])
            ->whereNotNull('source_process_id')
            ->latest('last_published_at')
            ->limit(100)
            ->get();
        foreach ($opportunities as $opportunity) {
            $result['opportunities']++;
            $stats = $service->discoverForOpportunity($opportunity);
            foreach (['discovered', 'created', 'updated', 'unchanged'] as $field) $result[$field] += $stats[$field] ?? 0;
            foreach ($opportunity->documents()->where('status', 'discovered')->get() as $document) {
                $processed = $service->process($document);
                $result['processed'] += $processed['status'] === 'processed' ? 1 : 0;
                $result['failed'] += $processed['status'] === 'failed' ? 1 : 0;
            }
        }

        return $result;
    }

    private function syncSource(tenders_sync_service $service, $connection, string $source, string $mode, tenders_configuration_service $configuration): array
    {
        $settings = $configuration->settings($connection);
        $days = (int) ($this->option('lookback-days') ?: data_get($settings, 'transport.lookback_days', 7));
        $pageSize = (int) ($this->option('page-size') ?: data_get($settings, 'transport.page_size', 250));
        $maxPages = max(1, (int) ($this->option('max-pages') ?: data_get($settings, 'transport.max_pages', 20)));
        $results = [];
        $cursorAt = null;
        $cursorId = null;
        for ($page = 0; $page < $maxPages; $page++) {
            $result = $service->syncBatch($connection, $source, $days, $pageSize, $cursorAt, $cursorId, $mode, (bool) $this->option('reset-cursor') && $page === 0);
            $results[] = $result;
            if (! $result['has_more']) break;
            $cursorAt = $result['next_cursor_at'];
            $cursorId = $result['next_cursor_id'];
        }

        return ['ok' => true, 'sources' => [$source => $results]];
    }
}
