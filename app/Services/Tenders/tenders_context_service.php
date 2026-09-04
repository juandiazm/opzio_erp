<?php

namespace App\Services\Tenders;

use App\Models\tenders_context;

class tenders_context_service
{
    public function get(string $tenantId): tenders_context
    {
        return tenders_context::firstOrCreate(
            ['tenant_id' => $tenantId],
            $this->defaults()
        );
    }

    public function save(string $tenantId, array $data, ?int $actorId = null): tenders_context
    {
        $context = $this->get($tenantId);
        $context->fill([
            'company_name' => $data['company_name'],
            'identification' => $data['identification'] ?? null,
            'description' => $data['description'] ?? null,
            'services' => $this->normalizeList($data['services'] ?? null),
            'technologies' => $this->normalizeList($data['technologies'] ?? null),
            'sectors' => $this->normalizeList($data['sectors'] ?? null),
            'geography' => $this->normalizeList($data['geography'] ?? null),
            'excluded_terms' => $this->normalizeList($data['excluded_terms'] ?? null),
            'min_contract_value' => $data['min_contract_value'] ?? null,
            'max_contract_value' => $data['max_contract_value'] ?? null,
            'version' => max(1, (int) $context->version + 1),
            'updated_by' => $actorId,
        ]);
        $context->save();

        return $context->fresh();
    }

    public function payload(tenders_context $context): array
    {
        return [
            'company_name' => $context->company_name,
            'identification' => $context->identification,
            'description' => $context->description,
            'services' => $context->services ?? [],
            'technologies' => $context->technologies ?? [],
            'sectors' => $context->sectors ?? [],
            'geography' => $context->geography ?? [],
            'excluded_terms' => $context->excluded_terms ?? [],
            'min_contract_value' => $context->min_contract_value,
            'max_contract_value' => $context->max_contract_value,
            'profile_version' => (int) $context->version,
        ];
    }

    private function normalizeList(?string $value): array
    {
        $parts = preg_split('/[,;\r\n]+/', $value ?? '') ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, fn ($part) => $part !== '');

        return array_values(array_unique($parts));
    }

    private function defaults(): array
    {
        return [
            'company_name' => 'OPZIO S.A.S.',
            'description' => null,
            'services' => [],
            'technologies' => [],
            'sectors' => [],
            'geography' => [],
            'excluded_terms' => [],
            'version' => 1,
        ];
    }
}
