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

class reports_users implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct()
    {
        
    }
    public function title(): string
    {
        return 'Usuarios';
    }
    
    
    public function headings(): array
    {
        return [
            'ID',//A
            'Nombre',//B
            'Usuario',//C
            'Identificación',//D
            'Correo',//E
            'Fecha de creación',//F
        ];
    }
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
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
        $users = json_decode(Storage::disk('reports')->get('users'.Session::get('user')['unique_id'].'.json'));
        $rows = [];
        foreach ($users as $licence) {
            $rows[] = [
                'unique_id' => $licence->unique_id,
                'name' => $licence->complete_name,
                'username' => $licence->username,
                'identification' => $licence->identification,
                'email' => $licence->email,
                'created_at' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($licence->created_at)->timestamp),
            ];
        }
        return collect($rows);
    }
}