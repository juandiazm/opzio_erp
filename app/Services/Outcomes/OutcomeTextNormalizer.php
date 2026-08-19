<?php

namespace App\Services\Outcomes;

use Illuminate\Support\Str;

class OutcomeTextNormalizer
{
    private const NOISE_WORDS = [
        'A',
        'BOLD',
        'CO',
        'COMISION',
        'COMPRA',
        'CON',
        'CUOTA',
        'DE',
        'DEBITO',
        'DEL',
        'EL',
        'EN',
        'GMF',
        'GOBIERNO',
        'IMPTO',
        'IMPUESTO',
        'IVA',
        'LA',
        'LAS',
        'LOS',
        'MANEJO',
        'PAGO',
        'POR',
        'PROV',
        'PROVE',
        'SAS',
        'SA',
        'TARJETA',
        'TRANSFERENCIA',
        'UNA',
        'UN',
    ];

    public function normalize($value): string
    {
        $value = Str::ascii(mb_strtoupper(trim((string) $value), 'UTF-8'));
        $value = preg_replace('/\b(?=[A-Z0-9]*\d)[A-Z0-9]{7,}\b/', ' ', $value);
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public function merchantTokens($value): array
    {
        $tokens = preg_split('/\s+/', $this->normalize($value), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_filter($tokens, function (string $token): bool {
            return strlen($token) > 2
                && !in_array($token, self::NOISE_WORDS, true)
                && !preg_match('/^(?=.*\d)[A-Z0-9]{7,}$/', $token);
        });

        return array_values(array_unique($tokens));
    }

    public function merchantKey($value): string
    {
        $normalized = $this->normalize($value);
        $tokens = $this->merchantTokens($value);
        $category = 'GENERAL';

        foreach (['GMF', 'COMISION', 'COMPRA', 'IMPTO', 'IMPUESTO', 'IVA', 'CUOTA', 'PAGO', 'TRANSFERENCIA'] as $candidate) {
            if (preg_match('/\b' . $candidate . '\b/', $normalized)) {
                $category = $candidate;
                break;
            }
        }

        return $category . '|' . implode(' ', $tokens);
    }

    public function similarity($left, $right): float
    {
        $leftNormalized = $this->normalize($left);
        $rightNormalized = $this->normalize($right);
        if ($leftNormalized === '' || $rightNormalized === '') {
            return 0.0;
        }
        if ($leftNormalized === $rightNormalized) {
            return 1.0;
        }
        if ($this->merchantKey($left) === $this->merchantKey($right)) {
            return 0.93;
        }

        $leftTokens = $this->merchantTokens($left);
        $rightTokens = $this->merchantTokens($right);
        $union = array_unique(array_merge($leftTokens, $rightTokens));
        $intersection = array_intersect($leftTokens, $rightTokens);
        $jaccard = count($union) > 0 ? count($intersection) / count($union) : 0.0;
        similar_text($leftNormalized, $rightNormalized, $similarity);

        return max($jaccard, ($jaccard * 0.8) + (($similarity / 100) * 0.2));
    }
}