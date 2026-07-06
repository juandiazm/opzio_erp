<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\arl;

trait arl_trait
{
    
    #region bus brands
    public function ARL_GetARL(){
        
        try{
            if(Session::has('arl')){
                $arl = Session::get('arl');
            }else{
                $arl = arl::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'ARL obtenidas correctamente.',
                'data' => $arl
            ];
        }catch(\Exception $e){
            info('ARL_GetARL error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ARL_AddARL($name){
        try{
            $arl = new arl();
            $arl->name = $name;
            $arl->save();
            Session::forget('arl');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $arl
            ];
        }catch(\Exception $e){
            info('ARL_AddARL error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ARL_UpdateARL(
        $id,
        $name
    ){
        try{
            $arl = arl::find($id);
            if($arl == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $arl->name = $name;
            $arl->save();
            Session::forget('arl');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $arl
            ];
        }catch(\Exception $e){
            info('ARL_UpdateARL error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ARL_DeleteARL(
        $id
    ){
        try{
            $arl = arl::find($id);
            if($arl == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $arl->delete();
            Session::forget('arl');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $arl
            ];
        }catch(\Exception $e){
            info('ARL_DeleteARL error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}