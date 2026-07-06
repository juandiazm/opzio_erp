<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\country_trait;

class country_controller extends Controller
{
    use country_trait;
    public function get_country(Request $request){
        $Response = $this->Country_GetCountry();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_country(Request $request){
        $Response = $this->Country_AddCountry($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_country(Request $request){
        $Response = $this->Country_UpdateCountry($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_country(Request $request){
        $Response = $this->Country_DeleteCountry($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
