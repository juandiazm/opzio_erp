<?php

namespace App\Http\Controllers;

use App\Services\Tenders\tenders_ai_client;
use App\Services\Tenders\tenders_context_service;
use Illuminate\Http\Request;

class tenders_controller extends Controller
{
    public function page(tenders_context_service $contextService)
    {
        $context = $contextService->get($this->tenantId());

        return view('erp.tenders', compact('context'));
    }

    public function discovery(
        Request $request,
        tenders_ai_client $client,
        tenders_context_service $contextService
    )
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:open,updated,closed',
            'eligibility_state' => 'nullable|in:probably_fit,requires_validation,high_risk,unknown',
            'data_confidence' => 'nullable|in:high,medium,low',
            'feedback_state' => 'nullable|in:interested,not_interested,undefined',
        ]);

        $context = $contextService->get($this->tenantId());
        $sync = $client->sync_context($contextService->payload($context));

        if ($sync['status'] !== 1) {
            $httpStatus = $sync['http_status'] ?? 503;
            unset($sync['http_status']);

            return response()->json($sync, $httpStatus);
        }

        $response = $client->discovery($validated);
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function sync(
        Request $request,
        tenders_ai_client $client,
        tenders_context_service $contextService
    )
    {
        $validated = $request->validate([
            'source' => 'nullable|in:all,secop1,secop2',
            'lookback_days' => 'nullable|integer|min:1|max:90',
            'page_size' => 'nullable|integer|min:1|max:250',
            'max_pages' => 'nullable|integer|min:1|max:100',
            'recheck_days' => 'nullable|integer|min:1|max:30',
            'recheck_page_size' => 'nullable|integer|min:1|max:250',
            'reset_cursor' => 'nullable|boolean',
        ]);
        $context = $contextService->get($this->tenantId());
        $contextSync = $client->sync_context($contextService->payload($context));

        if ($contextSync['status'] !== 1) {
            $httpStatus = $contextSync['http_status'] ?? 503;
            unset($contextSync['http_status']);

            return response()->json($contextSync, $httpStatus);
        }

        $response = $client->sync($validated);
        $httpStatus = $response['http_status'] ?? 202;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function update_context(
        Request $request,
        tenders_ai_client $client,
        tenders_context_service $contextService
    )
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'identification' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:10000',
            'services' => 'nullable|string|max:5000',
            'technologies' => 'nullable|string|max:5000',
            'sectors' => 'nullable|string|max:3000',
            'geography' => 'nullable|string|max:3000',
            'excluded_terms' => 'nullable|string|max:5000',
            'min_contract_value' => 'nullable|numeric|min:0',
            'max_contract_value' => 'nullable|numeric|min:0',
        ]);

        $actorId = data_get(session('user'), 'id');
        $context = $contextService->save($this->tenantId(), $validated, $actorId ? (int) $actorId : null);
        $sync = $client->sync_context($contextService->payload($context));

        if ($sync['status'] !== 1) {
            $httpStatus = $sync['http_status'] ?? 503;
            unset($sync['http_status']);
            $sync['message'] = 'Contexto guardado en el ERP, pero no pudo sincronizarse con el servicio de IA. Se reintentara en la proxima consulta.';
            $sync['data'] = $contextService->payload($context);

            return response()->json($sync, $httpStatus);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Contexto guardado y sincronizado.',
            'data' => $contextService->payload($context),
            'meta' => $sync['meta'] ?? [],
            'request_id' => $sync['request_id'] ?? null,
        ]);
    }

    public function opportunity(string $opportunityId, tenders_ai_client $client)
    {
        $response = $client->opportunity($opportunityId);
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function feedback(Request $request, tenders_ai_client $client)
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|string|max:400',
            'event_type' => 'required|in:interested,not_interested,saved,preparing,submitted,won,lost,dismissed',
            'reason_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);
        $idempotencyKey = $request->header('Idempotency-Key') ?: (string) \Illuminate\Support\Str::uuid();
        $response = $client->feedback(
            $validated['opportunity_id'],
            collect($validated)->except('opportunity_id')->all(),
            $idempotencyKey
        );
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function pipeline(Request $request, tenders_ai_client $client)
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|string|max:400',
            'stage' => 'required|in:saved,reviewing,preparing,submitted,won,lost,archived',
            'due_at' => 'nullable|date',
            'outcome' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);
        $response = $client->pipeline(
            $validated['opportunity_id'],
            collect($validated)->except('opportunity_id')->all()
        );
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function save_opportunity(string $opportunityId, tenders_ai_client $client)
    {
        $response = $client->save_opportunity($opportunityId);
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function pipeline_history(string $opportunityId, Request $request, tenders_ai_client $client)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $response = $client->pipeline_history(
            $opportunityId,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 25)
        );
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function update_pipeline_entry(
        string $opportunityId,
        int $entryId,
        Request $request,
        tenders_ai_client $client
    ) {
        $validated = $request->validate([
            'stage' => 'required|in:saved,reviewing,preparing,submitted,won,lost,archived',
            'due_at' => 'nullable|date',
            'outcome' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);
        $response = $client->update_pipeline_entry($opportunityId, $entryId, $validated);
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function delete_pipeline_entry(string $opportunityId, int $entryId, tenders_ai_client $client)
    {
        $response = $client->delete_pipeline_entry($opportunityId, $entryId);
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function applications(Request $request, tenders_ai_client $client)
    {
        $validated = $request->validate([
            'stage' => 'nullable|in:saved,reviewing,preparing,submitted,won,lost,archived',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);
        $response = $client->applications(
            $validated['stage'] ?? null,
            $validated['search'] ?? null,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 10)
        );
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    public function sync_status(Request $request, tenders_ai_client $client)
    {
        $validated = $request->validate([
            'source' => 'nullable|in:secop1,secop2',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);
        $response = $client->sync_runs(
            $validated['source'] ?? null,
            $validated['limit'] ?? 5
        );
        $httpStatus = $response['http_status'] ?? 200;
        unset($response['http_status']);

        return response()->json($response, $httpStatus);
    }

    private function tenantId(): string
    {
        return (string) config('services.tenders_ai.tenant_id', 'opzio');
    }
}