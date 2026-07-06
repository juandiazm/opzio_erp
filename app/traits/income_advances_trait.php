<?php 
namespace App\traits;

use App\Models\income_advance;
use App\Models\income;
use Carbon\Carbon;

trait income_advances_trait
{
    // Crear un abono
    public function IncomeAdvance_Create(
        $income_id,
        $amount,
        $payment_date,
        $payment_method = null,
        $reference = null,
        $notes = null,
        $created_by = null
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error al crear el abono',
            'data' => null
        );
        
        try {
            // Validar que el income existe
            $income = income::find($income_id);
            if (!$income) {
                $Response['message'] = 'El ingreso no existe';
                return $Response;
            }
            
            // Validar que el monto sea válido
            if ($amount <= 0) {
                $Response['message'] = 'El monto debe ser mayor a 0';
                return $Response;
            }
            
            // Calcular balance pendiente
            $total_advances = income_advance::where('income_id', $income_id)->sum('amount');
            $balance_pending = $income->total - $total_advances;
            
            // Validar que el abono no exceda el balance pendiente
            if ($amount > $balance_pending) {
                $Response['message'] = 'El abono excede el balance pendiente';
                return $Response;
            }
            
            // Crear el abono
            $advance = income_advance::create([
                'income_id' => $income_id,
                'amount' => $amount,
                'payment_date' => $payment_date,
                'payment_method' => $payment_method,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $created_by
            ]);
            
            // Verificar si se completó el pago
            $new_total_advances = $total_advances + $amount;
            if ($new_total_advances >= $income->total) {
                $income->payment_state = 1;
                $income->payment_date = $payment_date;
                $income->save();
            }
            
            $Response['status'] = 1;
            $Response['message'] = 'Abono creado exitosamente';
            $Response['data'] = [
                'advance' => $advance,
                'total_advances' => $new_total_advances,
                'balance_pending' => $income->total - $new_total_advances
            ];
            
        } catch (\Exception $e) {
            $Response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $Response;
    }
    
    // Obtener abonos de un ingreso
    public function IncomeAdvance_GetByIncome($income_id)
    {
        $Response = array(
            'status' => 0,
            'message' => 'Error al obtener los abonos',
            'data' => null
        );
        
        try {
            $income = income::find($income_id);
            if (!$income) {
                $Response['message'] = 'El ingreso no existe';
                return $Response;
            }
            
            $advances = income_advance::where('income_id', $income_id)
                ->with('user:id,name')
                ->orderBy('payment_date', 'desc')
                ->get();
            
            $total_advances = $advances->sum('amount');
            $balance_pending = $income->total - $total_advances;
            
            $Response['status'] = 1;
            $Response['message'] = 'Abonos obtenidos exitosamente';
            $Response['data'] = [
                'advances' => $advances,
                'total_advances' => $total_advances,
                'balance_pending' => $balance_pending,
                'income_total' => $income->total
            ];
            
        } catch (\Exception $e) {
            $Response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $Response;
    }
    
    // Eliminar un abono
    public function IncomeAdvance_Delete($advance_id)
    {
        $Response = array(
            'status' => 0,
            'message' => 'Error al eliminar el abono',
            'data' => null
        );
        
        try {
            $advance = income_advance::find($advance_id);
            if (!$advance) {
                $Response['message'] = 'El abono no existe';
                return $Response;
            }
            
            $income = income::find($advance->income_id);
            $advance->delete();
            
            // Recalcular el estado de pago del income
            $total_advances = income_advance::where('income_id', $income->id)->sum('amount');
            if ($total_advances < $income->total) {
                $income->payment_state = 0;
                $income->payment_date = null;
                $income->save();
            }
            
            $Response['status'] = 1;
            $Response['message'] = 'Abono eliminado exitosamente';
            $Response['data'] = [
                'total_advances' => $total_advances,
                'balance_pending' => $income->total - $total_advances
            ];
            
        } catch (\Exception $e) {
            $Response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $Response;
    }
    
    // Actualizar un abono
    public function IncomeAdvance_Update(
        $advance_id,
        $amount = null,
        $payment_date = null,
        $payment_method = null,
        $reference = null,
        $notes = null
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error al actualizar el abono',
            'data' => null
        );
        
        try {
            $advance = income_advance::find($advance_id);
            if (!$advance) {
                $Response['message'] = 'El abono no existe';
                return $Response;
            }
            
            $income = income::find($advance->income_id);
            
            // Si se actualiza el monto, validar
            if ($amount !== null) {
                if ($amount <= 0) {
                    $Response['message'] = 'El monto debe ser mayor a 0';
                    return $Response;
                }
                
                // Calcular balance sin este abono
                $total_other_advances = income_advance::where('income_id', $income->id)
                    ->where('id', '!=', $advance_id)
                    ->sum('amount');
                
                $balance_available = $income->total - $total_other_advances;
                
                if ($amount > $balance_available) {
                    $Response['message'] = 'El monto excede el balance disponible';
                    return $Response;
                }
                
                $advance->amount = $amount;
            }
            
            if ($payment_date !== null) {
                $advance->payment_date = $payment_date;
            }
            
            if ($payment_method !== null) {
                $advance->payment_method = $payment_method;
            }
            
            if ($reference !== null) {
                $advance->reference = $reference;
            }
            
            if ($notes !== null) {
                $advance->notes = $notes;
            }
            
            $advance->save();
            
            // Recalcular el estado de pago del income
            $total_advances = income_advance::where('income_id', $income->id)->sum('amount');
            if ($total_advances >= $income->total) {
                $income->payment_state = 1;
                if (!$income->payment_date) {
                    $income->payment_date = $advance->payment_date;
                }
                $income->save();
            } else {
                $income->payment_state = 0;
                $income->payment_date = null;
                $income->save();
            }
            
            $Response['status'] = 1;
            $Response['message'] = 'Abono actualizado exitosamente';
            $Response['data'] = [
                'advance' => $advance,
                'total_advances' => $total_advances,
                'balance_pending' => $income->total - $total_advances
            ];
            
        } catch (\Exception $e) {
            $Response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $Response;
    }
}
