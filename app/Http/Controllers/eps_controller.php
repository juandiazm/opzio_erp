<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\eps_trait;

class eps_controller extends Controller
{
    use eps_trait;
    public function get_eps(Request $request){
        $Response = $this->EPS_GetEPS();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_eps(Request $request){
        $Response = $this->EPS_AddEPS($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_eps(Request $request){
        $Response = $this->EPS_UpdateEPS($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_eps(Request $request){
        $Response = $this->EPS_DeleteEPS($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
