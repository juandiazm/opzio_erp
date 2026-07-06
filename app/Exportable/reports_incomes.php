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

class reports_incomes implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct()
    {
        
    }
    public function title(): string
    {
        return 'Ingresos';
    }
    public function headings(): array
    {
        return [
            'ID',//A
            'Cliente',//B
            'Identificación',//C
            'Factura',//D
            'Valor cobrado',//E
            'Valor pagado',//F
            'Comisión',//G
            'Vendedor',//H
            'Estado',//I
            'Estado de pago',//J
            'Fecha de pago',//K
            'Fecha pago oportuno',//L
            'Fecha de corte',//M
            'Fecha de creación',//N
            'Referencia de pago',//N

        ];
    }
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_CURRENCY_USD, // Columna C para fecha con formato 'dd/mm/yyyy'
            'F' => NumberFormat::FORMAT_CURRENCY_USD, // Columna C para fecha con formato 'dd/mm/yyyy'
            'G' => NumberFormat::FORMAT_PERCENTAGE_00, // Columna C para fecha con formato 'dd/mm/yyyy'
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
            'K' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
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
                $event->sheet->getDelegate()->getStyle('A1:M'.$event->sheet->getDelegate()->getHighestRow())->applyFromArray($styleArray);

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
        $incomes = json_decode(Storage::disk('reports')->get('incomes'.Session::get('user')['unique_id'].'.json'));
        $rows = [];
        foreach($incomes as $income){
            $income->income_licenses = collect($income->income_licenses);
            $employee = collect($income->income_licenses)->map(function($item){if($item->employee!=null){return $item->employee->complete_name;}});
            /*remove empty values and duplicates*/
            $employee = $employee->filter(function($item){return $item!='';})->unique();
            $employee = $employee->implode(', ');
            /*remove last comma*/
            $employee = substr($employee, 0, -2);
            $rows[] = [
                'unique_id' => $income->unique_id,
                'client' => $income->client->name,
                'identification' => $income->client_identification,
                'Factura' => $income->bill_name,
                'charged' => $income->total,
                'paid' => $income->bill_final_value==null?$income->total:$income->bill_final_value,
                'comission_value' => $income->income_licenses->sum('comission_value'),
                'employee' => $employee,
                'status' => $income->state_text,
                'payment_status' => $income->payment_state_text,
                'payment_date' => ($income->payment_date==null?'':\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($income->payment_date)->timestamp)),
                'timely_payment' => ($income->timely_payment==null?'':\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($income->timely_payment)->timestamp)),
                'cut_date' => ($income->cutoff_date==null?'':\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($income->cutoff_date)->timestamp)),
                'created_at' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($income->created_at)->timestamp),
                'payment_reference' => $income->payment_reference,
            ];
        }
        return collect($rows);
    }
    
}