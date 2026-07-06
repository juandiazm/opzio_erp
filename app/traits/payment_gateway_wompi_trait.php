<?php 
namespace App\traits;

use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;


use App\Models\payment_gateway;
use App\Models\payment_gateway_key;
use App\Models\payment_gateway_key_value;


trait payment_gateway_wompi_trait
{
    public function Wompi_GetTransactionNewData($reference, $income){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            //Wompi is always id=1
            $payment_gateway_keys = payment_gateway_key::where('payment_gateway_id', 1)->get();
            $payment_gateway_key_values = payment_gateway_key_value::whereIn('key_id', $payment_gateway_keys->pluck('id'))->get();
            //Get public key
            $public_key = $payment_gateway_keys->where('name', 'public_key')->first();
            $public_key->value = decrypt($payment_gateway_key_values->where('key_id', $public_key->id)->first()->value);
            //Get integrity key
            $integrity_key = $payment_gateway_keys->where('name', 'integrity_key')->first();
            $integrity_key->value = decrypt($payment_gateway_key_values->where('key_id', $integrity_key->id)->first()->value);
            //Get events key
            $event_key = $payment_gateway_keys->where('name', 'events_key')->first();
            $event_key->value = decrypt($payment_gateway_key_values->where('key_id', $event_key->id)->first()->value);
            //Generate integrity signature
            $amount = $income->total*100;
            $currency = 'COP';
            $expiration_date = Carbon::now()->addMinutes(30)->format('Y-m-d\TH:i:s.000\Z');
            $integrity_value = $reference.$amount.$currency.$integrity_key->value;
            $integrity_signature = hash("sha256", $integrity_value);
            $redirection_url = route('payment_response', ['unique_id' => $reference]);
            $Response['data'] = array(
                'public_key' => $public_key->value,
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'expiration_date' => $expiration_date,
                'integrity_signature' => $integrity_signature,
                'event_key' => $event_key->value,
                'redirection_url' => $redirection_url
            );
            $Response['status'] = 1;
        }catch(\Exception $e){
            info('Wompi_GetTransactionNewData: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Wompi_GetWompiKey($key){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            //Wompi is always id=1
            $payment_gateway_key = payment_gateway_key::where('payment_gateway_id', 1)->where('name', $key)->first();
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
            info('Wompi_GetWompiKey: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
}