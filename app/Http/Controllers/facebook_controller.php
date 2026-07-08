<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\facebook_trait;
use App\traits\mail_trait;

class facebook_controller extends Controller
{
    use 
    facebook_trait
    ,mail_trait
    ;
    //
    public function add_ia_facebook_feed_post(Request $request){
        set_time_limit(0);
        $Response = $this->Facebook_AddIAFeedPost(
            $request->subject
        );
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
            /*
            $Mails[] = [
                'address' => 'daniel.mr@opzio.com.co',
                'name' => 'daniel.mr@opzio.com.co'
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
    }
    public function approve_facebook_post(Request $request){
        $Response = $this->Facebook_ApprovePost(
            $request->unique_id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    /*Subjects*/
    function set_subject_facebook(Request $request){
        $Response = $this->Facebook_SetSubjectFacebooks(
            $request->subjects
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_subject_facebook(Request $request){
        $Response = $this->Facebook_GetSubjectFacebook();
        return $Response;
    }
    function delete_subject_facebook(Request $request){
        $Response = $this->Facebook_DeleteSubjectFacebook(
            $request->unique_id
        );
        return $Response;
    }
}
