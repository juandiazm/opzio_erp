<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\eps;

trait eps_trait
{
    
    #region bus brands
    public function EPS_GetEPS(){
        
        try{
            if(Session::has('eps')){
                $eps = Session::get('eps');
            }else{
                $eps = eps::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'EPS obtenidas correctamente.',
                'data' => $eps
            ];
        }catch(\Exception $e){
            info('EPS_GetEPS error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function EPS_AddEPS($name){
        try{
            $eps = new eps();
            $eps->name = $name;
            $eps->save();
            Session::forget('eps');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $eps
            ];
        }catch(\Exception $e){
            info('EPS_AddEPS error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function EPS_UpdateEPS(
        $id,
        $name
    ){
        try{
            $eps = eps::find($id);
            if($eps == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $eps->name = $name;
            $eps->save();
            Session::forget('eps');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $eps
            ];
        }catch(\Exception $e){
            info('EPS_UpdateEPS error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function EPS_DeleteEPS(
        $id
    ){
        try{
            $eps = eps::find($id);
            if($eps == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $eps->delete();
            Session::forget('eps');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $eps
            ];
        }catch(\Exception $e){
            info('EPS_DeleteEPS error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}