<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Exportable\reports_sheets;
use Illuminate\Support\Str;
use Storage;
use Carbon\Carbon;

class report_controller extends Controller
{
    //
    public function export_report(Request $request){
        try{
            $uid = Session::get('user')->unique_id;
            Excel::store( new reports_sheets($request->sheets), $uid.'.xlsx', 'reports');
            return [
                'status' => 1,
                'message' => 'Reporte generado',
                'data' => $uid
            ];
        }catch(\Exception $e){
            return \Response::json([
                'status' => 0,
                'message' => $e->getMessage()
            ] , 400);
        }
    }
    public function download_report(Request $request){
        //check if file exists $request->unique_id
        if(Storage::disk('reports')->exists($request->unique_id.'.xlsx')){
            $file =  Storage::disk('reports')->download($request->unique_id.'.xlsx',  'Reporte-'.Carbon::now()->format('Y-m-d').'.xlsx');
            return $file;
        }else{
            return \Response::json([
                'status' => 0,
                'message' => 'El archivo no existe'
            ] , 400);
            //return back();
        }
    }
    public function delete_report(Request $request){
        //check if file exists $request->unique_id
        if(Storage::disk('reports')->exists($request->unique_id.'.xlsx')){
            Storage::disk('reports')->delete($request->unique_id.'.xlsx');
            return [
                'status' => 1,
                'message' => 'Reporte eliminado'
            ];
        }else{
            return \Response::json([
                'status' => 0,
                'message' => 'El archivo no existe'
            ] , 400);
        }
    }
}
