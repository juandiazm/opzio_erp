@php
    $clientText = function ($value, $fallback = ''): string {
        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }
        if (is_array($value)) {
            return collect($value)->flatten()->filter(fn ($item): bool => is_scalar($item) && trim((string) $item) !== '')->map(fn ($item): string => trim((string) $item))->implode(', ');
        }
        return $fallback;
    };
    $clientNumber = function ($value, $decimals = 0): string {
        return is_numeric($value) ? number_format((float) $value, $decimals, ',', '.') : 'No incluido';
    };
@endphp

<section class="section client-report-copy">
    <div class="section-heading"><span class="section-number">01</span><h2>Resumen ejecutivo</h2></div>
    <p class="lead">{{ $clientText($content['executive_summary'] ?? null, 'Sin resumen disponible.') }}</p>
</section>

<section class="section client-report-copy">
    <div class="section-heading"><span class="section-number">02</span><h2>Resultados documentados</h2></div>
    @forelse(($content['documented_results'] ?? []) as $result)
        <h3>{{ $clientText($result['title'] ?? null, 'Resultados documentados') }}</h3>
        @foreach(($result['paragraphs'] ?? []) as $paragraph)
            <p>{{ $clientText($paragraph, 'Sin detalle documentado.') }}</p>
        @endforeach
    @empty
        <p class="muted">No se documentaron resultados narrativos para el periodo.</p>
    @endforelse
</section>

<section class="section client-report-copy">
    <div class="section-heading"><span class="section-number">03</span><h2>Esfuerzo registrado</h2></div>
    <p>{{ $clientText($content['effort_analysis'] ?? null, 'Sin analisis de esfuerzo disponible.') }}</p>
    <table class="metric-grid client-report-metrics">
        <tr>
            <td><span class="metric-label">Registros de trabajo</span><span class="metric-value">{{ $clientNumber($clientSource['total_records'] ?? $summary['issue_count'] ?? 0) }}</span></td>
            <td><span class="metric-label">Esfuerzo</span><span class="metric-value">{{ $clientNumber($clientSource['total_effort'] ?? null, 2) }}</span></td>
            <td><span class="metric-label">Sin esfuerzo registrado</span><span class="metric-value">{{ $clientNumber($clientSource['unestimated_records'] ?? 0) }}</span></td>
        </tr>
    </table>
    @if(!empty($clientSource['records_by_type']))
        <h3>Registros por tipo de trabajo</h3>
        <ul class="list">
            @foreach($clientSource['records_by_type'] as $type => $count)
                <li>
                    <strong>{{ $clientText($type, 'Sin clasificar') }}:</strong> {{ $clientNumber($count) }} registros
                    @if(array_key_exists($type, $clientSource['effort_by_type'] ?? []))
                        , con {{ $clientNumber($clientSource['effort_by_type'][$type], 2) }} de esfuerzo
                    @else
                        , sin estimacion registrada
                    @endif
                    .
                </li>
            @endforeach
        </ul>
    @endif
</section>

<section class="section client-report-copy">
    <div class="section-heading"><span class="section-number">04</span><h2>Consideraciones de interpretacion</h2></div>
    @forelse(($content['interpretation_notes'] ?? []) as $note)
        <p>{{ $clientText($note, 'Sin observaciones.') }}</p>
    @empty
        <p>El Esfuerzo corresponde al valor de estimated_hours registrado para las actividades seleccionadas.</p>
    @endforelse
</section>

<section class="section client-report-copy">
    <div class="section-heading"><span class="section-number">05</span><h2>Cierre</h2></div>
    @forelse(($content['closing'] ?? []) as $paragraph)
        <p>{{ $clientText($paragraph, 'Sin cierre disponible.') }}</p>
    @empty
        <p>{{ $clientText($content['executive_summary'] ?? null, 'Sin cierre disponible.') }}</p>
    @endforelse
</section>

@if($clientActivities)
    <section class="section page-break client-report-copy">
        <div class="section-heading"><span class="section-number">06</span><h2>Detalle de actividades documentadas</h2></div>
        <p class="muted">Cada fila conserva su referencia y presenta una sintesis ejecutiva basada en la informacion disponible.</p>
        <table class="data-table client-activity-table">
            <thead>
                <tr>
                    <th style="width: 27%;">Actividad</th>
                    <th style="width: 22%;">Referencia</th>
                    <th style="width: 51%;">Descripcion de resultados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientActivities as $activity)
                    <tr>
                        <td><strong>{{ $clientText($activity['summary'] ?? null, 'Sin titulo') }}</strong><br><small>Referencia: {{ $clientText($activity['issue_key'] ?? null, '-') }}</small></td>
                        <td>
                            Tipo de trabajo: {{ $clientText($activity['issue_type'] ?? null, 'Sin clasificar') }}<br>
                            Prioridad: {{ $clientText($activity['priority'] ?? null, 'No registrada') }}<br>
                            @if(($activity['effort'] ?? null) === null)
                                Esfuerzo: Sin estimacion registrada
                            @else
                                Esfuerzo: {{ $clientNumber($activity['effort'], 2) }}
                            @endif
                            @if(filled($activity['parent_summary'] ?? null) && ($activity['parent_summary'] ?? '') !== 'Sin padre')
                                <br>Linea: {{ $clientText($activity['parent_summary'], 'Sin padre') }}
                            @endif
                        </td>
                        <td>{{ $clientText($activity['result_summary'] ?? null, 'No se documentaron resultados adicionales.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endif
