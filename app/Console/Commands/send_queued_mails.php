<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

use App\traits\mail_log_trait;
use App\traits\mail_trait;

class send_queued_mails extends Command
{
    use 
    mail_log_trait
    ,mail_trait
    ;    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send_queued_mails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send queued mails';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $isSunday = Carbon::now(config('app.timezone'))->isSunday();
            $afterId = null;
            do {
                $Response = $afterId === null
                    ? $this->MailLog_GetQueuedMails()
                    : $this->MailLog_GetQueuedMails(100, $afterId);
                if($Response['status'] != 1){
                    break;
                }

                $queuedMails = $Response['data'];
                foreach($queuedMails as $mail){
                    if ($this->Mail_ShouldDeferExternalOnSunday($mail['mail_data'], $mail['to'])) {
                        continue;
                    }

                    $mailData = is_array($mail['mail_data']) ? $mail['mail_data'] : [];
                    $from = $mailData['_from'] ?? [
                        'address' => $mail['from'] ?? null,
                        'name' => $mail['as'] ?? null,
                    ];
                    $replyTo = $mailData['_reply_to'] ?? null;
                    $MailResponse = $this->SendMail_attach_array(
                        [
                            'subject' => $mail['subject']
                        ]
                        , $mail['to']
                        , $mail['view']
                        , $mail['mail_data']
                        , $mail['attachments']
                        , $mail['unique_id']
                        , $from
                        , $replyTo
                    );
                }

                if (!$isSunday || count($queuedMails) < 100) {
                    break;
                }
                $lastMail = collect($queuedMails)->last();
                $afterId = $lastMail ? $lastMail->id : null;
            } while ($afterId !== null);
        }catch(\Exception $e){
            info('send_queued_mails error: '.$e->getMessage());
            return 1;
        }
        return 0;
    }
}
