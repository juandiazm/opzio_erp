<?php

namespace App\Services\Tenders;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class tenders_ai_client
{
    public function opportunity(string $opportunityId): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->get('/v1/opportunities/'.rawurlencode($opportunityId)),
            $requestId,
            'No fue posible consultar el detalle de la oportunidad.'
        );
    }

    public function feedback(
        string $opportunityId,
        array $payload,
        string $idempotencyKey
    ): array {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId, $idempotencyKey)
                ?->post('/v1/opportunities/'.rawurlencode($opportunityId).'/feedback', $payload),
            $requestId,
            'No fue posible guardar la decisión.'
        );
    }

    public function pipeline(string $opportunityId, array $payload): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->post('/v1/opportunities/'.rawurlencode($opportunityId).'/pipeline', $payload),
            $requestId,
            'No fue posible actualizar el seguimiento.'
        );
    }

    public function save_opportunity(string $opportunityId): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->post('/v1/opportunities/'.rawurlencode($opportunityId).'/save'),
            $requestId,
            'No fue posible guardar la oportunidad.'
        );
    }

    public function pipeline_history(
        string $opportunityId,
        int $page = 1,
        int $perPage = 25
    ): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->get('/v1/opportunities/'.rawurlencode($opportunityId).'/pipeline', [
                    'page' => $page,
                    'per_page' => $perPage,
                ]),
            $requestId,
            'No fue posible consultar el historial de seguimiento.'
        );
    }

    public function update_pipeline_entry(
        string $opportunityId,
        int $entryId,
        array $payload
    ): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->patch(
                    '/v1/opportunities/'.rawurlencode($opportunityId).'/pipeline/'.$entryId,
                    $payload
                ),
            $requestId,
            'No fue posible editar el registro de seguimiento.'
        );
    }

    public function delete_pipeline_entry(string $opportunityId, int $entryId): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->delete('/v1/opportunities/'.rawurlencode($opportunityId).'/pipeline/'.$entryId),
            $requestId,
            'No fue posible eliminar el registro de seguimiento.'
        );
    }

    public function applications(
        ?string $stage = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 10
    ): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->get('/v1/applications', array_filter([
                    'stage' => $stage,
                    'search' => $search,
                    'page' => $page,
                    'per_page' => $perPage,
                ], fn ($value) => $value !== null && $value !== '')),
            $requestId,
            'No fue posible consultar el seguimiento.'
        );
    }

    public function sync_runs(?string $source = null, int $limit = 5): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->get('/v1/sync-runs', array_filter([
                    'source' => $source,
                    'limit' => $limit,
                ])),
            $requestId,
            'No fue posible consultar el estado de sincronización.'
        );
    }

    public function sync_context(array $context): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        $baseUrl = rtrim((string) config('services.tenders_ai.base_url'), '/');
        $token = (string) config('services.tenders_ai.token');
        $tenantId = (string) config('services.tenders_ai.tenant_id', 'opzio');
        $actorId = (string) data_get(session('user'), 'id', 'system');

        if ($baseUrl === '' || $token === '') {
            return $this->unavailable(
                'El servicio de Licitaciones no esta configurado.',
                $requestId
            );
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withHeaders([
                    'X-Opzio-Secop-Token' => $token,
                    'X-Opzio-Tenant-Id' => $tenantId,
                    'X-Opzio-Actor-Id' => $actorId,
                    'X-Request-Id' => $requestId,
                ])
                ->timeout((float) config('services.tenders_ai.timeout', 30))
                ->put('/v1/profiles/'.rawurlencode($tenantId), $context);
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->unavailable(
                'No fue posible conectar con el servicio de Licitaciones.',
                $requestId
            );
        }

        if (! $response->successful()) {
            return $this->unavailable(
                (string) ($response->json('detail') ?: $response->json('message') ?: 'El contexto no pudo sincronizarse.'),
                $requestId,
                $response->status()
            );
        }

        $payload = $response->json();

        return [
            'status' => 1,
            'message' => 'Contexto sincronizado.',
            'data' => is_array($payload['data'] ?? null) ? $payload['data'] : [],
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'request_id' => $payload['request_id'] ?? $requestId,
        ];
    }

    public function sync(array $options = []): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $this->safeResponse(
            fn () => $this->authenticatedRequest($requestId)
                ?->post('/v1/sync', array_filter(
                    $options,
                    fn ($value) => $value !== null && $value !== ''
                )),
            $requestId,
            'No fue posible iniciar la sincronización con SECOP.'
        );
    }

    public function discovery(array $filters = []): array
    {
        $requestId = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        $baseUrl = rtrim((string) config('services.tenders_ai.base_url'), '/');
        $token = (string) config('services.tenders_ai.token');
        $tenantId = (string) config('services.tenders_ai.tenant_id', 'opzio');
        $actorId = (string) data_get(session('user'), 'id', 'system');

        if ($baseUrl === '' || $token === '') {
            return $this->unavailable(
                'El servicio de Licitaciones no esta configurado.',
                $requestId
            );
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withHeaders([
                    'X-Opzio-Secop-Token' => $token,
                    'X-Opzio-Tenant-Id' => $tenantId,
                    'X-Opzio-Actor-Id' => $actorId,
                    'X-Request-Id' => $requestId,
                ])
                ->timeout((float) config('services.tenders_ai.timeout', 30))
                ->get('/v1/discovery', array_filter(
                    $filters,
                    fn ($value) => $value !== null && $value !== ''
                ));
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->unavailable(
                'No fue posible conectar con el servicio de Licitaciones.',
                $requestId
            );
        }

        if (! $response->successful()) {
            return $this->unavailable(
                (string) ($response->json('detail') ?: $response->json('message') ?: 'El servicio de Licitaciones no esta disponible.'),
                $requestId,
                $response->status()
            );
        }

        $payload = $response->json();

        return [
            'status' => 1,
            'message' => 'Resultados de Discovery consultados.',
            'data' => is_array($payload['data'] ?? null) ? $payload['data'] : [],
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'generated_at' => $payload['generated_at'] ?? null,
            'request_id' => $payload['request_id'] ?? $requestId,
        ];
    }

    private function unavailable(string $message, string $requestId, int $httpStatus = 503): array
    {
        return [
            'status' => 0,
            'message' => $message,
            'data' => [],
            'meta' => [],
            'request_id' => $requestId,
            'http_status' => $httpStatus,
        ];
    }

    private function authenticatedRequest(string $requestId, ?string $idempotencyKey = null)
    {
        $baseUrl = rtrim((string) config('services.tenders_ai.base_url'), '/');
        $token = (string) config('services.tenders_ai.token');
        $tenantId = (string) config('services.tenders_ai.tenant_id', 'opzio');
        $actorId = (string) data_get(session('user'), 'id', 'system');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        $headers = [
            'X-Opzio-Secop-Token' => $token,
            'X-Opzio-Tenant-Id' => $tenantId,
            'X-Opzio-Actor-Id' => $actorId,
            'X-Request-Id' => $requestId,
        ];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders($headers)
            ->timeout((float) config('services.tenders_ai.timeout', 30));
    }

    private function responsePayload($response, string $requestId, string $fallbackMessage): array
    {
        if ($response === null) {
            return $this->unavailable(
                'El servicio de Licitaciones no esta configurado.',
                $requestId
            );
        }

        try {
            if (! $response->successful()) {
                return $this->unavailable(
                    (string) ($response->json('detail') ?: $response->json('message') ?: $fallbackMessage),
                    $requestId,
                    $response->status()
                );
            }
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->unavailable(
                'No fue posible conectar con el servicio de Licitaciones.',
                $requestId
            );
        }

        $payload = $response->json();
        $data = is_array($payload['data'] ?? null)
            ? $payload['data']
            : (isset($payload['opportunity_id']) ? $payload : []);

        return [
            'status' => 1,
            'message' => $payload['message'] ?? 'Solicitud completada.',
            'data' => $data,
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'generated_at' => $payload['generated_at'] ?? null,
            'request_id' => $payload['request_id'] ?? $requestId,
        ];
    }

    private function safeResponse(callable $request, string $requestId, string $fallbackMessage): array
    {
        try {
            return $this->responsePayload($request(), $requestId, $fallbackMessage);
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->unavailable(
                'No fue posible conectar con el servicio de Licitaciones.',
                $requestId
            );
        }
    }
}