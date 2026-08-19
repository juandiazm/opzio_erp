<?php

namespace App\traits;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use App\Models\mail_log;
use App\Models\sms_log;

trait notifications_trait
{
    use mail_log_trait;
    use mail_senders_trait;
    use twilio_sms_trait;

    private function Notification_Response($message, $data = [], $status = 1)
    {
        return array_merge([
            'status' => $status,
            'message' => $message,
        ], $data);
    }

    private function Notification_AsArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function Notification_Bool($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function Notification_NormalizeSendAt($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('La fecha de envio no es valida');
        }
    }

    private function Notification_SanitizeHtml($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'div', 'span', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
        $allowedAttributes = ['style', 'href', 'target', 'rel', 'colspan', 'rowspan'];
        if (!class_exists(\DOMDocument::class)) {
            $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
            $html = preg_replace('/(?:javascript|vbscript)\s*:/i', '', $html);
            return trim(strip_tags($html, '<'.implode('><', $allowedTags).'>'));
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="notification-html-root">'.$html.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $root = (new \DOMXPath($document))->query('//*[@id="notification-html-root"]')->item(0);
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        $allowedStyles = ['text-align', 'font-weight', 'font-style', 'text-decoration', 'color', 'background-color', 'font-size', 'font-family', 'line-height', 'padding', 'margin', 'width', 'height', 'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-collapse', 'vertical-align', 'float', 'display', 'box-sizing'];
        $forbiddenTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'meta', 'link'];
        $sanitizeNode = function ($node) use (&$sanitizeNode, $allowedTags, $allowedAttributes, $allowedStyles, $forbiddenTags) {
            for ($child = $node->firstChild; $child;) {
                $next = $child->nextSibling;
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);
                    if (in_array($tag, $forbiddenTags, true)) {
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    if (!in_array($tag, $allowedTags, true)) {
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    for ($index = $child->attributes->length - 1; $index >= 0; $index--) {
                        $attribute = $child->attributes->item($index);
                        $name = strtolower($attribute->name);
                        if (!in_array($name, $allowedAttributes, true)) {
                            $child->removeAttribute($attribute->name);
                            continue;
                        }
                        if ($name === 'style') {
                            $styles = [];
                            foreach (explode(';', $attribute->value) as $declaration) {
                                $parts = explode(':', $declaration, 2);
                                $property = strtolower(trim($parts[0] ?? ''));
                                $value = trim($parts[1] ?? '');
                                if ($property === '' || $value === '' || !in_array($property, $allowedStyles, true) || preg_match('/url\s*\(|expression\s*\(|javascript\s*:|vbscript\s*:|[<>]/i', $value)) {
                                    continue;
                                }
                                $styles[] = $property.': '.$value;
                            }
                            if ($styles) {
                                $child->setAttribute('style', implode('; ', $styles));
                            } else {
                                $child->removeAttribute('style');
                            }
                        }
                        if ($name === 'href' && !preg_match('/^(https?:|mailto:|\/|#)/i', trim($attribute->value))) {
                            $child->removeAttribute('href');
                        }
                    }
                    $sanitizeNode($child);
                }
                $child = $next;
            }
        };
        $sanitizeNode($root);

        $result = '';
        for ($child = $root->firstChild; $child; $child = $child->nextSibling) {
            $result .= $document->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return trim($result);
    }

    private function Notification_NormalizeEmails($value)
    {
        $values = [];
        $items = is_array($value) ? $value : [$value];
        foreach ($items as $item) {
            $name = '';
            $clientId = null;
            if (is_array($item)) {
                $name = trim((string) ($item['name'] ?? $item['recipient_name'] ?? ''));
                $clientId = $item['client_id'] ?? null;
                $item = $item['address'] ?? $item['email'] ?? '';
            }
            foreach (preg_split('/[\s,;]+/', trim((string) $item), -1, PREG_SPLIT_NO_EMPTY) as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('El correo '.$email.' no es valido');
                }
                $key = strtolower($email);
                if (!isset($values[$key])) {
                    $values[$key] = [
                        'address' => $email,
                        'name' => $name,
                        'client_id' => $clientId ? (int) $clientId : null,
                    ];
                }
            }
        }

        $result = array_values($values);
        if (count($result) > 500) {
            throw new \InvalidArgumentException('No puede procesar mas de 500 destinatarios por envio');
        }

        return $result;
    }

