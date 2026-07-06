<?php 
namespace App\traits;

use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;

use App\Models\payment_gateway;
use App\Models\payment_gateway_key;
use App\Models\payment_gateway_key_value;


trait payment_gateway_bold_trait
{
    /**
     * Obtiene los datos necesarios para crear una transacción con Bold
     * @param string $reference - Referencia única de la transacción
     * @param object $income - Objeto income con el total a pagar
     * @return array - Response con datos para el widget de Bold
     */
    public function Bold_GetTransactionNewData($reference, $income){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            // Bold es siempre id=2 en la tabla payment_gateways
            $payment_gateway = payment_gateway::where('name', 'Bold')->first();
            if(!$payment_gateway){
                throw new \Exception('Bold payment gateway not found');
            }
            
            $payment_gateway_keys = payment_gateway_key::where('payment_gateway_id', $payment_gateway->id)->get();
            $payment_gateway_key_values = payment_gateway_key_value::whereIn('key_id', $payment_gateway_keys->pluck('id'))->get();
            
            // Get API key (public)
            $api_key = $payment_gateway_keys->where('name', 'api_key')->first();
            $api_key_value_record = $payment_gateway_key_values->where('key_id', $api_key->id)->first();
            $api_key_value = $api_key_value_record ? decrypt($api_key_value_record->value) : '';
            
            // Get secret key (private)
            $secret_key = $payment_gateway_keys->where('name', 'secret_key')->first();
            $secret_key_value_record = $payment_gateway_key_values->where('key_id', $secret_key->id)->first();
            $secret_key_value = $secret_key_value_record ? decrypt($secret_key_value_record->value) : '';
            
            // Get environment (test/production)
            $environment = $payment_gateway_keys->where('name', 'environment')->first();
            $environment_value_record = $payment_gateway_key_values->where('key_id', $environment->id)->first();
            $environment_value = $environment_value_record ? decrypt($environment_value_record->value) : 'test';
            
            // En modo test, la secret key para firma es cadena vacía
            $signature_secret = ($environment_value === 'test') ? '' : $secret_key_value;
            
            // Bold NO usa centavos, el monto es directo
            $amount = intval($income->total);
            $currency = 'COP';
            
            // Generar firma de integridad: SHA256(orderId + amount + currency + secretKey)
            $integrity_value = $reference . $amount . $currency . $signature_secret;
            $integrity_signature = hash("sha256", $integrity_value);
            
            // URL de redirección después del pago
            $redirection_url = route('payment_response', ['unique_id' => $reference]);
            
            $Response['data'] = array(
                'api_key' => $api_key_value,
                'order_id' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'integrity_signature' => $integrity_signature,
                'redirection_url' => $redirection_url,
                'environment' => $environment_value,
                'description' => 'Pago de licencias RIDDER'
            );
            $Response['status'] = 1;
        }catch(\Exception $e){
            info('Bold_GetTransactionNewData: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }

    /**
     * Obtiene una key específica de Bold
     * @param string $key - Nombre de la key (api_key, secret_key, environment)
     * @return array - Response con el valor de la key
     */
    public function Bold_GetBoldKey($key){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            // Bold es siempre id=2 en la tabla payment_gateways
            $payment_gateway = payment_gateway::where('name', 'Bold')->first();
            if(!$payment_gateway){
                throw new \Exception('Bold payment gateway not found');
            }
            
            $payment_gateway_key = payment_gateway_key::where('payment_gateway_id', $payment_gateway->id)
                ->where('name', $key)
                ->first();
            
            if($payment_gateway_key){
                $payment_gateway_key_value = payment_gateway_key_value::where('key_id', $payment_gateway_key->id)->first();
                if($payment_gateway_key_value){
                    $Response['data'] = array(
                        'key' => $key,
                        'value' => decrypt($payment_gateway_key_value->value)
                    );
                    $Response['status'] = 1;
                }
            }
            
        }catch(\Exception $e){
            info('Bold_GetBoldKey: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }

    /**
     * Valida la firma del webhook de Bold
     * La firma se genera como: HMAC-SHA256(Base64(body), secret_key)
     * En modo test, la secret key es cadena vacía
     * @param string $body - Body crudo del request
     * @param string $signature - Firma recibida en header x-bold-signature
     * @return bool - True si la firma es válida
     */
    public function Bold_ValidateWebhookSignature($body, $signature){
        try{
            // Obtener secret key
            $secretKeyResponse = $this->Bold_GetBoldKey('secret_key');
            $environmentResponse = $this->Bold_GetBoldKey('environment');
            
            $secret_key = '';
            if($secretKeyResponse['status'] == 1){
                $secret_key = $secretKeyResponse['data']['value'];
            }
            
            // En modo test, usar cadena vacía como secret key
            $environment = 'test';
            if($environmentResponse['status'] == 1){
                $environment = $environmentResponse['data']['value'];
            }
            
            if($environment === 'test'){
                $secret_key = '';
            }
            
            // Codificar body en Base64
            $encoded_body = base64_encode($body);
            
            // Generar HMAC-SHA256
            $calculated_signature = hash_hmac('sha256', $encoded_body, $secret_key);
            
            // Comparar firmas de forma segura
            return hash_equals($calculated_signature, $signature);
            
        }catch(\Exception $e){
            info('Bold_ValidateWebhookSignature: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Consulta el estado de una transacción por API cuando el webhook falla
     * @param string $payment_id - ID de la transacción en Bold
     * @return array - Response con el estado de la transacción
     */
    public function Bold_GetTransactionStatus($payment_id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            // Obtener API key
            $apiKeyResponse = $this->Bold_GetBoldKey('api_key');
            if($apiKeyResponse['status'] != 1){
                throw new \Exception('Could not retrieve Bold API key');
            }
            
            $api_key = $apiKeyResponse['data']['value'];
            
            // Consultar estado de la transacción
            $url = 'https://integrations.api.bold.co/payments/webhook/notifications/' . $payment_id;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: x-api-key ' . $api_key,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if($http_code == 200){
                $data = json_decode($response, true);
                $Response['status'] = 1;
                $Response['data'] = $data;
            }else{
                $Response['message'] = 'Failed to get transaction status. HTTP Code: ' . $http_code;
            }
            
        }catch(\Exception $e){
            info('Bold_GetTransactionStatus: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }

    /**
     * Mapea el tipo de evento de Bold a un estado de pago
     * @param string $event_type - Tipo de evento (SALE_APPROVED, SALE_REJECTED, etc.)
     * @return int - Estado del pago (0: pendiente, 1: aprobado, 2: rechazado)
     */
    public function Bold_MapEventToPaymentState($event_type){
        switch ($event_type) {
            case 'SALE_APPROVED':
                return 1;
            case 'SALE_REJECTED':
                return 2;
            case 'VOID_APPROVED':
                return 0;
            case 'VOID_REJECTED':
                return 0;
            default:
                return 0;
        }
    }
}
