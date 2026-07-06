<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\payment_gateway_trait;

class payment_gateway_controller extends Controller
{
    use payment_gateway_trait;
    public function get_all_payment_gateways(Request $request){
        $Response = $this->PaymentGateway_GetPaymentGateways($request->key_data);
        if($Response['status']==1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_payment_gatewate_data(Request $request){
        $Response = $this->PaymentGateway_UpdatePaymentGateway($request->payment_gateway_id, $request->values);
        if($Response['status']==1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
