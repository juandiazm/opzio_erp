@php
    $report = $Data['report'] ?? null;
    $snapshot = $Data['snapshot'] ?? [];
    $content = $Data['content'] ?? [];
    $summary = $snapshot['summary'] ?? [];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 38px; }
        body { color: #26323a; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.5; }
        h1 { color: #1868db; font-size: 22px; margin: 0 0 6px; }
        h2 { border-bottom: 1px solid #dce4e8; color: #1868db; font-size: 13px; margin: 22px 0 8px; padding-bottom: 4px; }
        h3 { color: #26323a; font-size: 11px; margin: 15px 0 5px; }
        .muted { color: #6d7a83; }
        .header { border-bottom: 2px solid #1868db; margin-bottom: 20px; padding-bottom: 10px; }
        .meta { color: #6d7a83; font-size: 9px; }
        .metrics { display: table; table-layout: fixed; width: 100%; }
        .metric { background: #f4f7f8; border: 1px solid #dce4e8; display: table-cell; padding: 10px; }
        .metric strong { color: #0c4da2; display: block; font-size: 17px; margin-top: 5px; }
        li { margin-bottom: 4px; }
        .page-break { page-break-before: always; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #dce4e8; padding: 6px; text-align: left; }
        th { color: #6d7a83; font-size: 8px; text-transform: uppercase; }
    </style>
</head>
<body>
    <header class="header">
        <div class="muted">OPZIO ERP · REPORTE JIRA</div>
        <h1>{{ $content['report_title'] ?? $report?->title ?? 'Reporte Jira' }}</h1>
        <div class="meta">Periodo: {{ $report?->from_date?->format('d/m/Y') }} a {{ $report?->to_date?->format('d/m/Y') }} · Generado: {{ ($report?->generated_at ?: now())->format('d/m/Y H:i') }}</div>
    </header>
    <section class="metrics">
        <div class="metric">Story Points<strong>{{ number_format((float) ($summary['story_points'] ?? 0), 2, ',', '.') }}</strong></div>
        <div class="metric">Issues completados<strong>{{ number_format((int) ($summary['completed_issues'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="metric">Horas registradas<strong>{{ number_format((float) ($summary['worklog_hours'] ?? 0), 2, ',', '.') }}</strong></div>
    </section>
    <h2>Resumen ejecutivo</h2>
    <p>{{ $content['executive_summary'] ?? 'Sin resumen disponible.' }}</p>
    <h2>Hallazgos clave</h2>
    @forelse(($content['key_findings'] ?? []) as $finding)<li>{{ $finding }}</li>@empty<p class="muted">Sin hallazgos.</p>@endforelse
    <h2>Esfuerzo</h2><p>{{ $content['effort_analysis'] ?? '' }}</p>
    <h2>Proyectos</h2><p>{{ $content['project_analysis'] ?? '' }}</p>
    <h2>Usuarios</h2><p>{{ $content['user_analysis'] ?? '' }}</p>
    <h2>Epicas</h2><p>{{ $content['epic_analysis'] ?? '' }}</p>
    <h2>Riesgos</h2>
    @forelse(($content['risks'] ?? []) as $risk)<li>{{ $risk }}</li>@empty<p class="muted">Sin riesgos registrados.</p>@endforelse
    <h2>Recomendaciones</h2>
    @forelse(($content['recommendations'] ?? []) as $recommendation)<h3>{{ $recommendation['title'] ?? 'Recomendacion' }} · {{ ucfirst($recommendation['priority'] ?? 'media') }}</h3><p>{{ $recommendation['action'] ?? $recommendation['rationale'] ?? '' }}</p>@empty<p class="muted">Sin recomendaciones.</p>@endforelse
    <h2>Limitaciones</h2>
    @forelse(($content['limitations'] ?? []) as $limitation)<li>{{ $limitation }}</li>@empty<p class="muted">Sin limitaciones registradas.</p>@endforelse
</body>
</html>
