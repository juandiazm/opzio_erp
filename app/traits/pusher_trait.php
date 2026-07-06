<?php 
namespace App\traits;

use Session;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Facades\Storage;


#REGLOG 60
trait pusher_trait
{
    private $DataInPusher = 1;
    public function Pusher_GetPusherJsonData(){
        try{
            $pusher =  collect(json_decode(Storage::get('pusher.json')));
            return [
                'status' => 1,
                'pusher' => $pusher
            ];
        }catch(\Exception $e){
            info('Pusher_GetPusherJsonData error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Company_SetPusherJsonData($Config){
        try{
            if(count($Config)==$this->DataInPusher){
                Storage::put('pusher.json', json_encode($Config));
                $this->AdminLogs_AddLog(6001, 'Se actualizaron los datos de pusher');
                return [
                    'status' => 1,
                    'message' => 'Datos actualizados'
                ];
            }else{
                return [
                    'status' => 0,
                    'message' => 'Los parámetros de entrada no son suficientes'
                ];
            }
        }catch(\Exception $e){
            info('Company_SetPusherJsonData error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    
}