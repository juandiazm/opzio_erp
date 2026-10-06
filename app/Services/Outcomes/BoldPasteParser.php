<?php

namespace App\Services\Outcomes;

use Carbon\Carbon;
use InvalidArgumentException;

class BoldPasteParser
{
    private const DATE_HEADERS = [
        'FECHA',
        'DATE',
        'FECHATRANSACCION',
        'FECHADETRANSACCION',
        'FECHAOPERACION',
        'FECHADEOPERACION',
        'FECHADECOMPRA',
        'DATETIME',
    ];

    private const AMOUNT_HEADERS = [
        'VALOR',
        'VALORNETO',
        'VALORTRANSACCION',
        'VALORDETRANSACCION',
        'VALORCOMPRA',
        'MONTO',
        'MONTONETO',
        'IMPORTE',
        'AMOUNT',
        'TOTAL',
        'DEBITO',
        'CARGO',
        'EGRESO',
        'GASTO',
    ];

    private const DESCRIPTION_HEADERS = [
        'DESCRIPCION',
        'DESCRIPCIONTRANSACCION',
        'DETALLE',
        'CONCEPTO',
        'COMERCIO',
        'ESTABLECIMIENTO',
        'PRODUCTO',
        'NOMBRE',
        'MOVIMIENTO',
        'TIPODETRANSACCION',
        'TIPODEMOVIMIENTO',
        'TIPO',
    ];

    private const IDENTIFIER_HEADERS = [
        'IDENTIFICADOR',
        'REFERENCIA',
        'REFERENCIATRANSACCION',
        'IDTRANSACCION',
        'CODIGO',
        'ID',
    ];

    private const TYPE_HEADERS = [
        'TIPODEMOVIMIENTO',
        'TIPODETRANSACCION',
        'TIPOOPERACION',
        'CATEGORIA',
        'CLASE',
        'OPERACION',
        'TIPO',
    ];

    public function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', trim($content));
        if ($content === '') {
            throw new InvalidArgumentException('No se recibieron datos para importar.');
        }

