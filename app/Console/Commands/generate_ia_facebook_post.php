<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\traits\facebook_trait;
use App\traits\mail_trait;

class generate_ia_facebook_post extends Command
{
    use 
    facebook_trait
    ,mail_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:generate_ia_facebook_post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ia facebook post';

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
        $Response = $this->Facebook_AddIAFeedPost(null);
        if($Response['status']==1){
            $post = $Response['data'];
            /*send approve mail*/
            $Mails = [];
            $Mails[] = [
                'address' => 'mariaf.franco@ridder.com.co',
                'name' => 'mariaf.franco@ridder.com.co'
            ];
            $Mails[] = [
                'address' => 'comunicaciones@ridder.com.co',
                'name' => 'comunicaciones@ridder.com.co'
            ];
            /*
            $Mails[] = [
                'address' => 'daniel.mr@ridder.com.co',
                'name' => 'daniel.mr@ridder.com.co'
            ];
            
            $Mails[] = [
                'address' => 'nelsonsanchez.cons@outlook.com',
                'name' => 'nelsonsanchez.cons@outlook.com'
            ];
            $Mails[] = [
                'address' => 'jasoncontacto@gmail.com',
                'name' => 'jasoncontacto@gmail.com'
            ];*/
            $MailData = 
            [
                'subject' => 'FACEBOOK Post (aprobar): '. $post['subject'],
            ];
            $View = 'mail.approve_ia_facebook_post';
            $ViewData = collect($post);
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
            set_time_limit(60);
            return $Response;
            
        }else{
            return \Response::json($Response , 400);
        }
        return 0;
    }
}
