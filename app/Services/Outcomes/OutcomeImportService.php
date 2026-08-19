<?php

namespace App\Services\Outcomes;

use App\Models\outcome;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutcomeImportService
{
    public function __construct(
        private BoldOutcomeParser $parser,
        private OutcomeClassifier $classifier
    ) {
    }

    public function import(string $path, string $source, int $userId): array
    {
        if ($source !== 'bold') {
            return [
                'status' => 0,
                'message' => 'La fuente de importación no está disponible.',
            ];
        }

        try {
            $parsed = $this->parser->parse($path);
        } catch (\Throwable $exception) {
            return [
                'status' => 0,
                'message' => $exception->getMessage(),
            ];
        }

        $stats = [
            'imported' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'history_classified' => 0,
            'catalog_classified' => 0,
            'ai_classified' => 0,
            'types_created' => 0,
            'fallback_classified' => 0,
            'unclassified' => 0,
            'errors' => [],
        ];
        $createdTypeIds = [];

        foreach ($parsed['errors'] as $error) {
            $stats['errors'][] = $error;
        }

        foreach ($parsed['rows'] as $row) {
            if ((float) $row['amount'] >= 0) {
                $stats['skipped']++;
                continue;
            }

            $sourceHash = $this->makeSourceHash($source, $row);
            if (outcome::withTrashed()->where('source_hash', $sourceHash)->exists()) {
                $stats['duplicates']++;
                continue;
            }

            $classification = $this->classifier->classify($row);
            if (empty($classification['outcome_type_id'])) {
                $stats['unclassified']++;
                $stats['errors'][] = [
                    'line' => $row['line'],
                    'message' => 'No se pudo determinar un tipo de egreso; la fila no fue guardada.',
                ];
                continue;
            }

            try {
                $created = false;
                DB::transaction(function () use ($row, $source, $sourceHash, $userId, $classification, &$created): void {
                    if (outcome::withTrashed()->where('source_hash', $sourceHash)->lockForUpdate()->exists()) {
                        return;
                    }

                    $outcome = new outcome();
                    $outcome->unique_id = (string) Str::uuid();
                    $outcome->outcome_type_id = (int) $classification['outcome_type_id'];
                    $outcome->name = mb_substr($row['description'], 0, 100, 'UTF-8');
                    $outcome->description = $row['description'];
                    $outcome->amount = abs((float) $row['amount']);
                    $outcome->date = $row['date'];
                    $outcome->user_id = $userId;
                    $outcome->provider_id = $classification['provider_id'] ?? null;
                    $outcome->employee_id = $classification['employee_id'] ?? null;
                    $outcome->department_id = $classification['department_id'] ?? null;
                    $outcome->client_id = $classification['client_id'] ?? null;
                    $outcome->source = $source;
                    $outcome->source_identifier = $row['identifier'];
                    $outcome->source_hash = $sourceHash;
                    $outcome->classification_source = $classification['classification_source'] ?? 'fallback';
                    $outcome->classification_confidence = $classification['classification_confidence'] ?? 0;
                    $outcome->save();
                    $created = true;
                });

                if ($created) {
                    $stats['imported']++;
                    $classificationSource = $classification['classification_source'] ?? 'fallback';
                    if ($classificationSource === 'history') {
                        $stats['history_classified']++;
                    } elseif ($classificationSource === 'catalog') {
                        $stats['catalog_classified']++;
                    } elseif ($classificationSource === 'ai') {
                        $stats['ai_classified']++;
                    } else {
                        $stats['fallback_classified']++;
                    }
                    $classificationTypeId = (int) ($classification['outcome_type_id'] ?? 0);
                    if (!empty($classification['outcome_type_created']) && !in_array($classificationTypeId, $createdTypeIds, true)) {
                        $stats['types_created']++;
                        $createdTypeIds[] = $classificationTypeId;
                    }
                } else {
                    $stats['duplicates']++;
                }
            } catch (QueryException $exception) {
                if (outcome::withTrashed()->where('source_hash', $sourceHash)->exists()) {
                    $stats['duplicates']++;
                    continue;
                }
                $stats['errors'][] = [
                    'line' => $row['line'],
                    'message' => 'No se pudo guardar el egreso.',
                ];
                info('OutcomeImportService database error: ' . $exception->getMessage());
            } catch (\Throwable $exception) {
                $stats['errors'][] = [
                    'line' => $row['line'],
                    'message' => $exception->getMessage(),
                ];
                info('OutcomeImportService row error: ' . $exception->getMessage());
            }
        }

        $processableRows = $stats['imported'] + $stats['duplicates'] + $stats['skipped'];
        $status = $processableRows > 0 || count($stats['errors']) === 0 ? 1 : 0;
        $message = 'Importación Bold: ' . $stats['imported'] . ' importados, '
            . $stats['duplicates'] . ' duplicados y ' . $stats['skipped'] . ' omitidos.';
        $message .= ' Tipos: ' . ($stats['history_classified'] + $stats['catalog_classified']) . ' existentes, '
            . $stats['types_created'] . ' creados por IA.';
        if (count($stats['errors']) > 0) {
            $messages = array_map(fn (array $error): string => 'Fila ' . $error['line'] . ': ' . $error['message'], array_slice($stats['errors'], 0, 5));
            $message .= ' Errores: ' . implode(' | ', $messages);
        }

        return [
            'status' => $status,
            'message' => $message,
            'data' => $stats,
        ];
    }

    private function makeSourceHash(string $source, array $row): string
    {
        return hash('sha256', json_encode([
            $source,
            trim((string) $row['identifier']),
            $row['date'],
            number_format(abs((float) $row['amount']), 2, '.', ''),
            mb_strtoupper(trim((string) $row['description']), 'UTF-8'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}