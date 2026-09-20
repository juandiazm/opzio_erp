<?php

namespace App\Services\Tenders;

use App\Models\tenders_opportunity;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class tenders_embedding_service
{
    public function process(int $limit = 100): array
    {
        $settings = app(tenders_configuration_service::class)->settings()['embeddings'];
        if (! ($settings['enabled'] ?? false)) return ['status' => 'disabled', 'embedded' => 0];
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') throw new RuntimeException('El proveedor de embeddings esta activo pero OPENAI_API_KEY no esta configurada.');
        $records = tenders_opportunity::query()
            ->where(function ($query): void {
                $query->whereNull('embedding_hash')->orWhereColumn('embedding_hash', 'payload_hash');
            })
            ->whereNotNull('payload_hash')
            ->latest('updated_at')
            ->limit(max(1, min(500, $limit)))
            ->get();
        if ($records->isEmpty()) return ['status' => 'succeeded', 'embedded' => 0];
        $texts = $records->map(fn (tenders_opportunity $record): string => implode(' ', array_filter([
            $record->title,
            $record->description,
            $record->entity,
            $record->category_code,
            $record->category_text,
            $record->contract_type,
            $record->procurement_method,
        ])))->all();
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((float) config('services.openai.timeout', 240))
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $settings['model'],
                'input' => $texts,
            ]);
        if (! $response->successful()) throw new RuntimeException('El proveedor de embeddings devolvio HTTP '.$response->status().'.');
        $data = $response->json('data');
        if (! is_array($data) || count($data) !== count($records)) throw new RuntimeException('El proveedor de embeddings devolvio una respuesta incompleta.');
        usort($data, fn (array $left, array $right): int => ((int) ($left['index'] ?? 0)) <=> ((int) ($right['index'] ?? 0)));
        foreach ($records->values() as $index => $record) {
            $record->update([
                'embedding' => $data[$index]['embedding'] ?? null,
                'embedding_model' => $settings['model'],
                'embedding_hash' => $record->payload_hash,
            ]);
        }

        return ['status' => 'succeeded', 'embedded' => count($records), 'model' => $settings['model']];
    }
}
