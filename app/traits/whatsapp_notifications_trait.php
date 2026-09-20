<?php

namespace App\traits;

use App\Models\client;
use App\Models\whatsapp_conversation;
use App\Models\whatsapp_message;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

trait whatsapp_notifications_trait
{
    use twilio_whatsapp_trait;

    private function Notification_BroadcastWhatsapp($eventName, array $payload): void
    {
        try {
            event(new \App\Events\pusherEvents('whatsapp', $eventName, $payload));
        } catch (\Throwable $exception) {
            info('Notification_BroadcastWhatsapp error: '.$exception->getMessage());
        }
    }

    private function Notification_WhatsappResponse($message, $data = [], $status = 1): array
    {
        return array_merge([
            'status' => $status,
            'message' => $message,
        ], $data);
    }

    private function Notification_WhatsappArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function Notification_WhatsappDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone', 'America/Bogota'))->format('Y-m-d\\TH:i');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function Notification_WhatsappClientForPhone(string $phone): ?client
    {
        return client::where('active', 1)
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->first(fn ($item) => $this->TwilioWhatsApp_NormalizePhone($item->phone) === $phone);
    }
    private function Notification_WhatsappClientName(?client $client): ?string
    {
        if (!$client) {
            return null;
        }
        $name = trim((string) $client->name);
        $lastname = trim((string) $client->lastname);
        return trim($name.' '.$lastname) ?: null;
    }

    private function Notification_WhatsappConversation(string $phone, string $businessAddress, ?string $profileName = null, ?string $waId = null, ?int $clientId = null): whatsapp_conversation
    {
        $phone = $this->TwilioWhatsApp_NormalizePhone($phone);
        $businessAddress = trim($businessAddress) !== '' ? $businessAddress : $this->TwilioWhatsApp_BusinessAddress();
        $conversation = whatsapp_conversation::where('phone', $phone)
            ->where('business_address', $businessAddress)
            ->first();

        if (!$conversation) {
            $client = $clientId ? client::find($clientId) : $this->Notification_WhatsappClientForPhone($phone);
            try {
                $conversation = whatsapp_conversation::create([
                    'unique_id' => strtoupper(Str::uuid()->toString()),
                    'client_id' => $client?->id,
                    'phone' => $phone,
                    'business_address' => $businessAddress,
                    'wa_id' => $waId,
                    'display_name' => $profileName ?: $this->Notification_WhatsappClientName($client),
                    'profile_name' => $profileName,
                    'unread_count' => 0,
                ]);
            } catch (QueryException $exception) {
                $conversation = whatsapp_conversation::where('phone', $phone)
                    ->where('business_address', $businessAddress)
                    ->firstOrFail();
            }
        }

        $changed = false;
        if (!$conversation->client_id && $clientId) {
            $conversation->client_id = $clientId;
            $changed = true;
        }
        if (!$conversation->client_id) {
            $client = $this->Notification_WhatsappClientForPhone($phone);
            if ($client) {
                $conversation->client_id = $client->id;
                $changed = true;
            }
        }
        if ($profileName && $conversation->profile_name !== $profileName) {
            $conversation->profile_name = $profileName;
            $changed = true;
        }
        if ($waId && $conversation->wa_id !== $waId) {
            $conversation->wa_id = $waId;
            $changed = true;
        }
        if (!$conversation->display_name) {
            $conversation->display_name = $profileName ?: $this->Notification_WhatsappClientName($conversation->client) ?: $phone;
            $changed = true;
        }
        if ($changed) {
            $conversation->save();
        }

        return $conversation;
    }

    private function Notification_WhatsappConversationPayload(whatsapp_conversation $conversation): array
    {
        $conversation->loadMissing('client');
        $name = $conversation->display_name
            ?: $conversation->profile_name
            ?: $this->Notification_WhatsappClientName($conversation->client)
            ?: $conversation->phone;

        return [
            'id' => $conversation->id,
            'phone' => $conversation->phone,
            'business_address' => $conversation->business_address,
            'client_id' => $conversation->client_id,
            'display_name' => $name,
            'profile_name' => $conversation->profile_name,
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'last_message_at_local' => $this->Notification_WhatsappDate($conversation->last_message_at),
            'unread_count' => (int) $conversation->unread_count,
            'window_expires_at' => $conversation->window_expires_at?->toIso8601String(),
            'window_expires_at_local' => $this->Notification_WhatsappDate($conversation->window_expires_at),
            'window_open' => $conversation->window_expires_at?->isFuture() ?? false,
        ];
    }

