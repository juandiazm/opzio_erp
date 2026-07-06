<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\admin_logs_trait;
use App\traits\pusher_trait;

class pusher_controller extends Controller
{
    use pusher_trait;
    public function get_pusher_data(Request $request){
        $Response = $this->Pusher_GetPusherJsonData();
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
}
