<?php

namespace App\Services\Outcomes;

use Carbon\Carbon;
use InvalidArgumentException;

class BoldOutcomeParser
{
    private const REQUIRED_HEADERS = [
        'FECHA',
        'IDENTIFICADOR',
        'DESCRIPCION',
        'VALOR',
        'SALDO',
    ];

    public function parse(string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('No se pudo abrir el archivo de Bold.');
        }

        try {
            $delimiter = $this->detectDelimiter($handle);
            rewind($handle);

            $header = null;
            $lineNumber = 0;
            $rows = [];
            $errors = [];

            while (($row = fgetcsv($handle, 1024 * 1024, $delimiter)) !== false) {
                $lineNumber++;
                if ($this->isBlankRow($row)) {
                    continue;
                }

                if ($header === null) {
                    $candidate = $this->buildHeaderMap($row);
                    if (count($candidate) === count(self::REQUIRED_HEADERS)) {
                        $header = $candidate;
                    }
                    continue;
                }

                try {
                    $identifier = trim((string) ($row[$header['IDENTIFICADOR']] ?? ''));
                    $date = trim((string) ($row[$header['FECHA']] ?? ''));
                    $description = trim((string) ($row[$header['DESCRIPCION']] ?? ''));
                    $amount = $this->parseMoney($row[$header['VALOR']] ?? null);
                    $balance = $this->parseMoney($row[$header['SALDO']] ?? null);

                    if ($identifier === '') {
                        throw new InvalidArgumentException('El identificador está vacío.');
                    }
                    if ($description === '') {
                        $description = 'Sin descripción';
                    }
                    if ($amount === null) {
                        throw new InvalidArgumentException('El valor está vacío o no tiene un formato válido.');
                    }

                    $rows[] = [
                        'line' => $lineNumber,
                        'date' => $this->parseDate($date),
                        'identifier' => $identifier,
                        'description' => $description,
                        'amount' => $amount,
                        'balance' => $balance,
                    ];
                } catch (\Throwable $exception) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'message' => $exception->getMessage(),
                    ];
                }
            }

            if ($header === null) {
                throw new InvalidArgumentException('El archivo no contiene los encabezados requeridos de Bold.');
            }

            return [
                'rows' => $rows,
                'errors' => $errors,
            ];
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter($handle): string
    {
        $firstLine = '';
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line !== false && trim($line) !== '') {
                $firstLine = $line;
                break;
            }
        }

        if ($firstLine === '') {
            throw new InvalidArgumentException('El archivo está vacío.');
        }

        $delimiter = ',';
        $highestCount = 0;
        foreach (["\t", ';', ','] as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $highestCount) {
                $delimiter = $candidate;
                $highestCount = $count;
            }
        }

        return $delimiter;
    }

    private function buildHeaderMap(array $row): array
    {
        $map = [];
        foreach ($row as $index => $value) {
            $header = $this->normalizeHeader($value);
            if (in_array($header, self::REQUIRED_HEADERS, true)) {
                $map[$header] = $index;
            }
        }

        return $map;
    }

    private function normalizeHeader($value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        return mb_strtoupper(trim($value), 'UTF-8');
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDate(string $value): string
    {
        if ($value === '') {
            throw new InvalidArgumentException('La fecha está vacía.');
        }

        foreach (['!d/m/Y', '!Y-m-d', '!d-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                $dateErrors = Carbon::getLastErrors();
                if ($date !== false && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        throw new InvalidArgumentException("La fecha '{$value}' no es válida.");
    }

    private function parseMoney($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\xC2\xA0", ' ', '$'], '', $value);
        $negative = str_starts_with($value, '-') || (str_starts_with($value, '(') && str_ends_with($value, ')'));
        $value = trim($value, '()-');
        $value = preg_replace('/[^0-9,.]/u', '', $value);
        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1 || preg_match('/\.\d{3}$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        if (!preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            return null;
        }

        $amount = number_format((float) $value, 2, '.', '');
        if ((float) $amount === 0.0) {
            return '0.00';
        }

        return ($negative ? '-' : '') . $amount;
    }
}