        $lines = preg_split('/\r\n|\n|\r/', $content);
        $firstLine = '';
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $firstLine = $line;
                break;
            }
        }
        if ($firstLine === '') {
            throw new InvalidArgumentException('No se recibieron datos para importar.');
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $rows = [];
        $header = null;
        $lineNumber = 0;
        $errors = [];

        foreach ($lines as $line) {
            $lineNumber++;
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            if ($header === null) {
                $header = $this->buildHeaderMap($cells);
                if ($header['date'] === null || $header['amount'] === null) {
                    throw new InvalidArgumentException('No se encontraron columnas reconocibles de fecha y valor en la tabla pegada.');
                }
                continue;
            }

            try {
                $dateValue = trim((string) ($cells[$header['date']] ?? ''));
                $amount = $this->parseMoney($cells[$header['amount']] ?? null);
                if ($amount === null) {
                    throw new InvalidArgumentException('El valor está vacío o no tiene un formato válido.');
                }

                $date = $this->parseDate($dateValue);
                $movementType = trim((string) ($cells[$header['type']] ?? ''));
                if ($this->isExplicitNonExpenseMovement($movementType)) {
                    $amount = ltrim($amount, '-');
                } elseif ((float) $amount > 0 && (
                    $this->isDebitColumn($header['normalized'][$header['amount']])
                    || $this->isExpenseMovement($movementType)
                )) {
                    $amount = '-' . ltrim($amount, '-');
                }

                $description = $this->buildDescription($cells, $header);
                if ($description === '') {
                    throw new InvalidArgumentException('La fila no contiene detalle descriptivo.');
                }

                $identifier = trim((string) ($cells[$header['identifier']] ?? ''));
                if ($identifier === '') {
                    $identifier = 'PASTE-' . substr(hash('sha256', json_encode([
                        $date,
                        $amount,
                        $description,
                        $cells,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 24);
                }

                $rows[] = [
                    'line' => $lineNumber,
                    'date' => $date,
                    'identifier' => $identifier,
                    'description' => $description,
                    'amount' => $amount,
                    'balance' => null,
                ];
            } catch (\Throwable $exception) {
                $errors[] = [
                    'line' => $lineNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $delimiter = "\t";
        $highestCount = 0;
        foreach (["\t", ';', ','] as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $highestCount) {
                $delimiter = $candidate;
                $highestCount = $count;
            }
        }

        return $delimiter;
    }

    private function buildHeaderMap(array $headers): array
    {
        $normalized = array_map(fn ($header): string => $this->normalizeHeader($header), $headers);
        $date = $this->findHeader($normalized, self::DATE_HEADERS, 'FECHA');
        $amount = $this->findHeader($normalized, self::AMOUNT_HEADERS, 'VALOR');
        $description = $this->findHeader($normalized, self::DESCRIPTION_HEADERS);
        $identifier = $this->findHeader($normalized, self::IDENTIFIER_HEADERS);
        $type = $this->findHeader($normalized, self::TYPE_HEADERS);

        return [
            'date' => $date,
            'amount' => $amount,
            'description' => $description,
            'identifier' => $identifier,
            'type' => $type,
            'normalized' => $normalized,
        ];
    }

    private function findHeader(array $headers, array $aliases, ?string $fallbackPrefix = null): ?int
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }

        if ($fallbackPrefix !== null) {
            foreach ($headers as $index => $header) {
                if (str_starts_with($header, $fallbackPrefix)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function normalizeHeader($value): string
    {
        $value = mb_strtoupper(trim((string) $value), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return preg_replace('/[^A-Z0-9]/', '', $ascii === false ? $value : $ascii);
    }

    private function buildDescription(array $cells, array $header): string
    {
        $description = trim((string) ($cells[$header['description']] ?? ''));
        $details = [];
        $excludedColumns = array_filter([
            $header['date'],
            $header['amount'],
            $header['description'],
            $header['identifier'],
        ], fn ($index): bool => $index !== null);

        foreach ($cells as $index => $cell) {
            $value = trim((string) $cell);
            if ($value === '' || in_array($index, $excludedColumns, true)) {
                continue;
            }
            if (str_contains($header['normalized'][$index] ?? '', 'SALDO')) {
                continue;
            }

            $label = trim((string) ($header['normalized'][$index] ?? ''));
            $details[] = $label === '' ? $value : $label . ': ' . $value;
        }

        if ($description !== '') {
            array_unshift($details, $description);
        }

        return mb_substr(implode(' | ', $details), 0, 2000, 'UTF-8');
    }

    private function isDebitColumn(string $header): bool
    {
        return str_contains($header, 'DEBITO')
            || str_contains($header, 'CARGO')
            || str_contains($header, 'EGRESO')
            || str_contains($header, 'GASTO');
    }

    private function isExplicitNonExpenseMovement(string $movementType): bool
    {
        $type = $this->normalizeHeader($movementType);
        foreach (['ABONO', 'INGRESO', 'CREDITO', 'VENTA', 'REEMBOLSO', 'DEVOLUCION', 'RECIBIDO', 'RECIBIDA', 'ANULACION', 'REVERSO', 'REVERSA'] as $marker) {
            if (str_contains($type, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function isExpenseMovement(string $movementType): bool
    {
        $type = $this->normalizeHeader($movementType);
        foreach (['GASTO', 'EGRESO', 'COMPRA', 'CARGO', 'DEBITO', 'COMISION', 'RETIRO', 'PAGO', 'TARIFA', 'SUSCRIPCION'] as $marker) {
            if (str_contains($type, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function parseDate(string $value): string
    {
        if ($value === '') {
            throw new InvalidArgumentException('La fecha está vacía.');
        }

        foreach (['!d/m/Y', '!d/m/Y H:i:s', '!d/m/Y H:i', '!Y-m-d', '!Y-m-d H:i:s', '!Y-m-d H:i', '!d-m-Y', '!d-m-Y H:i:s', '!d-m-Y H:i'] as $format) {
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
