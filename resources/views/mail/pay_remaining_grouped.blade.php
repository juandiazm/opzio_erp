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
        .email-header { padding: 24px 30px; text-align: center; border-bottom: 3px solid #0153FF; }
        .email-logo-img { max-width: 180px; height: auto; display: block; margin: 0 auto; }
        .email-tagline { font-size: 10px; color: #999999; margin-top: 6px; text-transform: uppercase; letter-spacing: 2px; }
        .email-content { padding: 32px 30px; }
        .email-greeting { font-size: 20px; font-weight: 600; color: #1A1A1A; margin-bottom: 16px; }
        .email-text { font-size: 15px; color: #555555; line-height: 1.7; margin-bottom: 16px; }
        .email-text strong { color: #1A1A1A; }
        .ia-message-box { background-color: #EEF3FF; border-left: 3px solid #0153FF; padding: 16px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; font-size: 14px; color: #333333; line-height: 1.6; }
        .client-box { background-color: #F7F7F8; border-left: 3px solid #D0D0D0; padding: 16px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .client-name { font-size: 16px; font-weight: 600; color: #0153FF; }
        .client-nit { font-size: 13px; color: #555555; margin-top: 4px; }
        .section-label { font-size: 14px; color: #555555; margin: 20px 0 10px 0; }
        .income-card { background-color: #F7F7F8; border: 1px solid #E0E0E0; border-radius: 6px; padding: 16px 20px; margin: 12px 0; }
        .income-card-title { font-size: 13px; font-weight: 700; color: #0153FF; margin-bottom: 12px; border-bottom: 1px solid #E0E0E0; padding-bottom: 8px; }
        .detail-row { padding: 6px 0; border-bottom: 1px solid #F0F0F0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 12px; color: #888888; }
        .detail-value { font-size: 12px; font-weight: 600; color: #333333; }
        .detail-value.danger { color: #d9534f; }
        .btn-wrap { text-align: center; margin-top: 12px; }
        .email-button { display: inline-block; padding: 10px 24px; background-color: #0153FF; color: #FFFFFF !important; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600; }
        .summary-box { background-color: #0153FF; border-radius: 6px; padding: 16px 20px; margin: 20px 0; color: #FFFFFF; text-align: center; }
        .summary-box-title { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .summary-box-amount { font-size: 22px; font-weight: 700; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #0153FF; text-decoration: none; font-size: 12px; }
        .social-icons { margin-top: 12px; }
        .social-icon { display: inline-block; margin: 0 6px; }
        .social-icon img { height: 24px; width: auto; vertical-align: middle; }
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
            <img src="{{ asset('images/business_blues.png') }}" alt="Opzio S.A.S" class="email-logo-img">
            
        </div>
        <div class="email-content">
            <h1 class="email-greeting">Recordatorio de {{ count($Data['incomes']) > 1 ? 'pagos pendientes' : 'pago pendiente' }}</h1>
            @if(isset($Data['ia_message']) && $Data['ia_message'])
            <div class="ia-message-box">{{ $Data['ia_message'] }}</div>
            @endif
            <div class="client-box">
                <div class="client-name">{{ $Data['client']['name'] }}</div>
                <div class="client-nit">NIT: {{ $Data['client']['identification'] }}</div>
            </div>
            <p class="section-label">{{ count($Data['incomes']) > 1 ? 'Te compartimos los datos de tus órdenes de compra pendientes:' : 'Te compartimos los datos de tu orden de compra pendiente:' }}</p>
            @foreach($Data['incomes'] as $income)
            <div class="income-card">
                <div class="income-card-title">{{ $income['state'] == 2 ? 'Orden de compra' : 'Cotización' }} #{{ substr($income['unique_id'], -10) }}</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Pago oportuno</td>
                    <td align="right" class="detail-value">{{ \Carbon\Carbon::parse($income['timely_payment'])->format('d/m/Y') }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Fecha de corte</td>
                    <td align="right" class="detail-value">{{ \Carbon\Carbon::parse($income['cutoff_date'])->format('d/m/Y') }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Total</td>
                    <td align="right" class="detail-value">COP ${{ number_format($income['total'],0,',','.') }}</td>
                </tr></table>
                @if(isset($income['siigo_invoice_url']) && $income['siigo_invoice_url'])
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">F.E.</td>
                    <td align="right" class="detail-value"><a href="{{ $income['siigo_invoice_url'] }}" target="_blank" style="color:#0153FF;text-decoration:none;font-weight:600;">Ver Factura Electrónica</a></td>
                </tr></table>
                @endif
                @if(isset($income['days_overdue']) && $income['days_overdue'] > 0)
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Días de mora</td>
                    <td align="right" class="detail-value danger">{{ $income['days_overdue'] }} días</td>
                </tr></table>
                @endif
                <div class="btn-wrap">
                    <a href="{{ $income['payment_link'] }}" class="email-button">Pagar #{{ substr($income['unique_id'], -10) }}</a>
                </div>
            </div>
            @endforeach
            @if(count($Data['incomes']) > 1)
            <div class="summary-box">
                <div class="summary-box-title">Total a pagar</div>
                <div class="summary-box-amount">COP ${{ number_format(array_sum(array_column($Data['incomes'], 'total')),0,',','.') }}</div>
            </div>
            @endif
            <p class="email-text">Gracias por confiar en nosotros. Si necesitas ayuda, estamos aquí para ti.</p>
        </div>
        <div class="email-footer">
            <p class="footer-text">&copy; {{ date('Y') }} Opzio S.A.S &mdash; <a href="https://www.opzio.co" class="footer-link">www.opzio.co</a></p>
            <div class="social-icons">
                <a href="mailto:contabilidad@opzio.co" class="social-icon" target="_blank"><img src="{{ asset('images/social-media/mail.png') }}" alt="Email"></a>
                <a href="https://www.facebook.com/opziosyh/" class="social-icon" target="_blank"><img src="{{ asset('images/social-media/facebook.png') }}" alt="Facebook"></a>
                <a href="https://www.instagram.com/opziosyh/" class="social-icon" target="_blank"><img src="{{ asset('images/social-media/instagram.png') }}" alt="Instagram"></a>
                <a href="https://wa.me/573197536472" class="social-icon" target="_blank"><img src="{{ asset('images/social-media/whatsapp.png') }}" alt="WhatsApp"></a>
            </div>
        </div>
    </div>
</div>
</body>
</html>