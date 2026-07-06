<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\service_trait;

class service_controller extends Controller
{
    use service_trait;
    public function get_service(Request $request){
        $Response = $this->Service_GetService();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_service(Request $request){
        $Response = $this->Service_AddService($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_service(Request $request){
        $Response = $this->Service_UpdateService($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_service(Request $request){
        $Response = $this->Service_DeleteService($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
