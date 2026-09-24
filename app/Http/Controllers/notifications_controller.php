<?php

namespace App\Http\Controllers;

use App\Exportable\contacts_directory;
use App\Imports\GenericImport;
use App\Models\whatsapp_message;
use App\Services\WhatsappMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Session;

use App\traits\notifications_trait;

class notifications_controller extends Controller
{
    use notifications_trait;

    private function response($response)
    {
        return $response['status'] == 1 ? $response : \Response::json($response, 400);
    }

    private function actorId()
    {
        return data_get(Session::get('user'), 'id');
    }

    public function get_clients()
    {
        return $this->response($this->Notification_GetClients());
    }

    public function get_emails(Request $request)
    {
        return $this->response($this->Notification_GetEmails($request->pagination, $request->search, $request->status, $request->date_from, $request->date_to));
    }

    public function get_sms(Request $request)
    {
        return $this->response($this->Notification_GetSms($request->pagination, $request->search, $request->status, $request->date_from, $request->date_to));
    }

    public function get_email(Request $request)
    {
        return $this->response($this->Notification_GetEmail($request->id));
    }

    public function get_sms_by_id(Request $request)
    {
        return $this->response($this->Notification_GetSmsById($request->id));
    }

    public function validate_sms_delivery(Request $request)
    {
        return $this->response($this->Notification_ValidateSmsDelivery($request->id));
    }

    public function add_email(Request $request)
    {
        return $this->response($this->Notification_CreateEmail($request->all(), $request->file('attachments', []), $this->actorId()));
    }

    public function add_sms(Request $request)
    {
        return $this->response($this->Notification_CreateSms($request->all(), $this->actorId()));
    }

    public function get_tags(Request $request)
    {
        return $this->response($this->NotificationContact_GetTags((bool) $request->with_trashed));
    }

    public function add_tag(Request $request)
    {
        return $this->response($this->NotificationContact_AddTag($request->all()));
    }

    public function update_tag(Request $request)
    {
        return $this->response($this->NotificationContact_UpdateTag($request->id, $request->all()));
    }

    public function delete_tag(Request $request)
    {
        return $this->response($this->NotificationContact_DeleteTag($request->id));
    }

    public function attach_contact_tag(Request $request)
    {
        return $this->response($this->NotificationContact_AttachTag($request->contact_id, $request->tag_id));
    }

    public function detach_contact_tag(Request $request)
    {
        return $this->response($this->NotificationContact_DetachTag($request->contact_id, $request->tag_id));
    }

    public function get_contact_directory_filters()
    {
        return $this->response($this->NotificationContact_GetDirectoryFilters());
    }

    public function get_contact_directory_page(Request $request)
    {
        return $this->response($this->NotificationContact_GetDirectoryPage($request->all()));
    }

    public function get_contact_message_context(Request $request)
    {
        return $this->response($this->NotificationContact_GetMessageContext($request->id));
    }

    public function export_contact_directory(Request $request)
    {
        $response = $this->NotificationContact_GetDirectoryExport($request->all());
        if (($response['status'] ?? 0) !== 1) {
            return $this->response($response);
        }

        return Excel::download(
            new contacts_directory($response['contacts']),
            'contactos-'.now()->format('Y-m-d_H-i').'.xlsx'
        );
    }

