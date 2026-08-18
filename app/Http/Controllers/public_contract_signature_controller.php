<?php

namespace App\Http\Controllers;

use App\traits\contracts_trait;
use Illuminate\Http\Request;

class public_contract_signature_controller extends Controller
{
    use contracts_trait;

    public function show(string $uniqueId, string $token)
    {
        $contract = $this->Contract_GetPublicSignatureContract($uniqueId, $token);
        if (!$contract) {
            abort(404);
        }

        return view('public.contracts.signature', compact('contract'));
    }

    public function upload(Request $request, string $uniqueId, string $token)
    {
        $contract = $this->Contract_GetPublicSignatureContract($uniqueId, $token);
        if (!$contract) {
            abort(404);
        }

        if ($contract->signature_status === 'accepted') {
            return view('public.contracts.signature', compact('contract'));
        }

        $request->validate([
            'signed_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'signed_pdf.required' => 'Selecciona el PDF firmado para continuar.',
            'signed_pdf.file' => 'El archivo seleccionado no es valido.',
            'signed_pdf.mimes' => 'Solo se acepta un archivo PDF.',
            'signed_pdf.max' => 'El PDF no puede superar los 20 MB.',
        ]);

        $file = $request->file('signed_pdf');
        if (!$file || !str_starts_with((string) $file->get(), '%PDF-')) {
            return back()->withErrors(['signed_pdf' => 'El archivo no parece ser un PDF valido.']);
        }

        $response = $this->Contract_UploadPublicSignature($uniqueId, $token, $file);
        if ($response['status'] !== 1) {
            return back()->withErrors(['signed_pdf' => $response['message']]);
        }

        return redirect()
            ->route('public.contract.signature', ['uniqueId' => $uniqueId, 'token' => $token])
            ->with('signature_uploaded', true);
    }
}
