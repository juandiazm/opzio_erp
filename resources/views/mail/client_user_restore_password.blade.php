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
        .info-box { background-color: #F7F7F8; border-left: 3px solid #D0D0D0; padding: 20px; margin: 24px 0; border-radius: 0 4px 4px 0; }
        .info-box.primary { border-left-color: #0153FF; }
        .info-label { font-size: 11px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .restore-code { font-size: 36px; font-weight: 700; color: #0153FF; letter-spacing: 4px; font-family: 'Courier New', Courier, monospace; display: block; text-align: center; padding: 8px 0; text-decoration: none; }
        .notice-box { background-color: #FFF8EC; border-left: 3px solid #F6AA1C; padding: 14px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; font-size: 13px; color: #555555; }
        .notice-box strong { color: #1A1A1A; }
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
            <h1 class="email-greeting">¡Restablecimiento de contraseña!</h1>
            <p class="email-text">Hola <strong>{{ $Data['name'] }}</strong>, hemos recibido una solicitud para restablecer la contraseña de tu cuenta <strong>{{ $Data['email'] }}</strong>.</p>
            <div class="info-box primary">
                <p class="info-label">Tu código de restablecimiento</p>
                <a href="{{ env('APP_URL') }}client?restore-email={{ $Data['email'] }}&restore-code={{ $Data['restore-code'] }}" class="restore-code" target="_blank">{{ $Data['restore-code'] }}</a>
            </div>
            <div class="notice-box">
                Este código expirará a las <strong>{{ \Carbon\Carbon::parse($Data['reset_password_date'])->format('Y-m-d H:i') }}</strong>. Si no solicitaste este cambio, ignora este mensaje.
            </div>
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
