<?php

namespace App\traits;

use App\Models\whatsapp_conversation;
use App\Models\whatsapp_message;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Rest\Content\V1\ContentModels;
use Twilio\Rest\Content\V1\Content\ApprovalCreateModels;
use Twilio\Security\RequestValidator;

trait twilio_whatsapp_trait
{
    protected function TwilioWhatsApp_CreateClient()
    {
        return new Client(config('services.twilio.sid'), config('services.twilio.token'));
    }

    protected function TwilioWhatsApp_NormalizePhone($phone): string
    {
        $phone = trim((string) $phone);
        if (stripos($phone, 'whatsapp:') === 0) {
            $phone = substr($phone, 9);
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }

        $digits = ltrim($phone, '+');
        $countryCode = preg_replace('/[^0-9]/', '', (string) config('services.twilio.whatsapp.default_country_code', '+57'));
        if ($countryCode !== '' && strlen($digits) <= 10) {
            $digits = $countryCode.$digits;
        }

        return $digits === '' ? '' : '+'.$digits;
    }

    protected function TwilioWhatsApp_ChannelAddress($phone): string
    {
        $phone = trim((string) $phone);
        if (stripos($phone, 'whatsapp:') === 0) {
            $phone = substr($phone, 9);
        }

        $phone = $this->TwilioWhatsApp_NormalizePhone($phone);
        return $phone === '' ? '' : 'whatsapp:'.$phone;
    }

    protected function TwilioWhatsApp_BusinessAddress(): string
    {
        $from = trim((string) config('services.twilio.whatsapp.from'));
        if ($from !== '') {
            return $this->TwilioWhatsApp_ChannelAddress($from);
        }

        $messagingServiceSid = trim((string) config('services.twilio.whatsapp.messaging_service_sid'));
        return $messagingServiceSid !== '' ? 'messaging_service:'.$messagingServiceSid : 'whatsapp:default';
    }

    private function TwilioWhatsApp_MessageProperty($message, string $property, $default = null)
    {
        if (is_array($message)) {
            return $message[$property] ?? $default;
        }
        if (!is_object($message)) {
            return $default;
        }

        try {
            return $message->{$property} ?? $default;
        } catch (\Throwable $exception) {
            return $default;
        }
    }

    protected function TwilioWhatsApp_StatusLabel($status): string
    {
        return match (strtolower(trim((string) $status))) {
            'queued', 'accepted' => 'En cola',
            'sending' => 'Enviando',
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'read' => 'Leido',
            'received' => 'Recibido',
            'undelivered' => 'No entregado',
            'failed' => 'Fallido',
            'canceled', 'cancelled' => 'Cancelado',
            default => $status ? ucfirst((string) $status) : 'Pendiente',
        };
    }

