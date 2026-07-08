<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\traits\twitter_trait;
use App\traits\mail_trait;

class generate_ia_twitter_post extends Command
{
    use 
    twitter_trait
    ,mail_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:generate_ia_twitter_post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ia twitter post';

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
        $Response = $this->Twitter_AddIAMainFeedPost(null);
        if($Response['status']==1){
            $post = $Response['data'];
            /*send approve mail*/
            $Mails = [];
            $Mails[] = [
                'address' => 'mariaf.franco@opzio.com.co',
                'name' => 'mariaf.franco@opzio.com.co'
            ];
            $Mails[] = [
                'address' => 'info@opzio.co',
                'name' => 'info@opzio.co'
            ];
            /*$Mails[] = [
                'address' => 'juandiazm@opzio.com.co',
                'name' => 'juandiazm@opzio.com.co'
            ];*/
            $MailData = 
            [
                'subject' => 'TWITTER Post (aprobar): '. $post['subject'],
            ];
            $View = 'mail.approve_ia_twitter_post';
            $ViewData = collect($post);
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
            set_time_limit(60);
            return $Response;
        }else{
            info('Error: '.$Response['message']);
        }
        return 0;
    }
}
