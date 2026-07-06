<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\afp;

trait afp_trait
{
    
    #region bus brands
    public function AFP_GetAFP(){
        
        try{
            if(Session::has('afp')){
                $afp = Session::get('afp');
            }else{
                $afp = afp::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'AFP obtenidas correctamente.',
                'data' => $afp
            ];
        }catch(\Exception $e){
            info('AFP_GetAFP error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function AFP_AddAFP($name){
        try{
            $afp = new afp();
            $afp->name = $name;
            $afp->save();
            Session::forget('afp');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $afp
            ];
        }catch(\Exception $e){
            info('AFP_AddAFP error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function AFP_UpdateAFP(
        $id,
        $name
    ){
        try{
            $afp = afp::find($id);
            if($afp == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $afp->name = $name;
            $afp->save();
            Session::forget('afp');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $afp
            ];
        }catch(\Exception $e){
            info('AFP_UpdateAFP error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function AFP_DeleteAFP(
        $id
    ){
        try{
            $afp = afp::find($id);
            if($afp == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $afp->delete();
            Session::forget('afp');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $afp
            ];
        }catch(\Exception $e){
            info('AFP_DeleteAFP error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}