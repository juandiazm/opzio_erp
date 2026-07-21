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
        .detail-row { padding: 12px 0; border-bottom: 1px solid #F0F0F0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 13px; color: #888888; }
        .detail-value { font-size: 13px; font-weight: 600; color: #333333; }
        .detail-value.primary { color: #0153FF; }
        .info-box { background-color: #F7F7F8; border-left: 3px solid #0153FF; padding: 20px; margin: 24px 0; border-radius: 0 4px 4px 0; }
        .info-box-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #0153FF; text-decoration: none; font-size: 12px; }
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
            <h1 class="email-greeting">Nuevo pago recibido</h1>
            <p class="email-text">Se ha registrado un nuevo pago en el sistema. A continuación los detalles:</p>
            <div class="info-box">
                <div class="info-box-title">Información del pago</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Cliente</td>
                    <td align="right" class="detail-value primary">{{ $Data['client']['name'] }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Identificación</td>
                    <td align="right" class="detail-value">{{ $Data['client']['identification'] }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Ref. Orden</td>
                    <td align="right" class="detail-value primary">{{ $Data['income']['unique_id'] }}</td>
                </tr></table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Ref. Pago</td>
                    <td align="right" class="detail-value primary">{{ $Data['income_payment']['unique_id'] }}</td>
                </tr></table>
                @if(isset($Data['income']['siigo_invoice_url']) && $Data['income']['siigo_invoice_url'])
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">F.E.</td>
                    <td align="right" class="detail-value"><a href="{{ $Data['income']['siigo_invoice_url'] }}" target="_blank" style="color:#0153FF;text-decoration:none;font-weight:600;">Ver Factura Electrónica</a></td>
                </tr></table>
                @endif
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
                    <td class="detail-label">Valor</td>
                    <td align="right" class="detail-value">COP ${{ number_format($Data['income_payment']['total'],0,',','.') }}</td>
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