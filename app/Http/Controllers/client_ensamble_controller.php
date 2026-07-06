<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ensamble_coca_cola_certificate;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\traits\pdf_trait;


class client_ensamble_controller extends Controller
{
    use pdf_trait;
    private $URL_CERTIFICATE_PATH = 'documents/api/clients/ensamble/certificates/';
    public function download_certificate(Request $request){
        $certificate = ensamble_coca_cola_certificate::where('identification', $request->identification)->first();
        if($certificate){
            $certificate->downloaded = true;
            $certificate->downloaded_at = \Carbon\Carbon::now();
            $certificate->save();
            /////////////////////
            $file = $this->URL_CERTIFICATE_PATH . $certificate->uid.'.pdf';
            return Storage::disk('public')->download($file);
        }
        return view('api.clients.ensamble.certificate_not_found');
    }
    public function get_certificates(Request $request){
        return view('api.clients.ensamble.get_certificate');
    }
    public function generate_certificates(Request $request){
        /*$Data =
        [
            'identification' => '1018468726',
            'name' => 'JUAN CARLOS DIAZ MOSQUERA'
        ];
        return view('api.clients.ensamble.certificate', compact('Data'));*/
        set_time_limit(0);
        //first 100
        $documents = ensamble_coca_cola_certificate::where('uid', null)->orWhere('uid', '')->take(100)->get();
        $result = [];
        foreach($documents as $document){
            try{
                $document = ensamble_coca_cola_certificate::find($document->id);
                if($document->uid == null && $document->uid == ''){
                    $Pdf_Data =
                    [
                        'identification' => $document->identification,
                        'name' => $document->name
                    ];
                    $pdf = $this->GenerarPDF('api.clients.ensamble.certificate', $Pdf_Data, 'landscape');
                    $uid = strtoupper(Str::uuid()->toString());
                    $document->uid = $uid;
                    $document->created_at = \Carbon\Carbon::now();
                    $document->updated_at = \Carbon\Carbon::now();
                    $document->save();
                    Storage::disk('public')->put($this->URL_CERTIFICATE_PATH . $uid.'.pdf', $pdf->output());
                    $result[] = [
                        'identification' => $document->identification,
                        'name' => $document->name,
                        'uid' => $uid
                    ];
                }
            }catch(\Exception $e){
                $result[] = [
                    'identification' => $document->identification,
                    'name' => $document->name,
                    'error' => $e->getMessage()
                ];
            }
            
        }
        set_time_limit(30);
        return $result; 
    }
}
