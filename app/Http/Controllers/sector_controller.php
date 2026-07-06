<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\sector_trait;

class sector_controller extends Controller
{
    use sector_trait;
    public function get_sector(Request $request){
        $Response = $this->Sector_GetSector();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_sector(Request $request){
        $Response = $this->Sector_AddSector($request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_sector(Request $request){
        $Response = $this->Sector_UpdateSector($request->id, $request->name);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_sector(Request $request){
        $Response = $this->Sector_DeleteSector($request->id);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
