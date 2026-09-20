<?php

namespace App\traits;

use App\Services\Tenders\tenders_pipeline_service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait tenders_pipeline_trait
{
    public function Tenders_Feedback(Request $request, tenders_pipeline_service $service): array
    {
        $validated = $request->validate([
            'opportunity_id' => ['required', 'string', 'max:400'],
            'event_type' => ['required', 'in:interested,not_interested,saved,preparing,submitted,won,lost,dismissed'],
            'reason_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $key = $request->header('Idempotency-Key') ?: (string) Str::uuid();

        return [
            'message' => 'Decision guardada.',
            'data' => $service->feedback($this->tenantId(), $validated['opportunity_id'], $this->Tenders_ActorId(), collect($validated)->except('opportunity_id')->all(), $key),
        ];
    }

    public function Tenders_SaveOpportunity(string $opportunityId, tenders_pipeline_service $service): array
    {
        $result = $service->save($this->tenantId(), $opportunityId, $this->Tenders_ActorId());

        return ['message' => 'Oportunidad guardada.', 'data' => $result['data'], 'meta' => ['created' => $result['created']]];
    }

    public function Tenders_SavePipeline(Request $request, tenders_pipeline_service $service): array
    {
        $validated = $request->validate([
            'opportunity_id' => ['required', 'string', 'max:400'],
            'stage' => ['required', 'in:saved,reviewing,preparing,submitted,won,lost,archived'],
            'due_at' => ['nullable', 'date'],
            'outcome' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return ['message' => 'Seguimiento guardado.', 'data' => $service->pipeline($this->tenantId(), $validated['opportunity_id'], $this->Tenders_ActorId(), collect($validated)->except('opportunity_id')->all())];
    }

    public function Tenders_Applications(Request $request, tenders_pipeline_service $service): array
    {
        $validated = $request->validate([
            'stage' => ['nullable', 'in:saved,reviewing,preparing,submitted,won,lost,archived'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return $service->applications($this->tenantId(), $validated['stage'] ?? null, $validated['search'] ?? null, (int) ($validated['page'] ?? 1), (int) ($validated['per_page'] ?? 10));
    }

    public function Tenders_PipelineHistory(string $opportunityId, Request $request, tenders_pipeline_service $service): ?array
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $service->history($this->tenantId(), $opportunityId, (int) ($validated['page'] ?? 1), (int) ($validated['per_page'] ?? 25));
    }

    public function Tenders_UpdatePipelineEntry(string $opportunityId, int $entryId, Request $request, tenders_pipeline_service $service): ?array
    {
        $validated = $request->validate([
            'stage' => ['required', 'in:saved,reviewing,preparing,submitted,won,lost,archived'],
            'due_at' => ['nullable', 'date'],
            'outcome' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return $service->updateEntry($this->tenantId(), $opportunityId, $entryId, $this->Tenders_ActorId(), $validated);
    }

    public function Tenders_DeletePipelineEntry(string $opportunityId, int $entryId, tenders_pipeline_service $service): ?bool
    {
        return $service->deleteEntry($this->tenantId(), $opportunityId, $entryId, $this->Tenders_ActorId());
    }
}
