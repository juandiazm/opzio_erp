<?php

namespace App\Services\Jira;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class jira_report_recurrence_service
{
    public const FREQUENCY_UNITS = ['days', 'months'];

    public const RANGE_UNITS = ['days', 'months'];

    public function normalize(array $data): array
    {
        $frequencyValue = (int) ($data['frequency_value'] ?? 0);
        $frequencyUnit = (string) ($data['frequency_unit'] ?? '');
        $executionDay = filled($data['execution_day'] ?? null) ? (int) $data['execution_day'] : null;
        $rangeValue = (int) ($data['range_value'] ?? 0);
        $rangeUnit = (string) ($data['range_unit'] ?? '');

        if ($frequencyValue < 1 || $frequencyValue > 365) {
            throw ValidationException::withMessages(['frequency_value' => 'La frecuencia debe estar entre 1 y 365.']);
        }
        if (! in_array($frequencyUnit, self::FREQUENCY_UNITS, true)) {
            throw ValidationException::withMessages(['frequency_unit' => 'La frecuencia debe expresarse en dias o meses.']);
        }
        if ($frequencyUnit === 'months' && ($executionDay === null || $executionDay < 1 || $executionDay > 31)) {
            throw ValidationException::withMessages(['execution_day' => 'Selecciona el dia del mes en que debe ejecutarse el reporte.']);
        }
        if ($rangeValue < 1 || $rangeValue > 365) {
            throw ValidationException::withMessages(['range_value' => 'El rango debe estar entre 1 y 365.']);
        }
        if (! in_array($rangeUnit, self::RANGE_UNITS, true)) {
            throw ValidationException::withMessages(['range_unit' => 'El rango debe expresarse en dias o meses.']);
        }

        return [
            'frequency_value' => $frequencyValue,
            'frequency_unit' => $frequencyUnit,
            'execution_day' => $frequencyUnit === 'months' ? $executionDay : null,
            'range_value' => $rangeValue,
            'range_unit' => $rangeUnit,
        ];
    }

    public function addFrequency(Carbon $date, int $value, string $unit): Carbon
    {
        return $unit === 'months'
            ? $date->copy()->addMonthsNoOverflow($value)
            : $date->copy()->addDays($value);
    }

    public function nextRunAt(Carbon $from, int $value, string $unit, ?int $executionDay = null): Carbon
    {
        $next = $this->addFrequency($from->copy()->startOfDay(), $value, $unit);
        if ($unit !== 'months' || $executionDay === null) {
            return $next->startOfDay();
        }

        return $next->day(min($executionDay, $next->daysInMonth))->startOfDay();
    }

    public function reportPeriod(Carbon $runAt, int $rangeValue, string $rangeUnit): array
    {
        $to = $rangeUnit === 'months'
            ? $runAt->copy()->startOfMonth()->subDay()
            : $runAt->copy()->startOfDay()->subDay();
        $from = $rangeUnit === 'months'
            ? $to->copy()->startOfMonth()->subMonthsNoOverflow($rangeValue - 1)
            : $to->copy()->subDays($rangeValue - 1);

        return [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
        ];
    }
}