@php($data = $Data ?? [])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $data['report_title'] ?? 'Reporte de resultados' }}</title>
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
		.info-box { background-color: #F7F7F8; border-left: 3px solid #220245; padding: 20px; margin: 20px 0; border-radius: 0 4px 4px 0; }
		.info-box-title { font-size: 12px; font-weight: 600; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
		.detail-row { padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
		.detail-row:last-child { border-bottom: none; }
		.detail-label { font-size: 13px; color: #888888; }
		.detail-value { font-size: 13px; font-weight: 600; color: #333333; }
		.detail-value.primary { color: #220245; }
		.attachment-box { background-color: #EEF3FF; border-left: 3px solid #220245; padding: 16px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; font-size: 14px; color: #333333; line-height: 1.6; }
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
			<h1 class="email-greeting">Tu reporte de resultados ha sido generado</h1>
			<p class="email-text">Hola, te compartimos el reporte solicitado por el equipo de Opzio. El documento completo se encuentra adjunto en formato PDF.</p>
			<div class="info-box">
				<div class="info-box-title">Detalle del reporte</div>
				<table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
					<td class="detail-label">Reporte</td>
					<td align="right" class="detail-value primary">{{ $data['report_title'] ?? 'Reporte de resultados' }}</td>
				</tr></table>
				<table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
					<td class="detail-label">Periodo analizado</td>
					<td align="right" class="detail-value">{{ $data['period'] ?? '-' }}</td>
				</tr></table>
				<table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
					<td class="detail-label">Generado</td>
					<td align="right" class="detail-value">{{ $data['generated_at'] ?? '-' }}</td>
				</tr></table>
				@if(!empty($data['recurrence']))
					<table width="100%" cellpadding="0" cellspacing="0" border="0" class="detail-row"><tr>
						<td class="detail-label">Modalidad</td>
						<td align="right" class="detail-value">{{ $data['recurrence'] }}</td>
					</tr></table>
				@endif
			</div>
			<div class="attachment-box">El archivo PDF se encuentra adjunto a este correo para su consulta y descarga.</div>
			<p class="email-text">Nuestro equipo permanece a tu disposición para cualquier consulta o soporte que requieras.</p>
		</div>
		<div class="email-footer">
			<p class="footer-text">&copy; {{ date('Y') }} Opzio S.A.S &mdash; <a href="https://www.opzio.co" class="footer-link">www.opzio.co</a></p>
			<div class="social-icons">
				<a href="mailto:soporte@opzio.co" class="social-icon" target="_blank"><img src="{{ asset('images/email-social/mail.svg') }}" alt="Email"></a>
			</div>
		</div>
	</div>
</div>
</body>
</html>
