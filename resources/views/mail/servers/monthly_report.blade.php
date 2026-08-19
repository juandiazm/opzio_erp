<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opzio S.A.S</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #555555; background-color: #FFFFFF; }
        .email-wrapper { width: 100%; background-color: #FFFFFF; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #E0E0E0; border-radius: 6px; overflow: hidden; }
        .email-header { padding: 24px 30px; text-align: center; border-bottom: 3px solid #220245; }
        .email-logo-img { max-width: 180px; height: auto; display: block; margin: 0 auto; }
        .email-tagline { font-size: 10px; color: #999999; margin-top: 6px; text-transform: uppercase; letter-spacing: 2px; }
        .email-content { padding: 32px 30px; }
        .email-greeting { font-size: 20px; font-weight: 600; color: #1A1A1A; margin-bottom: 16px; }
        .email-text { font-size: 15px; color: #555555; line-height: 1.7; margin-bottom: 16px; }
        .email-text strong { color: #1A1A1A; }
        .info-box { background-color: #F7F7F8; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .info-box-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .detail-row { padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 13px; color: #888888; }
        .detail-value { font-size: 13px; font-weight: 600; color: #333333; }
        .detail-value.primary { color: #220245; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px 12px; }
        .summary-cell { width: 50%; background-color: #F2F2E8; border: 1px solid #E5E0D4; padding: 14px; vertical-align: top; }
        .summary-label { display: block; color: #667085; font-size: 10px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
        .summary-value { display: block; color: #220245; font-size: 20px; font-weight: 700; margin: 3px 0; }
        .summary-message { display: block; color: #555555; font-size: 12px; line-height: 1.45; }
        .section-title { color: #220245; font-size: 13px; font-weight: 600; margin: 24px 0 10px; text-transform: uppercase; letter-spacing: .5px; }
        .recommendations { background-color: #FFF8F1; border: 1px solid #F3D8BB; padding: 15px 18px; border-radius: 4px; }
        .recommendations ul { margin: 0; padding-left: 18px; }
        .recommendations li { color: #665143; font-size: 13px; margin: 3px 0; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #220245; text-decoration: none; font-size: 12px; }
        .social-icons { margin-top: 12px; }
        .social-icon { display: inline-block; margin: 0 6px; }
        .social-icon img { height: 24px; width: auto; vertical-align: middle; }
        @media only screen and (max-width: 600px) {
            .email-container { border-radius: 0; }
            .email-header { padding: 20px; }
            .email-content { padding: 24px 20px; }
            .email-footer { padding: 20px; }
            .summary-table { border-spacing: 4px; margin-left: -4px; margin-right: -4px; }
            .summary-cell { display: block; width: 100%; }
        }
    </style>
</head>
<body>
@php
    $report = $Data['report'];
    $project = $report['project'];
    $displayName = $project['display_name'] ?? $project['name'];
@endphp
<div class="email-wrapper">
    <div class="email-container">
        <div class="email-header">
            <img src="{{ asset('images/opzio-logo-wide-purple-transparent.webp') }}" alt="Opzio S.A.S" class="email-logo-img">
            <p class="email-tagline">Observabilidad y continuidad operativa</p>
        </div>
        <div class="email-content">
            <h1 class="email-greeting">Reporte mensual de estado</h1>
            @if(data_get($report, 'delivery.availability_alert'))
            <div class="info-box" style="border: 1px solid #E2B8B8; background-color: #FFF5F5;">
                <div class="info-box-title" style="color: #9B2226;">Alerta operativa</div>
                <p class="email-text" style="margin-bottom: 0;">La disponibilidad observada fue de <strong>{{ number_format((float) $report['delivery']['availability_percent'], 2, ',', '.') }}%</strong>. Este reporte completo fue dirigido a Soporte Opzio para su análisis.</p>
            </div>
            @endif
            <p class="email-text">Estimados, compartimos el estado de <strong>{{ $displayName }}</strong> durante {{ $report['period']['label'] }}. Encontrarán un resumen ejecutivo en este correo y el detalle técnico completo en el PDF adjunto.</p>
            <div class="info-box">
                <div class="info-box-title">Datos del proyecto</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Proyecto</td>
                    <td align="right" class="detail-value primary">{{ $displayName }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Período</td>
                    <td align="right" class="detail-value">{{ $report['period']['label'] }}</td>
                </tr></table>
            </div>
            <h2 class="section-title">Resumen ejecutivo</h2>
            <table class="summary-table" role="presentation">
                @foreach(array_chunk($report['stakeholder_summary'], 2) as $row)
                    <tr>
                        @foreach($row as $item)
                            <td class="summary-cell">
                                <span class="summary-label">{{ $item['label'] }}</span>
                                <span class="summary-value">{{ $item['value'] }}</span>
                                <span class="summary-message">{{ $item['message'] }}</span>
                            </td>
                        @endforeach
                        @if(count($row) < 2)<td class="summary-cell"></td>@endif
                    </tr>
                @endforeach
            </table>
            <h2 class="section-title">Lectura del período</h2>
            <p class="email-text">Se atendieron {{ number_format($report['metrics']['traffic']['requests_total'], 0, ',', '.') }} solicitudes con una cobertura de {{ number_format($report['metrics']['traffic']['coverage_percent'], 2, ',', '.') }}% de las ventanas observables. Se registraron {{ number_format($report['metrics']['operations']['events_total'], 0, ',', '.') }} eventos técnicos y se analizaron capacidad, rendimiento, errores y almacenamiento.</p>
            <h2 class="section-title">Observaciones de operación</h2>
            <div class="recommendations">
                <ul>
                    @foreach($report['recommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="email-text" style="margin-top: 20px;">Gracias por confiar en Opzio. Nuestro equipo continúa acompañando la operación del proyecto.</p>
        </div>
        <div class="email-footer">
            <p class="footer-text">&copy; {{ date('Y') }} Opzio S.A.S &mdash; <a href="https://www.opzio.co" class="footer-link">www.opzio.co</a></p>
            <div class="social-icons">
                <a href="mailto:contabilidad@opzio.co" class="social-icon" target="_blank"><img src="{{ asset('images/email-social/mail.svg') }}" alt="Email"></a>
                <a href="https://www.facebook.com/opziosyh/" class="social-icon" target="_blank"><img src="{{ asset('images/email-social/facebook.svg') }}" alt="Facebook"></a>
                <a href="https://www.instagram.com/opziosyh/" class="social-icon" target="_blank"><img src="{{ asset('images/email-social/instagram.svg') }}" alt="Instagram"></a>
                <a href="https://wa.me/573197536472" class="social-icon" target="_blank"><img src="{{ asset('images/email-social/whatsapp.svg') }}" alt="WhatsApp"></a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
