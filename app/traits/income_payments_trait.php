<?php 
namespace App\traits;

use App\Models\income_payment;
use App\Models\income_license;
use App\Models\income;
use App\Models\income_advance;
use App\Models\client;
use App\Models\licenses;
use App\Models\license_notification;
use Carbon\Carbon;

use Illuminate\Support\Str;

trait income_payments_trait
{
    use 
    payment_gateway_wompi_trait
    , payment_gateway_bold_trait
    , incomes_trait
    , licenses_trait
    , mail_trait
    , twilio_sms_trait
    , siigo_new_trait
    ;
    //Get Income Payment web data
    //Add Wompi Payment
    public function IncomePayment_AddWompiPayment(
        $income_unique_id,
        $client_user_id
    ){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            $income = income::where('unique_id', $income_unique_id)->first();
            if($income){
                $income_licenses = income_license::where('income_id', $income->id)->get();
                $licenses_notifications = license_notification::whereIn('license_id', $income_licenses->pluck('license_id'))->get();
                $reference = strtoupper(Str::uuid()->toString());
                
                // Calcular el saldo pendiente considerando los abonos
                $total_advances = income_advance::where('income_id', $income->id)->sum('amount');
                $balance_pending = $income->total - $total_advances;
                
                // Usar el saldo pendiente para la transacción
                $income_with_balance = clone $income;
                $income_with_balance->total = $balance_pending;
                
                $wompiResponse = $this->Wompi_GetTransactionNewData($reference, $income_with_balance);
                if($wompiResponse['status'] == 1){
                    $wompiData = $wompiResponse['data'];
                    //add income payment
                    $income_payment = new income_payment();
                    $income_payment->income_id = $income->id;
                    $income_payment->unique_id = $reference;
                    $income_payment->payment_method = 'wompi';
                    $income_payment->currency = $wompiData['currency'];
                    $income_payment->subtotal = $income_licenses->sum('value');
                    $income_payment->discount = 0;
                    $income_payment->tax = $income_licenses->sum(function($license){
                        return $license->value*($license->tax_value);
                    });
                    $income_payment->total = $balance_pending;
                    $income_payment->client_user_id = $client_user_id;
                    $income_payment->save();
                    ////////////////////
                    $Response['status'] = 1;
                    $Response['message'] = 'Wompi payment added';
                    $Response['data'] = [
                        'unique_id' => $income_payment->unique_id,
                        'client_name' => $income->client_name,
                        'notifications' => $licenses_notifications,
                        'wompi' => $wompiResponse['data']
                    ];
                }else{
                    $Response['message'] = $wompiResponse['message'];
                }
            }else{
                $Response['message'] = 'Income not found';
            }
        }catch(\Exception $e){
            info('IncomePayment_AddWompiPayment error: '.$e->getMessage());
            $Response['message'] = 'IncomePayment_AddWompiPayment: '.$e->getMessage();
        }
        return $Response;
    }
    //Finished Wompi Payment
    public function IncomePayment_FinishedWompiPayment(
        $security,
        $unique_id,
        $transaction
    ){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            //validate if is a secure transaction
            //Get wompi events key
            $verified = false;
            $WompiResponse = $this->Wompi_GetWompiKey('events_key');
            if($WompiResponse['status'] == 1){
                $events_key = $WompiResponse['data']['value'];
                $concatenated_signature = '';
                foreach ($security['signature']['properties'] as $value) {
                    $key = str_replace('transaction.', '', $value);
                    $concatenated_signature .= $transaction[$key];
                }
                $concatenated_signature .= $security['timestamp'].$events_key;
                $concatenated_signature = hash("sha256", $concatenated_signature);
                if($concatenated_signature == $security['signature']['checksum']){
                    $verified = true;
                }
            }
            $income_payment = income_payment::where('unique_id', $unique_id)->first();
            if($income_payment){
                //Required data for process
                $income = income::find($income_payment->income_id);
                $client = client::find($income->client_id);
                $income_licenses = income_license::where('income_id', $income->id)->get();
                $licenses_notifications = license_notification::whereIn('license_id', $income_licenses->pluck('license_id'))->get();
                //Update payment data
                $income_payment->transaction_id = $transaction['id'];
                if($verified == true){
                    switch ($transaction['status']) {
                        case 'APPROVED':
                            $income_payment->payment_state = 1;
                            break;
                        case 'DECLINED':
                            $income_payment->payment_state = 2;
                            break;
                        case 'VOIDED':
                            $income_payment->payment_state = 0;
                            break;
                        case 'ERROR':
                            $income_payment->payment_state = 2;
                            break;
                        default:
                            $income_payment->payment_state = 0;
                            break;
                    }
                    $income_payment->payment_message = $transaction['status_message'];
                }else{
                    $income_payment->payment_state = 2;
                    $income_payment->payment_message = 'Security error';
                }
                $income_payment->payment_reference =  $transaction['id'];
                $income_payment->payment_status = $transaction['status'];
                $income_payment->payment_response = json_encode($transaction);
                $income_payment->payment_date = Carbon::now();
                $income_payment->save();

                // Determinar etiqueta de estado del pago
                $payment_state_labels = [0 => 'Pendiente', 1 => 'Aprobado', 2 => 'Rechazado'];
                $payment_state_label = $payment_state_labels[$income_payment->payment_state] ?? 'Desconocido';
                
                if($income_payment->payment_state == 1){
                    //Update income
                    $IncomeResponse = $this->Income_UpdateIncomePaymentData(
                        $income_payment->income_id,
                        $income_payment->payment_state,
                        $income_payment->payment_date,
                        $income_payment->unique_id,
                        $income_payment->unique_id,
                        $income_payment->total,
                    );
                    //Update license if is reccurrent
                    $LicensesResponse = $this->License_UpdateBillingDataByIds($income_licenses);
                    //Send Mail to Ridder
                    $Mails = [];
                    $Mails[] = [
                        'address' => 'comunicaciones@ridder.com.co',
                        'name' => 'Ridder S.A.S'
                    ];
                    $MailData = 
                    [
                        'subject' => 'Nuevo pago '.$payment_state_label.' #'.substr($income_payment->unique_id, -10)
                    ];
                    $View = 'mail.income_payment_finished';
                    $ViewData = collect(
                    [
                        "income_payment" => $income_payment,
                        "income" => $income,
                        "client" => $client,
                    ]
                    );
                    $RidderEmailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                    //Send SMS to client
                    $message = 'Hola '.$client->name.', tu orden de compra #'.substr($income_payment->unique_id, -10).' ha sido procesada exitosamente.';
                    foreach ($licenses_notifications as $notification) {
                        if($notification->phone != null && $notification->phone != ''){
                            $PhoneResponse = $this->TwilioSMS_SendMessage(
                                '+57',
                                $notification->phone,
                                $message
                            );
                        }
                    }
                }
                //Send email to licenses_notifications
                //get distinct licenses_notifications by email
                $licenses_notifications = $licenses_notifications->unique('email');
                $Mails = [];
                foreach ($licenses_notifications as $notification) {
                    $Mails[] = [
                        'address' => $notification->email,
                        'name' => $client->name
                    ];
                }
                $MailData = 
                [
                    'subject' => 'Pago '.$payment_state_label.' #'.substr($income_payment->unique_id, -10)
                ];
                $View = 'mail.client_income_payment_finished';
                $ViewData = collect(
                [
                    "income_payment" => $income_payment,
                    "income" => $income,
                    "client" => $client,
                ]
                );
                $RidderEmailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                /////////////////////
                $Response['status'] = 1;
                $Response['message'] = 'Wompi payment finished';
                $Response['data'] = $income_payment;
            }else{
                $Response['message'] = 'Income payment not found';
            }
        }catch(\Exception $e){
            info('IncomePayment_FinishedWompiPayment error: '.$e->getMessage());
            $Response['message'] = 'IncomePayment_FinishedWompiPayment: '.$e->getMessage();
        }
        return $Response;
    }
    //Get income payment data
    public function IncomePayment_GetIncomePayment(
        $unique_id
    ){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            $income_payment = income_payment::with(['income'])->where('unique_id', $unique_id)->first();
            if($income_payment){
                $Response['status'] = 1;
                $Response['message'] = 'Income payment found';
                $Response['data'] = $income_payment;
            }else{
                $Response['message'] = 'Income payment not found';
            }
        }catch(\Exception $e){
            info('IncomePayment_GetIncomePayment error: '.$e->getMessage());
            $Response['message'] = 'IncomePayment_GetIncomePayment: '.$e->getMessage();
        }
        return $Response;
    }

    //Add Bold Payment
    public function IncomePayment_AddBoldPayment(
        $income_unique_id,
        $client_user_id
    ){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            $income = income::where('unique_id', $income_unique_id)->first();
            if($income){
                $income_licenses = income_license::where('income_id', $income->id)->get();
                $licenses_notifications = license_notification::whereIn('license_id', $income_licenses->pluck('license_id'))->get();
                $reference = strtoupper(Str::uuid()->toString());
                
                // Calcular el saldo pendiente considerando los abonos
                $total_advances = income_advance::where('income_id', $income->id)->sum('amount');
                $balance_pending = $income->total - $total_advances;
                
                // Usar el saldo pendiente para la transacción
                $income_with_balance = clone $income;
                $income_with_balance->total = $balance_pending;
                
                $boldResponse = $this->Bold_GetTransactionNewData($reference, $income_with_balance);
                if($boldResponse['status'] == 1){
                    $boldData = $boldResponse['data'];
                    //add income payment
                    $income_payment = new income_payment();
                    $income_payment->income_id = $income->id;
                    $income_payment->unique_id = $reference;
                    $income_payment->payment_method = 'bold';
                    $income_payment->currency = $boldData['currency'];
                    $income_payment->subtotal = $income_licenses->sum('value');
                    $income_payment->discount = 0;
                    $income_payment->tax = $income_licenses->sum(function($license){
                        return $license->value*($license->tax_value);
                    });
                    $income_payment->total = $balance_pending;
                    $income_payment->client_user_id = $client_user_id;
                    $income_payment->save();
                    ////////////////////
                    $Response['status'] = 1;
                    $Response['message'] = 'Bold payment added';
                    $Response['data'] = [
                        'unique_id' => $income_payment->unique_id,
                        'client_name' => $income->client_name,
                        'notifications' => $licenses_notifications,
                        'bold' => $boldResponse['data']
                    ];
                }else{
                    $Response['message'] = $boldResponse['message'];
                }
            }else{
                $Response['message'] = 'Income not found';
            }
        }catch(\Exception $e){
            info('IncomePayment_AddBoldPayment error: '.$e->getMessage());
            $Response['message'] = 'IncomePayment_AddBoldPayment: '.$e->getMessage();
        }
        return $Response;
    }

    //Finished Bold Payment (Webhook)
    public function IncomePayment_FinishedBoldPayment(
        $raw_body,
        $signature,
        $webhook_data
    ){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            // Validar firma del webhook
            $verified = $this->Bold_ValidateWebhookSignature($raw_body, $signature);
            
            if(!$verified){
                info('Bold webhook signature validation failed');
                $Response['message'] = 'Invalid webhook signature';
                return $Response;
            }
            
            // Obtener referencia del pago desde metadata
            $unique_id = $webhook_data['data']['metadata']['reference'] ?? null;
            
            // Si no hay referencia en metadata, intentar con el order_id
            if(!$unique_id && isset($webhook_data['subject'])){
                // El subject puede contener el payment_id, intentamos buscar por transaction_id
                $unique_id = null;
            }
            
            if(!$unique_id){
                // Buscar por payment_id en la transacción guardada
                $payment_id = $webhook_data['data']['payment_id'] ?? null;
                if($payment_id){
                    $income_payment = income_payment::where('transaction_id', $payment_id)->first();
                    if($income_payment){
                        $unique_id = $income_payment->unique_id;
                    }
                }
            }
            
            $income_payment = income_payment::where('unique_id', $unique_id)->first();
            
            if($income_payment){
                // Required data for process
                $income = income::find($income_payment->income_id);
                $client = client::find($income->client_id);
                $income_licenses = income_license::where('income_id', $income->id)->get();
                $licenses_notifications = license_notification::whereIn('license_id', $income_licenses->pluck('license_id'))->get();
                
                // Mapear evento a estado de pago
                $event_type = $webhook_data['type'] ?? 'UNKNOWN';
                $payment_state = $this->Bold_MapEventToPaymentState($event_type);
                
                // Update payment data
                $income_payment->transaction_id = $webhook_data['data']['payment_id'] ?? null;
                $income_payment->payment_state = $payment_state;
                $income_payment->payment_message = $event_type;
                $income_payment->payment_reference = $webhook_data['data']['payment_id'] ?? null;
                $income_payment->payment_status = $event_type;
                $income_payment->payment_response = json_encode($webhook_data);
                $income_payment->payment_date = Carbon::now();
                $income_payment->save();

                // Determinar etiqueta de estado del pago
                $payment_state_labels = [0 => 'Pendiente', 1 => 'Aprobado', 2 => 'Rechazado'];
                $payment_state_label = $payment_state_labels[$income_payment->payment_state] ?? 'Desconocido';
                
                if($income_payment->payment_state == 1){
                    // Update income
                    $IncomeResponse = $this->Income_UpdateIncomePaymentData(
                        $income_payment->income_id,
                        $income_payment->payment_state,
                        $income_payment->payment_date,
                        $income_payment->unique_id,
                        $income_payment->unique_id,
                        $income_payment->total,
                    );
                    // Update license if is recurrent
                    $LicensesResponse = $this->License_UpdateBillingDataByIds($income_licenses);
                    // Send Mail to Ridder
                    $Mails = [];
                    $Mails[] = [
                        'address' => 'comunicaciones@ridder.com.co',
                        'name' => 'Ridder S.A.S'
                    ];
                    $MailData = [
                        'subject' => 'Nuevo pago '.$payment_state_label.' (Bold) #'.substr($income_payment->unique_id, -10)
                    ];
                    $View = 'mail.income_payment_finished';
                    $ViewData = collect([
                        "income_payment" => $income_payment,
                        "income" => $income,
                        "client" => $client,
                    ]);
                    $RidderEmailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                    // Send SMS to client
                    $message = 'Hola '.$client->name.', tu orden de compra #'.substr($income_payment->unique_id, -10).' ha sido procesada exitosamente.';
                    foreach ($licenses_notifications as $notification) {
                        if($notification->phone != null && $notification->phone != ''){
                            $PhoneResponse = $this->TwilioSMS_SendMessage(
                                '+57',
                                $notification->phone,
                                $message
                            );
                        }
                    }
                }
                // Send email to licenses_notifications
                $licenses_notifications = $licenses_notifications->unique('email');
                $Mails = [];
                foreach ($licenses_notifications as $notification) {
                    $Mails[] = [
                        'address' => $notification->email,
                        'name' => $client->name
                    ];
                }
                $MailData = [
                    'subject' => 'Pago '.$payment_state_label.' #'.substr($income_payment->unique_id, -10)
                ];
                $View = 'mail.client_income_payment_finished';
                $ViewData = collect([
                    "income_payment" => $income_payment,
                    "income" => $income,
                    "client" => $client,
                ]);
                $RidderEmailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                
                $Response['status'] = 1;
                $Response['message'] = 'Bold payment finished';
                $Response['data'] = $income_payment;
            }else{
                $Response['message'] = 'Income payment not found';
            }
        }catch(\Exception $e){
            info('IncomePayment_FinishedBoldPayment error: '.$e->getMessage());
            $Response['message'] = 'IncomePayment_FinishedBoldPayment: '.$e->getMessage();
        }
        return $Response;
    }

    // Fallback: Consultar estado de transacción Bold por API
    public function IncomePayment_CheckBoldTransactionStatus($unique_id){
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try{
            $income_payment = income_payment::where('unique_id', $unique_id)->first();
            if($income_payment && $income_payment->transaction_id){
                $boldResponse = $this->Bold_GetTransactionStatus($income_payment->transaction_id);
                if($boldResponse['status'] == 1){
                    $Response['status'] = 1;
                    $Response['data'] = $boldResponse['data'];
                }else{
                    $Response['message'] = $boldResponse['message'];
                }
            }else{
                $Response['message'] = 'Income payment not found or no transaction ID';
            }
        }catch(\Exception $e){
            info('IncomePayment_CheckBoldTransactionStatus error: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }

}