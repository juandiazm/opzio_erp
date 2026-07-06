<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\arl_trait;

class arl_controller extends Controller
{
    use arl_trait;
    public function get_arl(Request $request){
        $Response = $this->ARL_GetARL();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_arl(Request $request){
        $Response = $this->ARL_AddARL($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_arl(Request $request){
        $Response = $this->ARL_UpdateARL($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_arl(Request $request){
        $Response = $this->ARL_DeleteARL($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
