<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\income_payments_trait;

class payment_gateway_wompi_controller extends Controller
{
    use income_payments_trait;
    public function post_wompi_payment(Request $request){
        $Response = $this->IncomePayment_FinishedWompiPayment(
            [
                'signature' => $request['signature'],
                'timestamp' => $request['timestamp'],
            ],
            $request['data']['transaction']['reference'],
            $request['data']['transaction']
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
