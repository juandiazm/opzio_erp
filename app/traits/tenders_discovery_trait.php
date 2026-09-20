<?php

namespace App\traits;

use App\Services\Tenders\tenders_discovery_service;
use Illuminate\Http\Request;

trait tenders_discovery_trait
{
    public function Tenders_Discovery(Request $request, tenders_discovery_service $service): array
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:open,updated,closed'],
            'eligibility_state' => ['nullable', 'in:probably_fit,requires_validation,high_risk,unknown'],
            'data_confidence' => ['nullable', 'in:high,medium,low'],
            'feedback_state' => ['nullable', 'in:interested,not_interested,undefined'],
        ]);
        $result = $service->listForTenant($this->tenantId(), $validated);

        return [
            'message' => 'Resultados de Discovery consultados.',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ];
    }

    public function Tenders_Opportunity(string $opportunityId, tenders_discovery_service $service): ?array
    {
        return $service->detail($this->tenantId(), $opportunityId);
    }
}
