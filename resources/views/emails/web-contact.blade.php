<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contacto desde opzio.co</title>
</head>
<body style="margin:0;padding:0;background:#F4F3EE;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F4F3EE;padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#FFFFFF;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(34,2,69,.08);">

        {{-- Header --}}
        <tr>
          <td style="background:#220245;padding:28px 36px 24px;text-align:center;">
            <span style="font-size:22px;font-weight:700;letter-spacing:0.12em;color:#FFFFFF;">OPZIO</span>
            <span style="display:inline-block;width:7px;height:7px;background:#F36803;border-radius:50%;vertical-align:super;margin-left:3px;"></span>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="padding:32px 36px 24px;">
            <p style="margin:0 0 20px;font-size:13px;font-weight:600;letter-spacing:0.08em;color:#F36803;text-transform:uppercase;">
              Nuevo mensaje de contacto — opzio.co
            </p>

            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:10px 0;border-bottom:1px solid #EEEEEE;">
                  <p style="margin:0;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Nombre</p>
                  <p style="margin:4px 0 0;font-size:15px;color:#220245;font-weight:600;">{{ $Data['name'] }}</p>
                </td>
              </tr>
              <tr>
                <td style="padding:10px 0;border-bottom:1px solid #EEEEEE;">
                  <p style="margin:0;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Correo</p>
                  <p style="margin:4px 0 0;font-size:15px;color:#220245;">
                    <a href="mailto:{{ $Data['email'] }}" style="color:#220245;text-decoration:underline;">{{ $Data['email'] }}</a>
                  </p>
                </td>
              </tr>
              <tr>
                <td style="padding:10px 0;">
                  <p style="margin:0;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Mensaje</p>
                  <p style="margin:8px 0 0;font-size:14px;color:#444;line-height:1.65;white-space:pre-wrap;">{{ $Data['message'] }}</p>
                </td>
              </tr>
            </table>

            <div style="margin-top:24px;padding:14px 18px;background:#F4F3EE;border-radius:6px;">
              <p style="margin:0;font-size:12px;color:#666;">
                Responde directamente a este correo para contactar a
                <strong style="color:#220245;">{{ $Data['name'] }}</strong>
                en <a href="mailto:{{ $Data['email'] }}" style="color:#220245;">{{ $Data['email'] }}</a>.
              </p>
            </div>
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="padding:16px 36px 24px;text-align:center;">
            <p style="margin:0;font-size:11px;color:#AAAAAA;">
              Recibido desde <a href="https://opzio.co" style="color:#220245;text-decoration:none;">opzio.co</a>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
