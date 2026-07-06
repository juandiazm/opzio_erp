<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\blog_email_subscriber_trait;

class blog_email_subscriber_controller extends Controller
{
    use blog_email_subscriber_trait;
    //
    public function unsubscribe_blog(Request $request){
        $Response = $this->BlogEmailSubscriber_UnsubscribeBlog(
            $request->unique_id
            ,$request->unsubscribe_reason
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function set_bulk_blog_email_subscriber(Request $request){
        $Response = $this->BlogEmailSubscriber_SetBulkBlogEmailSubscriber(
            $request->emails
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
