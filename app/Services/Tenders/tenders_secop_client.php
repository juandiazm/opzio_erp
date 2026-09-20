<?php

namespace App\Services\Tenders;

use App\Models\tenders_connection;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class tenders_secop_client
{
    private ?tenders_connection $connection = null;

    public function forConnection(tenders_connection $connection): self
    {
        $client = clone $this;
        $client->connection = $connection;

        return $client;
    }

    public function test(): array
    {
        $settings = app(tenders_configuration_service::class)->settings($this->connection);
        $result = [];
        foreach ($settings['sources'] as $name => $source) {
            if (! ($source['enabled'] ?? false)) {
                continue;
            }
            $response = $this->request()->get('/'.rawurlencode($source['dataset_id']).'.json', [
                '$limit' => 1,
            ]);
            $this->ensureSuccessful($response, $name);
            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('SECOP devolvio una respuesta invalida para '.$name.'.');
            }
            $result[$name] = ['ok' => true, 'rows' => count($payload)];
        }

        if ($result === []) {
            throw new RuntimeException('No hay fuentes SECOP activas.');
        }

        return $result;
    }

    public function fetchPage(
        string $sourceName,
        ?CarbonInterface $startAt,
        ?CarbonInterface $cursorAt,
        ?string $cursorId,
        int $pageSize,
    ): array {
        $source = $this->source($sourceName);
        $startText = ($startAt ?: now()->subDays(7))->utc()->format('Y-m-d\\TH:i:s');
        $timestampField = $source['timestamp_field'];
        $idField = $source['id_field'];
        $where = $timestampField." >= '".$this->escape($startText)."'";
        if ($cursorAt !== null && $cursorId !== null) {
            $cursorText = $cursorAt->utc()->format('Y-m-d\\TH:i:s');
            $where .= " AND ("
                .$timestampField." > '".$this->escape($cursorText)."' OR ("
                .$timestampField." = '".$this->escape($cursorText)."' AND "
                .$idField." > '".$this->escape($cursorId)."'))";
        }

        $response = $this->request()->get('/'.rawurlencode($source['dataset_id']).'.json', [
            '$select' => '*',
            '$where' => $where,
            '$order' => $timestampField.' ASC, '.$idField.' ASC',
            '$limit' => max(1, min(250, $pageSize)),
        ]);
        $this->ensureSuccessful($response, $sourceName);
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('SECOP devolvio una pagina invalida para '.$sourceName.'.');
        }

        return array_values(array_filter($payload, 'is_array'));
    }

    public function fetchDocuments(string $processId, int $limit = 20): array
    {
        $datasetId = 'dmgg-8hin';
        $response = $this->request()->get('/'.rawurlencode($datasetId).'.json', [
            '$where' => "proceso = '".$this->escape($processId)."'",
            '$order' => 'fecha_carga DESC, id_documento DESC',
            '$limit' => max(1, min(100, $limit)),
        ]);
        $this->ensureSuccessful($response, 'documentos');
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('SECOP devolvio documentos invalidos.');
        }

        return array_values(array_filter($payload, 'is_array'));
    }

    private function request(): PendingRequest
    {
        if (! $this->connection) {
            throw new RuntimeException('No hay una conexion SECOP configurada.');
        }
        $configuration = app(tenders_configuration_service::class);
        $settings = $configuration->settings($this->connection);
        $baseUrl = rtrim((string) ($settings['base_url'] ?? ''), '/');
        if ($baseUrl !== 'https://www.datos.gov.co/resource') {
            throw new RuntimeException('La URL base SECOP no esta permitida.');
        }
        $transport = $settings['transport'];
        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'Opzio ERP Tenders/1.0'])
            ->timeout(max(1, (float) $transport['timeout']))
            ->retry(max(0, (int) $transport['retries']), 500, null, false);
        $token = trim((string) $this->connection->credential('app_token'));
        if ($token !== '') {
            $request = $request->withHeaders(['X-App-Token' => $token]);
        }

        return $request;
    }

    private function source(string $sourceName): array
    {
        $settings = app(tenders_configuration_service::class)->settings($this->connection);
        $source = $settings['sources'][$sourceName] ?? null;
        if (! is_array($source) || ! ($source['enabled'] ?? false)) {
            throw new RuntimeException('La fuente SECOP '.$sourceName.' no esta activa.');
        }

        return $source;
    }

    private function ensureSuccessful(Response $response, string $source): void
    {
        if ($response->successful()) {
            return;
        }
        $message = $response->json('message') ?: $response->json('error') ?: $response->reason();
        throw new RuntimeException('SECOP '.$source.' devolvio HTTP '.$response->status().': '.Str::limit((string) $message, 300, ''));
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
