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
        return $this->response($this->Notification_GetEmails($request->pagination, $request->search, $request->status));
    }

    public function get_sms(Request $request)
    {
        return $this->response($this->Notification_GetSms($request->pagination, $request->search, $request->status));
    }

    public function get_email(Request $request)
    {
        return $this->response($this->Notification_GetEmail($request->id));
    }

    public function get_sms_by_id(Request $request)
    {
        return $this->response($this->Notification_GetSmsById($request->id));
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

    public function resend_sms(Request $request)
    {
        return $this->response($this->Notification_ResendSms($request->id, $request->all(), $this->actorId()));
    }
}