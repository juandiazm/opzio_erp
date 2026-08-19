<?php 
namespace App\traits;

use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use App\Models\mail_log;
use App\Models\mail_log_attachment;


trait mail_log_trait
{
    private function MailLog_SaveAttachments($mail_log, $attachments)
    {
        if (!$attachments) {
            return;
        }

        if (is_array($attachments) && array_key_exists('path', $attachments)) {
            $attachments = [$attachments];
        }

        foreach ((array) $attachments as $attachment) {
            if (!is_array($attachment) || empty($attachment['path']) || empty($attachment['name'])) {
                continue;
            }

            $mail_log_attachment = new mail_log_attachment();
            $mail_log_attachment->mail_log_id = $mail_log->id;
            $mail_log_attachment->path = $attachment['path'];
            $mail_log_attachment->name = mb_substr((string) $attachment['name'], 0, 150);
            $mail_log_attachment->save();
        }
    }

    public function MailLog_CreatePending(
        $subject,
        $view,
        $from,
        $as,
        $to,
        $mail_data,
        $attachments = null,
        $send_at = null,
        $notification_batch = null
    ) {
        if (method_exists($this, 'Mail_GetSenderForView')) {
            $sender = $this->Mail_GetSenderForView($view, $from);
            $replyTo = $this->Mail_GetReplyTo();
            $from = $sender['address'];
            $as = $sender['name'];
            $mail_data = $this->Mail_AddEnvelopeMetadata($mail_data, $sender, $replyTo);
        }

        $mail_log = new mail_log();
        $mail_log->unique_id = strtoupper(Str::uuid()->toString());
        $mail_log->subject = $subject;
        $mail_log->view = $view;
        $mail_log->from = $from;
        $mail_log->as = $as;
        $mail_log->to = $to;
        $mail_log->bcc = null;
        $mail_log->mail_data = $mail_data;
        $mail_log->status = 0;
        $mail_log->send_at = $send_at;
        $mail_log->notification_batch = $notification_batch;
        $mail_log->save();
        $this->MailLog_SaveAttachments($mail_log, $attachments);

        return $mail_log;
    }

    //Add Email Log
    public function MailLog_SetLog(
        $unique_id,
        $subject,
        $view,
        $from,
        $as,
        $to,
        $bcc,
        $mail_data,
        $status,
        $attachments,
        $error_message = null,
        $send_at = null,
        $notification_batch = null
    ){
        try{
            $mail_log = $unique_id === null
                ? null
                : mail_log::where('unique_id', $unique_id)->first();

            if($mail_log === null){
                $mail_log = new mail_log();
                $mail_log->unique_id = strtoupper(Str::uuid()->toString());
                $mail_log->subject = $subject;
                $mail_log->view = $view;
                $mail_log->from = $from;
                $mail_log->as = $as;
                $mail_log->to = $to;
                $mail_log->bcc = $bcc;
                $mail_log->mail_data = $mail_data;
                $mail_log->status = $status;
                $mail_log->send_at = $send_at;
                $mail_log->notification_batch = $notification_batch;
                $mail_log->error_message = $error_message==''?null:$error_message;
                if($status == 1)$mail_log->sent_at = Carbon::now();
                $mail_log->save();
                $this->MailLog_SaveAttachments($mail_log, $attachments);

            }else{
                $mail_log->attemps = $mail_log->attemps + 1;
                $mail_log->status = $status == 1 ? 1 : ($mail_log->attemps >= 3 ? 2 : 0);
                $mail_log->error_message = $error_message==''?null:($mail_log->error_message ? $mail_log->error_message."\n".$error_message : $error_message);
                if($status == 1)$mail_log->sent_at = Carbon::now();
                $mail_log->save();
            }
        }catch(\Exception $e){
            info('MailLog_SetLog error: '.$e->getMessage());
        }
    }
    //Get queued mails
    public function MailLog_GetQueuedMails(){
        $Response = [
            'status' => 0,
            'message' => '',
            'data' => []
        ];
        try{
            $mail_logs = mail_log::where('status', 0)
                ->where('attemps', '<', 3)
                ->where(function ($query) {
                    $query->whereNull('send_at')->orWhere('send_at', '<=', Carbon::now());
                })
                ->orderBy('id')
                ->limit(100)
                ->get();
            foreach($mail_logs as $mail_log){
                $mail_log->mail_data = is_array($mail_log->mail_data)
                    ? $mail_log->mail_data
                    : (json_decode($mail_log->mail_data, true) ?: []);
                $mail_log->to = is_array($mail_log->to)
                    ? $mail_log->to
                    : (json_decode($mail_log->to, true) ?: []);
                $attachments = mail_log_attachment::where('mail_log_id', $mail_log->id)->get();
                $files = [];
                foreach($attachments as $attachment){
                    $path = $attachment->path;
                    if (!is_file($path) && Storage::disk('local')->exists($path)) {
                        $path = Storage::disk('local')->path($path);
                    }
                    $files[] = [
                        'path' => $path,
                        'name' => $attachment->name
                    ];
                }
                $mail_log->attachments = $files;
            }
            $Response ['status'] = 1;
            $Response ['data'] = $mail_logs;
        }catch(\Exception $e){
            info('MailLog_GetQueuedMails error: '.$e->getMessage());
        }
        return $Response;
    }
}