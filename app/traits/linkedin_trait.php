<?php 
namespace App\traits;

use App\Models\linkedin_post;
use App\Models\service;
use App\Models\linkedin_subject;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;


trait linkedin_trait
{
    use 
    open_ia_trait
    ,linkedin_api_trait
    ;
    /*Add an instragram post using IA*/
    public function LinkedIn_AddIAMainFeedPost(
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
                $subject = linkedin_subject::inRandomOrder()->first();
                $subject = $subject->name;
            }
            $content['subject'] = $subject;
            /*generate caption*/
            $Response = $this->OpenIA_MakeQuestionToAssistant(
                $this->ES_LINKEDIN_ASSISTANT_ID
                , $content['thread_id']
                , 'Redacta un párrafo en español (máximo 300 caracteres) sobre "'.$subject.'", escrito desde la perspectiva de una empresa de desarrollo de software llamada "Opzio Software Developers". El texto debe ser innovador, persuasivo y diseñado para despertar una necesidad en el lector, incentivando los clics hacia su página [Enlace]. Evita repetir temas y mantén el contenido fresco y atractivo.'
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
                'Create an image on the theme: "' . $content['subject'] . '". Focus on pastel colors with an emphasis on blue shades. The style should combine minimalistic line art with a subtle three-dimensional effect. Keep the background white, and ensure the image is clean and simple. Do not include any text or letters in the image.'
            );
            if($Response['status']==1){
                $image_url =  collect($Response['data']['data'][0])['url'];
                /*store image from url*/
                $file_format = 'webp';
                $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                $img = Image::make($image_url)->encode('webp', 90);
                Storage::disk('linkedin_post_images')->put($uid, $img->stream());
                ImageOptimizer::optimize(Storage::disk('linkedin_post_images')->path($uid));
                $content['image_url'] = $uid;
            }
            /*save post*/
            $post = new linkedin_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = null;
            $post->user_name = 'Opzio Software Developers';
            $post->subject = $subject;
            $post->message = $content['message'];
            $post->image_url = $content['image_url'];
            $post->media_type = 'MAIN_FEED';
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
            info('LinkedIn_AddIAPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function LinkedIn_ApprovePost(
        $unique_id
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = linkedin_post::where('unique_id', $unique_id)->where('authorized', 0)->first();
            if($post == null){
                $Response['message'] = 'Post not found';
                return $Response;
            }
            /*----------------------------*/
            $publishResponse = null;
            switch($post->media_type){
                case 'MAIN_FEED':
                    $publishResponse = $this->LinkedIn_PublishLinkedInFeedPost(
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
            info('LinkedIn_ApprovePost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function LinkedIn_PublishLinkedInFeedPost($post){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $url = '/rest/images';
            $headers = [
                'Authorization' => 'Bearer '.env('LINKEDIN_API_ACCESS_TOKEN')
            ];
            $params = [
                'action' => 'initializeUpload'
            ];
            $body = [
                "initializeUploadRequest" => [
                    "owner" => env('LINKEDIN_API_USER_ID')
                ]
            ];
            $linkedinResponse = $this->LinkedInAPI_PostRequest($url, $headers, $params, $body);
            if($linkedinResponse['status']==1){
                $url = $linkedinResponse['data']['value']['uploadUrl'];
                $image_id = $linkedinResponse['data']['value']['image'];
                //get params from url
                $url_params = explode('?', $url);
                $url = $url_params[0];
                $url_params = $url_params[1];
                $headers = [
                    'Authorization' => 'Bearer '.env('LINKEDIN_API_ACCESS_TOKEN')
                ];
                $body = [
                    [
                        "name" => "file",
                        "contents" => fopen(Storage::disk('linkedin_post_images')->path($post->image_url), 'r'),
                        "filename" => $post->image_url
                    ]
                ];
                $linkedinResponse = $this->LinkedInAPI_PutRequest('', $headers, $url_params, $body, $url);
                if($linkedinResponse['status']==1){
                    $url = '/rest/posts';
                    $headers = [
                        'Authorization' => 'Bearer '.env('LINKEDIN_API_ACCESS_TOKEN')
                    ];
                    $params = [];
                    $body = [
                        "author" => env('LINKEDIN_API_USER_ID'),
                        "commentary" => $post->message,
                        "visibility" => "PUBLIC",
                        "distribution" => [
                            "feedDistribution" =>  "MAIN_FEED",
                            "targetEntities" =>  [],
                            "thirdPartyDistributionChannels" =>  []
                        ],
                        "content" =>  [
                            "media" =>  [
                                "title" => $post->subject,
                                "id" =>  $image_id
                            ]
                        ],
                        "lifecycleState" => "PUBLISHED",
                        "isReshareDisabledByAuthor" => false
                    ];
                    if($post->link != null){
                        $body['content'] = [
                            "article" => [
                                "source" => $post->link,
                                "thumbnail" => $image_id,
                                "title" => $post->subject,
                                "description" => $post->message
                            ]
                            
                        ];
                    }else{
                        $body['content'] = [
                            "media" => [
                                "title" => $post->subject,
                                "id" =>  $image_id
                            ]
                        ];
                    }
                    $linkedinResponse = $this->LinkedInAPI_PostRequest($url, $headers, $params, $body);
                    if($linkedinResponse['status'] == 1){
                        $Response['status'] = 1;
                        $Response['message'] = 'Success';
                        $Response['data'] = $post;
                    }else{
                        $Response['message'] = $linkedinResponse['message'];
                    }
                }else{
                    $Response['message'] = $linkedinResponse['message'];
                }
            }else{
                $Response['message'] = $linkedinResponse['message'];
            } 
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('LinkedIn_PublishLinkedInFeedPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function LinkedIn_AddPost(
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
            $post = new linkedin_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = $user_id;
            $post->user_name = $user_name;
            $post->subject = $subject;
            $post->message = $message;
            if($image_url != null){
                $getImage = file_get_contents($image_url);
                $uid = Str::uuid()->toString().'.webp';
                Storage::disk('linkedin_post_images')->put($uid, $getImage);
                ImageOptimizer::optimize(Storage::disk('linkedin_post_images')->path($uid));
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
            info('LinkedIn_AddPost error: '. $e->getMessage());
        }
        return $Response;
    }
    /*subjects*/
    public function LinkedIn_SetSubjectLinkedIns($subjects){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            foreach($subjects as $subject){
                $Subject = linkedin_subject::where('name', $subject)->first();
                if(!$Subject){
                    $Subject = new linkedin_subject();
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
    public function LinkedIn_GetSubjectLinkedIn(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subjects = linkedin_subject::all();
            $Response['status'] = 1;
            $Response['message'] = 'Temas obtenidos correctamente';
            $Response['data'] = $Subjects;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function LinkedIn_DeleteSubjectLinkedIn(
        $unique_id
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subject = linkedin_subject::where('unique_id', $unique_id)->first();
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