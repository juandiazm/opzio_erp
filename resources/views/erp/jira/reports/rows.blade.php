@php
    $statusLabels = ['generated' => 'Generado', 'generating' => 'En proceso', 'failed' => 'Con error'];
    $intentionLabels = [
        'internal_improvement' => 'Mejora interna',
        'client_report' => 'Resultados para cliente',
        'executive_summary' => 'Resumen ejecutivo',
        'team_capacity' => 'Capacidad y distribucion del equipo',
        'project_progress' => 'Avance por proyecto',
        'delivery_risks' => 'Riesgos de entrega',
        'unplanned_work' => 'Calidad y trabajo no planificado',
    ];
@endphp
@forelse($reports as $report)
    @php($displayTitle = trim((string) preg_replace('/\bJira\b|Informe\s+para\s+el\s+cliente/iu', '', $report->title ?: '')) ?: 'Informe de resultados')
    <tr data-report-id="{{ $report->unique_id }}">
        <td class="text-start">
            <div class="jira-report-table-identity">
                <div class="jira-report-title-line">
                    <strong>{{ $displayTitle }}</strong>
                    @if($report->recurrence_id)
                        <span class="jira-report-recurrence-indicator" title="Reporte recurrente" aria-label="Reporte recurrente"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i></span>
                    @endif
                </div>
                <small>{{ $report->unique_id }}</small>
            </div>
        </td>
        <td class="text-start">
            <div class="jira-report-table-meta">
                <strong>{{ $report->project?->project_key ?: 'Todos los proyectos' }}</strong>
                <small>{{ $report->project?->name ?: 'Alcance global' }}</small>
            </div>
        </td>
        <td class="text-start">{{ $intentionLabels[$report->intention] ?? $report->intention }}</td>
        <td class="text-center">
            <div class="jira-report-table-meta">
                <span>{{ $report->from_date?->format('d/m/Y') }} a {{ $report->to_date?->format('d/m/Y') }}</span>
                <small>{{ $report->epic?->issue_key ?: 'Todas las epicas' }}</small>
            </div>
        </td>
        <td class="text-center">{{ ($report->updated_at ?: $report->created_at)?->format('d/m/Y H:i') }}</td>
        <td class="text-center"><span class="jira-status-pill jira-status-{{ $report->status }}">{{ $statusLabels[$report->status] ?? $report->status }}</span></td>
        <td class="text-end">
            <div class="jira-row-actions">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-view="{{ $report->unique_id }}" aria-label="Ver reporte {{ $displayTitle }}" title="Ver reporte"><i class="fa-light fa-eye" aria-hidden="true"></i></button>
                @if($report->status === 'generated')
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-preview="{{ $report->unique_id }}" aria-label="Abrir PDF de {{ $displayTitle }}" title="Abrir vista previa"><i class="fa-light fa-file-pdf" aria-hidden="true"></i></button>
                @endif
                <button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-regenerate="{{ $report->unique_id }}" aria-label="Regenerar {{ $displayTitle }}" title="Regenerar"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i></button>
                <button class="btn btn-sm btn-outline-danger" type="button" data-jira-report-delete="{{ $report->unique_id }}" aria-label="Eliminar {{ $displayTitle }}" title="Eliminar"><i class="fa-light fa-trash-can" aria-hidden="true"></i></button>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="7" class="jira-empty">Aun no hay reportes generados.</td></tr>
@endforelse