    public function import_contact_directory(Request $request)
    {
        $request->validate([
            'import-file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        if (!$request->file('import-file')->isValid()) {
            return response()->json([
                'status' => 0,
                'message' => 'El archivo subido es inválido o está corrupto.',
            ], 422);
        }

        try {
            $sheets = Excel::toCollection(new GenericImport(), $request->file('import-file'));
            $rows = $sheets->first() ?? collect();
            if ($rows->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'El archivo no contiene datos.',
                ], 422);
            }

            $headerRow = $rows->shift();
            $headers = [];
            foreach ($headerRow as $header) {
                $headers[] = $this->normalizeContactImportHeader($header);
            }
            if (!in_array('value', $headers, true)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'La plantilla debe incluir la columna Valor.',
                ], 422);
            }

            $mappedRows = $rows->map(function ($row) use ($headers): array {
                $values = is_object($row) ? $row->toArray() : (array) $row;
                $mapped = [];
                foreach ($headers as $index => $key) {
                    if ($key !== null) {
                        $mapped[$key] = $values[$index] ?? null;
                    }
                }
                return $mapped;
            })->filter(function (array $row): bool {
                foreach ($row as $value) {
                    if (trim((string) $value) !== '') {
                        return true;
                    }
                }
                return false;
            })->values()->all();

            return response()->json($this->NotificationContact_ImportDirectory($mappedRows));
        } catch (\Throwable $exception) {
            info('import_contact_directory error: '.$exception->getMessage());
            return response()->json([
                'status' => 0,
                'message' => 'No fue posible leer el archivo: '.$exception->getMessage(),
            ], 422);
        }
    }

    private function normalizeContactImportHeader($value): ?string
    {
        $header = Str::lower(Str::ascii(trim((string) $value)));
        $header = trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');

        return [
            'id' => 'id',
            'contact_id' => 'id',
            'contacto_id' => 'id',
            'nombre' => 'name',
            'name' => 'name',
            'valor' => 'value',
            'value' => 'value',
            'tipo' => 'type',
            'type' => 'type',
            'canales' => 'channels',
            'canal' => 'channels',
            'channels' => 'channels',
            'cliente_id' => 'client_id',
            'client_id' => 'client_id',
            'licencia_id' => 'license_id',
            'license_id' => 'license_id',
            'etiquetas' => 'tags',
            'etiqueta' => 'tags',
            'tags' => 'tags',
            'estado' => 'active',
            'status' => 'active',
            'activo' => 'active',
            'active' => 'active',
        ][$header] ?? null;
    }

    public function update_contact_directory(Request $request)
    {
        return $this->response($this->NotificationContact_UpdateDirectory($request->id, $request->all()));
    }

    public function add_contact_directory(Request $request)
    {
        return $this->response($this->NotificationContact_AddDirectory($request->all()));
    }

    public function toggle_contact_directory_status(Request $request)
    {
        return $this->response($this->NotificationContact_ToggleDirectoryStatus($request->id, $request->input('active')));
    }

    public function delete_contact_directory(Request $request)
    {
        return $this->response($this->NotificationContact_Delete($request->id));
    }

    public function resend_email(Request $request)
    {
        return $this->response($this->Notification_ResendEmail($request->id, $request->all(), $request->file('attachments', []), $this->actorId()));
    }

    public function change_email_status(Request $request)
    {
        return $this->response($this->Notification_ChangeEmailStatus($request->id, $request->status));
    }

    public function resend_sms(Request $request)
    {
        return $this->response($this->Notification_ResendSms($request->id, $request->all(), $this->actorId()));
    }

    public function get_whatsapp_conversations(Request $request)
    {
        return $this->response($this->Notification_GetWhatsappConversations($request->pagination, $request->search, $request->unread_only));
    }

    public function get_whatsapp_conversation(Request $request)
    {
        return $this->response($this->Notification_GetWhatsappConversation($request->id));
    }

    public function get_whatsapp_media($messageId, $mediaIndex)
    {
        $message = whatsapp_message::find((int) $messageId);
        if (!$message) {
            abort(404);
        }

        $mediaIndex = (int) $mediaIndex;
        $media = is_array($message->media) ? $message->media : [];
        $item = $mediaIndex >= 0 ? ($media[$mediaIndex] ?? null) : null;
        $storage = new WhatsappMediaStorage();
        $storagePath = is_array($item) ? trim((string) ($item['storage_path'] ?? '')) : '';

        if ($storage->isSafeStoragePath($storagePath)) {
            $filesystem = Storage::disk(WhatsappMediaStorage::DISK);
            if ($filesystem->exists($storagePath)) {
                $contentType = $storage->normalizeContentType($item['content_type'] ?? $filesystem->mimeType($storagePath));
                return response()->file($filesystem->path($storagePath), [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="'.basename($storagePath).'"',
                    'Cache-Control' => 'private, max-age=3600',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        $url = is_array($item) ? trim((string) ($item['url'] ?? '')) : '';
        if (!$storage->isTwilioMediaUrl($url)) {
            abort(404);
        }

        try {
            $providerResponse = Http::timeout(30)
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->get($url);
        } catch (\Throwable $exception) {
            info('get_whatsapp_media error: '.$exception->getMessage());
            abort(404);
        }

        if (!$providerResponse->successful()) {
            abort(404);
        }

        $contentType = strtolower(trim(explode(';', (string) $providerResponse->header('Content-Type', 'application/octet-stream'))[0]));
        if (in_array($contentType, ['text/html', 'application/xhtml+xml'], true)) {
            $contentType = 'application/octet-stream';
        }

        return response($providerResponse->body(), 200, [
            'Content-Type' => $contentType ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="whatsapp-'.$message->id.'-'.$mediaIndex.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function start_whatsapp_conversation(Request $request)
    {
        return $this->response($this->Notification_StartWhatsappConversation($request->all(), $this->actorId()));
    }

    public function send_whatsapp_message(Request $request)
    {
        return $this->response($this->Notification_SendWhatsappMessage($request->conversation_id, $request->all(), $this->actorId()));
    }

    public function mark_whatsapp_read(Request $request)
    {
        return $this->response($this->Notification_MarkWhatsappConversationRead($request->id));
    }

    public function get_whatsapp_unread_count()
    {
        return $this->response($this->Notification_GetWhatsappUnreadCount());
    }

    public function get_whatsapp_templates()
    {
        return $this->response($this->Notification_GetWhatsappTemplates());
    }

    public function add_whatsapp_template(Request $request)
    {
        return $this->response($this->Notification_CreateWhatsappTemplate($request->all()));
    }

    public function update_whatsapp_template(Request $request)
    {
        return $this->response($this->Notification_UpdateWhatsappTemplate($request->sid, $request->all()));
    }

    public function delete_whatsapp_template(Request $request)
    {
        return $this->response($this->Notification_DeleteWhatsappTemplate($request->sid));
    }

    public function submit_whatsapp_template(Request $request)
    {
        return $this->response($this->Notification_SubmitWhatsappTemplate($request->sid, $request->all()));
    }

    public function whatsapp_incoming_webhook(Request $request)
    {
        if (!$this->TwilioWhatsApp_ValidateWebhook($request, 'incoming')) {
            return response('Forbidden', 403);
        }

        try {
            $this->Notification_HandleWhatsappIncoming($request->all());
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Throwable $exception) {
            info('whatsapp_incoming_webhook error: '.$exception->getMessage());
            return response('Webhook processing failed', 500);
        }
    }

    public function whatsapp_status_webhook(Request $request)
    {
        if (!$this->TwilioWhatsApp_ValidateWebhook($request, 'status')) {
            return response('Forbidden', 403);
        }

        try {
            $this->Notification_HandleWhatsappStatus($request->all());
            return response('', 204);
        } catch (\Throwable $exception) {
            info('whatsapp_status_webhook error: '.$exception->getMessage());
            return response('Webhook processing failed', 500);
        }
    }
}