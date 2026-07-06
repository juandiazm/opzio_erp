<?php 
namespace App\traits;

use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\mail_log;
use App\Models\mail_log_attachment;


trait mail_log_trait
{
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
        $error_message = null
    ){
        try{
            if($unique_id == null){
                //Add new log
                $mail_log = new mail_log();
                $mail_log->unique_id = strtoupper(Str::uuid()->toString());
                $mail_log->subject = $subject;
                $mail_log->view = $view;
                $mail_log->from = $from;
                $mail_log->as = $as;
                $mail_log->to = json_encode($to);
                $mail_log->bcc = $bcc;
                $mail_log->mail_data = json_encode($mail_data);
                $mail_log->status = $status;
                $mail_log->error_message = $error_message==''?null:$error_message;
                if($status == 1)$mail_log->sent_at = Carbon::now();
                $mail_log->save();
                if($attachments != null){
                    foreach($attachments as $attachment){
                        /*$mail_log_attachment = new mail_log_attachment();
                        $mail_log_attachment->mail_log_id = $mail_log->id;
                        $mail_log_attachment->path = $attachment['path'];
                        $mail_log_attachment->name = $attachment['name'];
                        $mail_log_attachment->save();*/
                    }
                }

            }else{
                //Update log
                $mail_log = mail_log::where('unique_id', $unique_id)->first();
                if($mail_log){
                    $mail_log->status = $status;
                    $mail_log->attemps = $mail_log->attemps + 1;
                    $mail_log->error_message = $error_message==''?null:($mail_log->error_message.'\n'.$error_message);
                    if($status == 1)$mail_log->sent_at = Carbon::now();
                    $mail_log->save();
                }
                
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
            $mail_logs = mail_log::where('status', 0)->where('attemps', '<', 3)->get();
            foreach($mail_logs as $mail_log){
                $mail_log->mail_data = json_decode($mail_log->mail_data, true);
                $mail_log->to = json_decode($mail_log->to, true);
                $attachments = mail_log_attachment::where('mail_log_id', $mail_log->id)->get();
                $files = [];
                foreach($attachments as $attachment){
                    $files[] = [
                        'path' => $attachment->path,
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