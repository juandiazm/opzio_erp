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
        .info-box-title { font-size: 11px; color: #0153FF; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 8px; }
        .info-value { font-size: 14px; color: #333333; line-height: 1.6; }
        .email-button-container { text-align: center; margin: 28px 0; }
        .email-button { display: inline-block; padding: 12px 32px; background-color: #0153FF; color: #FFFFFF !important; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 600; }
        .post-image { width: 100%; height: auto; display: block; border-radius: 4px; margin-bottom: 20px; }
        .divider { height: 1px; background-color: #E6E6E6; margin: 24px 0; }
        .email-footer { padding: 24px 30px; text-align: center; border-top: 1px solid #E6E6E6; }
        .footer-text { font-size: 12px; color: #999999; margin-bottom: 4px; }
        .footer-link { color: #0153FF; text-decoration: none; font-size: 12px; }
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
            <img src="{{ asset('images/business_blues.png') }}" alt="RIDDER S.A.S" class="email-logo-img">
            
        </div>
        <div class="email-content">
            <h1 class="email-greeting">Post de LinkedIn pendiente</h1>
            <p class="email-text">Se ha generado un nuevo post para <strong>LinkedIn</strong> con inteligencia artificial. Revisa el contenido y apruébalo para publicarlo.</p>
            <img src="{{ $Data['image_url_complete'] }}" alt="Post LinkedIn" class="post-image">
            <div class="info-box">
                <div class="info-box-title">Contenido del post</div>
                <div class="info-value">{{ $Data['message'] }}</div>
            </div>
            <div class="divider"></div>
            <div class="email-button-container">
                <a href="{{ config('app.APP_URL').'api/linkedin/approve/'.$Data['unique_id'] }}" class="email-button" target="_blank">Aprobar Post</a>
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
