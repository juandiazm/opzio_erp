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
        .email-greeting { font-size: 20px; font-weight: 600; color: #1A1A1A; margin-bottom: 8px; }
        .email-subheading { font-size: 14px; color: #888888; margin-bottom: 24px; }
        .report-item { background-color: #F7F7F8; border: 1px solid #E0E0E0; border-radius: 6px; padding: 16px 20px; margin: 12px 0; }
        .report-item-title { font-size: 13px; font-weight: 700; color: #220245; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #E0E0E0; }
        .detail-row { padding: 6px 0; border-bottom: 1px solid #F0F0F0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 12px; color: #888888; }
        .detail-value { font-size: 12px; font-weight: 600; color: #333333; }
        .detail-value.danger { color: #d9534f; }
        .summary-box { background-color: #220245; border-radius: 6px; padding: 20px; margin: 24px 0; color: #FFFFFF; text-align: center; }
        .summary-box-title { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .summary-row { font-size: 14px; padding: 4px 0; }
        .summary-row-label { opacity: 0.85; }
        .summary-row-value { font-weight: 700; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #220245; text-decoration: none; font-size: 12px; }
        @media only screen and (max-width: 600px) {
            .email-container { border-radius: 0; }
            .email-header { padding: 20px; }
            .email-content { padding: 24px 20px; }
            .email-footer { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-container">
        <div class="email-header">
            <img src="{{ asset('images/opzio-logo-wide-purple-transparent.webp') }}" alt="Opzio S.A.S" class="email-logo-img">
            
        </div>
        <div class="email-content">
            <h1 class="email-greeting">Reporte de Recordatorios de Pago</h1>
            <p class="email-subheading">Se han enviado {{ count($Data['report_message']) }} recordatorio{{ count($Data['report_message']) != 1 ? 's' : '' }} de pago</p>
            @foreach($Data['report_message'] as $message)
            <div class="report-item">
                <div class="report-item-title">Orden #{{ $message['order_id'] }}</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Cliente</td>
                    <td align="right" class="detail-value">{{ $message['client'] }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Identificación</td>
                    <td align="right" class="detail-value">{{ $message['identification'] }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Total</td>
                    <td align="right" class="detail-value">COP ${{ number_format($message['total'],0,',','.') }}</td>
                </tr></table>
                @if(isset($message['siigo_invoice_url']) && $message['siigo_invoice_url'])
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">F.E.</td>
                    <td align="right" class="detail-value"><a href="{{ $message['siigo_invoice_url'] }}" target="_blank" style="color:#220245;text-decoration:none;font-weight:600;">Ver Factura Electrónica</a></td>
                </tr></table>
                @endif
                @if(isset($message['days_overdue']) && $message['days_overdue'] > 0)
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Días de mora</td>
                    <td align="right" class="detail-value danger">{{ $message['days_overdue'] }} días</td>
                </tr></table>
                @endif
            </div>
            @endforeach
            <div class="summary-box">
                <div class="summary-box-title">Resumen</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="summary-row"><tr>
                    <td class="summary-row-label">Total de órdenes</td>
                    <td align="right" class="summary-row-value">{{ count($Data['report_message']) }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="summary-row"><tr>
                    <td class="summary-row-label">Monto total</td>
                    <td align="right" class="summary-row-value">COP ${{ number_format(array_sum(array_column($Data['report_message'], 'total')),0,',','.') }}</td>
                </tr></table>
            </div>
        </div>
        <div class="email-footer">
            <p class="footer-text">&copy; {{ date('Y') }} Opzio S.A.S &mdash; Sistema Interno</p>
            <p class="footer-text"><a href="https://www.opzio.co" class="footer-link">www.opzio.co</a></p>
        </div>
    </div>
</div>
</body>
</html>
      