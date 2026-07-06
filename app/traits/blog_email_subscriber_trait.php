<?php 
namespace App\traits;

use Illuminate\Support\Str;

use App\Models\blog_email_subscriber;

trait blog_email_subscriber_trait
{
    public function BlogEmailSubscriber_UnsubscribeBlog(
        $unique_id
        ,$unsubscribe_reason
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error'
        ];
        try{
            $blog_email_subscriber = blog_email_subscriber::where('unique_id', $unique_id)->first();
            if($blog_email_subscriber){
                $blog_email_subscriber->is_active = 0;
                $blog_email_subscriber->unsubscribe_reason = $unsubscribe_reason;
                $blog_email_subscriber->save();
                $Response = [
                    'status' => 1,
                    'message' => 'Success'
                ];
            }else{
                $Response = [
                    'status' => 0,
                    'message' => 'Invalid unique_id'
                ];
            }
        }catch(\Exception $e){
            $Response = [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        
        return $Response;
    }
    public function BlogEmailSubscriber_SetBulkBlogEmailSubscriber(
        $emails
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error'
        ];
        try{
            foreach($emails as $email){
                $blog_email_subscriber = blog_email_subscriber::where('email', $email['email'])->first();
                if($blog_email_subscriber){
                    $blog_email_subscriber->is_active = $email['is_active'];
                }else{
                    $blog_email_subscriber = new blog_email_subscriber();
                    $blog_email_subscriber->email = $email['email'];
                    $blog_email_subscriber->unique_id = Str::uuid()->toString();
                    $blog_email_subscriber->is_active = $email['is_active'];
                }
                $blog_email_subscriber->save();
            }
            $Response = [
                'status' => 1,
                'message' => 'Success'
            ];
        }catch(\Exception $e){
            $Response = [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        
        return $Response;
    }
}