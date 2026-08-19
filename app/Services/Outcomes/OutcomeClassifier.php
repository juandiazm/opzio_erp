<?php

namespace App\Services\Outcomes;

use App\Models\client;
use App\Models\department;
use App\Models\employee;
use App\Models\outcome;
use App\Models\outcome_type;
use App\Models\provider;
use App\traits\open_ia_trait;

class OutcomeClassifier
{
    use open_ia_trait;

    private const HISTORY_THRESHOLD = 0.78;
    private const CATALOG_THRESHOLD = 0.90;
    private const AI_THRESHOLD = 0.80;

    private OutcomeTextNormalizer $normalizer;
    private array $cache = [];

    public function __construct(?OutcomeTextNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?: new OutcomeTextNormalizer();
    }

    public function classify(array $transaction): array
    {
        $description = (string) ($transaction['description'] ?? '');
        $cacheKey = $this->normalizer->normalize($description);
        if ($cacheKey !== '' && array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        try {
            $candidates = $this->findHistoricalCandidates($description);
            $historicalMatch = $this->findBestHistoricalMatch($description, $candidates);
            if ($historicalMatch
                && $historicalMatch['score'] >= self::HISTORY_THRESHOLD
                && $historicalMatch['outcome']->outcome_type_id
            ) {
                $classification = $this->classificationFromOutcome($historicalMatch['outcome'], 'history', $historicalMatch['score']);
            } else {
                $catalogMatch = $this->findCatalogTypeMatch($description);
                $classification = $catalogMatch
                    ?: $this->classifyWithAi($transaction, $candidates)
                    ?: $this->fallbackClassification('fallback');
            }
        } catch (\Throwable $exception) {
            info('OutcomeClassifier error: ' . $exception->getMessage());
            $classification = $this->fallbackClassification('fallback');
        }

        if ($cacheKey !== '') {
            $this->cache[$cacheKey] = $classification;
        }

        return $classification;
    }

    private function findHistoricalCandidates(string $description)
    {
        $tokens = array_slice($this->normalizer->merchantTokens($description), 0, 4);
        if (count($tokens) === 0) {
            return collect();
        }

        return outcome::query()
            ->with(['provider', 'employee', 'department', 'client'])
            ->select([
                'id',
                'outcome_type_id',
                'name',
                'description',
                'date',
                'provider_id',
                'employee_id',
                'department_id',
                'client_id',
                'created_at',
            ])
            ->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $query->orWhere('name', 'like', '%' . $token . '%')
                        ->orWhere('description', 'like', '%' . $token . '%');
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    private function findBestHistoricalMatch(string $description, $candidates): ?array
    {
        $best = null;
        foreach ($candidates as $candidate) {
            $score = $this->normalizer->similarity($description, $candidate->description ?: $candidate->name);
            if ($best === null || $score > $best['score']) {
                $best = [
                    'outcome' => $candidate,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    private function classificationFromOutcome(outcome $outcome, string $source, float $confidence): array
    {
        return [
            'provider_id' => $outcome->provider_id ?: null,
            'employee_id' => $outcome->employee_id ?: null,
            'department_id' => $outcome->department_id ?: null,
            'client_id' => $outcome->client_id ?: null,
            'outcome_type_id' => $outcome->outcome_type_id ? (int) $outcome->outcome_type_id : null,
            'classification_source' => $source,
            'classification_confidence' => round($confidence, 4),
        ];
    }

    private function findCatalogTypeMatch(string $description): ?array
    {
        $normalizedDescription = $this->normalizer->normalize($description);
        if ($normalizedDescription === '') {
            return null;
        }

        foreach (outcome_type::query()->orderBy('name')->get(['id', 'name']) as $type) {
            $normalizedType = $this->normalizer->normalize($type->name);
            if ($normalizedType === '' || strlen($normalizedType) < 4) {
                continue;
            }

            $descriptionWithPadding = ' ' . $normalizedDescription . ' ';
            $typeWithPadding = ' ' . $normalizedType . ' ';
            if ($normalizedDescription === $normalizedType || str_contains($descriptionWithPadding, $typeWithPadding)) {
                return [
                    'outcome_type_id' => (int) $type->id,
                    'classification_source' => 'catalog',
                    'classification_confidence' => self::CATALOG_THRESHOLD,
                    'provider_id' => null,
                    'employee_id' => null,
                    'department_id' => null,
                    'client_id' => null,
                ];
            }
        }

        return null;
    }

    private function classifyWithAi(array $transaction, $historicalCandidates): ?array
    {
        $services = config('services');
        $openaiConfig = is_array($services) ? ($services['openai'] ?? []) : [];
        if (empty($openaiConfig['api_key'])) {
            return null;
        }

        $catalog = $this->buildCatalog($transaction, $historicalCandidates);
        $typeCatalog = outcome_type::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);
        $allowedTypeIds = $typeCatalog->pluck('id')->map(fn ($type): int => (int) $type)->all();

        $context = [
            'transaction' => [
                'date' => $transaction['date'] ?? null,
                'description' => $transaction['description'] ?? null,
                'amount' => $transaction['amount'] ?? null,
            ],
            'historical_examples' => $historicalCandidates->take(8)->map(function (outcome $item): array {
                return [
                    'description' => $item->description ?: $item->name,
                    'outcome_type_id' => $item->outcome_type_id ? (int) $item->outcome_type_id : null,
                    'provider_id' => $item->provider_id,
                    'employee_id' => $item->employee_id,
                    'department_id' => $item->department_id,
                    'client_id' => $item->client_id,
                ];
            })->values()->all(),
            'outcome_types' => $typeCatalog->map(fn (outcome_type $type): array => [
                'id' => (int) $type->id,
                'name' => $type->name,
            ])->values()->all(),
            'catalog' => $catalog,
            'allowed_type_ids' => $allowedTypeIds,
        ];

        $prompt = 'Clasifica esta transaccion de egreso usando solo la evidencia suministrada. '
            . 'Elige un outcome_type_id existente cuando corresponda; si ninguno representa bien el movimiento, propone new_type_name. '
            . 'No inventes IDs. Si no hay evidencia suficiente, usa null en las asociaciones y false en should_associate. '
            . 'Solo puedes usar IDs presentes en catalog y un outcome_type_id presente en allowed_type_ids. '
            . 'Responde exclusivamente JSON con las claves should_associate, confidence, provider_id, employee_id, department_id, client_id, outcome_type_id, new_type_name y reason. '
            . 'new_type_name debe ser una categoria breve y reutilizable, no la descripcion completa de la transaccion. '
            . 'confidence debe ser un numero entre 0 y 1; asocia solo con confianza de al menos 0.80. '
            . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->OpenIA_MakeQuestion($prompt);
            if (($response['status'] ?? 0) !== 1) {
                return null;
            }

            $content = $response['data'][0] ?? null;
            if (!is_string($content)) {
                return null;
            }

            $result = $this->decodeJsonObject($content);
            if (!is_array($result)) {
                return null;
            }

            $confidence = (float) ($result['confidence'] ?? 0);
            if ($confidence < self::AI_THRESHOLD) {
                return null;
            }

            $shouldAssociate = filter_var($result['should_associate'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $classification = [
                'provider_id' => null,
                'employee_id' => null,
                'department_id' => null,
                'client_id' => null,
                'outcome_type_id' => null,
                'classification_source' => 'ai',
                'classification_confidence' => round(min($confidence, 1), 4),
            ];

            $typeId = filter_var($result['outcome_type_id'] ?? null, FILTER_VALIDATE_INT);
            if ($typeId !== false && in_array((int) $typeId, $allowedTypeIds, true)) {
                $classification['outcome_type_id'] = (int) $typeId;
            } else {
                $typeName = $this->normalizeAiTypeName($result['new_type_name'] ?? null);
                if ($typeName === '') {
                    return null;
                }

                $type = $this->findOrCreateOutcomeType($typeName);
                if (!$type) {
                    return null;
                }

                $classification['outcome_type_id'] = (int) $type->id;
                $classification['outcome_type_created'] = $type->wasRecentlyCreated;
            }

            if ($shouldAssociate) {
                $classification['provider_id'] = $this->allowedId($result['provider_id'] ?? null, $catalog['providers']);
                $classification['employee_id'] = $this->allowedId($result['employee_id'] ?? null, $catalog['employees']);
                $classification['department_id'] = $this->allowedId($result['department_id'] ?? null, $catalog['departments']);
                $classification['client_id'] = $this->allowedId($result['client_id'] ?? null, $catalog['clients']);
            }

            return $classification;
        } catch (\Throwable $exception) {
            info('OutcomeClassifier AI error: ' . $exception->getMessage());
            return null;
        }
    }

    private function buildCatalog(array $transaction, $historicalCandidates): array
    {
        $tokens = array_slice($this->normalizer->merchantTokens($transaction['description'] ?? ''), 0, 4);
        return [
            'providers' => $this->loadCatalog(provider::class, ['id', 'name', 'lastname'], $tokens, $historicalCandidates->pluck('provider_id')->filter()->all()),
            'employees' => $this->loadCatalog(employee::class, ['id', 'name', 'last_name'], $tokens, $historicalCandidates->pluck('employee_id')->filter()->all()),
            'departments' => $this->loadCatalog(department::class, ['id', 'name'], $tokens, $historicalCandidates->pluck('department_id')->filter()->all()),
            'clients' => $this->loadCatalog(client::class, ['id', 'name', 'lastname'], $tokens, $historicalCandidates->pluck('client_id')->filter()->all()),
        ];
    }

    private function loadCatalog(string $modelClass, array $columns, array $tokens, array $linkedIds): array
    {
        $query = (new $modelClass())->newQuery()->select($columns);
        if (count($tokens) > 0 || count($linkedIds) > 0) {
            $query->where(function ($query) use ($columns, $tokens, $linkedIds): void {
                foreach ($tokens as $token) {
                    $query->orWhere('name', 'like', '%' . $token . '%');
                    if (in_array('lastname', $columns, true)) {
                        $query->orWhere('lastname', 'like', '%' . $token . '%');
                    }
                    if (in_array('last_name', $columns, true)) {
                        $query->orWhere('last_name', 'like', '%' . $token . '%');
                    }
                }
                if (count($linkedIds) > 0) {
                    $query->orWhereIn('id', $linkedIds);
                }
            });
        }

        return $query->orderBy('name')->limit(40)->get()->map(function ($item): array {
            return [
                'id' => (int) $item->id,
                'label' => trim($item->name . ' ' . ($item->last_name ?? $item->lastname ?? '')),
            ];
        })->values()->all();
    }

    private function allowedId($value, array $catalog): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $id = (int) $value;
        foreach ($catalog as $item) {
            if ((int) $item['id'] === $id) {
                return $id;
            }
        }

        return null;
    }

    private function decodeJsonObject(string $content): ?array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function fallbackClassification(string $source): array
    {
        return [
            'provider_id' => null,
            'employee_id' => null,
            'department_id' => null,
            'client_id' => null,
            'outcome_type_id' => null,
            'classification_source' => $source,
            'classification_confidence' => 0,
        ];
    }

    private function normalizeAiTypeName($value): string
    {
        $name = trim(strip_tags((string) $value));
        $name = preg_replace('/\s+/u', ' ', $name) ?: '';
        $name = mb_substr($name, 0, 100, 'UTF-8');
        if ($name === '' || preg_match('/^(otro|sin tipo|pendiente)$/iu', $name)) {
            return '';
        }

        return $name;
    }

    private function findOrCreateOutcomeType(string $name): ?outcome_type
    {
        $existing = outcome_type::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
            ->first();
        if ($existing) {
            return $existing;
        }

        try {
            return outcome_type::create(['name' => $name]);
        } catch (\Throwable $exception) {
            info('OutcomeClassifier type creation error: ' . $exception->getMessage());

            return outcome_type::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
                ->first();
        }
    }
}