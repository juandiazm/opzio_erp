<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\outcomes_trait;

class outcomes_controller extends Controller
{
    use outcomes_trait;
    //
    public function create_outcome(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'outcome_type_id' => 'required|integer|exists:outcome_types,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'provider_id' => 'nullable|integer|exists:providers,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'client_id' => 'nullable|integer|exists:clients,id',
        ]);

        $userId = $request->input('user_id') ?: data_get(Session::get('user'), 'id');
        if(!$userId){
            return response()->json(['status' => 0, 'message' => 'No se encontró un usuario asociado'], 422);
        }

        $Response = $this->Outcome_CreateOutcome(
            $request->input('date'),
            $request->input('name'),
            $request->input('description') ?? '',
            $request->input('amount'),
            $request->input('outcome_type_id'),
            $userId,
            $request->input('provider_id'),
            $request->input('employee_id'),
            $request->input('department_id'),
            $request->input('client_id')
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return response()->json($Response, 400);
    }

    public function update_outcome(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:outcomes,id',
            'date' => 'required|date',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'outcome_type_id' => 'required|integer|exists:outcome_types,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'provider_id' => 'nullable|integer|exists:providers,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'client_id' => 'nullable|integer|exists:clients,id',
        ]);

        $outcome = \App\Models\outcome::find($request->input('id'));
        $userId = $request->input('user_id') ?: ($outcome ? $outcome->user_id : data_get(Session::get('user'), 'id'));
        if(!$userId){
            return response()->json(['status' => 0, 'message' => 'No se encontró un usuario asociado'], 422);
        }

        $Response = $this->Outcome_UpdateOutcome(
            $request->input('id'),
            $request->input('date'),
            $request->input('name'),
            $request->input('description') ?? '',
            $request->input('amount'),
            $request->input('outcome_type_id'),
            $userId,
            $request->input('provider_id'),
            $request->input('employee_id'),
            $request->input('department_id'),
            $request->input('client_id')
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return response()->json($Response, 400);
    }

    public function update_outcome_association(Request $request)
    {
        $associationRules = [
            'provider_id' => 'nullable|integer|exists:providers,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'client_id' => 'nullable|integer|exists:clients,id',
        ];
        $association = $request->input('association');

        if (!is_string($association) || !array_key_exists($association, $associationRules)) {
            return response()->json(['status' => 0, 'message' => 'Asociación no válida'], 422);
        }

        $request->validate([
            'id' => 'required|integer|exists:outcomes,id',
            'association_id' => $associationRules[$association],
        ]);

        $Response = $this->Outcome_UpdateOutcomeAssociation(
            (int) $request->input('id'),
            $association,
            $request->input('association_id')
        );
        if ($Response['status'] == 1) {
            return response()->json($Response);
        }
        return response()->json($Response, 400);
    }

    public function get_outcome_form_data()
    {
        $Response = $this->Outcome_GetOutcomeFormData();
        if($Response['status'] == 1){
            return $Response;
        }
        return response()->json($Response, 400);
    }

    public function get_outcomes(Request $request)
    {
        $Response = $this->Outcome_GetOutcomes($request);
        if ($Response['status'] === 1) {
            return response()->json($Response);
        }
        return response()->json($Response, 400);
    }
    
    public function delete_outcome(Request $request)
    {
        // validamos que venga un id
        $request->validate([
            'id' => 'required|integer|exists:outcomes,id',
        ]);

        $Response = $this->Outcome_DeleteOutcome($request->input('id'));

        if ($Response['status'] === 1) {
            return response()->json($Response);
        }
        return response()->json($Response, 400);
    }

    public function recover_outcome(Request $request)
    {
        // validamos que venga un id
        $request->validate([
            'id' => 'required|integer|exists:outcomes,id',
        ]);

        $Response = $this->Outcome_RecoverOutcome($request->input('id'));

        if ($Response['status'] === 1) {
            return response()->json($Response);
        }
        return response()->json($Response, 400);
    }
    
    public function import_outcomes(Request $request){
        $request->validate([
            'source' => 'required|in:bold',
            'import-file' => 'required|file|max:20480',
        ]);

        if(!$request->file('import-file')->isValid()){
            return response()->json([
                'status' => 0,
                'message' => 'El archivo subido es inválido o está corrupto.'
            ], 422);
        }

        $extension = strtolower($request->file('import-file')->getClientOriginalExtension());
        if($extension !== 'csv'){
            return response()->json([
                'status' => 0,
                'message' => 'La fuente Bold requiere un archivo CSV.'
            ], 422);
        }

        $userId = data_get(Session::get('user'), 'id');
        if(!$userId){
            return response()->json([
                'status' => 0,
                'message' => 'No se encontró un usuario asociado a la importación.'
            ], 422);
        }

        $Response = $this->Outcome_ImportOutcomes(
            $request->file('import-file'),
            $request->input('source'),
            (int) $userId
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return response()->json($Response, 400);
    }
    public function get_outcomes_by_date_range_report(Request $request){
        $Response = $this->Outcome_GetOutcomesByDateRangeReport(
            $request->fromDate
            ,$request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
