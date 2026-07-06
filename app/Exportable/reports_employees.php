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

class reports_employees implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct()
    {
        
    }
    public function title(): string
    {
        return 'Empleados';
    }
    public function headings(): array
    {
        return [
            'ID',//A
            'Nombre',//B
            'Tipo de documento',//C
            'Documento',//D
            'Teléfono',//E
            'Correo empresarial',//F
            'Correo personal',//G
            'Cargo',//H
            'Salario',//I
            'Estado',//J
            'Tipo de pago',//K
            'Banco',//L
            'Cuenta',//M
            'Tipo de cuenta',//N
            'EPS',//O
            'AFP',//P
            'ARL',//Q
            'Fecha retiro',//R
            'Fecha de creación',//S

        ];
    }
    public function columnFormats(): array
    {
        return [
            'Q' => NumberFormat::FORMAT_NUMBER_00, // Columna C para fecha con formato 'dd/mm/yyyy'
            'R' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
            'S' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
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
        $employees = json_decode(Storage::disk('reports')->get('employees'.Session::get('user')['unique_id'].'.json'));
        $rows = [];
        foreach ($employees as $employee) {
            $rows[] = [
                'unique_id' => $employee->uid,
                'name' => $employee->complete_name,
                'document_type' => $employee->id_type_string,
                'document' => $employee->identification,
                'phone' => $employee->phone,
                'email' => $employee->work_email,
                'personal_email' => $employee->personal_email,
                'position' => $employee->charge,
                'salary' => $employee->salary,
                'state' => $employee->state_string,
                'payment_type' => $employee->payment_type_string,
                'bank' => $employee->bank,
                'account' => $employee->account_number,
                'account_type' => $employee->account_type_string,
                'eps' => $employee->eps==null?'':$employee->eps->name,
                'afp' => $employee->afp==null?'':$employee->afp->name,
                'arl' => $employee->arl==null?'':$employee->arl->name,
                'retirement_date' => $employee->retirement_date==null?'':\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($employee->retirement_date)->timestamp),
                'created_at' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($employee->created_at)->timestamp),
            ];
        }
        return collect($rows);
    }
}