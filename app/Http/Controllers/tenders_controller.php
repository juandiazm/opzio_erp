<?php

namespace App\Http\Controllers;

use App\Services\Tenders\tenders_configuration_service;
use App\Services\Tenders\tenders_context_service;
use App\Services\Tenders\tenders_discovery_service;
use App\Services\Tenders\tenders_pipeline_service;
use App\Services\Tenders\tenders_sync_service;
use App\traits\tenders_configuration_trait;
use App\traits\tenders_discovery_trait;
use App\traits\tenders_pipeline_trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class tenders_controller extends Controller
{
    use tenders_configuration_trait;
    use tenders_discovery_trait;
    use tenders_pipeline_trait;

    public function page(
        tenders_context_service $contextService,
        tenders_configuration_service $configurationService
    ) {
        $connection = $configurationService->get();
        $context = $contextService->get($this->tenantId());
        $settings = $configurationService->settings($connection);

        return view('erp.tenders', compact('context', 'connection', 'settings'));
    }

    public function configuration_save(Request $request, tenders_configuration_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_SaveConfiguration($request, $service));
    }

    public function configuration_test(tenders_configuration_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_TestConfiguration($service));
    }

    public function discovery(Request $request, tenders_discovery_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_Discovery($request, $service));
    }

    public function sync(Request $request, tenders_configuration_service $configurationService, tenders_sync_service $service): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:all,secop1,secop2'],
            'mode' => ['nullable', 'in:incremental,full'],
            'lookback_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:250'],
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor_at' => ['nullable', 'date'],
            'cursor_id' => ['nullable', 'string', 'max:200'],
            'reset_cursor' => ['nullable', 'boolean'],
        ]);
        $connection = $configurationService->get();
        $mode = $validated['mode'] ?? 'incremental';
        $days = (int) ($validated['lookback_days'] ?? data_get($configurationService->settings($connection), 'transport.lookback_days', 7));
        $pageSize = (int) ($validated['page_size'] ?? data_get($configurationService->settings($connection), 'transport.page_size', 250));
        $result = $validated['source'] === 'all'
            ? $service->syncAll($connection, $mode, $days, $pageSize, (int) ($validated['max_pages'] ?? 1), (bool) ($validated['reset_cursor'] ?? false))
            : $service->syncBatch(
                $connection,
                $validated['source'],
                $days,
                $pageSize,
                $validated['cursor_at'] ?? null,
                $validated['cursor_id'] ?? null,
                $mode,
                (bool) ($validated['reset_cursor'] ?? false),
            );

        return response()->json([
            'status' => 1,
            'message' => 'Sincronizacion SECOP procesada.',
            'data' => $result,
            'meta' => [],
        ], 202);
    }

    public function update_context(Request $request, tenders_context_service $contextService): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'identification' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'services' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'string', 'max:5000'],
            'sectors' => ['nullable', 'string', 'max:3000'],
            'geography' => ['nullable', 'string', 'max:3000'],
            'excluded_terms' => ['nullable', 'string', 'max:5000'],
            'min_contract_value' => ['nullable', 'numeric', 'min:0'],
            'max_contract_value' => ['nullable', 'numeric', 'min:0'],
        ]);
        $context = $contextService->save($this->tenantId(), $validated, $this->Tenders_ActorId());

        return response()->json([
            'status' => 1,
            'message' => 'Contexto guardado correctamente.',
            'data' => $contextService->payload($context),
            'meta' => [],
        ]);
    }

    public function opportunity(string $opportunityId, tenders_discovery_service $service): JsonResponse
    {
        $data = $service->detail($this->tenantId(), $opportunityId);
        if ($data === null) {
            return response()->json(['status' => 0, 'message' => 'Oportunidad no encontrada.', 'data' => []], 404);
        }

        return response()->json(['status' => 1, 'message' => 'Detalle consultado.', 'data' => $data, 'meta' => []]);
    }

    public function feedback(Request $request, tenders_pipeline_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_Feedback($request, $service));
    }

    public function pipeline(Request $request, tenders_pipeline_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_SavePipeline($request, $service));
    }

    public function save_opportunity(string $opportunityId, tenders_pipeline_service $service): JsonResponse
    {
        return $this->tendersJson(fn (): array => $this->Tenders_SaveOpportunity($opportunityId, $service));
    }

    public function pipeline_history(string $opportunityId, Request $request, tenders_pipeline_service $service): JsonResponse
    {
        $data = $this->Tenders_PipelineHistory($opportunityId, $request, $service);
        if ($data === null) {
            return response()->json(['status' => 0, 'message' => 'Oportunidad no encontrada.', 'data' => []], 404);
        }

        return response()->json(['status' => 1, 'data' => $data['data'], 'meta' => $data['meta']]);
    }

    public function update_pipeline_entry(string $opportunityId, int $entryId, Request $request, tenders_pipeline_service $service): JsonResponse
    {
        $data = $this->Tenders_UpdatePipelineEntry($opportunityId, $entryId, $request, $service);
        if ($data === null) {
            return response()->json(['status' => 0, 'message' => 'Registro de seguimiento no encontrado.', 'data' => []], 404);
        }

        return response()->json(['status' => 1, 'message' => 'Seguimiento actualizado.', 'data' => $data]);
    }

    public function delete_pipeline_entry(string $opportunityId, int $entryId, tenders_pipeline_service $service): JsonResponse
    {
        $deleted = $this->Tenders_DeletePipelineEntry($opportunityId, $entryId, $service);
        if ($deleted === null) {
            return response()->json(['status' => 0, 'message' => 'Oportunidad no encontrada.', 'data' => []], 404);
        }
        if (! $deleted) {
            return response()->json(['status' => 0, 'message' => 'Registro de seguimiento no encontrado.', 'data' => []], 404);
        }

        return response()->json(['status' => 1, 'message' => 'Seguimiento eliminado.', 'data' => ['id' => $entryId, 'deleted' => true]]);
    }

    public function applications(Request $request, tenders_pipeline_service $service): JsonResponse
    {
        $result = $this->Tenders_Applications($request, $service);

        return response()->json(['status' => 1, 'data' => $result['data'], 'meta' => $result['meta']]);
    }

    public function sync_status(Request $request, tenders_sync_service $service): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'in:secop1,secop2'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'status' => 1,
            'data' => $service->status($validated['source'] ?? null, (int) ($validated['limit'] ?? 20)),
            'meta' => ['source' => $validated['source'] ?? null],
        ]);
    }

    private function tendersJson(callable $callback): JsonResponse
    {
        try {
            $result = $callback();
            if (($result['ok'] ?? true) === false) {
                return response()->json([
                    'status' => 0,
                    'message' => $result['message'] ?? 'La operacion de Licitaciones no fue exitosa.',
                    'data' => $result['data'] ?? $result,
                ], 422);
            }

            return response()->json([
                'status' => 1,
                'message' => $result['message'] ?? 'Operacion completada.',
                'data' => $result['data'] ?? $result,
                'meta' => $result['meta'] ?? [],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 0,
                'message' => 'La informacion enviada no es valida.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 0,
                'message' => $exception->getMessage(),
                'data' => [],
            ], 422);
        }
    }

    private function tenantId(): string
    {
        return (string) config('tenders.tenant_id', 'opzio');
    }
}
