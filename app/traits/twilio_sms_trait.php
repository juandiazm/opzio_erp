<?php
namespace App\traits;

use App\Models\sms_log;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Twilio\Rest\Client; // make sure to import the Twilio client

trait twilio_sms_trait
{
    protected function TwilioSMS_CreateClient()
    {
        return new Client(config('services.twilio.sid'), config('services.twilio.token'));
    }

    private function TwilioSMS_MessageProperty($message, $property, $default = null)
    {
        if (!is_object($message)) {
            return $default;
        }

        try {
            return $message->{$property} ?? $default;
        } catch (\Throwable $exception) {
            return $default;
        }
    }

    private function TwilioSMS_SaveProviderData($smsLog, $message)
    {
        $status = strtolower(trim((string) $this->TwilioSMS_MessageProperty($message, 'status', '')));
        $dateSent = $this->TwilioSMS_MessageProperty($message, 'dateSent');

        $smsLog->twilio_sid = $this->TwilioSMS_MessageProperty($message, 'sid');
        $smsLog->twilio_status = $status ?: null;
        $smsLog->twilio_error_code = $this->TwilioSMS_MessageProperty($message, 'errorCode');
        $smsLog->twilio_error_message = $this->TwilioSMS_MessageProperty($message, 'errorMessage');
        $smsLog->twilio_checked_at = Carbon::now();
        if ($dateSent) {
            try {
                $smsLog->sent_at = Carbon::parse($dateSent);
            } catch (\Throwable $exception) {
            }
        }
        $smsLog->save();

        return $status;
    }

    private function TwilioSMS_IsFailedStatus($status)
    {
        return in_array($status, ['failed', 'undelivered', 'canceled', 'cancelled', 'expired'], true);
    }

    public function TwilioSMS_GetProviderStatusLabel($status)
    {
        return match (strtolower(trim((string) $status))) {
            'queued', 'accepted' => 'En cola',
            'sending' => 'Enviando',
            'sent' => 'Enviado por el operador',
            'delivered' => 'Entregado',
            'undelivered' => 'No entregado',
            'failed' => 'Fallido',
            'canceled', 'cancelled' => 'Cancelado',
            'expired' => 'Expirado',
            default => $status ? ucfirst((string) $status) : 'Sin consultar',
        };
    }

    public function TwilioSMS_ValidateDelivery($smsLog)
    {
        if (!$smsLog || trim((string) $smsLog->twilio_sid) === '') {
            return [
                'status' => 0,
                'message' => 'Este SMS no tiene un SID de Twilio y no se puede validar.'
            ];
        }

        try {
            $client = $this->TwilioSMS_CreateClient();
            $message = $client->messages($smsLog->twilio_sid)->fetch();
            $providerStatus = $this->TwilioSMS_SaveProviderData($smsLog, $message);

            if ($this->TwilioSMS_IsFailedStatus($providerStatus)) {
                $smsLog->status = 2;
                $smsLog->error_message = $smsLog->twilio_error_message ?: 'Twilio reporto que el SMS no fue entregado.';
                $messageText = 'Twilio reporta el SMS como '.$this->TwilioSMS_GetProviderStatusLabel($providerStatus).'.';
            } else {
                $smsLog->status = 1;
                $smsLog->error_message = null;
                $messageText = 'Twilio reporta el SMS como '.$this->TwilioSMS_GetProviderStatusLabel($providerStatus).'.';
            }
            $smsLog->save();

            return [
                'status' => 1,
                'message' => $messageText,
                'provider_status' => $providerStatus,
            ];
        } catch (\Throwable $exception) {
            info('TwilioSMS_ValidateDelivery error: '.$exception->getMessage());
            return [
                'status' => 0,
                'message' => $exception->getMessage(),
            ];
        }
    }

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
        $Response = [
            'status' => 0,
            'message' => ''
        ];

        $isLocal = App::environment('local');
        $phone = $this->TwilioSMS_NormalizePhone($phone);
        if ($isLocal) {
            $phone = '+573145433746';
        }
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

            if ($smsLog && $isLocal) {
                $smsLog->to = $phone;
            }

            $receiverNumber = $phone;
            $message = 'Opzio S.A.S: '.$messages;
    
            $fromNumber = config('services.twilio.from');

            $client = $this->TwilioSMS_CreateClient();
            $twilioMessage = $client->messages->create($receiverNumber, [
                'from' => $fromNumber,
                'body' => $message
            ]);
            $this->TwilioSMS_SaveProviderData($smsLog, $twilioMessage);
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