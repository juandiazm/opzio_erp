<?php 
namespace App\traits;

use App\Models\facebook_post;
use App\Models\service;
use App\Models\facebook_subject;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;


trait facebook_trait
{
    use 
    open_ia_trait
    ,facebook_api_trait
    ;
    /*Add an instragram post using IA*/
    public function Facebook_AddIAFeedPost(
        $subject
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $content = [
                'subject' => null
                ,'thread_id' => null
                ,'message' => null
                ,'image_url' => null
            ];
            /*----------------------------*/
            $thread = $this->OpenIA_AddThread();
            if($thread['status']==0){
                $Response['message'] = $thread['message'];
                return $Response;
            }  
            $content['thread_id'] = $thread['data']['thread_id'];//Set the thread id
            if($subject == null || $subject == ''){
                $subject = facebook_subject::inRandomOrder()->first();
                $subject = $subject->name;
            }
            $content['subject'] = $subject;
            /*generate message*/
            $Response = $this->OpenIA_MakeQuestionToAssistant(
                $this->ES_FACEBOOK_ASSISTANT_ID
                , $content['thread_id']
                ,'Redacta un párrafo en español (máximo 300 caracteres) sobre "'.$subject.'", escrito desde la perspectiva de una empresa de desarrollo de software llamada "Opzio Software Developers". El texto debe ser innovador, persuasivo y diseñado para despertar una necesidad en el lector, incentivando los clics hacia su página [Enlace]. Evita repetir temas y mantén el contenido fresco y atractivo.'
                , 5
                , 5
            );
            if($Response['status']==1){
                $content['message'] = $Response['data'][0];
                /*if message containes [Enlace] replace it with the home page url*/
                /*check if the message contains [Enlace]*/
                if(strpos($content['message'], '[Enlace]') !== false){
                    $content['message'] = str_replace('[Enlace]', env('APP_HOME_PAGE_URL'), $content['message']);
                }else if(strpos($content['message'], '[enlace]') !== false){
                    $content['message'] = str_replace('[enlace]', env('APP_HOME_PAGE_URL'), $content['message']);
                }else if(strpos($content['message'], '[link]') !== false){
                    $content['message'] = str_replace('[link]', env('APP_HOME_PAGE_URL'), $content['message']);
                }else{
                    $content['message'] = $content['message']. ' '. env('APP_HOME_PAGE_URL');
                }
            }
            /*generate image*/
            $Response = $this->OpenIA_GenerateImage(
                'Create an image on the theme: "' . $content['subject'] . '". Use pastel colors focused on shades of blue and orange. The style should be minimalistic line art with only a few elements. Use a white background. Do not include any text or letters in the image.'
            );
            if($Response['status']==1){
                $image_url =  collect($Response['data']['data'][0])['url'];
                /*store image from url*/
                $file_format = 'webp';
                $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                $img = Image::make($image_url)->encode('webp', 90);
                Storage::disk('facebook_post_images')->put($uid, $img->stream());
                ImageOptimizer::optimize(Storage::disk('facebook_post_images')->path($uid));
                $content['image_url'] = Storage::disk('facebook_post_images')->url($uid);
            }
            /*save post*/
            $post = new facebook_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = null;
            $post->user_name = 'Opzio Software Developers';
            $post->subject = $content['subject'];
            $post->message = $content['message'];
            $post->link = null;
            $post->image_url = $content['image_url'];
            $post->save();
            /*----------------------------*/
            /*----------------------------*/
            /*----------------------------*/
            $Response['status'] = 1;
            $Response['message'] = 'Success';
            $Response['data'] = $post;
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Facebook_AddIAPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Facebook_AddPost(
        $user_id
        , $user_name
        , $subject
        , $message
        , $link
        , $authorized
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = new facebook_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = $user_id;
            $post->user_name = $user_name;
            $post->subject = $subject;
            $post->message = $message;
            $post->link = $link;
            $post->authorized = $authorized;
            if($authorized == 1){
                $post->published = 1;
                $post->published_at = Carbon::now();
            }
            $post->save();
            /*----------------------------*/
            $Response['status'] = 1;
            $Response['message'] = 'Success';
            $Response['data'] = $post;
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Facebook_AddPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Facebook_ApprovePost(
        $unique_id
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = facebook_post::where('unique_id', $unique_id)->where('authorized', 0)->first();
            if($post == null){
                $Response['message'] = 'Post not found';
                return $Response;
            }
            /*----------------------------*/
            
            $publishResponse = null;
            switch($post->image_url){
                case null:
                    $publishResponse = $this->Facebook_PublishLinkPost(
                        $post
                    );
                    break;
                default:
                    $publishResponse = $this->Facebook_PublishImagePost(
                        $post
                    );
                    break;
            }
            /*----------------------------*/
            if($publishResponse['status']==1){
                $post = $publishResponse['data'];
                $post->authorized = 1;
                $post->published = 1;
                $post->published_at = Carbon::now();
                $post->save();
                $Response['status'] = 1;
                $Response['message'] = 'Success';
                $Response['data'] = $post;
            }
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Facebook_ApprovePost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Facebook_PublishLinkPost($post){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $url = env('FACEBOOK_API_USER_ID').'/feed';
            $params = [
                'access_token' => env('FACEBOOK_API_ACCESS_TOKEN')
                ,'message' => $post->message/*'https://erp.opzio.co/storage/blog/principal/21f64d1f_09ff_4f6f_aaa2_1b21539517a5.webp'*/
                ,'link' => $post->link
            ];
            $facebookResponse = $this->FacebookAPI_PostRequest($url, $params, []);
            $Response['status'] = $facebookResponse['status'];
            $Response['message'] = $facebookResponse['message'];
            $Response['data'] = $post;
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Facebook_PublishLinkPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Facebook_PublishImagePost($post){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $url = env('FACEBOOK_API_USER_ID').'/photos';
            $params = [
                'access_token' => env('FACEBOOK_API_ACCESS_TOKEN')
                ,'message' => $post->message
                ,'url' => $post->image_url
            ];
            $facebookResponse = $this->FacebookAPI_PostRequest($url, $params, []);
            $Response['status'] = $facebookResponse['status'];
            $Response['message'] = $facebookResponse['message'];
            $Response['data'] = $post;
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Facebook_PublishImagePost error: '. $e->getMessage());
        }
        return $Response;
    }
    /*subjects*/
    public function Facebook_SetSubjectFacebooks($subjects){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            foreach($subjects as $subject){
                $Subject = facebook_subject::where('name', $subject)->first();
                if(!$Subject){
                    $Subject = new facebook_subject();
                    $Subject->unique_id = Str::uuid()->toString();
                    $Subject->name = $subject;
                    $Subject->save();
                }
            }
            $Response['status'] = 1;
            $Response['message'] = 'Temas agregados correctamente';
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Facebook_GetSubjectFacebook(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subjects = facebook_subject::all();
            $Response['status'] = 1;
            $Response['message'] = 'Temas obtenidos correctamente';
            $Response['data'] = $Subjects;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Facebook_DeleteSubjectFacebook(
        $unique_id
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subject = facebook_subject::where('unique_id', $unique_id)->first();
            if($Subject){
                $Subject->delete();
                $Response['status'] = 1;
                $Response['message'] = 'Tema eliminado correctamente';
            }else{
                $Response['message'] = 'Tema no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
}