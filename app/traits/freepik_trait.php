<?php
namespace App\traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Session;

trait freepik_trait
{
    use freepik_api_trait;
    public function Freepik_GenerateImage(
        $prompt
        ,$negative_prompt
        ,$num_inference_steps
        ,$guidance_scale
        ,$seed
        ,$num_images
        ,$image_size
        ,$styling_style
        ,$styling_color
        ,$styling_lightning
        ,$styling_framing
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            $body = [
                'prompt' => $prompt
                ,'negative_prompt' => $negative_prompt
                ,'num_inference_steps' => $num_inference_steps
                ,'guidance_scale' => $guidance_scale
                ,'seed' => $seed
                ,'num_images' => $num_images
                ,'image' => [
                    'size' => $image_size
                ]
                ,'styling' => [
                    'style' => $styling_style
                    ,'color' => $styling_color
                    ,'lightning' => $styling_lightning
                    ,'framing' => $styling_framing
                ]
            ];
            $APIResponse = $this->Freepik_PostRequest('ai/text-to-image', [], $body);
            $Response = $APIResponse;
            if($APIResponse['status']==1){
                $base64 = $APIResponse['data']['data'][0]['base64'];
                $image = base64_decode($base64);
                $uid = Str::uuid()->toString();
                Storage::disk('freepik_post_images')->put($uid.'.png', $image);
                $Response['status'] = 1;
                $Response['message'] = 'Success';
                /*url to access the image*/
                $Response['data'] = Storage::disk('freepik_post_images')->url($uid.'.png');
                $Response['uid'] = $uid.'.png';
                
            }
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Freepik_GenerateImage error: '. $e->getMessage());
        }
        return $Response;
    }
    public function Freepik_RemoveImage($uid){
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try{
            Storage::disk('freepik_post_images')->delete($uid);
            $Response['status'] = 1;
            $Response['message'] = 'Success';
        }catch(\Exception $e){
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
            info('Freepik_RemoveImage error: '. $e->getMessage());
        }
        return $Response;
    }
}