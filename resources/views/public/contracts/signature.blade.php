@extends('layouts.public')

@section('title', 'Firma de documento | Opzio')

@push('styles')
<style>
    .signature-eyebrow {
        margin: 0 0 8px;
        color: var(--opzio-purple);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .signature-title {
        margin: 0 0 12px;
        font-size: clamp(24px, 5vw, 34px);
        line-height: 1.15;
    }
    .signature-copy {
        margin: 0 0 24px;
        color: var(--opzio-muted);
        line-height: 1.65;
    }
    .signature-details {
        width: 100%;
        margin: 0 0 24px;
        border-collapse: collapse;
    }
    .signature-details td {
        padding: 12px 0;
        border-bottom: 1px solid var(--opzio-line);
        vertical-align: top;
    }
    .signature-details td:first-child {
        width: 38%;
        color: var(--opzio-muted);
        font-size: 13px;
    }
    .signature-details td:last-child {
        color: var(--opzio-ink);
        font-weight: 700;
        text-align: right;
    }
    .signature-status {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 10px;
        color: var(--opzio-purple);
        background: #f0ebf5;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }
    .signature-panel {
        padding: 22px;
        background: #faf9fb;
        border: 1px solid var(--opzio-line);
    }
    .signature-panel h2 {
        margin: 0 0 8px;
        font-size: 18px;
    }
    .signature-panel p {
        margin: 0 0 18px;
        color: var(--opzio-muted);
        line-height: 1.55;
    }
    .signature-upload {
        display: block;
        width: 100%;
        padding: 12px;
        background: var(--opzio-surface);
        border: 1px dashed #b8b2c1;
        border-radius: 4px;
    }
    .signature-submit {
        width: 100%;
        margin-top: 14px;
        padding: 12px 16px;
        color: #fff;
        background: var(--opzio-purple);
        border: 0;
        border-radius: 4px;
        font-weight: 700;
        cursor: pointer;
    }
    .signature-submit:hover { background: #37105d; }
    .signature-alert {
        margin: 0 0 20px;
        padding: 12px 14px;
        background: #eef7f0;
        border: 1px solid #cce4d1;
        color: #276238;
        line-height: 1.5;
    }
    .signature-error {
        margin: 0 0 20px;
        padding: 12px 14px;
        background: #fff1f1;
        border: 1px solid #edcaca;
        color: #8c2f2f;
        line-height: 1.5;
    }
</style>
@endpush

@section('content')
    <p class="signature-eyebrow">Documento contractual</p>
    <h1 class="signature-title">{{ $contract->name }}</h1>
    <p class="signature-copy">Este espacio permite completar el proceso de firma del documento de forma segura.</p>

    @if(session('signature_uploaded'))
        <p class="signature-alert">El PDF firmado fue cargado correctamente. La información quedó registrada y está pendiente de revisión.</p>
    @endif

    @if($errors->any())
        <div class="signature-error">{{ $errors->first() }}</div>
    @endif

    <table class="signature-details">
        <tr><td>Titular</td><td>{{ $contract->contractable_name }}</td></tr>
        <tr><td>Identificador</td><td>{{ $contract->unique_id }}</td></tr>
        <tr><td>Vigencia</td><td>{{ $contract->start_date?->format('d/m/Y') ?: 'Sin inicio' }} - {{ $contract->end_date?->format('d/m/Y') ?: 'Sin vencimiento' }}</td></tr>
        <tr><td>Estado del PDF firmado</td><td><span class="signature-status">{{ $contract->signature_status_string }}</span></td></tr>
    </table>

    @if($contract->signature_status === 'pending')
        <div class="signature-panel">
            <h2>Carga el documento firmado</h2>
            <p>Sube el PDF que contiene la firma de la persona correspondiente. Solo se acepta un archivo PDF de hasta 20 MB.</p>
            <form method="post" action="{{ route('public.contract.signature.upload', ['uniqueId' => $contract->unique_id, 'token' => request()->route('token')]) }}" enctype="multipart/form-data">
                @csrf
                <label for="signed_pdf">PDF firmado</label>
                <input class="signature-upload" id="signed_pdf" name="signed_pdf" type="file" accept="application/pdf" required>
                <button class="signature-submit" type="submit">Cargar documento firmado</button>
            </form>
        </div>
    @elseif($contract->signature_status === 'uploaded')
        <div class="signature-panel">
            <h2>Información diligenciada</h2>
            <p>El PDF firmado ya fue cargado correctamente. El equipo legal revisará el documento y actualizará su estado.</p>
        </div>
    @else
        <div class="signature-panel">
            <h2>Documento aceptado</h2>
            <p>La información ya fue revisada y aceptada. Este enlace se encuentra cerrado y no permite nuevas cargas.</p>
        </div>
    @endif
@endsection
