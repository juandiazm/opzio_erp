<?php
namespace App\traits;

use Session;
use \Carbon\Carbon;
use App\Models\country;

trait country_trait
{
    
    #region bus brands
    public function Country_GetCountry(){
        
        try{
            if(Session::has('country')){
                $country = Session::get('country');
            }else{
                $country = country::orderBy('name', 'asc')->get();
            }
            return [
                'status' => 1,
                'message' => 'Country obtenidas correctamente.',
                'data' => $country
            ];
        }catch(\Exception $e){
            info('Country_GetCountry error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Country_AddCountry($name){
        try{
            $country = new country();
            $country->name = $name;
            $country->save();
            Session::forget('country');
            return [
                'status' => 1,
                'message' => 'Ciudad agregada correctamente.',
                'data' => $country
            ];
        }catch(\Exception $e){
            info('Country_AddCountry error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Country_UpdateCountry(
        $id,
        $name
    ){
        try{
            $country = country::find($id);
            if($country == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $country->name = $name;
            $country->save();
            Session::forget('country');
            return [
                'status' => 1,
                'message' => 'Ciudad actualizada correctamente.',
                'data' => $country
            ];
        }catch(\Exception $e){
            info('Country_UpdateCountry error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Country_DeleteCountry(
        $id
    ){
        try{
            $country = country::find($id);
            if($country == null){
                return [
                    'status' => 0,
                    'message' => 'No se encontró la ciudad.'
                ];
            }
            $country->delete();
            Session::forget('country');
            return [
                'status' => 1,
                'message' => 'Ciudad eliminada correctamente.',
                'data' => $country
            ];
        }catch(\Exception $e){
            info('Country_DeleteCountry error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}