    private function Notification_NormalizePhones($value)
    {
        $values = [];
        $items = is_array($value) ? $value : [$value];
        foreach ($items as $item) {
            $name = '';
            $clientId = null;
            if (is_array($item)) {
                $name = trim((string) ($item['name'] ?? $item['recipient_name'] ?? ''));
                $clientId = $item['client_id'] ?? null;
                $item = $item['phone'] ?? $item['to'] ?? $item['address'] ?? '';
            }
            $rawPhone = trim((string) $item);
            if ($rawPhone === '') {
                continue;
            }
            $phone = preg_replace('/[^0-9+]/', '', $rawPhone);
            if (str_starts_with($phone, '00')) {
                $phone = '+'.substr($phone, 2);
            }
            if (!str_starts_with($phone, '+')) {
                $phone = '+57'.ltrim($phone, '0');
            }
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) < 7 || strlen($digits) > 15) {
                throw new \InvalidArgumentException('El telefono '.$rawPhone.' no es valido');
            }
            if (!isset($values[$phone])) {
                $values[$phone] = [
                    'phone' => $phone,
                    'name' => $name,
                    'client_id' => $clientId ? (int) $clientId : null,
                ];
            }
        }

        $result = array_values($values);
        if (count($result) > 500) {
            throw new \InvalidArgumentException('No puede procesar mas de 500 destinatarios por envio');
        }

        return $result;
    }

    private function Notification_ClientContacts($input)
    {
        $allClients = $this->Notification_Bool($input['all_clients'] ?? false);
        $clientIds = collect($this->Notification_AsArray($input['client_ids'] ?? []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (!$allClients && $clientIds->isEmpty()) {
            return ['emails' => [], 'phones' => []];
        }

        $query = client::query()->where('active', 1)->with('licenses.license_notifications');
        if ($allClients) {
            $clients = $query->orderBy('id')->limit(500)->get();
        } else {
            $clients = $query->whereIn('id', $clientIds)->get();
        }

        $emails = [];
        $phones = [];
        foreach ($clients as $client) {
            $name = trim((string) ($client->complete_name ?: $client->name));
            if (filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = ['address' => $client->email, 'name' => $name, 'client_id' => $client->id];
            }
            if (trim((string) $client->phone) !== '') {
                $phones[] = ['phone' => $client->phone, 'name' => $name, 'client_id' => $client->id];
            }
            foreach ($client->licenses as $license) {
                if (!$license->active) {
                    continue;
                }
                foreach ($license->license_notifications as $notification) {
                    if (!$notification->active) {
                        continue;
                    }
                    if (filter_var($notification->email, FILTER_VALIDATE_EMAIL)) {
                        $emails[] = ['address' => $notification->email, 'name' => $name, 'client_id' => $client->id];
                    }
                    if (trim((string) $notification->phone) !== '') {
                        $phones[] = ['phone' => $notification->phone, 'name' => $name, 'client_id' => $client->id];
                    }
                }
            }
        }

        return [
            'emails' => $this->Notification_NormalizeEmails($emails),
            'phones' => $this->Notification_NormalizePhones($phones),
        ];
    }

    private function Notification_StoreAttachments($files, $batch)
    {
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $files = array_values(array_filter((array) $files));
        if (!$files) {
            return [];
        }
        if (count($files) > 5) {
            throw new \InvalidArgumentException('No puede adjuntar mas de 5 archivos');
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
        $attachments = [];
        $totalBytes = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw new \InvalidArgumentException('Uno de los archivos adjuntos no es valido');
            }
            $extension = strtolower($file->getClientOriginalExtension());
            $totalBytes += (int) $file->getSize();
            if (!in_array($extension, $allowedExtensions, true) || $file->getSize() > 10 * 1024 * 1024) {
                throw new \InvalidArgumentException('Los adjuntos deben tener un tipo permitido y pesar maximo 10 MB cada uno');
            }
            if ($totalBytes > 25 * 1024 * 1024) {
                throw new \InvalidArgumentException('El tamano total de los adjuntos no puede superar 25 MB');
            }
            $originalName = basename((string) $file->getClientOriginalName());
            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName);
            $safeName = trim($safeName, '._-') ?: ('archivo.'.$extension);
            $storedName = Str::uuid()->toString().'_'.$safeName;
            $path = $file->storeAs('notifications/attachments/'.$batch, $storedName, 'local');
            if (!$path) {
                throw new \RuntimeException('No fue posible guardar un archivo adjunto');
            }
            $attachments[] = ['path' => $path, 'name' => $originalName];
        }

        return $attachments;
    }

    private function Notification_CopyAttachments($attachments)
    {
        return collect($attachments)->map(function ($attachment) {
            return [
                'path' => $attachment->path ?? $attachment['path'] ?? '',
                'name' => $attachment->name ?? $attachment['name'] ?? '',
            ];
        })->filter(fn ($attachment) => $attachment['path'] !== '' && $attachment['name'] !== '')->values()->all();
    }

    private function Notification_EmailPayload($input, $recipientName = '')
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        if ($subject === '' || mb_strlen($subject) > 255) {
            throw new \InvalidArgumentException('Debe ingresar un asunto de hasta 255 caracteres');
        }
        $content = $this->Notification_SanitizeHtml($input['content'] ?? '');
        if ($content === '' || trim(strip_tags($content)) === '') {
            throw new \InvalidArgumentException('Debe ingresar contenido para el correo');
        }

        $from = $this->Mail_GetSenderForView('mail.notification', $input['from'] ?? null);
        $replyToData = $this->Mail_GetReplyTo();

        return [
            'subject' => $subject,
            'content' => $content,
            'send_at' => $this->Notification_NormalizeSendAt($input['send_at'] ?? null),
            'from' => $from,
            'reply_to' => $replyToData,
            'recipient_name' => $recipientName,
        ];
    }

    private function Notification_MailData($payload, $resendOfId = null, $clientId = null)
    {
        return [
            'content' => $payload['content'],
            'recipient_name' => $payload['recipient_name'],
            'client_id' => $clientId,
            '_from' => $payload['from'],
            '_reply_to' => $payload['reply_to'],
            'resend_of_id' => $resendOfId,
        ];
    }

    private function Notification_EmailRecipients($input, $fallback = [])
    {
        $contacts = $this->Notification_ClientContacts($input);
        $manual = $input['recipients'] ?? [];
        $manual = $this->Notification_AsArray($manual) ?: $manual;
        return $this->Notification_NormalizeEmails(array_merge($contacts['emails'], (array) $manual, $fallback));
    }

    private function Notification_SmsRecipients($input, $fallback = [])
    {
        $contacts = $this->Notification_ClientContacts($input);
        $manual = $input['phones'] ?? $input['recipients'] ?? [];
        $manual = $this->Notification_AsArray($manual) ?: $manual;
        return $this->Notification_NormalizePhones(array_merge($contacts['phones'], (array) $manual, $fallback));
    }

    public function Notification_CreateEmail(array $input, $files = [], $createdBy = null)
    {
        try {
            $payload = $this->Notification_EmailPayload($input);
            $recipients = $this->Notification_EmailRecipients($input);
            if (!$recipients) {
                throw new \InvalidArgumentException('Debe indicar al menos un destinatario');
            }
            $batch = strtoupper(Str::uuid()->toString());
            $attachments = $this->Notification_StoreAttachments($files, $batch);
            $mode = in_array(($input['recipient_mode'] ?? 'individual'), ['massive', 'individual'], true)
                ? $input['recipient_mode']
                : 'individual';
            $logs = [];
            if ($mode === 'massive') {
                $data = $this->Notification_MailData($payload);
                $logs[] = $this->MailLog_CreatePending(
                    $payload['subject'],
                    'mail.notification',
                    $payload['from']['address'],
                    $payload['from']['name'],
                    $recipients,
                    $data,
                    $attachments,
                    $payload['send_at'],
                    $batch
                );
            } else {
                foreach ($recipients as $recipient) {
                    $individualPayload = $payload;
                    $individualPayload['recipient_name'] = $recipient['name'];
                    $logs[] = $this->MailLog_CreatePending(
                        $payload['subject'],
                        'mail.notification',
                        $payload['from']['address'],
                        $payload['from']['name'],
                        [$recipient],
                        $this->Notification_MailData($individualPayload, null, $recipient['client_id']),
                        $attachments,
                        $payload['send_at'],
                        $batch
                    );
                }
            }

            return $this->Notification_Response('Correo registrado para envio', [
                'batch' => $batch,
                'count' => count($logs),
                'scheduled_for' => $payload['send_at'],
            ]);
        } catch (\Throwable $exception) {
            info('Notification_CreateEmail error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_CreateSms(array $input, $createdBy = null)
    {
        try {
            $body = trim((string) ($input['body'] ?? $input['content'] ?? ''));
            if ($body === '' || mb_strlen($body) > 1600) {
                throw new \InvalidArgumentException('El mensaje SMS debe tener entre 1 y 1600 caracteres');
            }
            $recipients = $this->Notification_SmsRecipients($input);
            if (!$recipients) {
                throw new \InvalidArgumentException('Debe indicar al menos un telefono');
            }
            $sendAt = $this->Notification_NormalizeSendAt($input['send_at'] ?? null);
            $batch = strtoupper(Str::uuid()->toString());
            $logs = [];
            foreach ($recipients as $recipient) {
                $logs[] = sms_log::create([
                    'unique_id' => strtoupper(Str::uuid()->toString()),
                    'client_id' => $recipient['client_id'],
                    'recipient_name' => $recipient['name'],
                    'to' => $recipient['phone'],
                    'body' => $body,
                    'attempts' => 0,
                    'status' => 0,
                    'send_at' => $sendAt,
                    'notification_batch' => $batch,
                    'created_by' => $createdBy,
                ]);
            }

            return $this->Notification_Response('SMS registrado para envio', [
                'batch' => $batch,
                'count' => count($logs),
                'scheduled_for' => $sendAt,
            ]);
        } catch (\Throwable $exception) {
            info('Notification_CreateSms error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetClients()
    {
        try {
            $clients = client::where('active', 1)->orderBy('name')->limit(500)->get(['id', 'name', 'lastname', 'email', 'phone']);
            return $this->Notification_Response('Clientes obtenidos', ['clients' => $clients]);
        } catch (\Throwable $exception) {
            info('Notification_GetClients error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    private function Notification_Pagination($value, $defaultSize = 10)
    {
        $pagination = $this->Notification_AsArray($value);
        return [
            'page' => max(1, (int) ($pagination['page'] ?? 1)),
            'size' => min(100, max(5, (int) ($pagination['size'] ?? $defaultSize))),
        ];
    }

    private function Notification_StatusLabel($status)
    {
        return match ((int) $status) {
            1 => 'Enviado',
            2 => 'Fallido',
            default => 'Pendiente',
        };
    }

    private function Notification_NormalizeDateFilter($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
            if ($date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('La fecha de filtro no es valida');
            }
            return $date->format('Y-m-d');
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('La fecha de filtro no es valida');
        }
    }

    private function Notification_ApplyDateRange($query, $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $this->Notification_NormalizeDateFilter($dateFrom);
        $dateTo = $this->Notification_NormalizeDateFilter($dateTo);
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            throw new \InvalidArgumentException('El rango de fechas no es valido');
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    private function Notification_RenderEmailContent($mail, array $data)
    {
        $content = trim((string) ($data['content'] ?? ''));
        if ($content === '' && is_string($mail->view) && view()->exists($mail->view)) {
            try {
                $content = view($mail->view, ['Data' => $data])->render();
            } catch (\Throwable $exception) {
                info('Notification_RenderEmailContent error for mail log '.$mail->id.': '.$exception->getMessage());
            }
        }

        return $this->Notification_SanitizeHtml($content);
    }

    private function Notification_FormatDateForClient($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->setTimezone(config('app.timezone', 'America/Bogota'))
                ->format('Y-m-d\\TH:i');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function Notification_GetEmails($pagination = [], $search = null, $status = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $pagination = $this->Notification_Pagination($pagination);
            $query = $this->Notification_ApplyDateRange(mail_log::with('attachments'), $dateFrom, $dateTo)
                ->orderByDesc('id');
            if (trim((string) $search) !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('subject', 'like', '%'.trim($search).'%')
                        ->orWhere('to', 'like', '%'.trim($search).'%');
                });
            }
            if ($status !== null && $status !== '') {
                $query->where('status', (int) $status);
            }
            $result = $query->paginate($pagination['size'], ['*'], 'page', $pagination['page']);
            $result->getCollection()->transform(function ($mail) {
                $data = $this->Notification_AsArray($mail->mail_data);
                $mail->status_string = $this->Notification_StatusLabel($mail->status);
                $mail->recipient_count = count(is_array($mail->to) ? $mail->to : []);
                $mail->attachments_count = $mail->attachments->count();
                $mail->can_resend = $this->Notification_RenderEmailContent($mail, $data) !== '';
                $mail->send_at_local = $this->Notification_FormatDateForClient($mail->send_at);
                $mail->sent_at_local = $this->Notification_FormatDateForClient($mail->sent_at);
                unset($mail->mail_data);
                return $mail;
            });
            return $this->Notification_Response('Correos obtenidos', [
                'emails' => $result->items(),
                'pagination' => [
                    'page' => $result->currentPage(),
                    'size' => $result->perPage(),
                    'total' => $result->total(),
                    'totalPages' => $result->lastPage(),
                ],
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetEmails error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetSms($pagination = [], $search = null, $status = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $pagination = $this->Notification_Pagination($pagination);
            $query = $this->Notification_ApplyDateRange(sms_log::with('client'), $dateFrom, $dateTo)->orderByDesc('id');
            if (trim((string) $search) !== '') {
                $term = trim($search);
                $query->where(function ($builder) use ($term) {
                    $builder->where('body', 'like', '%'.$term.'%')
                        ->orWhere('to', 'like', '%'.$term.'%')
                        ->orWhere('recipient_name', 'like', '%'.$term.'%');
                });
            }
            if ($status !== null && $status !== '') {
                $query->where('status', (int) $status);
            }
            $result = $query->paginate($pagination['size'], ['*'], 'page', $pagination['page']);
            $result->getCollection()->transform(function ($sms) {
                $sms->status_string = $this->Notification_StatusLabel($sms->status);
                $sms->client_name = $sms->client?->complete_name;
                $sms->send_at_local = $this->Notification_FormatDateForClient($sms->send_at);
                $sms->sent_at_local = $this->Notification_FormatDateForClient($sms->sent_at);
                unset($sms->client);
                return $sms;
            });
            return $this->Notification_Response('SMS obtenidos', [
                'sms' => $result->items(),
                'pagination' => [
                    'page' => $result->currentPage(),
                    'size' => $result->perPage(),
                    'total' => $result->total(),
                    'totalPages' => $result->lastPage(),
                ],
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetSms error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetEmail($id)
    {
        try {
            $mail = mail_log::with('attachments')->find($id);
            if (!$mail) {
                return $this->Notification_Response('El correo no existe', [], 0);
            }
            $data = $this->Notification_AsArray($mail->mail_data);
            $content = $this->Notification_RenderEmailContent($mail, $data);
            return $this->Notification_Response('Correo obtenido', [
                'email' => [
                    'id' => $mail->id,
                    'subject' => $mail->subject,
                    'content' => $content,
                    'from' => data_get($data, '_from.address', $mail->from),
                    'from_name' => data_get($data, '_from.name', $mail->as),
                    'reply_to' => data_get($data, '_reply_to.address', ''),
                    'reply_to_name' => data_get($data, '_reply_to.name', ''),
                    'recipients' => is_array($mail->to) ? $mail->to : [],
                    'status' => (int) $mail->status,
                    'status_string' => $this->Notification_StatusLabel($mail->status),
                    'send_at' => $this->Notification_FormatDateForClient($mail->send_at),
                    'sent_at' => $this->Notification_FormatDateForClient($mail->sent_at),
                    'send_at_local' => $this->Notification_FormatDateForClient($mail->send_at),
                    'sent_at_local' => $this->Notification_FormatDateForClient($mail->sent_at),
                    'can_resend' => $content !== '',
                    'attachments' => $mail->attachments->map(fn ($attachment) => ['name' => $attachment->name])->values()->all(),
                ],
            ]);
        } catch (\Throwable $exception) {
            info('Notification_GetEmail error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_GetSmsById($id)
    {
        try {
            $sms = sms_log::find($id);
            if (!$sms) {
                return $this->Notification_Response('El SMS no existe', [], 0);
            }
            $sms->send_at_local = $this->Notification_FormatDateForClient($sms->send_at);
            $sms->sent_at_local = $this->Notification_FormatDateForClient($sms->sent_at);
            return $this->Notification_Response('SMS obtenido', ['sms' => $sms]);
        } catch (\Throwable $exception) {
            info('Notification_GetSmsById error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_ResendEmail($id, array $input, $files = [], $createdBy = null)
    {
        try {
            $original = mail_log::with('attachments')->find($id);
            if (!$original) {
                return $this->Notification_Response('El correo original no existe', [], 0);
            }
            $originalData = is_array($original->mail_data) ? $original->mail_data : [];
            $originalContent = $this->Notification_RenderEmailContent($original, $originalData);
            if ($originalContent === '') {
                return $this->Notification_Response('El correo original no tiene un contenido reenviable', [], 0);
            }
            $hasRequestedRecipients = array_key_exists('recipients', $input) || array_key_exists('client_ids', $input) || array_key_exists('all_clients', $input);
            $input = array_merge([
                'subject' => $original->subject,
                'content' => $originalContent,
                'from' => data_get($originalData, '_from.address', $original->from),
                'from_name' => data_get($originalData, '_from.name', $original->as),
                'reply_to' => data_get($originalData, '_reply_to.address', ''),
                'reply_to_name' => data_get($originalData, '_reply_to.name', ''),
                'send_at' => null,
                'recipient_mode' => 'individual',
                'recipients' => is_array($original->to) ? $original->to : [],
            ], $input);
            $payload = $this->Notification_EmailPayload($input);
            $recipients = $this->Notification_EmailRecipients($input, $hasRequestedRecipients ? [] : (is_array($original->to) ? $original->to : []));
            if (!$recipients) {
                throw new \InvalidArgumentException('Debe indicar al menos un destinatario');
            }
            $batch = strtoupper(Str::uuid()->toString());
            $attachments = $this->Notification_StoreAttachments($files, $batch);
            if (!$attachments) {
                $attachments = $this->Notification_CopyAttachments($original->attachments);
            }
            $logs = [];
            foreach ($recipients as $recipient) {
                $individualPayload = $payload;
                $individualPayload['recipient_name'] = $recipient['name'];
                $logs[] = $this->MailLog_CreatePending(
                    $payload['subject'],
                    'mail.notification',
                    $payload['from']['address'],
                    $payload['from']['name'],
                    [$recipient],
                    $this->Notification_MailData($individualPayload, $original->id, $recipient['client_id']),
                    $attachments,
                    $payload['send_at'],
                    $batch
                );
            }
            return $this->Notification_Response('Reenvio registrado', ['batch' => $batch, 'count' => count($logs)]);
        } catch (\Throwable $exception) {
            info('Notification_ResendEmail error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_ResendSms($id, array $input, $createdBy = null)
    {
        try {
            $original = sms_log::find($id);
            if (!$original) {
                return $this->Notification_Response('El SMS original no existe', [], 0);
            }
            $hasRequestedRecipients = array_key_exists('recipients', $input) || array_key_exists('phones', $input) || array_key_exists('client_ids', $input) || array_key_exists('all_clients', $input);
            $input = array_merge([
                'body' => $original->body,
                'phones' => [['phone' => $original->to, 'name' => $original->recipient_name, 'client_id' => $original->client_id]],
                'send_at' => null,
            ], $input);
            if ($hasRequestedRecipients) {
                unset($input['phones']);
            } else {
                $input['recipients'] = $input['phones'];
            }
            $body = trim((string) $input['body']);
            if ($body === '' || mb_strlen($body) > 1600) {
                throw new \InvalidArgumentException('El mensaje SMS debe tener entre 1 y 1600 caracteres');
            }
            $recipients = $this->Notification_SmsRecipients($input);
            $sendAt = $this->Notification_NormalizeSendAt($input['send_at'] ?? null);
            if (!$recipients) {
                throw new \InvalidArgumentException('Debe indicar al menos un telefono');
            }
            $batch = strtoupper(Str::uuid()->toString());
            foreach ($recipients as $recipient) {
                sms_log::create([
                    'unique_id' => strtoupper(Str::uuid()->toString()),
                    'client_id' => $recipient['client_id'],
                    'recipient_name' => $recipient['name'],
                    'to' => $recipient['phone'],
                    'body' => $body,
                    'attempts' => 0,
                    'status' => 0,
                    'send_at' => $sendAt,
                    'notification_batch' => $batch,
                    'resend_of_id' => $original->id,
                    'created_by' => $createdBy,
                ]);
            }
            return $this->Notification_Response('Reenvio SMS registrado', ['batch' => $batch, 'count' => count($recipients)]);
        } catch (\Throwable $exception) {
            info('Notification_ResendSms error: '.$exception->getMessage());
            return $this->Notification_Response($exception->getMessage(), [], 0);
        }
    }

    public function Notification_ProcessSmsQueue($now = null)
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();
        $logs = sms_log::where('status', 0)
            ->where('attempts', '<', 3)
            ->where(function ($query) use ($now) {
                $query->whereNull('send_at')->orWhere('send_at', '<=', $now);
            })
            ->orderBy('id')
            ->limit(100)
            ->get();
        $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($logs as $sms) {
            try {
                $response = $this->TwilioSMS_SendMessage('+57', $sms->to, $sms->body, $sms->id);
                $sms->attempts = (int) $sms->attempts + 1;
                $result['processed']++;
                if (($response['status'] ?? 0) == 1) {
                    $sms->status = 1;
                    $sms->sent_at = Carbon::now();
                    $sms->error_message = null;
                    $result['sent']++;
                } else {
                    $sms->status = $sms->attempts >= 3 ? 2 : 0;
                    $sms->error_message = trim((string) ($response['message'] ?? 'Error al enviar SMS'));
                    if ($sms->status === 2) {
                        $result['failed']++;
                    }
                }
                $sms->save();
            } catch (\Throwable $exception) {
                $sms->attempts = (int) $sms->attempts + 1;
                $sms->status = $sms->attempts >= 3 ? 2 : 0;
                $sms->error_message = $exception->getMessage();
                $sms->save();
                $result['processed']++;
                if ($sms->status === 2) {
                    $result['failed']++;
                }
            }
        }

        return $this->Notification_Response('Cola SMS procesada', ['data' => $result]);
    }
}