<?php 
namespace App\traits;
use Dompdf\Dompdf;
use Dompdf\Options;
trait pdf_trait
{
    public function PDF_GenerarPDF($view, $Data, $orientation = 'portrait'){
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $view = \View::make($view, compact('Data'))->render();
        $dompdf->loadHtml($view, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        return $dompdf;
    }
}