<?php 
namespace App\traits;
use Dompdf\Dompdf;
use Dompdf\Options;
trait pdf_trait
{
    public function PDF_GenerarPDF($view, $Data, $orientation = 'portrait'){
        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $view =  \View::make($view, compact('Data'))->render();
        $dompdf->loadHtml($view, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        return $dompdf;
    }
}