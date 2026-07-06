<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\open_ia_trait;

class open_ia_controller extends Controller
{
    use open_ia_trait;
    public function open_ia_make_question(Request $request){
        $Response = $this->OpenIA_MakeQuestion($request->question);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_add_assistant(Request $request){
        $Response = $this->OpenIA_AddAssistant($request->instruction, $request->name);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_add_thread(Request $request){
        $Response = $this->OpenIA_AddThread();
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_get_messages(Request $request){
        $Response = $this->OpenIA_GetMessages($request->thread_id);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_add_message(Request $request){
        $Response = $this->OpenIA_AddMessage($request->thread_id, $request->role, $request->content);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_run_assistant(Request $request){
        $Response = $this->OpenIA_RunAssistant($request->assistant_id, $request->thread_id);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_get_run_assistant(Request $request){
        $Response = $this->OpenIA_GetRunAssistant($request->thread_id, $request->run_id);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function open_ia_generate_image(Request $request){
        $Response = $this->OpenIA_GenerateImage($request->message);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
}
