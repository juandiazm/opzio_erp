<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\freepik_trait;

class freepik_controller extends Controller
{
    use freepik_trait;
    //
    public function generate_image(Request $request){
        $Response = $this->Freepik_GenerateImage(
            $request->prompt
            ,$request->negative_prompt
            ,$request->num_inference_steps
            ,$request->guidance_scale
            ,$request->seed
            ,$request->num_images
            ,$request->image_size
            ,$request->styling_style
            ,$request->styling_color
            ,$request->styling_lightning
            ,$request->styling_framing
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
