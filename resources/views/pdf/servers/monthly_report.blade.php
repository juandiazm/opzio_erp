<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte mensual de servidor</title>
    <style>
        @page { margin: 104px 48px 56px; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { color: #28303b; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.5; }
        .document-content { position: relative; z-index: 2; }
        h2 { border-bottom: 1px solid #ddd5e5; color: #220245; font-size: 13px; margin: 18px 0 8px; padding-bottom: 5px; }
        h3 { color: #220245; font-size: 11px; margin: 12px 0 5px; }
        p { line-height: 1.5; margin: 4px 0; }
        .muted { color: #667085; }
        .cards { border-collapse: separate; border-spacing: 6px; margin: 0 -6px; width: 100%; }
        .card { background: #f7f4f9; border: 1px solid #e5ddec; padding: 9px; vertical-align: top; width: 25%; }
        .card-label { color: #667085; display: block; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .card-value { color: #220245; display: block; font-size: 15px; font-weight: bold; margin-top: 3px; }
        .metric-table { border-collapse: collapse; width: 100%; }
        .metric-table th { background: #220245; color: #fff; font-size: 9px; padding: 6px 7px; text-align: left; }
        .metric-table td { border-bottom: 1px solid #e5e0e9; padding: 6px 7px; vertical-align: top; }
        .metric-table td:first-child { color: #667085; width: 38%; }
        .recommendations { background: #fff8f1; border: 1px solid #f7d5b4; padding: 8px 12px; }
        .recommendations li { margin: 3px 0; }
        .footer { border-top: 1px solid #e5e0e9; color: #8a919d; font-size: 8px; margin-top: 22px; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
@php
    $report = $Data['report'];
    $metrics = $report['metrics'];
    $formatBytes = function ($value) {
        if ($value === null) return '-';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $number = (float) $value;
        $unit = 0;
        while (abs($number) >= 1024 && $unit < count($units) - 1) { $number /= 1024; $unit++; }
        return number_format($number, $unit === 0 ? 0 : 1, ',', '.').' '.$units[$unit];
    };
    $formatNumber = fn ($value) => $value === null ? '-' : number_format((float) $value, 2, ',', '.');
    $formatPercent = fn ($value) => $value === null ? '-' : number_format((float) $value, 2, ',', '.').'%';
@endphp
<main class="document-content">
@if(data_get($report, 'delivery.availability_alert'))
<div style="background: #FFF5F5; border: 1px solid #E2B8B8; color: #6D1A1D; margin-bottom: 12px; padding: 8px 10px;"><strong>Alerta operativa:</strong> disponibilidad observada de {{ number_format((float) $report['delivery']['availability_percent'], 2, ',', '.') }}%. Reporte dirigido a Soporte Opzio para análisis.</div>
@endif
<h2>Resumen ejecutivo</h2>
<table class="cards">
    <tr>
        @foreach($report['stakeholder_summary'] as $item)
            <td class="card"><span class="card-label">{{ $item['label'] }}</span><span class="card-value">{{ $item['value'] }}</span><p>{{ $item['message'] }}</p></td>
        @endforeach
    </tr>
</table>
<h2>Tráfico y cobertura</h2>
<table class="metric-table"><tr><th>Indicador</th><th>Resultado</th></tr>
    <tr><td>Solicitudes atendidas</td><td>{{ number_format($metrics['traffic']['requests_total'], 0, ',', '.') }}</td></tr>
    <tr><td>Solicitudes por minuto observado</td><td>{{ $formatNumber($metrics['traffic']['requests_per_minute']) }}</td></tr>
    <tr><td>Cobertura observada</td><td>{{ $formatPercent($metrics['traffic']['coverage_percent']) }} ({{ $formatNumber($metrics['traffic']['coverage_minutes']) }} min)</td></tr>
    <tr><td>Bytes de entrada / salida</td><td>{{ $formatBytes($metrics['traffic']['request_bytes']) }} / {{ $formatBytes($metrics['traffic']['response_bytes']) }}</td></tr>
    <tr><td>Ventanas HTTP analizadas</td><td>{{ number_format($metrics['traffic']['buckets'], 0, ',', '.') }}</td></tr>
</table>
<h2>Confiabilidad y experiencia</h2>
<table class="metric-table"><tr><th>Indicador</th><th>Resultado</th></tr>
    <tr><td>Disponibilidad estimada</td><td>{{ $formatPercent($metrics['reliability']['availability_percent']) }}</td></tr>
    <tr><td>Respuestas 2xx / 3xx / 4xx / 5xx</td><td>{{ number_format($metrics['reliability']['status_2xx'], 0, ',', '.') }} / {{ number_format($metrics['reliability']['status_3xx'], 0, ',', '.') }} / {{ number_format($metrics['reliability']['status_4xx'], 0, ',', '.') }} / {{ number_format($metrics['reliability']['status_5xx'], 0, ',', '.') }}</td></tr>
    <tr><td>Tasa de error 4xx/5xx</td><td>{{ $formatPercent($metrics['reliability']['error_rate_percent']) }}</td></tr>
    <tr><td>Latencia promedio / p95 promedio / p95 máximo</td><td>{{ $formatNumber($metrics['performance']['latency_average_ms']) }} ms / {{ $formatNumber($metrics['performance']['p95_average_ms']) }} ms / {{ $formatNumber($metrics['performance']['p95_peak_ms']) }} ms</td></tr>
</table>
<h2>Capacidad y almacenamiento</h2>
<table class="metric-table"><tr><th>Indicador</th><th>Resultado</th></tr>
    <tr><td>CPU promedio / pico</td><td>{{ $formatPercent($metrics['capacity']['cpu_average_percent']) }} / {{ $formatPercent($metrics['capacity']['cpu_peak_percent']) }}</td></tr>
    <tr><td>RAM RSS pico / PSS pico</td><td>{{ $formatBytes($metrics['capacity']['memory_rss_peak_bytes']) }} / {{ $formatBytes($metrics['capacity']['memory_pss_peak_bytes']) }}</td></tr>
    <tr><td>Cola PHP-FPM promedio / pico / máxima</td><td>{{ $formatNumber($metrics['capacity']['fpm_queue_average']) }} / {{ $formatNumber($metrics['capacity']['fpm_queue_peak']) }} / {{ $formatNumber($metrics['capacity']['fpm_max_queue']) }}</td></tr>
    <tr><td>Almacenamiento actual / crecimiento</td><td>{{ $formatBytes($metrics['capacity']['storage_total_bytes']) }} / {{ $formatBytes($metrics['capacity']['storage_growth_bytes']) }}</td></tr>
    <tr><td>Archivos / directorios</td><td>{{ $formatNumber($metrics['capacity']['storage_files']) }} / {{ $formatNumber($metrics['capacity']['storage_directories']) }}</td></tr>
</table>
<h2>Operación y seguimiento</h2>
<table class="metric-table"><tr><th>Indicador</th><th>Resultado</th></tr>
    <tr><td>Muestras de proyecto</td><td>{{ number_format($metrics['operations']['samples'], 0, ',', '.') }}</td></tr>
    <tr><td>Eventos técnicos</td><td>{{ number_format($metrics['operations']['events_total'], 0, ',', '.') }} ({{ json_encode($metrics['operations']['events_by_severity'], JSON_UNESCAPED_UNICODE) }})</td></tr>
    <tr><td>Última observación del período</td><td>{{ $metrics['operations']['last_observed_at'] ?: '-' }}</td></tr>
</table>
<h2>Observaciones de operación</h2>
<div class="recommendations"><ul>@foreach($report['recommendations'] as $recommendation)<li>{{ $recommendation }}</li>@endforeach</ul></div>
</main>
</body>
</html>
