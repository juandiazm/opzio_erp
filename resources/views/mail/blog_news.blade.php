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
        .post-image { width: 100%; height: auto; display: block; border-radius: 4px; margin-bottom: 24px; }
        .email-button-container { text-align: center; margin: 28px 0; }
        .email-button { display: inline-block; padding: 12px 32px; background-color: #220245; color: #FFFFFF !important; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 600; }
        .divider { height: 1px; background-color: #E6E6E6; margin: 24px 0; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #220245; text-decoration: none; font-size: 12px; }
        .social-icons { margin-top: 12px; }
        .social-icon { display: inline-block; margin: 0 6px; }
        .social-icon img { height: 24px; width: auto; vertical-align: middle; }
        .unsubscribe-text { font-size: 11px; color: #BBBBBB; margin-top: 16px; }
        .unsubscribe-text a { color: #BBBBBB; text-decoration: underline; }
        @media only screen and (max-width: 600px) {
            .email-container { border-radius: 0; }
            .email-header { padding: 20px; }
            .email-content { padding: 24px 20px; }
            .email-button { display: block; width: 100%; text-align: center; }
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
            <img src="{{ $Data['blog']['image_path'] }}" alt="{{ $Data['blog']['title'] }}" class="post-image">
            <h1 class="email-greeting">{{ $Data['blog']['title'] }}</h1>
            <p class="email-text">{{ $Data['blog']['brief'] }}</p>
            <div class="divider"></div>
            <div class="email-button-container">
                <a href="{{ config('app.APP_HOME_PAGE_URL').'blogs/view/'.$Data['blog']['url'] }}" class="email-button" target="_blank">Leer Más</a>
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
            <p class="unsubscribe-text"><a href="{{ config('app.APP_URL').'api/blog/unsubscribe/'.$Data['subscriber']['unique_id'] }}">Dejar de recibir notificaciones</a></p>
        </div>
    </div>
</div>
</body>
</html>
