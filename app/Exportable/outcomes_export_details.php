<?php

namespace App\Exportable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class outcomes_export_details extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithTitle, WithColumnFormatting, WithEvents, WithCustomValueBinder
{
    public function __construct(private Builder $query)
    {
    }

    public function title(): string
    {
        return 'Detalle';
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'ID interno',
            'ID único',
            'Fecha del egreso',
            'Hora del egreso',
            'Concepto',
            'Descripción',
            'Tipo de egreso',
            'ID tipo',
            'Monto',
            'Proveedor',
            'ID proveedor',
            'Empleado',
            'ID empleado',
            'Departamento',
            'ID departamento',
            'Cliente',
            'ID cliente',
            'Usuario que registró',
            'ID usuario',
            'Fuente',
            'Referencia de fuente',
            'Huella de origen',
            'Clasificación',
            'Confianza de clasificación',
            'Estado',
            'Fecha de creación',
            'Hora de creación',
            'Fecha de actualización',
            'Hora de actualización',
            'Fecha de eliminación',
            'Hora de eliminación',
        ];
    }

    public function query(): Builder
    {
        return (clone $this->query)
            ->with(['provider', 'employee', 'department', 'user', 'client', 'outcomeType'])
            ->orderBy('date')
            ->orderBy('id');
    }

    public function map($outcome): array
    {
        $date = $outcome->date ? Carbon::parse($outcome->date) : null;
        $createdAt = $outcome->created_at ? Carbon::parse($outcome->created_at) : null;
        $updatedAt = $outcome->updated_at ? Carbon::parse($outcome->updated_at) : null;
        $deletedAt = $outcome->deleted_at ? Carbon::parse($outcome->deleted_at) : null;

        return [
            (int) $outcome->id,
            $outcome->unique_id,
            $this->excelDate($date),
            $this->excelTime($date),
            $outcome->name,
            $outcome->description,
            $outcome->outcomeType ? $outcome->outcomeType->name : null,
            $outcome->outcome_type_id,
            (float) $outcome->amount,
            $this->personName($outcome->provider, ['last_name', 'lastname']),
            $outcome->provider_id,
            $this->personName($outcome->employee, ['last_name', 'lastname']),
            $outcome->employee_id,
            $outcome->department ? $outcome->department->name : null,
            $outcome->department_id,
            $this->personName($outcome->client, ['last_name', 'lastname']),
            $outcome->client_id,
            $this->personName($outcome->user, ['last_name', 'lastname']),
            $outcome->user_id,
            $outcome->source,
            $outcome->source_identifier,
            $outcome->source_hash,
            $outcome->classification_source,
            $outcome->classification_confidence === null ? null : (float) $outcome->classification_confidence,
            $deletedAt ? 'Eliminado' : 'Vigente',
            $this->excelDate($createdAt),
            $this->excelTime($createdAt),
            $this->excelDate($updatedAt),
            $this->excelTime($updatedAt),
            $this->excelDate($deletedAt),
            $this->excelTime($deletedAt),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 24,
            'C' => 15,
            'D' => 12,
            'E' => 30,
            'F' => 48,
            'G' => 24,
            'H' => 12,
            'I' => 16,
            'J' => 25,
            'K' => 13,
            'L' => 25,
            'M' => 13,
            'N' => 24,
            'O' => 14,
            'P' => 25,
            'Q' => 12,
            'R' => 25,
            'S' => 12,
            'T' => 16,
            'U' => 28,
            'V' => 68,
            'W' => 20,
            'X' => 18,
            'Y' => 14,
            'Z' => 15,
            'AA' => 12,
            'AB' => 15,
            'AC' => 12,
            'AD' => 15,
            'AE' => 12,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => 'yyyy-mm-dd',
            'D' => 'hh:mm:ss',
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'X' => '0.00%',
            'Z' => 'yyyy-mm-dd',
            'AA' => 'hh:mm:ss',
            'AB' => 'yyyy-mm-dd',
            'AC' => 'hh:mm:ss',
            'AD' => 'yyyy-mm-dd',
            'AE' => 'hh:mm:ss',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
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
                $sheet->getStyle('F:F')->getAlignment()->setWrapText(true);
            },
        ];
    }

    private function excelDate(?Carbon $date): ?float
    {
        return $date ? Date::dateTimeToExcel($date->copy()->startOfDay()) : null;
    }

    private function excelTime(?Carbon $date): ?float
    {
        if (!$date) {
            return null;
        }

        return (
            ((int) $date->format('H') * 3600)
            + ((int) $date->format('i') * 60)
            + (int) $date->format('s')
        ) / 86400;
    }

    private function personName($person, array $lastNameFields): ?string
    {
        if (!$person) {
            return null;
        }

        $lastName = null;
        foreach ($lastNameFields as $field) {
            if (!empty($person->{$field})) {
                $lastName = $person->{$field};
                break;
            }
        }

        $name = trim(implode(' ', array_filter([$person->name, $lastName])));
        return $name === '' ? null : $name;
    }
}
