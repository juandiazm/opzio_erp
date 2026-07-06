<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\traits\blog_trait;
use App\traits\mail_trait;
use App\traits\facebook_trait;
use App\traits\linkedin_trait;
use App\traits\twitter_trait;

class blog_controller extends Controller
{
    use 
    blog_trait
    , facebook_trait
    , linkedin_trait
    , twitter_trait
    , mail_trait
    ;
    function add_blog(Request $request){
        $Response = $this->Blog_AddBlog(
            $request->title
            , $request->url
            , $request->brief
            , $request->author
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function add_blog_by_ia(Request $request){
        set_time_limit(0);
        $Response = $this->Blog_AddBlogByIA($request->title);
        if($Response['status']==1){
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
            set_time_limit(60);
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_page_blog(Request $request){
        $Response = $this->Blog_GetPageBlog(
            $request->lang,
            $request->pagination
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function edit_blog(Request $request){
        $Response = $this->Blog_EditBlog(
            $request->id
            , $request->title
            , $request->url
            , $request->brief
            , $request->author
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function delete_blog(Request $request){
        $Response = $this->Blog_DeleteBlog(
            $request->id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function change_principal_image_blog(Request $request){
        $Response = $this->Blog_ChangePrincipalImageBlog(
            $request->id
            , $request->img
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_principal_information_blog(Request $request){
        $Response = $this->Blog_GetPrincipalInformationBlog(
            $request->id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_blog_by_url(Request $request){
        $Response = $this->Blog_GetBlogByUrl(
            $request->url
        );
        return $Response;
    }
    function add_segment_blog(Request $request){
        $Response = $this->Blog_AddSegmentBlog(
            $request->blog_id
            , $request->title
            , $request->title_position
            , $request->paragraph
            , $request->img
            , $request->img_position
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_segment_blog(Request $request){
        $Response = $this->Blog_GetBlogSegments(
            $request->blog_id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function up_position_segment_blog(Request $request){
        $Response = $this->Blog_UpPositionSegmentBlog(
            $request->id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function down_position_segment_blog(Request $request){
        $Response = $this->Blog_DownPositionSegmentBlog(
            $request->id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function delete_segment_blog(Request $request){
        $Response = $this->Blog_DeleteSegmentBlog(
            $request->id
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function edit_segment_blog(Request $request){
        $Response = $this->Blog_EditSegmentBlog(
            $request->id
            , $request->title
            , $request->title_position
            , $request->paragraph
            , $request->img
            , $request->img_position
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_last_blog(Request $request){
        $Response = $this->Blog_GetLastBlog();
        return $Response;
    }
    function approve_blog(Request $request){
        $Response = $this->Blog_ApproveBlog(
            $request->unique_id
            ,$request->send_to_subscribers
        );
        if($Response['status']==1){
            $Blog = $Response['data'];
            if($request->send_to_facebook == 'true'){
                $FBResponse = $this->Facebook_AddPost(
                    null
                    , $Blog['author']
                    , $Blog['reduce_title']
                    , $Blog['brief']
                    , env('APP_HOME_PAGE_URL').'blogs/view/'.$Blog['url']
                    , 1
                );
                if($FBResponse['status']==1){
                    $FBPost = $FBResponse['data'];
                    $FBResponse = $this->Facebook_PublishLinkPost($FBPost);
                    
                }
                $Response['data']['facebook'] = $FBResponse;
            }
            if($request->send_to_linkedin == 'true'){
                $LIResponse = $this->LinkedIn_AddPost(
                    null
                    , $Blog['author']
                    , $Blog['reduce_title']
                    , $Blog['brief']
                    , Storage::disk('blog_principal_images')->path($Blog['img'])
                    , 1
                    , env('APP_HOME_PAGE_URL').'blogs/view/'.$Blog['url']
                );
                if($LIResponse['status']==1){
                    $LIPost = $LIResponse['data'];
                    $LIResponse = $this->LinkedIn_PublishLinkedInFeedPost($LIPost);
                }
                $Response['data']['linkedin'] = $LIResponse;
            }
            if($request->send_to_twitter == 'true'){
                $TwitterResponse = $this->Twitter_AddPost(
                    null
                    , $Blog['author']
                    , $Blog['reduce_title']
                    , $Blog['reduce_title']
                    , Storage::disk('blog_principal_images')->path($Blog['img'])
                    , 1
                    , env('APP_HOME_PAGE_URL').'blogs/view/'.$Blog['url']
                );
                if($TwitterResponse['status']==1){
                    $TwitterPost = $TwitterResponse['data'];
                    $TwitterResponse = $this->Twitter_PublishTwitterFeedPost($TwitterPost);
                }
            }
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    /*Subjects*/
    function set_subject_blogs(Request $request){
        $Response = $this->Blog_SetSubjectBlogs(
            $request->subjects
        );
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    function get_subject_blog(Request $request){
        $Response = $this->Blog_GetSubjectBlog();
        return $Response;
    }
    function delete_subject_blog(Request $request){
        $Response = $this->Blog_DeleteSubjectBlog(
            $request->unique_id
        );
        return $Response;
    }
}
