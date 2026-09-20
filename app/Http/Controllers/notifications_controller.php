<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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