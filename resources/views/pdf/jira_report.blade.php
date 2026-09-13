@php
    $report = $Data['report'] ?? null;
    $snapshot = is_array($Data['snapshot'] ?? null) ? $Data['snapshot'] : [];
    $content = is_array($Data['content'] ?? null) ? $Data['content'] : [];
    $summary = is_array($snapshot['summary'] ?? null) ? $snapshot['summary'] : [];
    $filters = is_array($snapshot['filters'] ?? null) ? $snapshot['filters'] : [];
    $sourceLabels = [
        'story_points' => 'Story Points',
        'worklogs' => 'Horas de worklog',
        'projects' => 'Proyectos',
        'epics' => 'Epicas',
        'users' => 'Usuarios',
        'statuses' => 'Estados',
        'erp_relations' => 'Relaciones ERP',
        'quality' => 'Calidad de datos',
    ];
    $cleanReportLabel = function ($value): string {
        $value = is_scalar($value) ? trim((string) $value) : '';
        $value = preg_replace('/\bJira\b|Informe\s+para\s+el\s+cliente/iu', '', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', trim($value, " \t\n\r\0\x0B-:·")) ?? $value;
        return trim($value) ?: 'Informe de resultados';
    };
    $reportTitle = $cleanReportLabel($content['report_title'] ?? $report?->title ?? null);
    $sources = is_array($report?->data_sources) ? $report->data_sources : ($snapshot['report']['sources'] ?? []);
    $sources = collect($sources)->map(fn ($source): string => $sourceLabels[$source] ?? (string) $source)->filter()->values();
    $project = $report?->project;
    $epic = $report?->epic;
    $projectLabel = $project?->project_key ?: 'Todos los proyectos';
    $projectName = $project?->name ?: 'Alcance global';
    $epicLabel = $epic?->issue_key ? $epic->issue_key.' - '.($epic->summary ?: 'Epica') : 'Todas las epicas';
    $reportFilters = is_array(data_get($snapshot, 'report.filters')) ? data_get($snapshot, 'report.filters') : [];
    $selectedProjectLabels = collect($reportFilters['projects'] ?? [])->pluck('label')->filter()->values();
    $selectedEpicLabels = collect($reportFilters['epics'] ?? [])->pluck('label')->filter()->values();
    $selectedUserLabels = collect($reportFilters['users'] ?? [])->pluck('label')->filter()->values();
    $selectedStatusLabels = collect($reportFilters['statuses'] ?? [])->filter()->values();
    $projectScope = $selectedProjectLabels->isNotEmpty() ? $selectedProjectLabels->implode(', ') : ($project?->project_key ? $projectLabel.' - '.$projectName : $projectLabel);
    $epicScope = $selectedEpicLabels->isNotEmpty() ? $selectedEpicLabels->implode(', ') : $epicLabel;
    $userScope = $selectedUserLabels->isNotEmpty() ? $selectedUserLabels->implode(', ') : 'Todos los usuarios';
    $statusScope = $selectedStatusLabels->isNotEmpty() ? $selectedStatusLabels->implode(', ') : 'Todos los estados';
    $creatorName = trim((string) ($report?->creator?->complete_name ?? ''));
    if ($creatorName === '') {
        $creatorName = trim((string) ($report?->creator?->name ?? '').' '.(string) ($report?->creator?->lastname ?? ''));
    }
    $creatorName = $creatorName ?: 'Opzio ERP';
    $generatedAt = $report?->generated_at ?: now();
    $generatedLabel = $generatedAt instanceof \DateTimeInterface ? $generatedAt->format('d/m/Y H:i') : (string) $generatedAt;
    $fromLabel = $report?->from_date?->format('d/m/Y') ?: ($filters['from'] ?? '-');
    $toLabel = $report?->to_date?->format('d/m/Y') ?: ($filters['to'] ?? '-');
    $formatNumber = function ($value, $decimals = 0): string {
        return $value === null ? 'No incluido' : number_format((float) $value, $decimals, ',', '.');
    };
    $formatDate = function ($value): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        if (! filled($value)) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    };
    $projects = is_array($snapshot['projects'] ?? null) ? $snapshot['projects'] : [];
    $users = is_array($snapshot['users'] ?? null) ? $snapshot['users'] : [];
    $epics = is_array($snapshot['epics'] ?? null) ? $snapshot['epics'] : [];
    $issues = is_array($snapshot['issues'] ?? null) ? $snapshot['issues'] : [];
    $relations = is_array(data_get($snapshot, 'erp_relations.projects')) ? data_get($snapshot, 'erp_relations.projects') : [];
    $quality = is_array($snapshot['quality'] ?? null) ? $snapshot['quality'] : [];
    $reportIntention = is_array($report) ? ($report['intention'] ?? null) : (is_object($report) ? ($report->intention ?? null) : null);
    $isClientReport = $reportIntention === 'client_report' || ($content['format'] ?? null) === 'client_report';
    if ($isClientReport) {
        $hadEffortSource = $sources->contains(fn (string $source): bool => in_array($source, ['Story Points', 'Horas de worklog'], true));
        $sources = $sources
            ->reject(fn (string $source): bool => in_array($source, ['Story Points', 'Horas de worklog'], true))
            ->values();
        if ($hadEffortSource) {
            $sources->prepend('Esfuerzo');
        }
    }
    $clientSource = is_array($snapshot['client_report'] ?? null) ? $snapshot['client_report'] : [];
    $clientActivities = is_array($content['activity_summaries'] ?? null) ? $content['activity_summaries'] : [];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page { margin: 104px 48px 56px; }
        html, body { margin: 0; padding: 0; }
        body { color: #222222; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.5; }
        .watermark { position: fixed; top: 50%; left: 50%; width: 100%; transform: translate(-50%, -50%); opacity: 0.06; z-index: 1; }
        .watermark img { display: block; width: 100%; }
        .document-content { position: relative; z-index: 2; }
        .document-kicker { color: #220245; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin: 0 0 7px; text-transform: uppercase; }
        h1 { color: #222222; font-size: 24px; line-height: 1.2; margin: 0 0 8px; }
        h2 { color: #220245; font-size: 14px; line-height: 1.25; margin: 0; }
        h3 { color: #222222; font-size: 10px; margin: 12px 0 5px; }
        p { margin: 0 0 9px; }
        .lead { color: #555555; font-size: 11px; margin-bottom: 14px; }
        .muted { color: #777777; }
        .intro { border-bottom: 2px solid #220245; margin-bottom: 16px; padding-bottom: 12px; }
        .scope-table, .metric-grid, .data-table, .recommendation-table { border-collapse: collapse; width: 100%; }
        .scope-table { margin: 0 0 14px; table-layout: fixed; }
        .scope-table td { border: 1px solid #d9d9d9; padding: 7px 8px; vertical-align: top; width: 25%; }
        .scope-label { color: #777777; display: block; font-size: 7px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
        .scope-value { color: #222222; display: block; font-size: 9px; word-wrap: break-word; }
        .source-list { margin: 0 0 16px; }
        .source { border: 1px solid #cfcfcf; color: #555555; display: inline-block; font-size: 8px; margin: 0 4px 4px 0; padding: 3px 6px; }
        .section { margin-top: 19px; }
        .section-heading { border-bottom: 1px solid #220245; margin-bottom: 9px; padding-bottom: 5px; }
        .section-number { color: #777777; display: inline-block; font-size: 8px; margin-right: 6px; vertical-align: middle; }
        .section-heading h2 { display: inline-block; vertical-align: middle; }
        .metric-grid { margin: 0 0 13px; table-layout: fixed; }
        .metric-grid td { background: #f5f5f5; border: 1px solid #d9d9d9; padding: 8px; vertical-align: top; width: 33.33%; }
        .metric-grid tr:first-child td { border-bottom: 0; }
        .metric-grid tr:last-child td { border-top: 0; }
        .metric-label { color: #777777; display: block; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .metric-value { color: #220245; display: block; font-size: 16px; font-weight: bold; line-height: 1.2; margin-top: 4px; }
        .metric-value.is-muted { color: #777777; font-size: 10px; }
        .analysis { page-break-inside: avoid; }
        .analysis p { text-align: justify; }
        .client-report-copy p { text-align: justify; }
        .client-report-copy h3 { color: #222222; margin-top: 13px; }
        .client-report-metrics { margin-top: 10px; }
        .client-activity-table th:nth-child(1), .client-activity-table td:nth-child(1) { width: 27%; }
        .client-activity-table th:nth-child(2), .client-activity-table td:nth-child(2) { width: 22%; }
        .client-activity-table th:nth-child(3), .client-activity-table td:nth-child(3) { width: 51%; }
        .data-table { margin: 8px 0 12px; table-layout: fixed; }
        .data-table th, .data-table td { border: 1px solid #d9d9d9; padding: 5px 6px; text-align: left; vertical-align: top; word-wrap: break-word; }
        .data-table th { background: #f0f0f0; color: #220245; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .data-table td { font-size: 8px; }
        .data-table .number { text-align: right; white-space: nowrap; }
        .list { margin: 5px 0 10px 16px; padding: 0; }
        .list li { margin-bottom: 5px; padding-left: 2px; }
        .recommendation-table { margin: 7px 0 12px; }
        .recommendation-table td { border-bottom: 1px solid #d9d9d9; padding: 7px 4px; vertical-align: top; }
        .recommendation-title { color: #220245; font-weight: bold; }
        .priority { color: #777777; font-size: 8px; text-transform: uppercase; white-space: nowrap; }
        .note { background: #f7f7f7; border: 1px solid #d9d9d9; color: #555555; padding: 8px 9px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="watermark" aria-hidden="true">
        <img src="{{ public_path('images/opzio-monogram-purple-transparent.png') }}" alt="">
    </div>

    <main class="document-content">
        <section class="intro">
            <div class="document-kicker">Informe de resultados</div>
            <h1>{{ $reportTitle }}</h1>
            <table class="scope-table">
                @if($isClientReport)
                <tr>
                    <td colspan="2"><span class="scope-label">Proyectos</span><span class="scope-value">{{ $projectScope }}</span></td>
                    <td colspan="2"><span class="scope-label">Periodo analizado</span><span class="scope-value">{{ $fromLabel }} a {{ $toLabel }}</span></td>
                </tr>
                @else
                <tr>
                    <td><span class="scope-label">Proyectos</span><span class="scope-value">{{ $projectScope }}</span></td>
                    <td><span class="scope-label">Epicas</span><span class="scope-value">{{ $epicScope }}</span></td>
                    <td><span class="scope-label">Periodo analizado</span><span class="scope-value">{{ $fromLabel }} a {{ $toLabel }}</span></td>
                    <td><span class="scope-label">Responsable</span><span class="scope-value">{{ $creatorName }}<br>{{ $generatedLabel }}</span></td>
                </tr>
                <tr>
                    <td colspan="2"><span class="scope-label">Usuarios</span><span class="scope-value">{{ $userScope }}</span></td>
                    <td colspan="2"><span class="scope-label">Estados</span><span class="scope-value">{{ $statusScope }}</span></td>
                </tr>
                @endif
            </table>
            @if(!$isClientReport)
                <div class="scope-label">Fuentes incluidas</div>
                <div class="source-list">
                    @forelse($sources as $source)
                        <span class="source">{{ $source }}</span>
                    @empty
                        <span class="muted">No se registraron fuentes.</span>
                    @endforelse
                </div>
            @endif
        </section>

        @if($isClientReport)
            @include('pdf.partials.jira_client_report', ['content' => $content, 'summary' => $summary, 'clientSource' => $clientSource, 'clientActivities' => $clientActivities, 'formatNumber' => $formatNumber])
        @else
        <section class="section">
            <div class="section-heading"><span class="section-number">01</span><h2>Resumen ejecutivo</h2></div>
            <p class="lead">{{ $content['executive_summary'] ?? 'Sin resumen disponible.' }}</p>
            @if(filled($content['objective_alignment'] ?? null))
                <h3>Alineacion con el objetivo</h3>
                <p>{{ $content['objective_alignment'] }}</p>
            @endif
        </section>

        <section class="section">
            <div class="section-heading"><span class="section-number">02</span><h2>Indicadores de gestion</h2></div>
            <table class="metric-grid">
                <tr>
                    <td><span class="metric-label">Story Points</span><span class="metric-value {{ ($summary['story_points'] ?? null) === null ? 'is-muted' : '' }}">{{ $formatNumber($summary['story_points'] ?? null, 2) }}</span></td>
                    <td><span class="metric-label">Issues analizados</span><span class="metric-value">{{ $formatNumber($summary['story_issues'] ?? 0) }}</span></td>
                    <td><span class="metric-label">Issues completados</span><span class="metric-value">{{ $formatNumber($summary['completed_issues'] ?? 0) }}</span></td>
                </tr>
                <tr>
                    <td><span class="metric-label">Horas registradas</span><span class="metric-value {{ ($summary['worklog_hours'] ?? null) === null ? 'is-muted' : '' }}">{{ $formatNumber($summary['worklog_hours'] ?? null, 2) }}</span></td>
                    <td><span class="metric-label">Horas estimadas</span><span class="metric-value">{{ $formatNumber($summary['estimated_hours'] ?? 0, 2) }}</span></td>
                    <td><span class="metric-label">Proyectos / usuarios activos</span><span class="metric-value">{{ $formatNumber($summary['active_projects'] ?? 0) }} / {{ $formatNumber($summary['active_users'] ?? 0) }}</span></td>
                </tr>
            </table>
            <div class="note"><strong>Lectura ejecutiva:</strong> Story Points representan estimaciones de alcance y no deben interpretarse como horas trabajadas.</div>
        </section>

        <section class="section analysis">
            <div class="section-heading"><span class="section-number">03</span><h2>Hallazgos y analisis</h2></div>
            <h3>Hallazgos clave</h3>
            @forelse(($content['key_findings'] ?? []) as $finding)
                <ul class="list"><li>{{ $finding }}</li></ul>
            @empty
                <p class="muted">Sin hallazgos registrados.</p>
            @endforelse
            <h3>Esfuerzo y capacidad</h3>
            <p>{{ $content['effort_analysis'] ?? 'Sin analisis de esfuerzo disponible.' }}</p>
            <h3>Avance por proyecto</h3>
            <p>{{ $content['project_analysis'] ?? 'Sin analisis de proyectos disponible.' }}</p>
            <h3>Distribucion por usuario</h3>
            <p>{{ $content['user_analysis'] ?? 'Sin analisis de usuarios disponible.' }}</p>
            <h3>Avance por epica</h3>
            <p>{{ $content['epic_analysis'] ?? 'Sin analisis de epicas disponible.' }}</p>
        </section>

        @if($projects || $users || $epics)
            <section class="section page-break">
                <div class="section-heading"><span class="section-number">04</span><h2>Detalle operativo</h2></div>
                @if($projects)
                    <h3>Proyectos con actividad</h3>
                    <table class="data-table">
                        <thead><tr><th style="width: 34%;">Proyecto</th><th class="number">Issues</th><th class="number">Story Points</th><th class="number">Horas estimadas</th><th class="number">Horas worklog</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($projects, 0, 10) as $item)
                                <tr><td>{{ $item['label'] ?? 'Proyecto sin nombre' }}</td><td class="number">{{ $formatNumber($item['issues'] ?? 0) }}</td><td class="number">{{ $formatNumber($item['story_points'] ?? null, 2) }}</td><td class="number">{{ $formatNumber($item['estimated_hours'] ?? 0, 2) }}</td><td class="number">{{ $formatNumber($item['hours'] ?? null, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @if($users)
                    <h3>Capacidad por usuario</h3>
                    <table class="data-table">
                        <thead><tr><th style="width: 36%;">Usuario</th><th class="number">Issues</th><th class="number">Story Points</th><th class="number">Horas estimadas</th><th class="number">Horas worklog</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($users, 0, 10) as $item)
                                <tr><td>{{ $item['label'] ?? 'Sin responsable' }}</td><td class="number">{{ $formatNumber($item['issues'] ?? 0) }}</td><td class="number">{{ $formatNumber($item['story_points'] ?? null, 2) }}</td><td class="number">{{ $formatNumber($item['estimated_hours'] ?? 0, 2) }}</td><td class="number">{{ $formatNumber($item['hours'] ?? null, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @if($epics)
                    <h3>Epicas</h3>
                    <table class="data-table">
                        <thead><tr><th style="width: 40%;">Epica</th><th class="number">Issues</th><th class="number">Story Points</th><th class="number">Horas estimadas</th><th class="number">Horas worklog</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($epics, 0, 10) as $item)
                                <tr><td>{{ $item['label'] ?? 'Sin epica' }}</td><td class="number">{{ $formatNumber($item['issues'] ?? 0) }}</td><td class="number">{{ $formatNumber($item['story_points'] ?? null, 2) }}</td><td class="number">{{ $formatNumber($item['estimated_hours'] ?? 0, 2) }}</td><td class="number">{{ $formatNumber($item['hours'] ?? null, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endif

        @if($relations || ($quality['included'] ?? false))
            <section class="section">
                <div class="section-heading"><span class="section-number">05</span><h2>Integracion y calidad de datos</h2></div>
                @if($relations)
                    <h3>Relaciones ERP</h3>
                    <table class="data-table">
                        <thead><tr><th style="width: 25%;">Proyecto</th><th>Clientes relacionados</th><th>Licencias relacionadas</th></tr></thead>
                        <tbody>
                            @foreach($relations as $relation)
                                <tr>
                                    <td>{{ ($relation['project_key'] ?? '').' - '.($relation['project_name'] ?? 'Proyecto') }}</td>
                                    <td>{{ collect($relation['clients'] ?? [])->pluck('name')->filter()->implode(', ') ?: 'Sin clientes' }}</td>
                                    <td>{{ collect($relation['licenses'] ?? [])->pluck('name')->filter()->implode(', ') ?: 'Sin licencias' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @if($quality['included'] ?? false)
                    <h3>Controles de calidad</h3>
                    <table class="data-table">
                        <thead><tr><th>Control</th><th class="number">Resultado</th></tr></thead>
                        <tbody>
                            <tr><td>Issues sin epica</td><td class="number">{{ $formatNumber($quality['issues_without_epic'] ?? 0) }}</td></tr>
                            <tr><td>Issues sin responsable</td><td class="number">{{ $formatNumber($quality['issues_without_assignee'] ?? 0) }}</td></tr>
                            <tr><td>Usuarios sin mapeo ERP</td><td class="number">{{ $formatNumber($quality['users_without_mapping'] ?? 0) }}</td></tr>
                        </tbody>
                    </table>
                @endif
            </section>
        @endif

        <section class="section">
            <div class="section-heading"><span class="section-number">06</span><h2>Riesgos y decisiones</h2></div>
            <h3>Riesgos identificados</h3>
            @forelse(($content['risks'] ?? []) as $risk)
                <ul class="list"><li>{{ $risk }}</li></ul>
            @empty
                <p class="muted">Sin riesgos registrados.</p>
            @endforelse
            <h3>Recomendaciones</h3>
            @forelse(($content['recommendations'] ?? []) as $recommendation)
                <table class="recommendation-table"><tr><td style="width: 26%;"><span class="recommendation-title">{{ $recommendation['title'] ?? 'Recomendacion' }}</span><br><span class="priority">Prioridad: {{ ucfirst($recommendation['priority'] ?? 'media') }}</span></td><td><strong>Razon:</strong> {{ $recommendation['rationale'] ?? '' }}<br><strong>Accion:</strong> {{ $recommendation['action'] ?? '' }}</td></tr></table>
            @empty
                <p class="muted">Sin recomendaciones registradas.</p>
            @endforelse
        </section>

        <section class="section">
            <div class="section-heading"><span class="section-number">07</span><h2>Proximos pasos y limitaciones</h2></div>
            <h3>Proximos pasos</h3>
            @forelse(($content['next_steps'] ?? []) as $nextStep)
                <ul class="list"><li>{{ $nextStep }}</li></ul>
            @empty
                <p class="muted">Sin proximos pasos registrados.</p>
            @endforelse
            <h3>Limitaciones del analisis</h3>
            @forelse(($content['limitations'] ?? []) as $limitation)
                <ul class="list"><li>{{ $limitation }}</li></ul>
            @empty
                <p class="muted">Sin limitaciones registradas.</p>
            @endforelse
        </section>

        @if($issues)
            <section class="section page-break">
                <div class="section-heading"><span class="section-number">08</span><h2>Anexo - Registro de issues</h2></div>
                <p class="muted">Se muestran los primeros {{ min(20, count($issues)) }} registros del periodo, ordenados por la fecha de actividad disponible.</p>
                <table class="data-table">
                    <thead><tr><th style="width: 12%;">Clave</th><th style="width: 28%;">Resumen</th><th style="width: 16%;">Proyecto / epica</th><th style="width: 16%;">Responsable</th><th style="width: 12%;">Estado</th><th class="number">SP</th><th>Resolucion</th></tr></thead>
                    <tbody>
                        @foreach(array_slice($issues, 0, 20) as $issue)
                            <tr><td>{{ $issue['key'] ?? '-' }}</td><td>{{ $issue['summary'] ?? '-' }}</td><td>{{ ($issue['project'] ?? 'Sin proyecto').' / '.($issue['epic'] ?? 'Sin epica') }}</td><td>{{ $issue['assignee'] ?? 'Sin responsable' }}</td><td>{{ $issue['status'] ?? 'Sin estado' }}</td><td class="number">{{ $formatNumber($issue['story_points'] ?? null, 2) }}</td><td>{{ $formatDate($issue['resolved_at'] ?? null) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
        @endif
    </main>
</body>
</html>
