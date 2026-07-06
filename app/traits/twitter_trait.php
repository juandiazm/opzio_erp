<?php 
namespace App\traits;

use App\Models\twitter_post;
use App\Models\service;
use App\Models\twitter_subject;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;


trait twitter_trait
{
    use 
    open_ia_trait
    ,twitter_api_trait
    ;
    /*Add an instragram post using IA*/
    public function Twitter_AddIAMainFeedPost(
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
                $subject = twitter_subject::inRandomOrder()->first();
                $subject = $subject->name;
            }
            $content['subject'] = $subject;
            /*generate caption*/
            $Response = $this->OpenIA_MakeQuestionToAssistant(
                $this->ES_LINKEDIN_ASSISTANT_ID
                , $content['thread_id']
                ,'Redacta un párrafo en español (máximo 200 caracteres) sobre "'.$subject.'", escrito desde la perspectiva de una empresa de desarrollo de software llamada "Opzio Software Developers". El texto debe ser innovador, persuasivo y diseñado para despertar una necesidad en el lector, incentivando los clics hacia su página [Enlace]. Evita repetir temas y mantén el contenido fresco y atractivo.'
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
                'Create an image focused on the theme: "' . $content['subject'] . '". Use pastel colors with a minimalist, realistic, and volumetric style. The image should be simple and clean, with soft lighting to create depth. Use a white background. Do not include any text or letters in the image.'
            );
            if($Response['status']==1){
                $image_url =  collect($Response['data']['data'][0])['url'];
                /*store image from url*/
                $file_format = 'webp';
                $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                $img = Image::make($image_url)->encode('webp', 90);
                Storage::disk('twitter_post_images')->put($uid, $img->stream());
                ImageOptimizer::optimize(Storage::disk('twitter_post_images')->path($uid));
                $content['image_url'] = $uid;
            }
            /*save post*/
            $post = new twitter_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = null;
            $post->user_name = 'Opzio Software Developers';
            $post->subject = $subject;
            $post->message = $content['message'];
            $post->image_url = $content['image_url'];
            $post->media_type = null;
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
            info('Twitter_AddIAPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Twitter_ApprovePost(
        $unique_id
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = twitter_post::where('unique_id', $unique_id)->where('authorized', 0)->first();
            if($post == null){
                $Response['message'] = 'Post not found';
                return $Response;
            }
            /*----------------------------*/
            $publishResponse = null;
            switch($post->media_type){
                case null:
                    $publishResponse = $this->Twitter_PublishTwitterFeedPost(
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
            info('Twitter_ApprovePost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Twitter_PublishTwitterFeedPost($post){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $url = 'https://upload.twitter.com/1.1/media/upload.json';
            $headers = [];
            $params = [];
            $body = [
                [
                    "name" => "media",
                    "contents" => fopen(Storage::disk('twitter_post_images')->path($post->image_url), 'r'),
                    "filename" => $post->image_url
                ],
                [
                  'name' => 'media_category',
                  'contents' => 'tweet_image'
                ]
            ];
            $twitterResponse = $this->TwitterAPI_PostRequestMultimedia('', $headers, $params, $body, $url, true);
            if($twitterResponse['status']==1){
                $url = 'https://api.twitter.com/2/tweets';
                $image_id = $twitterResponse['data']['media_id'];
                //get params from url
                $message = $post->message.($post->link != null ? ' '.$post->link : '');
                $body = [
                    "text" => $message,
                    "media" => [
                        "media_ids" => ["$image_id"]
                    ]
                ];
                $twitterResponse = $this->TwitterAPI_PostRequest('', [], [], $body, $url, true);
                if($twitterResponse['status'] == 1){
                    $Response['status'] = 1;
                    $Response['message'] = 'Success';
                    $Response['data'] = $post;
                }else{
                    $Response['message'] = $twitterResponse['message'];
                }
            }else{
                $Response['message'] = $twitterResponse['message'];
            } 
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Twitter_PublishTwitterFeedPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Twitter_AddPost(
        $user_id
        ,$user_name
        ,$subject
        ,$message
        ,$image_url
        ,$authorized
        ,$link
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = new twitter_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = $user_id;
            $post->user_name = $user_name;
            $post->subject = $subject;
            $post->message = $message;
            if($image_url != null){
                $getImage = file_get_contents($image_url);
                $uid = Str::uuid()->toString().'.webp';
                Storage::disk('twitter_post_images')->put($uid, $getImage);
                ImageOptimizer::optimize(Storage::disk('twitter_post_images')->path($uid));
                $post->image_url = $uid;
            }
            $post->authorized = $authorized;
            $post->link = $link;
            $post->save();
            $Response['status'] = 1;
            $Response['message'] = 'Success';
            $Response['data'] = $post;
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Twitter_AddPost error: '. $e->getMessage());
        }
        return $Response;
    }
    /*subjects*/
    public function Twitter_SetSubjectTwitters($subjects){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            foreach($subjects as $subject){
                $Subject = twitter_subject::where('name', $subject)->first();
                if(!$Subject){
                    $Subject = new twitter_subject();
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
    public function Twitter_GetSubjectTwitter(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subjects = twitter_subject::all();
            $Response['status'] = 1;
            $Response['message'] = 'Temas obtenidos correctamente';
            $Response['data'] = $Subjects;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Twitter_DeleteSubjectTwitter(
        $unique_id
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subject = twitter_subject::where('unique_id', $unique_id)->first();
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