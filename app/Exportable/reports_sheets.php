<?php

namespace App\Exportable;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class reports_sheets implements WithMultipleSheets
{
    public $sheets;
    public function __construct($sheets)
    {
        $this->sheets = $sheets;
    }
    public function sheets():array
    {
        $sheetsData = [];
        foreach($this->sheets as $sheet){
            switch($sheet){
                case 'incomes':
                    $sheetsData['Ingresos'] = new reports_incomes();
                    break;
                case 'outcomes':
                    $sheetsData['Egresos'] = new reports_outcomes();
                    break;
                case 'clients':
                    $sheetsData['Clientes'] = new reports_clients();
                    break;
                case 'licenses':
                    $sheetsData['Licencias'] = new reports_licenses();
                    break;
                case 'users':
                    $sheetsData['Usuarios'] = new reports_users();
                    break;
                case 'employees':
                    $sheetsData['Empleados'] = new reports_employees();
                    break;
            }
        }
        return $sheetsData;
    }
}