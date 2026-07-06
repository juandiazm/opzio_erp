<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\income_advances_trait;
use Illuminate\Support\Facades\Auth;

class income_advances_controller extends Controller
{
    use income_advances_trait;
    
    // Crear un nuevo abono
    public function create(Request $request)
    {
        $request->validate([
            'income_id' => 'required|integer|exists:incomes,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);
        
        $response = $this->IncomeAdvance_Create(
            $request->income_id,
            $request->amount,
            $request->payment_date,
            $request->payment_method,
            $request->reference,
            $request->notes,
            Auth::id()
        );
        
        return response()->json($response);
    }
    
    // Obtener abonos de un ingreso
    public function getByIncome($income_id)
    {
        $response = $this->IncomeAdvance_GetByIncome($income_id);
        return response()->json($response);
    }
    
    // Actualizar un abono
    public function update(Request $request, $advance_id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);
        
        $response = $this->IncomeAdvance_Update(
            $advance_id,
            $request->amount,
            $request->payment_date,
            $request->payment_method,
            $request->reference,
            $request->notes
        );
        
        return response()->json($response);
    }
    
    // Eliminar un abono
    public function delete($advance_id)
    {
        $response = $this->IncomeAdvance_Delete($advance_id);
        return response()->json($response);
    }
}
