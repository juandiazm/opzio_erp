<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $Data['subject'] ?? 'Notificacion' }}</title>
</head>
<body style="margin: 0; padding: 24px; color: #222; font-family: Arial, sans-serif; line-height: 1.5;">
    {!! $Data['content'] ?? '' !!}
</body>
</html>