<?php

namespace App\Console\Commands;

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
            $Response = $this->MailLog_GetQueuedMails();
            if($Response['status'] == 1){
                foreach($Response['data'] as $mail){
                    $MailResponse = $this->SendMail_attach_array(
                        [
                            'subject' => $mail['subject']
                        ]
                        , $mail['to']
                        , $mail['view']
                        , $mail['mail_data']
                        , $mail['attachments']
                        , $mail['unique_id']
                    );
                }
            }
        }catch(\Exception $e){
            info('send_queued_mails error: '.$e->getMessage());
        }
        return 1;
    }
}
