<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\income_payments_trait;

class payment_gateway_bold_controller extends Controller
{
    use income_payments_trait;

    /**
     * Recibe el webhook de Bold con notificaciones de pago
     * Los eventos pueden ser: SALE_APPROVED, SALE_REJECTED, VOID_APPROVED, VOID_REJECTED
     */
    public function post_bold_payment(Request $request){
        // Obtener el body crudo para validar la firma
        $raw_body = file_get_contents('php://input');
        
        // Obtener la firma del header
        $signature = $request->header('x-bold-signature', '');
        
        // Obtener los datos del webhook
        $webhook_data = $request->all();
        
        // Procesar el pago
        $Response = $this->IncomePayment_FinishedBoldPayment(
            $raw_body,
            $signature,
            $webhook_data
        );
        
        if($Response['status'] == 1){
            return $Response;
        }
        
        return \Response::json($Response, 400);
    }

    /**
     * Endpoint de fallback para consultar el estado de una transacción
     * cuando el webhook no llegó correctamente
     */
    public function check_transaction_status(Request $request){
        $Response = $this->IncomePayment_CheckBoldTransactionStatus(
            $request->unique_id
        );
        
        if($Response['status'] == 1){
            return $Response;
        }
        
        return \Response::json($Response, 400);
    }
}
