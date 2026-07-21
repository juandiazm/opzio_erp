<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Reporte de Marketing</title>
        <style>
            @page { margin: 20px 0; }
            html { margin: 0; padding: 0; }
            body {
                font-family: sans-serif;
                margin: 0;
                padding: 20px 0;
                background-color: #ffffff;
                width: 100%;
            }

            #main-content {
                padding: 0 5% 30px 5%;
            }

            #cover-section {
                text-align: center;
                padding: 30px 5% 30px 5%;
                page-break-after: always;
            }
            #cover-section .cover-top-meta {
                text-align: left;
                margin-bottom: 30px;
                padding-bottom: 14px;
                border-bottom: 2px solid #00057B;
            }
            #cover-section .cover-top-meta .report-label {
                margin: 0 0 4px 0;
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            #cover-section .cover-top-meta .report-title-sm {
                margin: 0 0 2px 0;
                font-size: 15px;
                font-weight: bold;
                color: #111;
            }
            #cover-section .cover-top-meta .report-meta-sm {
                margin: 0;
                font-size: 12px;
                color: #555;
            }
            #cover-section .cover-top-meta .opzio-brand {
                float: right;
                text-align: center;
            }
            #cover-section .cover-top-meta .opzio-brand img {
                max-height: 36px;
                display: block;
                margin-left: auto;
                margin-bottom: 2px;
            }
            #cover-section .cover-top-meta .opzio-brand .opzio-name {
                font-size: 11px;
                font-weight: bold;
                color: #00057B;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            #cover-section .cover-top-meta .opzio-brand .opzio-sub {
                font-size: 9px;
                color: #999;
                margin: 0;
            }
            #cover-section .cover-top-meta .cover-top-logo {
                float: right;
                max-width: 60px;
                max-height: 60px;
                border: 1px solid #e0e8f0;
                border-radius: 4px;
                padding: 4px;
            }
            #cover-section .cover-top-meta .cover-top-logo-placeholder {
                float: right;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background-color: #e8ecf0;
                display: inline-block;
            }
            #cover-section .cover-client-logo {
                max-width: 120px;
                max-height: 120px;
                margin: 0 auto 28px auto;
                display: block;
            }
            #cover-section .cover-client-logo-placeholder {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                background-color: #e8ecf0;
                margin: 0 auto 16px auto;
                display: block;
            }
            #cover-section .cover-report-type {
                font-size: 13px;
                color: #00057B;
                text-transform: uppercase;
                letter-spacing: 2px;
                margin: 0 0 8px 0;
            }
            #cover-section .cover-title {
                font-size: 26px;
                font-weight: bold;
                color: #111;
                margin: 0 0 8px 0;
            }
            #cover-section .cover-period {
                font-size: 16px;
                color: #444;
                margin: 0 0 6px 0;
            }
            #cover-section .cover-client-name {
                font-size: 14px;
                color: #666;
                margin: 0 0 24px 0;
            }
            .cover-divider {
                width: 60px;
                height: 3px;
                background-color: #00057B;
                margin: 0 auto 24px auto;
            }
            #cover-section .cover-intent-box {
                text-align: left;
                background-color: #f4f7ff;
                border-radius: 6px;
                padding: 16px 20px;
                margin-top: 16px;
            }
            #cover-section .cover-intent-box .intent-label {
                font-size: 11px;
                color: #00057B;
                text-transform: uppercase;
                font-weight: bold;
                margin: 0 0 6px 0;
            }
            #cover-section .cover-intent-box .intent-text {
                font-size: 12px;
                color: #333;
                margin: 0;
                line-height: 1.6;
            }
            #cover-section .cover-brand {
                font-size: 11px;
                color: #999;
                margin: 24px 0 0 0;
                text-align: center;
            }

            .metrics-row {
                width: 100%;
                margin-bottom: 16px;
                display: table;
                table-layout: fixed;
                border-spacing: 0;
            }
            .metric-box {
                display: table-cell;
                width: 32%;
                background-color: #f4f7ff;
                border-radius: 6px;
                padding: 10px 12px;
                text-align: center;
                vertical-align: top;
            }
            .metric-box + .metric-box {
                padding-left: 20px;
            }
            .metric-box .metric-value {
                font-size: 18px;
                font-weight: bold;
                color: #00057B;
                margin: 0;
                padding: 0;
                word-break: break-word;
                overflow-wrap: break-word;
            }
            .metric-box .metric-label {
                font-size: 10px;
                color: #666;
                margin: 4px 0 0 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #00057B;
                margin: 18px 0 8px 0;
                padding: 0 0 4px 0;
                border-bottom: 1px solid #e0e8f0;
                page-break-after: avoid;
            }

            .field-label {
                font-size: 11px;
                font-weight: bold;
                color: #00057B;
                margin: 10px 0 3px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                page-break-after: avoid;
            }
            .field-text {
                font-size: 12px;
                color: #333;
                margin: 0 0 6px 0;
                line-height: 1.65;
            }

            .bullet-list {
                margin: 0 0 8px 0;
                padding-left: 18px;
            }
            .bullet-list li {
                font-size: 12px;
                color: #333;
                margin-bottom: 3px;
                line-height: 1.55;
            }

            /* -- Campaign cards -- */
            .campaign-card {
                background-color: #ffffff;
                border: 1px solid #dde8f5;
                border-radius: 6px;
                margin-bottom: 10px;
                page-break-inside: avoid;
                overflow: hidden;
            }
            .campaign-card .cc-name {
                font-size: 12px;
                font-weight: bold;
                color: #ffffff;
                background-color: #00057B;
                margin: 0;
                padding: 7px 14px;
            }
            .campaign-card .cc-metrics-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }
            .campaign-card .cc-metrics-table td {
                width: 25%;
                padding: 8px 12px;
                vertical-align: top;
                border-right: 1px solid #e8eef8;
            }
            .campaign-card .cc-metrics-table td:last-child {
                border-right: none;
            }
            .campaign-card .cc-metric-label {
                display: block;
                font-size: 8.5px;
                color: #00057B;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                margin-bottom: 3px;
                font-weight: bold;
            }
            .campaign-card .cc-metric-value {
                display: block;
                font-size: 11.5px;
                font-weight: bold;
                color: #111;
                line-height: 1.4;
            }
            .campaign-card .cc-interp {
                font-size: 11px;
                color: #444;
                font-style: italic;
                margin: 0;
                padding: 7px 14px;
                background-color: #f4f7ff;
                border-top: 1px solid #dde8f5;
                line-height: 1.55;
            }


            .bold    { font-weight: bold; }
            .no-wrap { white-space: nowrap; }

            .footer-divider {
                border: none;
                border-top: 1px solid #cccccc;
                margin: 30px 0 12px 0;
            }
            .footer-closing {
                text-align: center;
                font-size: 11px;
                color: #888888;
                margin: 0 0 10px 0;
            }
        </style>
    </head>
    <body>
    @php
        $report      = $Data['report'];
        $client      = $Data['client'];
        $publicPath  = $Data['public_path'];
        $generatedAt = $Data['generated_at'];
        $period      = $Data['period'];

        $clientName  = trim($client->name . ' ' . ($client->lastname ?? ''));
        $clientPhotoPath = $client->photo ? public_path('images/erp/clients/' . $client->photo) : null;
        $clientPhoto = null;
        if ($clientPhotoPath && file_exists($clientPhotoPath)) {
            $mime = mime_content_type($clientPhotoPath);
            $clientPhoto = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($clientPhotoPath));
        }

        // Section data shortcuts
        $sec1 = $report['report_header']         ?? [];
        $sec2 = $report['strategy_summary']      ?? [];
        $sec3 = $report['positioning_strategy']  ?? [];
        $sec4 = $report['conversion_strategy']   ?? [];
        $sec5 = $report['engagement_strategy']   ?? [];
        $sec6 = $report['campaign_results']      ?? [];
        $sec7 = $report['optimizations']         ?? [];
        $sec8 = $report['performance_evolution'] ?? [];
        $sec9 = $report['conclusions']           ?? [];

        $campaigns     = $sec6['campaigns']      ?? [];
        $globalMetrics = $sec6['global_metrics'] ?? [];
        $reportTitle   = $sec1['report_title']   ?? 'Reporte de Marketing Digital';
        $reportPeriod  = $sec1['period_analyzed'] ?? $period;
    @endphp

    <div id="main-content">

        {{-- ── Cover page ── --}}
        <div id="cover-section">
            {{-- Top meta bar --}}
            <div class="cover-top-meta">
                <div class="opzio-brand">
                    <img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('images/opzio-monogram-circle-purple-bg.webp'))) }}" alt="Opzio S.A.S">
                    <p class="opzio-name">Opzio S.A.S</p>
                    <p class="opzio-sub">Equipo de Marketing</p>
                </div>
                <p class="report-label">Informe de Marketing Digital</p>
                <p class="report-title-sm">{{ $reportTitle }}</p>
                <p class="report-meta-sm">{{ $clientName }} &mdash; {{ $reportPeriod }}</p>
                <div style="clear:both"></div>
            </div>

            @if($clientPhoto)
                <img src="{{ $clientPhoto }}" alt="{{ $clientName }}" class="cover-client-logo">
            @else
                <span class="cover-client-logo-placeholder"></span>
            @endif
            <p class="cover-report-type">{{ $sec1['report_type'] ?? 'Informe de Marketing Digital' }}</p>
            <p class="cover-title">{{ $reportTitle }}</p>
            <p class="cover-period">{{ $reportPeriod }}</p>
            <p class="cover-client-name">Preparado para: <strong>{{ $clientName }}</strong></p>
            @if(!empty($sec1['platform']))
                <p class="cover-client-name">Plataforma: {{ $sec1['platform'] }}</p>
            @endif
            <div class="cover-divider"></div>
            @if(!empty($sec2['business_intent']))
                <div class="cover-intent-box">
                    <p class="intent-label">Intención Estratégica</p>
                    <p class="intent-text">{{ $sec2['business_intent'] }}</p>
                </div>
            @endif
            <p class="cover-brand">Informe elaborado por el Equipo de Marketing &mdash; Opzio S.A.S</p>
        </div>

        {{-- ── Métricas Globales ── --}}
        <p class="section-title">Métricas Globales</p>
