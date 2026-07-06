<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\outcomes_trait;

class outcomes_controller extends Controller
{
    use outcomes_trait;
    //
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
        if(!$request->hasFile('import-file')){
            return response()->json([
                'status' => 0,
                'message' => 'No se recibió ningún archivo. Asegúrese de seleccionar un archivo antes de importar.'
            ], 400);
        }
        if(!$request->file('import-file')->isValid()){
            return response()->json([
                'status' => 0,
                'message' => 'El archivo subido es inválido o está corrupto. Error: '.$request->file('import-file')->getErrorMessage()
            ], 400);
        }
        $Response = $this->Outcome_ImportOutcomes(
            $request->file('import-file')
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
