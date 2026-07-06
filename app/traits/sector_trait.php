<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\sector;

trait sector_trait
{
    
    #region bus brands
    public function Sector_GetSector(){
        
        try{
            if(Session::has('sector')){
                $sector = Session::get('sector');
            }else{
                $sector = sector::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'Sector obtenidas correctamente.',
                'data' => $sector
            ];
        }catch(\Exception $e){
            info('Sector_GetSector error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sector_AddSector($name){
        try{
            $sector = new sector();
            $sector->name = $name;
            $sector->save();
            Session::forget('sector');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $sector
            ];
        }catch(\Exception $e){
            info('Sector_AddSector error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sector_UpdateSector(
        $id,
        $name
    ){
        try{
            $sector = sector::find($id);
            if($sector == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $sector->name = $name;
            $sector->save();
            Session::forget('sector');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $sector
            ];
        }catch(\Exception $e){
            info('Sector_UpdateSector error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Sector_DeleteSector(
        $id
    ){
        try{
            $sector = sector::find($id);
            if($sector == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $sector->delete();
            Session::forget('sector');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $sector
            ];
        }catch(\Exception $e){
            info('Sector_DeleteSector error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}