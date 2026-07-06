<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIDDER S.A.S</title>
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
        .info-box { background-color: #F7F7F8; border-left: 3px solid #0153FF; padding: 16px 20px; margin: 24px 0; border-radius: 0 4px 4px 0; }
        .info-box-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .info-item { margin-bottom: 8px; }
        .info-label { font-size: 11px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-value { font-size: 14px; font-weight: 600; color: #333333; }
        .divider { height: 1px; background-color: #E6E6E6; margin: 20px 0; }
        .chat-log { background-color: #F7F7F8; border-radius: 4px; padding: 16px 20px; margin: 20px 0; }
        .chat-log-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .chat-message { padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px; line-height: 1.5; }
        .chat-message:last-child { margin-bottom: 0; }
        .chat-message.from-admin { background-color: #E8F0FF; color: #333333; text-align: right; }
        .chat-message.from-user { background-color: #FFFFFF; border: 1px solid #E0E0E0; color: #333333; text-align: left; }
        .chat-message strong { font-weight: 600; }
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
            <img src="{{ asset('images/business_blues.png') }}" alt="RIDDER S.A.S" class="email-logo-img">
            
        </div>
        <div class="email-content">
            <h1 class="email-greeting">Nuevo mensaje de chat</h1>
            <p class="email-text">Un usuario ha enviado un mensaje a través de la página web.</p>
            <div class="info-box">
                <div class="info-box-title">Datos del contacto</div>
                <div class="info-item">
                    <div class="info-label">Correo electrónico</div>
                    <div class="info-value">{{ $Data['chat']['client_email'] }}</div>
                </div>
            </div>
            <div class="chat-log">
                <div class="chat-log-title">Historial de conversación</div>
                @foreach ($Data['chat_messages'] as $message)
                    @if($message['is_admin'])
                        <div class="chat-message from-admin"><strong>RIDDER:</strong> {{ $message['message'] }}</div>
                    @else
                        <div class="chat-message from-user"><strong>USUARIO:</strong> {{ $message['message'] }}</div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="email-footer">
            <p class="footer-text">&copy; {{ date('Y') }} RIDDER S.A.S &mdash; Sistema Interno</p>
            <p class="footer-text"><a href="https://www.ridder.com.co" class="footer-link">www.ridder.com.co</a></p>
        </div>
    </div>
</div>
</body>
</html>