@php
            $investmentClean = trim(preg_replace('/\s*\(.*?\)/s', '', $globalMetrics['total_investment'] ?? 'N/D'));
        @endphp
        <div class="metrics-row">
            <div class="metric-box">
                <p class="metric-value">{{ number_format($globalMetrics['total_impressions'] ?? 0, 0, ',', '.') }}</p>
                <p class="metric-label">Impresiones</p>
            </div>
            <div class="metric-box">
                <p class="metric-value">{{ number_format($globalMetrics['total_reach'] ?? 0, 0, ',', '.') }}</p>
                <p class="metric-label">Alcance</p>
            </div>
            <div class="metric-box">
                <p class="metric-value">{{ $investmentClean ?: 'N/D' }}</p>
                <p class="metric-label">Inversión Total</p>
            </div>
        </div>

        {{-- ── Sección 2 ── --}}
        <p class="section-title">2. Resumen de la Estrategia</p>
        @if(!empty($sec2['strategy_organization']))
            <p class="field-label">Organización de la estrategia</p>
            <p class="field-text">{{ $sec2['strategy_organization'] }}</p>
        @endif
        @if(!empty($sec2['funnel_phases']))
            <p class="field-label">Fases del embudo</p>
            <ul class="bullet-list">
                @foreach($sec2['funnel_phases'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        @if(!empty($sec2['business_intent']))
            <p class="field-label">Intención de negocio</p>
            <p class="field-text">{{ $sec2['business_intent'] }}</p>
        @endif

        {{-- ── Sección 3 ── --}}
        <p class="section-title">3. Estrategia de Posicionamiento</p>
        @if(!empty($sec3['communication_objective']))
            <p class="field-label">Objetivo de comunicación</p>
            <p class="field-text">{{ $sec3['communication_objective'] }}</p>
        @endif
        @if(!empty($sec3['segmentation_type']))
            <p class="field-label">Tipo de segmentación</p>
            <p class="field-text">{{ $sec3['segmentation_type'] }}</p>
        @endif
        @if(!empty($sec3['target_profile']))
            <p class="field-label">Perfil del público objetivo</p>
            <p class="field-text">{{ $sec3['target_profile'] }}</p>
        @endif
        @if(!empty($sec3['optimization_approach']))
            <p class="field-label">Tipo de optimización</p>
            <p class="field-text">{{ $sec3['optimization_approach'] }}</p>
        @endif
        @if(!empty($sec3['investment_focus']))
            <p class="field-label">Enfoque de inversión</p>
            <p class="field-text">{{ $sec3['investment_focus'] }}</p>
        @endif

        {{-- ── Sección 4 ── --}}
        <p class="section-title">4. Estrategia de Conversión</p>
        @if(!empty($sec4['conversion_mechanism']))
            <p class="field-label">Mecanismo de conversión</p>
            <p class="field-text">{{ $sec4['conversion_mechanism'] }}</p>
        @endif
        @if(!empty($sec4['ad_formats']))
            <p class="field-label">Formatos de anuncios</p>
            <ul class="bullet-list">
                @foreach($sec4['ad_formats'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        @if(!empty($sec4['friction_reduction']))
            <p class="field-label">Reducción de fricción</p>
            <p class="field-text">{{ $sec4['friction_reduction'] }}</p>
        @endif
        @if(!empty($sec4['behavioral_segmentation']))
            <p class="field-label">Segmentación comportamental</p>
            <p class="field-text">{{ $sec4['behavioral_segmentation'] }}</p>
        @endif

        {{-- ── Sección 5 ── --}}
        <p class="section-title">5. Estrategia de Interacción</p>
        @if(!empty($sec5['engagement_logic']))
            <p class="field-label">Lógica de engagement</p>
            <p class="field-text">{{ $sec5['engagement_logic'] }}</p>
        @endif
        @if(!empty($sec5['audience_expansion']))
            <p class="field-label">Ampliación de audiencia</p>
            <p class="field-text">{{ $sec5['audience_expansion'] }}</p>
        @endif
        @if(!empty($sec5['remarketing_preparation']))
            <p class="field-label">Preparación para remarketing</p>
            <p class="field-text">{{ $sec5['remarketing_preparation'] }}</p>
        @endif

        {{-- ── Sección 6: Campañas ── --}}
        <p class="section-title">6. Resultados por Campaña</p>
        @foreach($campaigns as $camp)
            <div class="campaign-card">
                <p class="cc-name">{{ $camp['name'] ?? '' }}</p>
                <table class="cc-metrics-table">
                    <tr>
                        <td>
                            <span class="cc-metric-label">Inversión</span>
                            <span class="cc-metric-value">{{ $camp['investment'] ?? 'N/D' }}</span>
                        </td>
                        <td>
                            <span class="cc-metric-label">Alcance / Impresiones</span>
                            <span class="cc-metric-value">{{ $camp['reach_or_impressions'] ?? 'N/D' }}</span>
                        </td>
                        <td>
                            <span class="cc-metric-label">Interacciones / Conv.</span>
                            <span class="cc-metric-value">{{ $camp['interactions_or_conversions'] ?? 'N/D' }}</span>
                        </td>
                        <td>
                            <span class="cc-metric-label">Costo / Resultado</span>
                            <span class="cc-metric-value">{{ $camp['cost_per_result'] ?? 'N/D' }}</span>
                        </td>
                    </tr>
                </table>
                @if(!empty($camp['performance_interpretation']))
                    <p class="cc-interp">{{ $camp['performance_interpretation'] }}</p>
                @endif
            </div>
        @endforeach

        {{-- ── Sección 7 ── --}}
        <p class="section-title">7. Ajustes y Optimizaciones</p>
        @if(!empty($sec7['adjustments_made']))
            <p class="field-label">Ajustes realizados</p>
            <ul class="bullet-list">
                @foreach($sec7['adjustments_made'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        @if(!empty($sec7['budget_changes']))
            <p class="field-label">Cambios de presupuesto</p>
            <p class="field-text">{{ $sec7['budget_changes'] }}</p>
        @endif
        @if(!empty($sec7['segmentation_improvements']))
            <p class="field-label">Mejoras de segmentación</p>
            <p class="field-text">{{ $sec7['segmentation_improvements'] }}</p>
        @endif
        @if(!empty($sec7['decisions_during_campaign']))
            <p class="field-label">Decisiones durante la pauta</p>
            <p class="field-text">{{ $sec7['decisions_during_campaign'] }}</p>
        @endif

        {{-- ── Sección 8 ── --}}
        <p class="section-title">8. Evolución del Rendimiento</p>
        @if(!empty($sec8['reach_improvements']))
            <p class="field-label">Mejoras en alcance</p>
            <p class="field-text">{{ $sec8['reach_improvements'] }}</p>
        @endif
        @if(!empty($sec8['interaction_increase']))
            <p class="field-label">Incremento de interacción</p>
            <p class="field-text">{{ $sec8['interaction_increase'] }}</p>
        @endif
        @if(!empty($sec8['format_behavior']))
            <p class="field-label">Comportamiento de formatos</p>
            <p class="field-text">{{ $sec8['format_behavior'] }}</p>
        @endif
        @if(!empty($sec8['key_milestones']))
            <p class="field-label">Hitos del período</p>
            <ul class="bullet-list">
                @foreach($sec8['key_milestones'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif

        {{-- ── Sección 9 ── --}}
        <p class="section-title">9. Conclusiones Estratégicas</p>
        @if(!empty($sec9['best_performing']))
            <p class="field-label">Qué funcionó mejor</p>
            <p class="field-text">{{ $sec9['best_performing'] }}</p>
        @endif
        @if(!empty($sec9['most_efficient_formats']))
            <p class="field-label">Formatos más eficientes</p>
            <p class="field-text">{{ $sec9['most_efficient_formats'] }}</p>
        @endif
        @if(!empty($sec9['best_responding_segment']))
            <p class="field-label">Segmento más receptivo</p>
            <p class="field-text">{{ $sec9['best_responding_segment'] }}</p>
        @endif
        @if(!empty($sec9['future_opportunities']))
            <p class="field-label">Oportunidades futuras</p>
            <ul class="bullet-list">
                @foreach($sec9['future_opportunities'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif

        <hr class="footer-divider">
        <p class="footer-closing">{{ now()->translatedFormat('j \de F \de Y') }} &mdash; &copy; Opzio S.A.S - Equipo de Marketing</p>

    </div>
    </body>
</html>
