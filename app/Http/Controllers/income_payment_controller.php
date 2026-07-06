<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\income_payments_trait;

class income_payment_controller extends Controller
{
    use income_payments_trait;
    public function add_wompi_payment_unlogged(Request $request){
        $Response = $this->IncomePayment_AddWompiPayment(
            $request->unique_id,
            null
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_bold_payment_unlogged(Request $request){
        $Response = $this->IncomePayment_AddBoldPayment(
            $request->unique_id,
            null
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function finished_wompi_payment(Request $request){
        $Response = $this->IncomePayment_FinishedWompiPayment(
            $request->unique_id,
            $request->transaction
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get income payment response for unlogged user
    public function get_income_payment_data(Request $request){
        $Response = $this->IncomePayment_GetIncomePayment(
            $request->unique_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
