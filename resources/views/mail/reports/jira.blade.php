@php($data = $Data ?? [])
<p>Hola,</p>
<p>Se ha generado el reporte Jira <strong>{{ $data['report_title'] ?? 'Reporte Jira' }}</strong>.</p>
<p>Periodo: {{ $data['period'] ?? '-' }}<br>Generado: {{ $data['generated_at'] ?? '-' }}</p>
<p>Encontraras el PDF adjunto a este correo.</p>
<p>Opzio ERP</p>
