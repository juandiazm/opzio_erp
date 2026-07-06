<?php 
namespace App\traits;

use Illuminate\Support\Facades\Storage;
use Session;
use \Carbon\Carbon;
use Illuminate\Support\Facades\App;

use App\Models\payment_gateway;
use App\Models\payment_gateway_key;
use App\Models\payment_gateway_key_value;

use App\Models\sale;
use App\Models\sale_product;
use App\Models\top_sales_register;
#REGLOG 60
trait payment_gateway_trait
{
    private $DataInPaymentGatewayData = 3;
    public function PaymentGateway_GetPaymentGateways($key_data = false){
        try{
            $payment_gateways =  payment_gateway::orderBy('position')->get();
            $provider_has_payment_gateway = false;
            if($key_data){
                foreach($payment_gateways as $payment_gateway){
                    $keys = payment_gateway_key::where('payment_gateway_id', $payment_gateway->id)->get();
                    //if($provider_id != null){
                        $KeyValues = payment_gateway_key_value::whereIn('key_id', $keys->pluck('id'))->get();
                        if(count($KeyValues)>0 && decrypt($KeyValues[0]['value']) != ''){
                            $provider_has_payment_gateway = true;
                            $payment_gateway['has_data'] = true;
                            foreach($keys as $key){
                                $value = $KeyValues->where('key_id', $key['id'])->first();
                                if($value){
                                    $key['value'] = decrypt($value['value']);
                                }else{
                                    $key['value'] = '';
                                }
                            }
                        }else{
                            $payment_gateway['has_data'] = false;
                            foreach($keys as $key){
                                $key['value'] = '';
                            }
                        }
                    //}
                    $payment_gateway['keys'] = $keys;
                }
            }
            return [
                'status' => 1,
                'payment_gateways' => $payment_gateways,
                'has_payment_gateways' => $provider_has_payment_gateway
            ];
        }catch(\Exception $e){
            info('PaymentGateway_GetPaymentGateways error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
                'payment_gateways' => [],
                'has_payment_gateways' => false
            ];
        }
    }
    public function PaymentGateway_GetPaymentGateway($payment_gateway_id, $key_data = false, $provider_id = null){
        try{
            $payment_gateway =  payment_gateway::find($payment_gateway_id);
            if($key_data){
                $keys = payment_gateway_key::where('payment_gateway_id', $payment_gateway->id)->get();
                if($provider_id != null){
                    $KeyValues = payment_gateway_key_value::where('provider_id', $provider_id)->get();
                    if(count($KeyValues)>0){
                        $payment_gateway['has_data'] = true;
                        foreach($keys as $key){
                            $value = $KeyValues->where('key_id', $key['id'])->first();
                            if($value){
                                $key['value'] = decrypt($value['value']);
                            }else{
                                $key['value'] = '';
                            }
                        }
                    }else{
                        $payment_gateway['has_data'] = false;
                    }
                    
                }
                $payment_gateway['keys'] = $keys;
            }
            return [
                'status' => 1,
                'payment_gateways' => $payment_gateway_id
            ];
        }catch(\Exception $e){
            info('PaymentGateway_GetPaymentGateway error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
                'payment_gateways' => []
            ];
        }
    }
    public function PaymentGateway_CheckProviderPaymentGateways($provider_id = null){
        try{
            $KeyValues = payment_gateway_key_value::where('provider_id', $provider_id)->get();
            if(count($KeyValues)>0 && decrypt($KeyValues[0]['value']) != ''){
                $has_payment_gateway = true;
            }else{
                $has_payment_gateway = false;
            }
            return [
                'status' => 1,
                'has_payment_gateway' => $has_payment_gateway
            ];
        }catch(\Exception $e){
            info('PaymentGateway_GetPaymentGateway error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
                'has_payment_gateway' => false
            ];
        }
    }
    function PaymentGateway_RandomNumber($n) { 
        $characters = '0123456789'; 
        $randomString = ''; 
        for ($i = 0; $i < $n; $i++) { 
          $index = rand(0, strlen($characters) - 1); 
          $randomString .= $characters[$index]; 
        } 
        return $randomString; 
      }
    public function PaymentGateway_GenerateRandomUniqueID(){
        try{
            $flag = false;
            $Random = '';
            while(!$flag){
                $Random = $this->PaymentGateway_RandomNumber(10);
                $Sale = sale::where('payment_reference', $Random)->get()->first();
                if(!$Sale){
                    $flag = true;
                }
            }
            return [
                'status' => 1,
                'unique_id' => $Random
            ];
        }catch(\Exception $e){
            info('PaymentGateway_GenerateRandomUniqueID error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function PaymentGateway_UpdatePaymentGateway($payment_gateway_id, $values){
        try{
            foreach($values as $value){
                $Val = payment_gateway_key_value::where('key_id', $value['id'])->get()->first();
                if($Val){
                    if($value['value'] != '' && $value['value'] != null){
                        $Val->value = encrypt($value['value']);
                        $Val->save();
                    }else{
                        $Val->delete();
                    }
                }else{
                    $Val = new payment_gateway_key_value();
                    $Val->key_id = $value['id'];
                    $Val->value = encrypt($value['value']);
                    $Val->save();
                }
                
            }
            return [
                'status' => 1
            ];
        }catch(\Exception $e){
            info('PaymentGateway_UpdatePaymentGateway error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function PaymentGateway_GeneratePaymentGatewayTransaction($PaymentGateway, $client_identification, $client_name, $client_last_name, $client_phone, $client_email, $client_address, $Service, $Destination){
        $response_data = [];
        try{
            $url = env('APP_URL');
            $Response = $this->PaymentGateway_GenerateRandomUniqueID();
            if($Response['status'] == 1){
                $referenceCode = $Response['unique_id'];
                //$products = $ShoppingCartInformation['products'];
                if($PaymentGateway['id'] == 1) //SIGNIFICA QUE PROCESAREMOS EL PAGO CON PAY U
                {
                    if (App::environment() === 'production') {
                        $response_data["payment_gateway_id"] = $PaymentGateway['id'];
                        $apiKey = $PaymentGateway['keys']->firstWhere('id', '5')['value'];
                        $merchantId = $PaymentGateway['keys']->firstWhere('id', '8')['value'];
                        $accountId = $PaymentGateway['keys']->firstWhere('id', '9')['value'];
                        $currency = 'COP';
                        $signature_pre = $apiKey.'~'.$merchantId.'~'.$referenceCode.'~'.$Service['total'].'~'.$currency;
                        $signature = md5($signature_pre);
                        $response_data["merchantId"] = $merchantId;
                        $response_data["accountId"] = $accountId;
                        $response_data["description"] = 'Servicio en Q-ASK.';
                        $response_data["referenceCode"] = $referenceCode;
                        $response_data["amount"] = $Service['total'];
                        $response_data["tax"] = 0;
                        $response_data["taxReturnBase"] = 0;
                        $response_data["currency"] = $currency;
                        $response_data["signature"] = $signature;
                        $response_data["test"] = 0;
                        $response_data["buyerEmail"] = $client_email;
                        $response_data["responseUrl"] = $url.'/Client/ShoppingCart/Responses/PayU/Response';
                        $response_data["confirmationUrl"] = $url.'/Client/ShoppingCart/Responses/PayU/Confirmation';
                        $response_data["form_action"] = "https://checkout.payulatam.com/ppp-web-gateway-payu/";
                        //$response_data["form_action"] = "https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/";
                    }else{
                        $response_data["payment_gateway_id"] = $PaymentGateway['id'];
                        $apiKey = '4Vj8eK4rloUd272L48hsrarnUA';
                        $merchantId = '508029';
                        $currency = 'COP';
                        $signature_pre = $apiKey.'~'.$merchantId.'~'.$referenceCode.'~'.$Service['total'].'~'.$currency;
                        $signature = md5($signature_pre);
                        $response_data["merchantId"] = $merchantId;
                        $response_data["accountId"] = '512321';
                        $response_data["description"] = 'Servicio en Q-ASK.';
                        $response_data["referenceCode"] = $referenceCode;
                        $response_data["amount"] = $Service['total'];
                        $response_data["tax"] = 0;
                        $response_data["taxReturnBase"] = 0;
                        $response_data["currency"] = $currency;
                        $response_data["signature"] = $signature;
                        $response_data["test"] = 0;
                        $response_data["buyerEmail"] = $client_email;
                        $response_data["responseUrl"] = $url.'/Client/ShoppingCart/Responses/PayU/Response';
                        $response_data["confirmationUrl"] = $url.'/Client/ShoppingCart/Responses/PayU/Confirmation';
                        $response_data["form_action"] = "https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/";
                    }
                }
            }else{
                
            }
            return [
                'status' => 1,
                'response_data' => $response_data,
                'runway_id' => $PaymentGateway['id']
            ];
        }catch(\Exception $e){
            info('PaymentGateway_GeneratePaymentGatewayTransaction error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    /*-----------------------------------*/
    /*-----------------------------------*/
    /*-----------------------------------*/
    /*-----------------------------------*/
    public function Sales_AddSaleRegister($payment_reference, $runway_id, $client_id, $client_identification, $client_name, $client_last_name, $client_phone, $client_email, $client_address, $Service, $Destination){
        try{
            $Sale = new sale();
            $Sale->shopping_cart_id = $Service['id'];
            $Sale->payment_reference = $payment_reference;
            $Sale->client_id = $client_id;
            $Sale->client_identification = $client_identification;
            $Sale->client_identification = $client_identification;
            $Sale->client_name = $client_name;
            $Sale->client_last_name = $client_last_name;
            $Sale->client_phone = $client_phone;
            $Sale->client_email = $client_email;
            $Sale->client_address = $client_address;
            $Sale->referral_code_id = null;
            $Sale->referral_code = null;
            $Sale->referral_code_discount = null;
            $Sale->sub_total = $Service['total'];
            $Sale->tax = $Service['tax'];
            $Sale->discount_total = 0;
            $Sale->total = $Service['total'];
            $Sale->status = 0;
            $Sale->process_status = 0;
            $Sale->destination_id = $Destination['id'];
            $Sale->destination_name = $Destination['name'];
            $Sale->destination_value = $Destination['value'];
            $Sale->bill_discount = 0;
            $Sale->payment_gateway_id = $payment_reference;
            $Sale->runway_id = $runway_id;
            $provider_id = 0;
            $Sale->save();
            return [
                'status' => 1,
                'sale_register' => $Sale
            ];
        }catch(\Exception $e){
            info('Sales_AddSaleRegister error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sales_GetSaleByKey($Key, $Value){
        try{
            $Sale = sale::where($Key, $Value)->get()->first();
            if($Sale){
                return [
                    'status' => 1,
                    'sale' => $Sale
                ];
            }
            return [
                'status' => 0,
                'message' => 'No se encontró la bolsa de compras'
            ];
        }catch(\Exception $e){
            info('ShoppingCart_GetShoppingCartByKey error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sales_UpdateFinsishedStatusSaleSale($sale_id, $payment_id, $status){
        try{
            $Sale = sale::find($sale_id);
            if($Sale){
                $Sale->payment_id = $payment_id;
                $Sale->status = $status;
                $Sale->save();
                return [
                    'status' => 1,
                    'sale' => $Sale
                ];
            }
            return [
                'status' => 0,
                'message' => 'No se encontró la venta'
            ];
        }catch(\Exception $e){
            info('Sales_UpdateFinsishedStatusSaleSale error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sales_GetSalesByFilter($provider_id, $text, $date_flag, $date, $status, $admin_status){
        try{
            if($date_flag){
                $date = Carbon::parse($date);
                $Sales = sale::where('provider_id', $provider_id)->whereDate('created_at','=', $date->format('Y-m-d'));
            }else{
                $Sales = sale::where('provider_id', $provider_id)->where('id', '>=', 1);
            }
            if($status != '-1'){
                $Sales = $Sales->where('status', $status);
            }
            if($admin_status != '-1'){
                $Sales = $Sales->where('process_status', $admin_status);
            }
            if($text != '' && $text != null){
                $query_array = explode(" ", $text);
                $Sales = $Sales->where(function($query) use ($query_array){
                    foreach ($query_array as $query_value) {
                      $query_value = '%'.mb_strtolower($query_value).'%';
                      $query->orWhere('client_name', 'like', $query_value)->orWhere('client_identification', 'like', $query_value)->orWhere('client_last_name', 'like', $query_value)->orWhere('client_phone', 'like', $query_value)->orWhere('client_email', 'like', $query_value)->orWhere('client_address', 'like', $query_value)->orWhere('payment_reference', 'like', $query_value)->orWhere('payment_id', 'like', $query_value); 
                    }
                  });
            }
            $Sales = $Sales->orderBy('created_at', 'desc')->get();
            foreach($Sales as $Sale){
                $Sale['runway_name'] = 'Sin pasarela';
                if($Sale['runway_id'] != 0){
                    $runway = payment_gateway::find($Sale['runway_id']);
                    if($runway){
                        $Sale['runway_name'] = $runway['name'];
                    }
                }
            }
            return [
                'status' => 1,
                'sales' => $Sales
            ];
        }catch(\Exception $e){
            info('Sales_GetSalesByFilter error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sales_GetSalesProducts($provider_id, $sale_id){
        try{
            $Sale = sale::find($sale_id);
            if($Sale && $Sale->provider_id == $provider_id){
                $Products = sale_product::where('sales_id', $sale_id)->get();
                return [
                    'status' => 1,
                    'products' => $Products
                ];
            }else{
                return [
                    'status' => 0,
                    'message' => 'No se encontro el registro de venta'
                ];
            }
        }catch(\Exception $e){
            info('Sales_GetSalesProducts error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sales_ChangeSaleAdminStatus($provider_id, $sale_id, $status){
        try{
            $Sale = sale::find($sale_id);
            if($Sale && $Sale->provider_id == $provider_id){
                $Sale->process_status = $status;
                $Sale->save();
                return [
                    'status' => 1,
                    'message' => 'Se actualizó el estado'
                ];
            }else{
                return [
                    'status' => 0,
                    'message' => 'No se encontro el registro de venta'
                ];
            }
        }catch(\Exception $e){
            info('Sales_ChangeSaleAdminStatus error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}