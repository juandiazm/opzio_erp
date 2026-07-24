<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\income_payments_trait;

class payment_gateway_wompi_controller extends Controller
{
    use income_payments_trait;
    public function post_wompi_payment(Request $request){
        return \Response::json(['status' => 0, 'message' => 'Wompi payments are disabled'], 403);
    }
}
