<?php

namespace App\Services\Tenders;

use App\Models\tenders_connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class tenders_configuration_service
{
    public function get(): tenders_connection
    {
        return tenders_connection::withTrashed()->latest('updated_at')->first()
            ?? $this->createDefault();
    }

    public function save(array $data, ?int $actorId = null): tenders_connection
    {
        $connection = tenders_connection::withTrashed()->latest('updated_at')->first();
        $isNew = $connection === null;
        $connection ??= new tenders_connection();
        if ($connection->trashed()) {
            $connection->restore();
        }

        $credentials = (array) $connection->credentials;
        if (filled($data['app_token'] ?? null)) {
            $credentials['app_token'] = trim((string) $data['app_token']);
        }

        $settings = $this->settings($connection);
        $settings['schema_version'] = 1;
        $settings['timezone'] = trim((string) ($data['timezone'] ?? $settings['timezone']));
        $settings['transport'] = array_replace($settings['transport'], [
            'timeout' => max(1, (int) ($data['timeout'] ?? $settings['transport']['timeout'])),
            'retries' => max(0, min(5, (int) ($data['retries'] ?? $settings['transport']['retries']))),
            'page_size' => max(1, min(250, (int) ($data['page_size'] ?? $settings['transport']['page_size']))),
            'lookback_days' => max(1, min(90, (int) ($data['lookback_days'] ?? $settings['transport']['lookback_days']))),
            'max_pages' => max(1, min(100, (int) ($data['max_pages'] ?? $settings['transport']['max_pages']))),
        ]);
        $settings['sources'] = $this->sourceSettings($data, $settings);
        $settings['filters'] = $this->filterSettings($data, $settings['filters']);
        $settings['filters_version'] = ((int) ($settings['filters_version'] ?? 0)) + 1;
        $settings['documents'] = array_replace($settings['documents'], [
            'enabled' => (bool) ($data['documents_enabled'] ?? $settings['documents']['enabled']),
            'limit_per_opportunity' => max(1, min(100, (int) ($data['document_limit'] ?? $settings['documents']['limit_per_opportunity']))),
        ]);

        $connection->fill([
            'singleton_key' => 1,
            'name' => trim((string) ($data['name'] ?? $connection->name ?? 'SECOP principal')),
            'provider' => 'secop_soda',
            'status' => $connection->status ?: 'draft',
            'credentials' => $credentials,
            'settings' => $settings,
        ]);
        if ($isNew) {
            $connection->created_by_user_id = $actorId;
        }
        $connection->save();

        return $connection->fresh();
    }

    public function payload(?tenders_connection $connection = null): array
    {
        $connection ??= $this->get();
        $settings = $this->settings($connection);

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'provider' => $connection->provider,
            'status' => $connection->status,
            'timezone' => $settings['timezone'],
            'sources' => $settings['sources'],
            'transport' => $settings['transport'],
            'filters' => $settings['filters'],
            'filters_version' => (int) ($settings['filters_version'] ?? 1),
            'documents' => $settings['documents'],
            'embeddings' => $settings['embeddings'],
            'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
            'last_sync_at' => $connection->last_sync_at?->toIso8601String(),
            'last_error' => $connection->last_error,
            'has_app_token' => filled($connection->credential('app_token')),
        ];
    }

    public function settings(?tenders_connection $connection = null): array
    {
        $connection ??= $this->get();
        $current = is_array($connection->settings) ? $connection->settings : [];

        return array_replace_recursive($this->defaults(), $current);
    }

    public function test(tenders_connection $connection): array
    {
        try {
            $result = app(tenders_secop_client::class)->forConnection($connection)->test();
            $connection->update([
                'status' => 'active',
                'last_tested_at' => now(),
                'last_error' => null,
            ]);

            return [
                'ok' => true,
                'message' => 'Conexion SECOP verificada correctamente.',
                'sources' => $result,
            ];
        } catch (\Throwable $exception) {
            $message = Str::limit(trim($exception->getMessage()), 500, '');
            $connection->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => $message,
            ]);

            return ['ok' => false, 'message' => $message];
        }
    }

    private function createDefault(): tenders_connection
    {
        $connection = new tenders_connection([
            'singleton_key' => 1,
            'name' => 'SECOP principal',
            'provider' => 'secop_soda',
            'status' => 'draft',
            'credentials' => [],
            'settings' => $this->defaults(),
        ]);
        $connection->save();

        return $connection;
    }

    private function sourceSettings(array $data, array $settings): array
    {
        foreach (['secop1', 'secop2'] as $source) {
            $settings['sources'][$source]['enabled'] = array_key_exists($source.'_enabled', $data)
                ? (bool) $data[$source.'_enabled']
                : (bool) $settings['sources'][$source]['enabled'];
            if (filled($data[$source.'_dataset_id'] ?? null)) {
                $settings['sources'][$source]['dataset_id'] = trim((string) $data[$source.'_dataset_id']);
            }
        }

        return $settings['sources'];
    }

    private function filterSettings(array $data, array $current): array
    {
        $listFields = [
            'statuses',
            'departments',
            'cities',
            'procurement_methods',
            'contract_types',
            'category_codes',
            'excluded_terms',
        ];
        foreach ($listFields as $field) {
            if (array_key_exists($field, $data)) {
                $current[$field] = $this->normalizeList($data[$field]);
            }
        }
        foreach (['only_postulable', 'min_days_to_deadline'] as $field) {
            if (array_key_exists($field, $data)) {
                $current[$field] = $field === 'only_postulable'
                    ? (bool) $data[$field]
                    : max(0, (int) $data[$field]);
            }
        }
        foreach (['min_contract_value', 'max_contract_value'] as $field) {
            if (array_key_exists($field, $data)) {
                $current[$field] = $data[$field] === null || $data[$field] === ''
                    ? null
                    : max(0, (float) $data[$field]);
            }
        }

        return $current;
    }

    private function normalizeList(mixed $value): array
    {
        $values = is_array($value)
            ? $value
            : (preg_split('/[,;\r\n]+/', (string) $value) ?: []);

        return collect($values)
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function defaults(): array
    {
        return [
            'schema_version' => 1,
            'timezone' => config('tenders.default_timezone', 'America/Bogota'),
            'base_url' => 'https://www.datos.gov.co/resource',
            'sources' => [
                'secop1' => [
                    'enabled' => true,
                    'dataset_id' => 'f789-7hwg',
                    'timestamp_field' => 'ultima_actualizacion',
                    'id_field' => 'uid',
                ],
                'secop2' => [
                    'enabled' => true,
                    'dataset_id' => 'p6dx-8zbt',
                    'timestamp_field' => 'fecha_de_ultima_publicaci',
                    'id_field' => 'id_del_proceso',
                ],
            ],
            'transport' => [
                'timeout' => (int) config('tenders.timeout', 30),
                'retries' => (int) config('tenders.retries', 2),
                'page_size' => (int) config('tenders.max_page_size', 250),
                'lookback_days' => 7,
                'max_pages' => 20,
            ],
            'filters' => [
                'only_postulable' => true,
                'min_days_to_deadline' => 0,
                'statuses' => [],
                'departments' => [],
                'cities' => [],
                'procurement_methods' => [],
                'contract_types' => [],
                'category_codes' => [],
                'excluded_terms' => [],
                'min_contract_value' => null,
                'max_contract_value' => null,
            ],
            'filters_version' => 1,
            'documents' => [
                'enabled' => true,
                'limit_per_opportunity' => 20,
                'max_bytes' => (int) config('tenders.document_max_bytes', 25000000),
                'allowed_extensions' => ['pdf', 'docx', 'xlsx', 'txt', 'csv'],
            ],
            'embeddings' => [
                'enabled' => false,
                'provider' => 'openai',
                'model' => 'text-embedding-3-small',
                'dimensions' => 1536,
            ],
        ];
    }
}