    protected function TwilioWhatsApp_IsFailedStatus($status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['failed', 'undelivered', 'canceled', 'cancelled', 'expired'], true);
    }

    protected function TwilioWhatsApp_SaveProviderData(whatsapp_message $messageLog, $providerMessage): string
    {
        $status = strtolower(trim((string) $this->TwilioWhatsApp_MessageProperty($providerMessage, 'status', '')));
        $dateSent = $this->TwilioWhatsApp_MessageProperty($providerMessage, 'dateSent');
        $providerBody = $this->TwilioWhatsApp_MessageProperty($providerMessage, 'body');

        $messageLog->twilio_sid = $this->TwilioWhatsApp_MessageProperty($providerMessage, 'sid', $messageLog->twilio_sid);
        $messageLog->status = $status ?: ($messageLog->status ?: 'pending');
        $messageLog->error_code = $this->TwilioWhatsApp_MessageProperty($providerMessage, 'errorCode');
        $messageLog->error_message = $this->TwilioWhatsApp_MessageProperty($providerMessage, 'errorMessage');
        $messageLog->status_updated_at = Carbon::now();
        if (trim((string) $messageLog->body) === '' && trim((string) $providerBody) !== '') {
            $messageLog->body = $providerBody;
        }
        if ($dateSent) {
            try {
                $messageLog->sent_at = Carbon::parse($dateSent);
            } catch (\Throwable $exception) {
            }
        } elseif (in_array($messageLog->status, ['sent', 'delivered', 'read'], true) && !$messageLog->sent_at) {
            $messageLog->sent_at = Carbon::now();
        }
        $messageLog->save();

        return $messageLog->status;
    }

    protected function TwilioWhatsApp_StatusCallbackUrl(): ?string
    {
        $url = trim((string) config('services.twilio.whatsapp.status_callback_url'));
        return $url !== '' ? $url : null;
    }

    public function TwilioWhatsApp_SendMessage(whatsapp_conversation $conversation, whatsapp_message $messageLog, string $body = '', ?string $contentSid = null, array $contentVariables = []): array
    {
        $body = trim($body);
        $contentSid = trim((string) $contentSid);
        if ($body === '' && $contentSid === '') {
            return ['status' => 0, 'message' => 'Debe indicar un mensaje o una plantilla de WhatsApp.'];
        }

        try {
            $options = [];
            $messagingServiceSid = trim((string) config('services.twilio.whatsapp.messaging_service_sid'));
            $from = trim((string) config('services.twilio.whatsapp.from'));
            if ($messagingServiceSid !== '') {
                $options['messagingServiceSid'] = $messagingServiceSid;
            } elseif ($from !== '') {
                $options['from'] = $this->TwilioWhatsApp_ChannelAddress($from);
            } else {
                throw new \InvalidArgumentException('Configura TWILIO_WHATSAPP_FROM o TWILIO_WHATSAPP_MESSAGING_SERVICE_SID.');
            }

            if ($body !== '') {
                $options['body'] = $body;
            }
            if ($contentSid !== '') {
                $options['contentSid'] = $contentSid;
                if ($contentVariables) {
                    $options['contentVariables'] = json_encode($contentVariables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            if ($callback = $this->TwilioWhatsApp_StatusCallbackUrl()) {
                $options['statusCallback'] = $callback;
            }

            $providerMessage = $this->TwilioWhatsApp_CreateClient()->messages->create(
                $this->TwilioWhatsApp_ChannelAddress($conversation->phone),
                $options
            );
            $messageLog->attempts = (int) $messageLog->attempts + 1;
            $status = $this->TwilioWhatsApp_SaveProviderData($messageLog, $providerMessage);
            $conversation->last_message_at = Carbon::now();
            $conversation->last_message_preview = $body !== '' ? $body : 'Plantilla de WhatsApp';
            $conversation->last_outbound_sid = $messageLog->twilio_sid;
            $conversation->save();

            return [
                'status' => 1,
                'message' => 'Mensaje de WhatsApp enviado a Twilio.',
                'provider_status' => $status,
            ];
        } catch (\Throwable $exception) {
            $messageLog->attempts = (int) $messageLog->attempts + 1;
            $messageLog->status = 'failed';
            $messageLog->error_message = $exception->getMessage();
            $messageLog->status_updated_at = Carbon::now();
            $messageLog->save();
            info('TwilioWhatsApp_SendMessage error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function TwilioWhatsApp_ValidateWebhook(Request $request, string $event = 'incoming'): bool
    {
        if (!config('services.twilio.whatsapp.validate_webhooks', true)) {
            return true;
        }

        $token = trim((string) config('services.twilio.token'));
        $signature = trim((string) $request->header('X-Twilio-Signature'));
        if ($token === '' || $signature === '') {
            return false;
        }

        $url = trim((string) config('services.twilio.whatsapp.'.($event === 'status' ? 'status_callback_url' : 'webhook_url')));
        if ($url === '') {
            $url = trim((string) $request->header('X-Opzio-Webhook-Url')) ?: $request->fullUrl();
        }

        $parameters = $request->all();
        $rawBody = (string) $request->getContent();
        if ($rawBody !== '' && str_contains(strtolower((string) $request->header('Content-Type')), 'application/x-www-form-urlencoded')) {
            $rawParameters = [];
            parse_str($rawBody, $rawParameters);
            if (is_array($rawParameters) && $rawParameters) {
                $parameters = $rawParameters;
            }
        }

        try {
            return (new RequestValidator($token))->validate($signature, $url, $parameters);
        } catch (\Throwable $exception) {
            info('TwilioWhatsApp_ValidateWebhook error: '.$exception->getMessage());
            return false;
        }
    }

    private function TwilioWhatsApp_ContentTypes(array $types): array
    {
        $allowed = [
            'twilio/text',
            'twilio/media',
            'twilio/location',
            'twilio/quick-reply',
            'twilio/call-to-action',
            'twilio/card',
            'twilio/carousel',
            'whatsapp/card',
            'whatsapp/authentication',
            'whatsapp/flows',
        ];

        return array_intersect_key($types, array_flip($allowed));
    }

    protected function TwilioWhatsApp_ContentRequest(array $input, bool $update = false)
    {
        $types = $input['types'] ?? [];
        if (is_string($types)) {
            $types = json_decode($types, true);
        }
        if (!is_array($types)) {
            $types = [];
        }
        if (!$types && trim((string) ($input['body'] ?? '')) !== '') {
            $types = ['twilio/text' => ['body' => trim((string) $input['body'])]];
        }
        $types = $this->TwilioWhatsApp_ContentTypes($types);
        if (!$types) {
            throw new \InvalidArgumentException('La plantilla debe incluir al menos un tipo de contenido compatible.');
        }

        $variables = $input['variables'] ?? [];
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        if (!is_array($variables)) {
            $variables = [];
        }

        $payload = [
            'types' => ContentModels::createTypes($types),
        ];
        if (array_key_exists('friendly_name', $input)) {
            $payload['friendly_name'] = trim((string) $input['friendly_name']);
        }
        if (array_key_exists('language', $input)) {
            $payload['language'] = trim((string) $input['language']);
        }
        if ($variables) {
            $payload['variables'] = $variables;
        }
        if (!$update && ($payload['friendly_name'] ?? '') === '') {
            throw new \InvalidArgumentException('El nombre de la plantilla es obligatorio.');
        }
        if (!$update && ($payload['language'] ?? '') === '') {
            throw new \InvalidArgumentException('El idioma de la plantilla es obligatorio.');
        }

        return $update
            ? ContentModels::createContentUpdateRequest($payload)
            : ContentModels::createContentCreateRequest($payload);
    }

    private function TwilioWhatsApp_FormatContentDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        return (string) $value;
    }

    protected function TwilioWhatsApp_FormatTemplate($resource): array
    {
        $approval = $this->TwilioWhatsApp_MessageProperty($resource, 'approvalRequests', []);
        if (is_array($approval) && isset($approval['whatsapp'])) {
            $approval = $approval['whatsapp'];
        }

        return [
            'sid' => $this->TwilioWhatsApp_MessageProperty($resource, 'sid'),
            'friendly_name' => $this->TwilioWhatsApp_MessageProperty($resource, 'friendlyName'),
            'language' => $this->TwilioWhatsApp_MessageProperty($resource, 'language'),
            'variables' => $this->TwilioWhatsApp_MessageProperty($resource, 'variables', []),
            'types' => $this->TwilioWhatsApp_MessageProperty($resource, 'types', []),
            'approval' => is_array($approval) ? $approval : [],
            'created_at' => $this->TwilioWhatsApp_FormatContentDate($this->TwilioWhatsApp_MessageProperty($resource, 'dateCreated')),
            'updated_at' => $this->TwilioWhatsApp_FormatContentDate($this->TwilioWhatsApp_MessageProperty($resource, 'dateUpdated')),
        ];
    }

    public function TwilioWhatsApp_ListTemplates(?int $limit = null): array
    {
        $limit = $limit ?: (int) config('services.twilio.whatsapp.template_limit', 100);
        $resources = $this->TwilioWhatsApp_CreateClient()->content->v1->contentAndApprovals->read(min(500, max(1, $limit)));
        return array_map(fn ($resource) => $this->TwilioWhatsApp_FormatTemplate($resource), $resources);
    }

    public function TwilioWhatsApp_GetTemplate(string $sid): array
    {
        $resource = $this->TwilioWhatsApp_CreateClient()->content->v1->contents($sid)->fetch();
        $template = $this->TwilioWhatsApp_FormatTemplate($resource);
        try {
            $approval = $resource->approvalFetch->getContext()->fetch();
            $template['approval'] = $this->TwilioWhatsApp_FormatTemplate($approval)['approval'] ?: [
                'status' => $this->TwilioWhatsApp_MessageProperty($approval, 'status'),
            ];
        } catch (\Throwable $exception) {
        }
        return $template;
    }

    public function TwilioWhatsApp_CreateTemplate(array $input): array
    {
        $resource = $this->TwilioWhatsApp_CreateClient()->content->v1->contents->create($this->TwilioWhatsApp_ContentRequest($input));
        return $this->TwilioWhatsApp_FormatTemplate($resource);
    }

    public function TwilioWhatsApp_UpdateTemplate(string $sid, array $input): array
    {
        $resource = $this->TwilioWhatsApp_CreateClient()->content->v1->contents($sid)->update($this->TwilioWhatsApp_ContentRequest($input, true));
        return $this->TwilioWhatsApp_FormatTemplate($resource);
    }

    public function TwilioWhatsApp_DeleteTemplate(string $sid): bool
    {
        return $this->TwilioWhatsApp_CreateClient()->content->v1->contents($sid)->delete();
    }

    public function TwilioWhatsApp_SubmitTemplate(string $sid, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $category = strtoupper(trim((string) ($input['category'] ?? 'UTILITY')));
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('El nombre de aprobacion solo acepta minusculas, numeros y guion bajo.');
        }
        if (!in_array($category, ['UTILITY', 'MARKETING', 'AUTHENTICATION'], true)) {
            throw new \InvalidArgumentException('La categoria de WhatsApp no es valida.');
        }

        $approval = $this->TwilioWhatsApp_CreateClient()->content->v1->contents($sid)->approvalCreate->create(
            ApprovalCreateModels::createContentApprovalRequest([
                'name' => $name,
                'category' => $category,
            ])
        );

        return [
            'sid' => $sid,
            'name' => $this->TwilioWhatsApp_MessageProperty($approval, 'name'),
            'category' => $this->TwilioWhatsApp_MessageProperty($approval, 'category'),
            'status' => $this->TwilioWhatsApp_MessageProperty($approval, 'status'),
            'rejection_reason' => $this->TwilioWhatsApp_MessageProperty($approval, 'rejectionReason'),
        ];
    }
}