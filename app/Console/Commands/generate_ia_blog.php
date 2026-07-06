<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\traits\blog_trait;
use App\traits\mail_trait;

class generate_ia_blog extends Command
{
    use
    blog_trait
    ,mail_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:generate_ia_blog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a blog post for the IA blog';

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
            $Response = $this->Blog_AddBlogByIA(null);
            if($Response['status'] == 1){
                $Blog = $Response['data'];
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
                    'subject' => 'BLOG Post (aprobar): '. $Blog['blog']['reduce_title'],
                ];
                $View = 'mail.approve_ia_blog';
                $ViewData = collect($Blog);
                $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
            }
            return $Response['status'];
        }catch(\Exception $e){
            info('generate_ia_blog error: '.$e->getMessage());
            return 0;
        }
        
    }
}
