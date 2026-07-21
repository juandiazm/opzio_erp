<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\twitter_trait;
use App\traits\mail_trait;

class twitter_controller extends Controller
{
    use 
    twitter_trait
    ,mail_trait
    ;
    //
    public function add_ia_twitter_feed_post(Request $request){
        set_time_limit(0);
        $Response = $this->Twitter_AddIAMainFeedPost(
            $request->subject
        );
        if($Response['status']==1){
            $post = $Response['data'];
            /*send approve mail*/
            $Mails = [];
            $Mails[] = [
                'address' => 'mariaf.franco@opzio.co',
                'name' => 'mariaf.franco@opzio.co'
            ];
            $Mails[] = [
                'address' => 'info@opzio.co',
                'name' => 'info@opzio.co'
            ];
            /*$Mails[] = [
                'address' => 'juandiazm@opzio.co',
                'name' => 'juandiazm@opzio.co'
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
            return \Response::json($Response , 400);
        }
    }
    public function approve_twitter_post(Request $request){
        $Response = $this->Twitter_ApprovePost(
            $request->unique_id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    /*Subjects*/
    function set_subject_twitter(Request $request){
        $Response = $this->Twitter_SetSubjectTwitters(
            $request->subjects
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_subject_twitter(Request $request){
        $Response = $this->Twitter_GetSubjectTwitter();
        return $Response;
    }
    function delete_subject_twitter(Request $request){
        $Response = $this->Twitter_DeleteSubjectTwitter(
            $request->unique_id
        );
        return $Response;
    }
}
