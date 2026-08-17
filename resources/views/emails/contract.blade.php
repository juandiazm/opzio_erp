<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $Data['contract']['subject'] ?? 'Contrato' }}</title>
</head>
<body>
    <p>Hola {{ $Data['recipient_name'] ?? '' }},</p>
    <p>Te compartimos el contrato <strong>{{ $Data['contract']['name'] ?? '' }}</strong>.</p>
    <div>{!! $Data['contract']['content'] ?? '' !!}</div>
    <p>Identificador: {{ $Data['contract']['unique_id'] ?? '' }}</p>
</body>
</html>