<?php
namespace App\traits;

use App\Models\sms_log;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Twilio\Rest\Client; // make sure to import the Twilio client

trait twilio_sms_trait
{
    private function TwilioSMS_NormalizePhone($phone)
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return '';
        }

        if (substr($phone, 0, 1) !== '+') {
            $phone = '+'.$phone;
        }

        $prefix = '+57';
        if (substr($phone, 0, strlen($prefix)) !== $prefix) {
            $phone = $prefix.substr($phone, 1);
        }

        return $phone;
    }

    private function TwilioSMS_UpdateLog($smsLog, $status, $message = null, $incrementAttempt = false)
    {
        if (!$smsLog) {
            return;
        }

        if ($incrementAttempt) {
            $smsLog->attempts = (int) $smsLog->attempts + 1;
        }
        $smsLog->status = $status;
        $smsLog->error_message = $status === 1 ? null : ($message ?: null);
        if ($status === 1) {
            $smsLog->sent_at = Carbon::now();
        }
        $smsLog->save();
    }

    public function TwilioSMS_SendMessage($prefix, $phone, $messages, $smsLogId = null, array $context = [])
    {
        //Set test number until the Twilio account is verified
        //$phone = '+573108226480';
        $Response = [
            'status' => 0,
            'message' => ''
        ];

        $phone = $this->TwilioSMS_NormalizePhone($phone);
        $messages = trim((string) $messages);

        try {
            if($phone === ''){
                return [
                    'status' => 0,
                    'message' => 'The phone number is required.'
                ];
            }
            if($messages === ''){
                return [
                    'status' => 0,
                    'message' => 'The message is required.'
                ];
            }

            $smsLog = $smsLogId === null ? null : sms_log::find($smsLogId);
            $createdLog = false;
            if (!$smsLog) {
                $smsLog = sms_log::create([
                    'unique_id' => strtoupper(Str::uuid()->toString()),
                    'client_id' => $context['client_id'] ?? null,
                    'recipient_name' => $context['recipient_name'] ?? null,
                    'to' => $phone,
                    'body' => $messages,
                    'attempts' => 0,
                    'status' => 0,
                    'send_at' => $context['send_at'] ?? null,
                    'notification_batch' => $context['notification_batch'] ?? null,
                    'created_by' => $context['created_by'] ?? null,
                ]);
                $createdLog = true;
            }

            if(App::environment() === 'local'){
                $this->TwilioSMS_UpdateLog($smsLog, 1, null, $createdLog);
                return [
                    'status' => 1,
                    'message' => 'Message sent successfully.'
                ];
            }

            $receiverNumber = $phone;
            $message = 'Opzio S.A.S: '.$messages;
    
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');
            $fromNumber = env('TWILIO_FROM');

            $client = new Client($sid, $token);
            $client->messages->create($receiverNumber, [
                'from' => $fromNumber,
                'body' => $message
            ]);
            $this->TwilioSMS_UpdateLog($smsLog, 1, null, $createdLog);
            $Response['status'] = 1;
            $Response['message'] = 'Message sent successfully.';
        } catch (\Throwable $e) {
            if (isset($smsLog)) {
                $this->TwilioSMS_UpdateLog($smsLog, 2, $e->getMessage(), $createdLog);
            }
            info('TwilioSMS_SendMessage error: ' . $e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
}