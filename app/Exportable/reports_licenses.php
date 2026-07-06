<?php

namespace App\Exportable;

use Session;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;
use Storage;

class reports_licenses implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct()
    {
        
    }
    public function title(): string
    {
        return 'Licencias';
    }
    
    public function headings(): array
    {
        return [
            'ID',//A
            'Cliente',//B
            'Servicio',//C
            'Tipo',//D
            'Valor',//E
            'Día de pago',//F
            'Vendedor',//G
            'Comisión',//H
            'Días restantes',//I
            'Recurrencia',//J
            'Estado',//K
            'Fecha expiración',//L
            'Ultimo pago',//M
            'Fecha de creación',//N
        ];
    }
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00, // Columna C para fecha con formato 'dd/mm/yyyy'
            'F' => NumberFormat::FORMAT_NUMBER, // Columna C para fecha con formato 'dd/mm/yyyy'
            'H' => NumberFormat::FORMAT_PERCENTAGE, // Columna C para fecha con formato 'dd/mm/yyyy'
            'I' => NumberFormat::FORMAT_NUMBER, // Columna C para fecha con formato 'dd/mm/yyyy'
            'J' => NumberFormat::FORMAT_NUMBER, // Columna C para fecha con formato 'dd/mm/yyyy'
            'L' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
            'M' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
            'N' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
               // All headers - set font size to 14
               
                // Apply array of styles to B2:G8 cell range
                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];
                $event->sheet->getDelegate()->getStyle('A1:N'.$event->sheet->getDelegate()->getHighestRow())->applyFromArray($styleArray);

                $styleArray = [
                    'font' => array(
                        'bold' => true,
                        'size' => 12,
                    )
                ];
                $event->sheet->getDelegate()->getStyle('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1')->applyFromArray($styleArray);
            },
        ];
    }
    public function collection()
    {
        $licences = json_decode(Storage ::disk('reports')->get('licenses'.Session::get('user')['unique_id'].'.json'));
        $rows = [];
        foreach ($licences as $licence) {
            $rows[] = [
                'unique_id' => $licence->unique_id,
                'client' => $licence->client->complete_name,
                'service' => $licence->service->name,
                'type' => $licence->type_string,
                'value' => $licence->value,
                'payment_day' => $licence->billing_day,
                'seller' => $licence->employee==null?'':$licence->employee->complete_name,
                'commission' => $licence->comission,
                'remaining_days' => $licence->remaining_days,
                'recurrence' => $licence->recurrence_months,
                'status' => $licence->active_string,
                'expiration_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($licence->next_billing_date)->timestamp),
                'last_payment' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($licence->last_payed_date)->timestamp),
                'created_at' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($licence->created_at)->timestamp),
            ];
        }
        return collect($rows);
    }
}