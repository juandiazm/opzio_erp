<?php 
namespace App\traits;

use App\Models\instagram_post;
use App\Models\service;
use App\Models\instagram_subject;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;


trait instagram_trait
{
    use 
    open_ia_trait
    ,instagram_api_trait
    ,freepik_trait
    ;
    /*Add an instragram post using IA*/
    public function Instagram_AddIAFeedPost(
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
                ,'caption' => null
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
                $subject = instagram_subject::inRandomOrder()->first();
                $subject = $subject->name;
            }
            $content['subject'] = $subject;
            /*generate caption*/
            $Response = $this->OpenIA_MakeQuestionToAssistant(
                $this->ES_INSTAGRAM_ASSISTANT_ID
                , $content['thread_id']
                , 'Genera un caption para una publicación en Instagram en español, enfocado en el tema: "'.$subject.'". La publicación debe ser creada desde la perspectiva de una empresa de desarrollo de software llamada "Opzio Software Developers". El tono debe ser innovador y persuasivo, buscando captar la atención y generar interés. El objetivo es provocar una necesidad en los lectores, incentivando la interacción y los clics en la publicación.'
                , 5
                , 5
            );
            if($Response['status']==1){
                $content['caption'] = $Response['data'][0];
            }
            /*select if freepick image or open ia image random*/
            $imageType = rand(0,1);
            /*generate image*/
            if($imageType == 0){
                //not in used until test freepick image
                //open IA image
                $Response = $this->OpenIA_GenerateImage(
                    'Create an image focused on: "' . $subject . '". The style should be hyper-realistic, using primarily blue and gray tones. Maintain color consistency without drastic changes or saturation. The image should be clean and simple. Do not include any text or words.'
                );
                if($Response['status']==1){
                    $image_url =  collect($Response['data']['data'][0])['url'];
                    /*store image from url*/
                    $file_format = 'webp';
                    $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                    $img = Image::make($image_url)->encode('webp', 90);
                    Storage::disk('instagram_post_images')->put($uid, $img->stream());
                    ImageOptimizer::optimize(Storage::disk('instagram_post_images')->path($uid));
                    $content['image_url'] = Storage::disk('instagram_post_images')->url($uid);
                }
            }else{
                //freepick image
                $personImage = rand(0, 1);
                if($personImage == 0){
                    //generate a image prompt base on open ia prompt 
                    $IAPrompt =  $this->OpenIA_MakeQuestion(
                        'Create a hyper-realistic image focused on the theme of "'.$subject.'". The composition should be minimalistic with only a few key elements, using a color palette dominated by shades of blue and gray. The atmosphere should feel modern, sleek, and slightly futuristic, with soft lighting and a sense of calm sophistication. Avoid including any text in the image.'    
                    );
                    if($IAPrompt['status']==1){
                        $IAPrompt = $IAPrompt['data'][0];
                        $FreepickImage = $this->Freepik_GenerateImage(
                            $IAPrompt
                            ,'no text'
                            ,20
                            ,1
                            ,null
                            ,1
                            ,'squeare'
                            ,'vector'
                            ,'dramatic'
                            ,'volumetric'
                            ,'first-person'
                        );
                        if($FreepickImage['status']==1){
                            $content['image_url'] = $FreepickImage['data'];
                        }
                    }
                }else{
                    $IAPrompt = 'Create a hyper-realistic image focused on the theme of "'.$subject.'". The scene should be minimalistic with only a few elements. Include a friendly-looking person in the foreground, with a warm smile and relaxed demeanor. The lighting should be soft and warm, casting gentle shadows to create a cozy atmosphere. The background should be simple and unobtrusive to keep the focus on the subject. The overall mood should feel inviting, positive, and inspiring, ideal for an Instagram post.';
                    $FreepickImage = $this->Freepik_GenerateImage(
                        $IAPrompt
                        ,'no text'
                        ,8
                        ,1
                        ,null
                        ,1
                        ,'squeare'
                        ,'vector'
                        ,'dramatic'
                        ,'volumetric'
                        ,'first-person'
                    );
                    if($FreepickImage['status']==1){
                        $content['image_url'] = $FreepickImage['data'];
                    }
                    
                }
            }
            /*save post*/
            $post = new instagram_post();
            $post->unique_id = Str::uuid()->toString();
            $post->user_id = null;
            $post->user_name = 'Opzio Software Developers';
            $post->subject = $subject;
            $post->caption = $content['caption'];
            $post->image_url = $content['image_url'];
            $post->is_carousel_item = 0;
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
            info('Instagram_AddIAPost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Instagram_ApprovePost(
        $unique_id
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $post = instagram_post::where('unique_id', $unique_id)->where('authorized', 0)->first();
            if($post == null){
                $Response['message'] = 'Post not found';
                return $Response;
            }
            /*----------------------------*/
            $publishResponse = null;
            switch($post->media_type){
                case null:
                    $publishResponse = $this->Instagram_PublishInstagramFeedPost(
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
            info('Instagram_ApprovePost error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Instagram_PublishInstagramFeedPost($post){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $url = env('INSTAGRAM_API_USER_ID').'/media';
            $params = [
                'access_token' => env('INSTAGRAM_API_ACCESS_TOKEN')
                ,'image_url' => $post->image_url/*'https://erp.opzio.co/storage/blog/principal/21f64d1f_09ff_4f6f_aaa2_1b21539517a5.webp'*/
                ,'caption' => $post->caption
            ];
            $instagramResponse = $this->InstagramAPI_PostRequest($url, $params, []);
            if($instagramResponse['status']==1){
                $post->ig_container_id = $instagramResponse['data']['id'];
                $url = env('INSTAGRAM_API_USER_ID').'/media_publish';
                $params = [
                    'access_token' => env('INSTAGRAM_API_ACCESS_TOKEN')
                    ,'creation_id' => $post->ig_container_id
                ];
                $instagramResponse = $this->InstagramAPI_PostRequest($url, $params, []);
                if($instagramResponse['status']==1){
                    $Response['status'] = 1;
                    $Response['message'] = 'Success';
                    $Response['data'] = $post;
                }else{
                    $Response['message'] = $instagramResponse['message'];
                }
            }else{
                $Response['message'] = $instagramResponse['message'];
            } 
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Instagram_PublishInstagramFeedPost error: '. $e->getMessage());
        }
        return $Response;
    }
    /*subjects*/
    public function Instagram_SetSubjectInstagrams($subjects){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            foreach($subjects as $subject){
                $Subject = instagram_subject::where('name', $subject)->first();
                if(!$Subject){
                    $Subject = new instagram_subject();
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
    public function Instagram_GetSubjectInstagram(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subjects = instagram_subject::all();
            $Response['status'] = 1;
            $Response['message'] = 'Temas obtenidos correctamente';
            $Response['data'] = $Subjects;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Instagram_DeleteSubjectInstagram(
        $unique_id
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subject = instagram_subject::where('unique_id', $unique_id)->first();
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