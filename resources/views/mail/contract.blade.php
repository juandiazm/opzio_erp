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
        .email-content { padding: 32px 30px; }
        .email-greeting { font-size: 20px; font-weight: 600; color: #1A1A1A; margin-bottom: 16px; }
        .email-text { font-size: 15px; color: #555555; line-height: 1.7; margin-bottom: 16px; }
        .email-text strong { color: #1A1A1A; }
        .info-box { background-color: #F7F7F8; border: 1px solid #E6E6E6; padding: 20px; margin: 24px 0; border-radius: 4px; }
        .info-box-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .detail-row { padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 13px; color: #888888; }
        .detail-value { font-size: 13px; font-weight: 600; color: #333333; }
        .detail-value.primary { color: #220245; }
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
            <h1 class="email-greeting">Documento contractual</h1>
            <p class="email-text">Hola <strong>{{ $Data['recipient_name'] ?? '' }}</strong>,</p>
            <p class="email-text">Te compartimos el documento contractual en formato PDF. Lo encontrarás adjunto a este correo.</p>
            <div class="info-box">
                <div class="info-box-title">Datos del contrato</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Titular</td>
                        <td align="right" class="detail-value primary">{{ $Data['contract']['holder'] ?? '' }}</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Nombre</td>
                        <td align="right" class="detail-value">{{ $Data['contract']['name'] ?? '' }}</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Asunto</td>
                        <td align="right" class="detail-value">{{ $Data['contract']['subject'] ?? '' }}</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Tipo</td>
                        <td align="right" class="detail-value">{{ $Data['contract']['type'] ?? '' }}</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Identificador</td>
                        <td align="right" class="detail-value">{{ $Data['contract']['unique_id'] ?? '' }}</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row">
                    <tr>
                        <td class="detail-label">Vigencia</td>
                        <td align="right" class="detail-value">{{ $Data['contract']['start_date'] ?: 'Sin inicio' }} - {{ $Data['contract']['end_date'] ?: 'Sin vencimiento' }}</td>
                    </tr>
                </table>
            </div>
            @if(!empty($Data['contract']['signature_url']))
                <p class="email-text">Cuando el documento esté firmado, puedes cargarlo desde este enlace:</p>
                <p style="margin: 24px 0; text-align: center;"><a href="{{ $Data['contract']['signature_url'] }}" style="display: inline-block; padding: 12px 18px; color: #FFFFFF; background-color: #220245; text-decoration: none; border-radius: 4px; font-weight: 600;">Cargar documento firmado</a></p>
            @endif
            <p class="email-text">Para cualquier inquietud, puedes responder directamente a este mensaje.</p>
        </div>
        <div class="email-footer">
            <p class="footer-text">&copy; {{ date('Y') }} Opzio S.A.S &mdash; Sistema Interno</p>
            <p class="footer-text"><a href="mailto:legal@opzio.co" class="footer-link">legal@opzio.co</a></p>
        </div>
    </div>
</div>
</body>
</html>
