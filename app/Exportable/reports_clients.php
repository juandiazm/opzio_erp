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
use Storage;
use Carbon\Carbon;

class reports_clients implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct()
    {
        
    }
    public function title(): string
    {
        return 'Clientes';
    }
    public function headings(): array
    {
        return [
            'ID',//A
            'Nombre',//B
            'Apellido',//C
            'Tipo de documento',//D
            'Número de documento',//E
            'Correo',//F
            'Teléfono',//G
            'Dirección',//H
            'País',//I
            'Sector',//J
            'Activo',//K
            'Verificado',//L
            'Fecha de creación',//M
        ];
    }
    public function columnFormats(): array
    {
        return [
            'M' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Columna C para fecha con formato 'dd/mm/yyyy'
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
        $clients = json_decode(Storage::disk('reports')->get('clients'.Session::get('user')['unique_id'].'.json'));
        $rows = [];
        foreach ($clients as $client) {
            $client->country = json_decode(json_encode($client->country), true);
            $client->sector = json_decode(json_encode($client->sector), true);
            $rows[] = [
                'unique_id' => $client->unique_id,
                'name' => $client->name,
                'last_name' => $client->lastname,
                'document_type' => $client->identification_type_string,
                'document_number' => $client->identification,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'country' => $client->country['name'],
                'sector' => $client->sector['name'],
                'active' => $client->active_string,
                'verified' => $client->verified_string,
                'created_at' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($client->created_at)->timestamp),
            ];
        }
        return collect($rows);
    }
}