    private function Notification_WhatsappMessagePayload(whatsapp_message $message): array
    {
        return [
            'id' => $message->id,
            'twilio_sid' => $message->twilio_sid,
            'direction' => $message->direction,
            'is_inbound' => $message->direction === 'inbound',
            'from' => $message->from,
            'to' => $message->to,
            'body' => $message->body,
            'message_type' => $message->message_type,
            'media' => $message->media ?: [],
            'content_sid' => $message->content_sid,
            'status' => $message->status,
            'status_label' => $this->TwilioWhatsApp_StatusLabel($message->status),
            'error_message' => $message->error_message,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_local' => $this->Notification_WhatsappDate($message->created_at),
            'sent_at_local' => $this->Notification_WhatsappDate($message->sent_at),
            'received_at_local' => $this->Notification_WhatsappDate($message->received_at),
        ];
    }

    public function Notification_GetWhatsappConversations($pagination = [], $search = null, $unreadOnly = false): array
    {
        try {
            $pagination = $this->Notification_WhatsappArray($pagination);
            $page = max(1, (int) ($pagination['page'] ?? 1));
            $size = min(100, max(5, (int) ($pagination['size'] ?? 20)));
            $query = whatsapp_conversation::with('client')->orderByDesc('last_message_at')->orderByDesc('id');
            $term = trim((string) $search);
            if ($term !== '') {
                $query->where(function ($builder) use ($term) {
                    $builder->where('phone', 'like', '%'.$term.'%')
                        ->orWhere('display_name', 'like', '%'.$term.'%')
                        ->orWhere('profile_name', 'like', '%'.$term.'%')
                        ->orWhereHas('client', function ($clientQuery) use ($term) {
                            $clientQuery->where('name', 'like', '%'.$term.'%')
                                ->orWhere('lastname', 'like', '%'.$term.'%');
                        });
                });
            }
            if (filter_var($unreadOnly, FILTER_VALIDATE_BOOLEAN)) {
                $query->where('unread_count', '>', 0);
            }
            $result = $query->paginate($size, ['*'], 'page', $page);

            return $this->Notification_WhatsappResponse('Conversaciones de WhatsApp obtenidas', [
                'conversations' => $result->getCollection()->map(fn ($conversation) => $this->Notification_WhatsappConversationPayload($conversation))->values()->all(),
                'pagination' => [
                    'page' => $result->currentPage(),
                    'size' => $result->perPage(),
                    'total' => $result->total(),
                    'totalPages' => $result->lastPage(),
                ],
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetWhatsappConversations error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetWhatsappConversation($id): array
    {
        try {
            $conversation = whatsapp_conversation::with('client')->find($id);
            if (!$conversation) {
                return $this->Notification_WhatsappResponse('La conversacion de WhatsApp no existe.', [], 0);
            }
            $messages = $conversation->messages()->orderByDesc('id')->limit(200)->get()->reverse()->values();
            return $this->Notification_WhatsappResponse('Conversacion obtenida', [
                'conversation' => $this->Notification_WhatsappConversationPayload($conversation),
                'messages' => $messages->map(fn ($message) => $this->Notification_WhatsappMessagePayload($message))->all(),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetWhatsappConversation error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_MarkWhatsappConversationRead($id): array
    {
        try {
            $conversation = whatsapp_conversation::find($id);
            if (!$conversation) {
                return $this->Notification_WhatsappResponse('La conversacion de WhatsApp no existe.', [], 0);
            }
            $conversation->unread_count = 0;
            $conversation->last_read_at = Carbon::now();
            $conversation->save();
            return $this->Notification_WhatsappResponse('Conversacion marcada como leida', [
                'conversation' => $this->Notification_WhatsappConversationPayload($conversation),
                'unread_count' => (int) whatsapp_conversation::sum('unread_count'),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_MarkWhatsappConversationRead error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetWhatsappUnreadCount(): array
    {
        try {
            return $this->Notification_WhatsappResponse('Pendientes de WhatsApp obtenidos', [
                'unread_count' => (int) whatsapp_conversation::sum('unread_count'),
                'conversation_count' => (int) whatsapp_conversation::where('unread_count', '>', 0)->count(),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetWhatsappUnreadCount error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_StartWhatsappConversation(array $input, $createdBy = null): array
    {
        try {
            $client = !empty($input['client_id']) ? client::find((int) $input['client_id']) : null;
            $phone = $this->TwilioWhatsApp_NormalizePhone($input['phone'] ?? $client?->phone);
            if ($phone === '') {
                throw new \InvalidArgumentException('Debes indicar un telefono de WhatsApp valido.');
            }
            $businessAddress = $this->TwilioWhatsApp_BusinessAddress();
            $conversation = $this->Notification_WhatsappConversation(
                $phone,
                $businessAddress,
                trim((string) ($input['display_name'] ?? '')) ?: $this->Notification_WhatsappClientName($client),
                null,
                $client?->id
            );
            return $this->Notification_WhatsappResponse('Conversacion lista', [
                'conversation' => $this->Notification_WhatsappConversationPayload($conversation),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_StartWhatsappConversation error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_SendWhatsappMessage($conversationId, array $input, $createdBy = null): array
    {
        try {
            $conversation = whatsapp_conversation::find($conversationId);
            if (!$conversation) {
                return $this->Notification_WhatsappResponse('La conversacion de WhatsApp no existe.', [], 0);
            }

            $body = trim((string) ($input['body'] ?? ''));
            if (mb_strlen($body) > 4096) {
                throw new \InvalidArgumentException('El mensaje de WhatsApp no puede superar 4096 caracteres.');
            }
            $contentSid = trim((string) ($input['content_sid'] ?? $input['contentSid'] ?? ''));
            if ($contentSid !== '' && !preg_match('/^HX[0-9a-fA-F]{32}$/', $contentSid)) {
                throw new \InvalidArgumentException('El ContentSid de WhatsApp no es valido.');
            }
            $contentVariables = $this->Notification_WhatsappArray($input['content_variables'] ?? $input['contentVariables'] ?? []);
            if ($body === '' && $contentSid === '') {
                throw new \InvalidArgumentException('Escribe un mensaje o selecciona una plantilla.');
            }
            if (!$contentSid && (!$conversation->window_expires_at || !$conversation->window_expires_at->isFuture())) {
                throw new \InvalidArgumentException('La ventana de atencion de 24 horas expiro. Usa una plantilla aprobada para iniciar el contacto.');
            }

            $messageLog = whatsapp_message::create([
                'unique_id' => strtoupper(Str::uuid()->toString()),
                'conversation_id' => $conversation->id,
                'client_id' => $conversation->client_id,
                'direction' => 'outbound',
                'from' => $conversation->business_address,
                'to' => $this->TwilioWhatsApp_ChannelAddress($conversation->phone),
                'body' => $body !== '' ? $body : 'Plantilla '.$contentSid,
                'message_type' => $contentSid ? 'template' : 'text',
                'content_sid' => $contentSid ?: null,
                'content_variables' => $contentVariables ?: null,
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            $response = $this->TwilioWhatsApp_SendMessage($conversation, $messageLog, $body, $contentSid ?: null, $contentVariables);
            if (($response['status'] ?? 0) !== 1) {
                return $this->Notification_WhatsappResponse($response['message'] ?? 'No fue posible enviar el mensaje.', [
                    'message_record' => $this->Notification_WhatsappMessagePayload($messageLog->fresh()),
                ], 0);
            }

            return $this->Notification_WhatsappResponse($response['message'], [
                'message_record' => $this->Notification_WhatsappMessagePayload($messageLog->fresh()),
                'conversation' => $this->Notification_WhatsappConversationPayload($conversation->fresh()),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_SendWhatsappMessage error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_HandleWhatsappIncoming(array $payload): array
    {
        $from = $this->TwilioWhatsApp_NormalizePhone($payload['From'] ?? '');
        if ($from === '') {
            throw new \InvalidArgumentException('El webhook de WhatsApp no incluyo From.');
        }
        $to = trim((string) ($payload['To'] ?? ''));
        $businessAddress = $to !== '' ? $this->TwilioWhatsApp_ChannelAddress($to) : $this->TwilioWhatsApp_BusinessAddress();
        $messageSid = trim((string) ($payload['MessageSid'] ?? $payload['SmsMessageSid'] ?? $payload['SmsSid'] ?? ''));
        if ($messageSid !== '' && whatsapp_message::where('twilio_sid', $messageSid)->exists()) {
            return ['status' => 1, 'duplicate' => true];
        }

        $profileName = trim((string) ($payload['ProfileName'] ?? '')) ?: null;
        $waId = trim((string) ($payload['WaId'] ?? '')) ?: null;
        $conversation = $this->Notification_WhatsappConversation($from, $businessAddress, $profileName, $waId);
        $media = [];
        $mediaCount = max(0, (int) ($payload['NumMedia'] ?? 0));
        for ($index = 0; $index < $mediaCount; $index++) {
            $url = trim((string) ($payload['MediaUrl'.$index] ?? ''));
            if ($url !== '') {
                $media[] = [
                    'url' => $url,
                    'content_type' => trim((string) ($payload['MediaContentType'.$index] ?? '')) ?: null,
                ];
            }
        }
        $body = trim((string) ($payload['Body'] ?? ''));
        if ($body === '' && trim((string) ($payload['ButtonText'] ?? '')) !== '') {
            $body = trim((string) $payload['ButtonText']);
        }
        if ($body === '' && $media) {
            $body = 'Archivo recibido';
        }

        try {
            $message = whatsapp_message::create([
                'unique_id' => strtoupper(Str::uuid()->toString()),
                'conversation_id' => $conversation->id,
                'client_id' => $conversation->client_id,
                'twilio_sid' => $messageSid ?: null,
                'direction' => 'inbound',
                'from' => 'whatsapp:'.$from,
                'to' => $businessAddress,
                'body' => $body,
                'message_type' => $media ? 'media' : 'text',
                'media' => $media ?: null,
                'status' => 'received',
                'received_at' => Carbon::now(),
                'status_updated_at' => Carbon::now(),
                'raw_payload' => $payload,
            ]);
        } catch (QueryException $exception) {
            if ($messageSid && ($message = whatsapp_message::where('twilio_sid', $messageSid)->first())) {
                return ['status' => 1, 'duplicate' => true, 'message_id' => $message->id];
            }
            throw $exception;
        }

        $conversation->unread_count = (int) $conversation->unread_count + 1;
        $conversation->last_message_preview = $body !== '' ? $body : 'Mensaje recibido';
        $conversation->last_message_at = Carbon::now();
        $conversation->last_inbound_at = Carbon::now();
        $conversation->window_expires_at = Carbon::now()->addHours(24);
        $conversation->last_inbound_sid = $messageSid ?: null;
        $conversation->save();
        $this->Notification_BroadcastWhatsapp('message', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'direction' => 'inbound',
            'unread_count' => (int) $conversation->unread_count,
        ]);

        return [
            'status' => 1,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ];
    }

    public function Notification_HandleWhatsappStatus(array $payload): array
    {
        $messageSid = trim((string) ($payload['MessageSid'] ?? $payload['SmsSid'] ?? ''));
        if ($messageSid === '') {
            return ['status' => 1, 'ignored' => true];
        }
        $message = whatsapp_message::where('twilio_sid', $messageSid)->first();
        if (!$message) {
            return ['status' => 1, 'ignored' => true];
        }

        $status = strtolower(trim((string) ($payload['MessageStatus'] ?? $payload['SmsStatus'] ?? '')));
        if ($status !== '') {
            $message->status = $status;
        }
        $errorCode = trim((string) ($payload['ErrorCode'] ?? ''));
        $message->error_code = $errorCode !== '' && ctype_digit($errorCode) ? (int) $errorCode : null;
        $message->error_message = $payload['ErrorMessage'] ?? ($this->TwilioWhatsApp_IsFailedStatus($status) ? 'Twilio reporto un fallo de entrega.' : null);
        $message->status_updated_at = Carbon::now();
        if (in_array($status, ['sent', 'delivered', 'read'], true) && !$message->sent_at) {
            $message->sent_at = Carbon::now();
        }
        $message->save();
        $this->Notification_BroadcastWhatsapp('status', [
            'conversation_id' => $message->conversation_id,
            'message_id' => $message->id,
            'direction' => 'outbound',
            'status' => $message->status,
        ]);

        return ['status' => 1, 'message_id' => $message->id, 'provider_status' => $status];
    }

    public function Notification_GetWhatsappTemplates(): array
    {
        try {
            return $this->Notification_WhatsappResponse('Plantillas obtenidas', [
                'templates' => $this->TwilioWhatsApp_ListTemplates(),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetWhatsappTemplates error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_CreateWhatsappTemplate(array $input): array
    {
        try {
            return $this->Notification_WhatsappResponse('Plantilla creada', [
                'template' => $this->TwilioWhatsApp_CreateTemplate($input),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_CreateWhatsappTemplate error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_UpdateWhatsappTemplate($sid, array $input): array
    {
        try {
            return $this->Notification_WhatsappResponse('Plantilla actualizada', [
                'template' => $this->TwilioWhatsApp_UpdateTemplate((string) $sid, $input),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_UpdateWhatsappTemplate error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_DeleteWhatsappTemplate($sid): array
    {
        try {
            $this->TwilioWhatsApp_DeleteTemplate((string) $sid);
            return $this->Notification_WhatsappResponse('Plantilla eliminada');
        } catch (\Throwable $exception) {
            info('Notification_DeleteWhatsappTemplate error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }

    public function Notification_SubmitWhatsappTemplate($sid, array $input): array
    {
        try {
            return $this->Notification_WhatsappResponse('Plantilla enviada a aprobacion', [
                'approval' => $this->TwilioWhatsApp_SubmitTemplate((string) $sid, $input),
            ]);
        } catch (\Throwable $exception) {
            info('Notification_SubmitWhatsappTemplate error: '.$exception->getMessage());
            return $this->Notification_WhatsappResponse($exception->getMessage(), [], 0);
        }
    }
}