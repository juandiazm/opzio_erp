<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\service;

trait service_trait
{
    
    #region bus brands
    public function Service_GetService(){
        
        try{
            if(Session::has('service')){
                $service = Session::get('service');
            }else{
                $service = service::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'Service obtenidas correctamente.',
                'data' => $service
            ];
        }catch(\Exception $e){
            info('Service_GetService error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Service_AddService($name){
        try{
            $service = new service();
            $service->name = $name;
            $service->save();
            Session::forget('service');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $service
            ];
        }catch(\Exception $e){
            info('Service_AddService error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Service_UpdateService(
        $id,
        $name
    ){
        try{
            $service = service::find($id);
            if($service == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $service->name = $name;
            $service->save();
            Session::forget('service');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $service
            ];
        }catch(\Exception $e){
            info('Service_UpdateService error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Service_DeleteService(
        $id
    ){
        try{
            $service = service::find($id);
            if($service == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $service->delete();
            Session::forget('service');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $service
            ];
        }catch(\Exception $e){
            info('Service_DeleteService error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}