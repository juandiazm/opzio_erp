<?php
namespace App\traits;

use Illuminate\Support\Facades\App;
use Twilio\Rest\Client; // make sure to import the Twilio client

trait twilio_sms_trait
{
    public function TwilioSMS_SendMessage($prefix, $phone, $messages)
    {
        //Set test number until the Twilio account is verified
        //$phone = '+573108226480';
        if(App::environment() === 'local'){
            return [
                'status' => 1,
                'message' => 'Message sent successfully.'
            ];
        }
        $prefix = '+57';
        $Response = [
            'status' => 0,
            'message' => ''
        ];
        try {
            if($phone == null || $phone == ''){
                return [
                    'status' => 0,
                    'message' => 'The phone number is required.'
                ];
            }
            if($messages == null || $messages == ''){
                return [
                    'status' => 0,
                    'message' => 'The message is required.'
                ];
            }
            //Check if phone number starts with + 
            if(substr($phone, 0, 1) != '+'){
                $phone = '+'.$phone;
            } 
            //Check if phone number start with +57
            if(substr($phone, 0, 3) != $prefix){
                $phone = $prefix.substr($phone, 1);
            }
            $receiverNumber = $phone;
            $message = 'RIDDER S.A.S: '.$messages;
    
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');
            $fromNumber = env('TWILIO_FROM');

            $client = new Client($sid, $token);
            $client->messages->create($receiverNumber, [
                'from' => $fromNumber,
                'body' => $message
            ]);
            $Response['status'] = 1;
            $Response['message'] = 'Message sent successfully.';
        } catch (\Twilio\Exceptions\RestException $e) {
            info('TwilioSMS_SendMessage error: ' . $e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
}