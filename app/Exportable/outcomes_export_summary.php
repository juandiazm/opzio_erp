<?php

namespace App\Exportable;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class outcomes_export_summary extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithColumnFormatting, WithEvents, WithCustomValueBinder
{
    public function __construct(private Builder $query)
    {
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return [
            'Tipo de egreso',
            'Estado',
            'Cantidad de registros',
            'Monto total',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        $summaryQuery = clone $this->query->getQuery();
        $groupedOutcomes = $summaryQuery
            ->leftJoin('outcome_types as summary_types', 'outcomes.outcome_type_id', '=', 'summary_types.id')
            ->selectRaw('COALESCE(summary_types.name, ?) as outcome_type', ['Sin tipo'])
            ->selectRaw('CASE WHEN outcomes.deleted_at IS NULL THEN 0 ELSE 1 END as deleted_status')
            ->selectRaw('COUNT(outcomes.id) as records, SUM(outcomes.amount) as amount')
            ->groupBy('outcomes.outcome_type_id', 'summary_types.name')
            ->groupByRaw('CASE WHEN outcomes.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('outcome_type')
            ->orderByRaw('CASE WHEN outcomes.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->get();

        $totals = (clone $this->query->getQuery())
            ->selectRaw(
                'COUNT(outcomes.id) as total_records,
                COALESCE(SUM(CASE WHEN outcomes.deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_records,
                COALESCE(SUM(CASE WHEN outcomes.deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as deleted_records,
                COALESCE(SUM(CASE WHEN outcomes.deleted_at IS NULL THEN outcomes.amount ELSE 0 END), 0) as active_amount,
                COALESCE(SUM(CASE WHEN outcomes.deleted_at IS NOT NULL THEN outcomes.amount ELSE 0 END), 0) as deleted_amount,
                COALESCE(SUM(outcomes.amount), 0) as total_amount'
            )
            ->first();

        $rows = $groupedOutcomes->map(function ($outcome) {
            return [
                $outcome->outcome_type,
                (int) $outcome->deleted_status === 0 ? 'Vigente' : 'Eliminado',
                (int) $outcome->records,
                (float) $outcome->amount,
            ];
        })->all();

        $rows[] = ['TOTAL VIGENTE', 'Vigente', (int) $totals->active_records, (float) $totals->active_amount];
        $rows[] = ['TOTAL ELIMINADO', 'Eliminado', (int) $totals->deleted_records, (float) $totals->deleted_amount];
        $rows[] = ['TOTAL GENERAL (incluye eliminados)', 'Todos', (int) $totals->total_records, (float) $totals->total_amount];

        return collect($rows);
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $totalsStartRow = max(2, $lastRow - 2);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:D'.$lastRow);
                $sheet->getStyle('A1:D1')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00057B'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ]);
                $sheet->getStyle('A'.$totalsStartRow.':D'.$lastRow)->getFont()->setBold(true);
            },
        ];
    }
}
