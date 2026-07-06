<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\instagram_trait;
use App\traits\mail_trait;

class instagram_controller extends Controller
{
    use 
    instagram_trait
    ,mail_trait
    ;
    //
    public function add_ia_instagram_feed_post(Request $request){
        set_time_limit(0);
        $Response = $this->Instagram_AddIAFeedPost(
            $request->subject
        );
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
                'subject' => 'INSTAGRAM Post (aprobar): '. $post['subject'],
            ];
            $View = 'mail.approve_ia_instagram_post';
            $ViewData = collect($post);
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
            set_time_limit(60);
            return $Response;
            
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function approve_instagram_post(Request $request){
        $Response = $this->Instagram_ApprovePost(
            $request->unique_id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    /*Subjects*/
    function set_subject_instagram(Request $request){
        $Response = $this->Instagram_SetSubjectInstagrams(
            $request->subjects
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_subject_instagram(Request $request){
        $Response = $this->Instagram_GetSubjectInstagram();
        return $Response;
    }
    function delete_subject_instagram(Request $request){
        $Response = $this->Instagram_DeleteSubjectInstagram(
            $request->unique_id
        );
        return $Response;
    }
}
