<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\afp_trait;

class afp_controller extends Controller
{
    use afp_trait;
    public function get_afp(Request $request){
        $Response = $this->AFP_GetAFP();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_afp(Request $request){
        $Response = $this->AFP_AddAFP($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_afp(Request $request){
        $Response = $this->AFP_UpdateAFP($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_afp(Request $request){
        $Response = $this->AFP_DeleteAFP($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
