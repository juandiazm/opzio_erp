<?php

namespace App\traits;

use App\Services\Tenders\tenders_configuration_service;
use Illuminate\Http\Request;

trait tenders_configuration_trait
{
    public function Tenders_SaveConfiguration(Request $request, tenders_configuration_service $service): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'app_token' => ['nullable', 'string', 'max:5000'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'secop1_enabled' => ['nullable', 'boolean'],
            'secop2_enabled' => ['nullable', 'boolean'],
            'secop1_dataset_id' => ['nullable', 'string', 'max:80'],
            'secop2_dataset_id' => ['nullable', 'string', 'max:80'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
            'retries' => ['nullable', 'integer', 'min:0', 'max:5'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:250'],
            'lookback_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:100'],
            'only_postulable' => ['nullable', 'boolean'],
            'min_days_to_deadline' => ['nullable', 'integer', 'min:0', 'max:365'],
            'statuses' => ['nullable', 'string', 'max:3000'],
            'departments' => ['nullable', 'string', 'max:3000'],
            'cities' => ['nullable', 'string', 'max:3000'],
            'procurement_methods' => ['nullable', 'string', 'max:3000'],
            'contract_types' => ['nullable', 'string', 'max:3000'],
            'category_codes' => ['nullable', 'string', 'max:5000'],
            'excluded_terms' => ['nullable', 'string', 'max:5000'],
            'min_contract_value' => ['nullable', 'numeric', 'min:0'],
            'max_contract_value' => ['nullable', 'numeric', 'min:0'],
            'documents_enabled' => ['nullable', 'boolean'],
            'document_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $connection = $service->save($data, $this->Tenders_ActorId());

        return [
            'message' => 'Configuracion SECOP guardada correctamente.',
            'data' => $service->payload($connection),
        ];
    }

    public function Tenders_TestConfiguration(tenders_configuration_service $service): array
    {
        $result = $service->test($service->get());
        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'message' => $result['message'] ?? 'No fue posible probar SECOP.', 'data' => $result];
        }

        return ['ok' => true, 'message' => $result['message'], 'data' => $result];
    }

    protected function Tenders_ActorId(): ?int
    {
        $actorId = data_get(session('user'), 'id');

        return $actorId === null ? null : (int) $actorId;
    }
}
