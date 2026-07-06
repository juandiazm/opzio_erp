<?php

namespace App\Imports;

use Session;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

use App\traits\outcomes_trait;

class outcomes_import implements ToCollection
{
    use outcomes_trait;
    public $Result;

    public function collection(Collection $rows)
    {
        $this->Result = [
            'status' => 0,
            'message' => 'Error al importar los datos'
        ];
        try{
            if($rows->isEmpty()){
                $this->Result['message'] = 'El archivo está vacío o no se pudo leer';
                return $this->Result;
            }

            $col_date = 0;
            $col_description = 1;
            $col_amount = 4;
            $start_process = false;
            $imported_count = 0;
            $skipped_count = 0;
            $row_errors = [];

            foreach ($rows as $rowIndex => $row) 
            {
                try{
                    if($start_process == false){
                        $cellValue = $row[$col_date] ?? null;
                        if($cellValue !== null && strpos((string)$cellValue, 'Movimientos:') !== false){
                            $start_process = true;
                            continue;
                        }else{
                            continue;
                        }
                    }

                    if(!isset($row[$col_amount]) || $row[$col_amount] === null){
                        $skipped_count++;
                        continue;
                    }

                    $amount = str_replace(',', '', (string)$row[$col_amount]);
                    if(is_numeric($amount) && $amount < 0){
                        if(!isset($row[$col_date]) || empty($row[$col_date])){
                            $row_errors[] = "Fila #{$rowIndex}: La fecha está vacía";
                            continue;
                        }
                        $date = $row[$col_date].Carbon::now()->format('/Y');
                        $date = str_replace('/', '-', $date);
                        try {
                            $date = Carbon::parse($date);
                        } catch(\Exception $e) {
                            $row_errors[] = "Fila #{$rowIndex}: Fecha inválida '{$row[$col_date]}' - {$e->getMessage()}";
                            continue;
                        }
                        $description = $row[$col_description] ?? 'Sin descripción';
                        $name = $description;
                        $amount = abs($amount);
                        $type = -1;

                        $user = Session::get('user');
                        if(!$user || !isset($user['id'])){
                            $this->Result['message'] = 'No se encontró la sesión del usuario. Por favor inicie sesión nuevamente.';
                            return $this->Result;
                        }

                        $createResult = $this->Outcome_CreateOutcome(
                            $date
                            ,$name
                            ,$description
                            ,$amount
                            ,$type
                            ,$user['id']
                            ,null
                        );
                        if($createResult['status'] == 1){
                            $imported_count++;
                        } else {
                            $row_errors[] = "Fila #{$rowIndex}: No se pudo crear el egreso - {$createResult['message']}";
                        }
                    } else {
                        $skipped_count++;
                    }
                }catch(\Exception $e){
                    $row_errors[] = "Fila #{$rowIndex}: {$e->getMessage()}";
                    info('Outcome_ImportOutcomes row error: '.$e->getMessage());
                }
            }

            if(!$start_process){
                $this->Result['message'] = 'No se encontró la fila de encabezado con "Movimientos:" en el archivo. Verifique que el formato del archivo sea correcto.';
                return $this->Result;
            }

            if($imported_count == 0 && !empty($row_errors)){
                $this->Result['message'] = "No se importó ningún registro. Errores encontrados: " . implode(' | ', array_slice($row_errors, 0, 5));
                return $this->Result;
            }

            $this->Result['status'] = 1;
            $this->Result['message'] = "Importación completada: {$imported_count} registros importados, {$skipped_count} omitidos.";
            if(!empty($row_errors)){
                $this->Result['message'] .= " Errores en " . count($row_errors) . " filas: " . implode(' | ', array_slice($row_errors, 0, 5));
            }
        }catch(\Exception $e){
            $this->Result['message'] = 'Error general al procesar el archivo: '.$e->getMessage();
            info('Outcome_ImportOutcomes error: '.$e->getMessage());
        }
        return $this->Result;
    }
}