<?php

namespace App\Services\Tenders;

use App\Models\tenders_opportunity;
use App\Models\tenders_feedback_event;
use Carbon\Carbon;
use Illuminate\Support\Str;

class tenders_discovery_service
{
    public function __construct(
        private readonly tenders_context_service $contextService,
        private readonly tenders_configuration_service $configurationService,
        private readonly tenders_normalization_service $normalizer,
    ) {
    }

    public function listForTenant(string $tenantId, array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));
        $status = $filters['status'] ?? null;
        $query = tenders_opportunity::query();
        if ($status === 'closed') {
            $query->whereIn('status', ['open', 'updated', 'closed']);
        } elseif ($status !== null) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['open', 'updated']);
        }
        if (filled($filters['search'] ?? null)) {
            $search = '%'.trim((string) $filters['search'].'%');
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', $search)
                    ->orWhere('entity', 'like', $search)
                    ->orWhere('reference', 'like', $search)
                    ->orWhere('source_id', 'like', $search);
            });
        }
        if ($status === 'closed') {
            $query->orderByDesc('last_published_at');
        } else {
            $query->orderByDesc('last_published_at');
        }
        $context = $this->contextService->get($tenantId);
        $settings = $this->configurationService->settings();
        $rows = $query->get();
        $feedbackStates = $this->feedbackStates($tenantId, $rows->pluck('id')->all());
        $items = [];
        foreach ($rows as $row) {
            if (! $this->allows($row, $context, $settings['filters'], $status)) continue;
            $item = $this->toItem($row, $context, $feedbackStates[$row->id] ?? null);
            if (! $this->matchesDerivedFilters($item, $filters)) continue;
            $items[] = $item;
        }
        $items = $this->deduplicate($items);
        usort($items, function (array $left, array $right): int {
            return [$right['fit_score'], $left['deadline'] ?? '9999-12-31', $left['opportunity_id']]
                <=> [$left['fit_score'], $right['deadline'] ?? '9999-12-31', $right['opportunity_id']];
        });
        $total = count($items);

        return [
            'data' => array_values(array_slice($items, ($page - 1) * $perPage, $perPage)),
            'meta' => [
                'tenant_id' => $tenantId,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total ? (int) ceil($total / $perPage) : 0,
                'next_cursor' => $page * $perPage < $total ? (string) ($page + 1) : null,
                'calculated_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function detail(string $tenantId, string $publicId): ?array
    {
        $opportunity = $this->find($publicId);
        if ($opportunity === null) return null;
        $context = $this->contextService->get($tenantId);
        $settings = $this->configurationService->settings();
        if (! $this->allows($opportunity, $context, $settings['filters'], null)) return null;
        $feedback = $this->feedbackStates($tenantId, [$opportunity->id])[$opportunity->id] ?? null;
        $item = $this->toItem($opportunity, $context, $feedback);
        $item['source_data'] = $opportunity->raw_payload ?: [];
        $item['documents'] = $opportunity->documents()->with('chunks')->latest('source_uploaded_at')->get()->map(function ($document): array {
            return [
                'document_id' => $document->source.':'.$document->source_document_id,
                'filename' => $document->filename,
                'extension' => $document->extension,
                'description' => $document->description,
                'source_url' => $document->source_url,
                'source_uploaded_at' => $document->source_uploaded_at?->toIso8601String(),
                'size_bytes' => $document->size_bytes,
                'mime_type' => $document->mime_type,
                'sha256' => $document->sha256,
                'status' => $document->status,
                'storage_path' => $document->storage_path,
                'extracted' => filled($document->extracted_text),
            ];
        })->all();
        $item['evidence'] = $opportunity->documents()->with('chunks')->get()->flatMap(function ($document): array {
            return $document->chunks->map(fn ($chunk): array => [
                'document_id' => $document->source.':'.$document->source_document_id,
                'page' => is_numeric($chunk->page_ref) ? (int) $chunk->page_ref : null,
                'quote' => Str::limit((string) $chunk->text, 700, ''),
            ])->all();
        })->take(5)->values()->all();

        return $item;
    }

    private function allows(tenders_opportunity $row, $context, array $filters, ?string $status): bool
    {
        if ($status === 'closed' && $row->status !== 'closed' && $this->normalizer->isPostulable($row->source_status)) return false;
        if (($filters['only_postulable'] ?? true) && ! $this->normalizer->isPostulable($row->source_status)) return false;
        $amount = $row->amount === null ? null : (float) $row->amount;
        $minimum = $filters['min_contract_value'] ?? $context->min_contract_value;
        $maximum = $filters['max_contract_value'] ?? $context->max_contract_value;
        if ($minimum !== null && ($amount === null || $amount < (float) $minimum)) return false;
        if ($maximum !== null && ($amount === null || $amount > (float) $maximum)) return false;
        $minDays = (int) ($filters['min_days_to_deadline'] ?? 0);
        if ($minDays > 0 && ($row->deadline_at === null || $row->deadline_at->lt(now()->addDays($minDays)))) return false;
        foreach (['departments' => 'department', 'cities' => 'city', 'procurement_methods' => 'procurement_method', 'contract_types' => 'contract_type'] as $filter => $field) {
            if (! empty($filters[$filter]) && ! $this->containsConfiguredValue($row->{$field}, $filters[$filter])) return false;
        }
        if (! empty($filters['category_codes']) && ! $this->containsConfiguredValue($row->category_code, $filters['category_codes'])) return false;
        $candidate = $this->normalizeText($this->candidateText($row));
        $excluded = array_merge((array) ($filters['excluded_terms'] ?? []), (array) ($context->excluded_terms ?? []));
        foreach ($excluded as $term) {
            if (filled($term) && str_contains($candidate, $this->normalizeText((string) $term))) return false;
        }

        return true;
    }

    private function matchesDerivedFilters(array $item, array $filters): bool
    {
        foreach (['eligibility_state', 'data_confidence', 'feedback_state'] as $field) {
            if (filled($filters[$field] ?? null) && ($item[$field] ?? null) !== $filters[$field]) return false;
        }
        return true;
    }

    private function toItem(tenders_opportunity $row, $context, ?string $feedbackState): array
    {
        $candidate = $this->normalizeText($this->candidateText($row));
        $terms = $this->contextTerms($context);
        $phrases = collect(array_merge((array) $context->services, (array) $context->technologies))
            ->map(fn ($value): string => $this->normalizeText((string) $value))->filter()->sortByDesc(fn ($value): int => strlen($value))->values()->all();
        $matchedPhrases = array_values(array_filter($phrases, fn ($phrase): bool => str_contains($candidate, $phrase)));
        $matchedTerms = array_values(array_filter($terms, fn ($term): bool => str_contains($candidate, $term)));
        $score = min(100, 20 + min(60, count($matchedPhrases) * 18) + min(20, count($matchedTerms) * 3));
        if ($feedbackState === 'interested') $score = min(100, $score + 5);
        if ($feedbackState === 'not_interested') $score = max(0, $score - 10);
        $hasContext = filled($context->description) || $terms !== [];
        $score = $hasContext ? $score : 0;
        $eligibility = ! $hasContext ? 'unknown' : ($score >= 65 ? 'probably_fit' : 'requires_validation');
        $confidence = $row->deadline_at === null || ! filled($row->description) ? 'medium' : 'high';
        $reasons = [];
        if ($matchedPhrases !== []) $reasons[] = 'Coincide con: '.implode(', ', array_slice($matchedPhrases, 0, 3));
        elseif ($matchedTerms !== []) $reasons[] = 'Comparte conceptos con: '.implode(', ', array_slice($matchedTerms, 0, 3));
        if ($feedbackState === 'interested') $reasons[] = 'Ajuste por decisiones anteriores';
        if ($context->min_contract_value !== null && $row->amount !== null) $reasons[] = 'Tiene un valor publicado para comparar con el contexto';
        if ($reasons === []) $reasons[] = 'Pendiente de enriquecer con el contexto de la empresa';
        $risks = [];
        if ($row->deadline_at === null) $risks[] = 'Fecha limite no disponible en la fuente consultada';
        if (! filled($row->description)) $risks[] = 'Descripcion incompleta';

        return [
            'opportunity_id' => $this->publicId($row), 'title' => $row->title, 'entity' => $row->entity,
            'entity_nit' => $row->entity_nit, 'reference' => $row->reference, 'source_process_id' => $row->source_process_id,
            'description' => $row->description, 'department' => $row->department, 'city' => $row->city,
            'source' => $row->source, 'source_url' => $row->source_url ?: '', 'status' => $row->status,
            'deadline' => $row->deadline_at?->toIso8601String(), 'amount' => $row->amount === null ? null : (float) $row->amount,
            'currency' => $row->currency, 'phase' => $row->phase, 'procurement_method' => $row->procurement_method,
            'contract_type' => $row->contract_type, 'category_code' => $row->category_code, 'category_text' => $row->category_text,
            'source_status' => $row->source_status, 'opening_status' => $row->opening_status,
            'published_at' => $row->published_at?->toIso8601String(), 'last_published_at' => $row->last_published_at?->toIso8601String(),
            'fit_score' => $score, 'eligibility_state' => $eligibility, 'data_confidence' => $confidence,
            'feedback_state' => in_array($feedbackState, ['interested', 'not_interested'], true) ? $feedbackState : null,
            'reasons' => array_slice($reasons, 0, 3), 'risks' => array_slice($risks, 0, 3), 'evidence' => [],
            'model_version' => 'rules-php-1', 'profile_version' => (int) $context->version,
            'calculated_at' => $row->updated_at?->toIso8601String() ?: now()->toIso8601String(),
        ];
    }

    private function feedbackStates(string $tenantId, array $opportunityIds): array
    {
        return tenders_feedback_event::query()->where('tenant_id', $tenantId)->whereIn('tenders_opportunity_id', $opportunityIds)
            ->latest('created_at')->get()->unique('tenders_opportunity_id')->mapWithKeys(fn ($event): array => [$event->tenders_opportunity_id => $event->event_type])->all();
    }

    private function contextTerms($context): array
    {
        $values = array_merge([$context->company_name, $context->description], (array) $context->services, (array) $context->technologies, (array) $context->sectors, (array) $context->geography);
        $stopwords = ['para', 'como', 'desde', 'hacia', 'sobre', 'entre', 'esta', 'este', 'empresa', 'empresas', 'servicio', 'servicios', 'contrato', 'colombia', 'remoto', 'remota', 'hibrido', 'hibrida', 'gobierno', 'educacion', 'salud'];
        $terms = [];
        foreach ($values as $value) {
            preg_match_all('/[a-z]{4,}/', $this->normalizeText((string) $value), $matches);
            foreach ($matches[0] as $term) if (! in_array($term, $stopwords, true)) $terms[$term] = true;
        }
        return array_keys($terms);
    }

    private function containsConfiguredValue(?string $value, array $configured): bool
    {
        $normalized = $this->normalizeText($value);
        foreach ($configured as $item) if ($normalized === $this->normalizeText((string) $item) || str_contains($normalized, $this->normalizeText((string) $item))) return true;
        return false;
    }

    private function find(string $publicId): ?tenders_opportunity
    {
        [$source, $sourceId] = array_pad(explode(':', $publicId, 2), 2, null);
        if (! $source || ! $sourceId) return null;
        return tenders_opportunity::query()->where('source', $source)->where('source_id', $sourceId)->first();
    }

    private function publicId(tenders_opportunity $row): string
    {
        return $row->source.':'.$row->source_id;
    }

    private function candidateText(tenders_opportunity $row): string
    {
        return implode(' ', array_filter([$row->title, $row->description, $row->entity, $row->category_code, $row->category_text]));
    }

    private function normalizeText(?string $value): string
    {
        return Str::ascii(Str::lower(trim((string) $value)));
    }

    private function deduplicate(array $items): array
    {
        $selected = [];
        foreach ($items as $item) {
            $key = $item['source'].':'.($item['source_process_id'] ?: $item['opportunity_id']);
            if (! isset($selected[$key]) || ($item['last_published_at'] ?? '') > ($selected[$key]['last_published_at'] ?? '')) $selected[$key] = $item;
        }
        return array_values($selected);
    }
}
