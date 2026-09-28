@php($data = $Data ?? [])
<!doctype html>
<html lang="es"><head><meta charset="UTF-8"><title>{{ $data['title'] ?? 'Actualizacion de desarrollo' }}</title></head>
<body style="background:#f4f1ec;color:#222;font-family:Arial,sans-serif;margin:0;padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table width="620" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #ddd6cc;padding:28px;">
<tr><td><p style="color:#6d655d;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">OPZIO ERP - Desarrollo autonomo</p><h1 style="font-size:24px;margin:0 0 18px;">{{ $data['title'] ?? 'Actualizacion de desarrollo' }}: {{ $data['issue']->issue_key ?? '-' }}</h1>
<p>{{ $data['message'] ?? 'Hay una nueva actualizacion de la ejecucion.' }}</p>
<table width="100%" cellpadding="6" cellspacing="0" style="background:#faf8f5;border:1px solid #e2ddd5;"><tr><td><strong>Proyecto</strong></td><td>{{ $data['issue']->project?->project_key ?? '-' }} - {{ $data['issue']->project?->name ?? '-' }}</td></tr><tr><td><strong>Titulo</strong></td><td>{{ $data['issue']->summary ?? '-' }}</td></tr><tr><td><strong>Estado Jira</strong></td><td>{{ $data['issue']->status ?? '-' }}</td></tr><tr><td><strong>Fase del flujo</strong></td><td>{{ $data['execution']->current_phase ?? '-' }}</td></tr><tr><td><strong>Intento</strong></td><td>{{ $data['execution']->attempt ?? 0 }}</td></tr><tr><td><strong>Agente</strong></td><td>{{ $data['execution']->agent?->name ?? '-' }}</td></tr></table>
@if (!empty($data['details']))<h2 style="font-size:16px;margin-bottom:8px;">Detalle</h2><table width="100%" cellpadding="6" cellspacing="0" style="background:#fffdf9;border:1px solid #e2ddd5;">@foreach ($data['details'] as $label => $value)<tr><td><strong>{{ $label }}</strong></td><td>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</td></tr>@endforeach</table>@endif
@if (!empty($data['issue_url']))<p><a href="{{ $data['issue_url'] }}" style="background:#1d6b46;color:#fff;display:inline-block;padding:12px 18px;text-decoration:none;">Abrir historia en Jira</a></p>@endif
<p style="font-size:12px;color:#716b63;">Este correo es informativo. Las decisiones del flujo se gestionan desde Jira y el ERP.</p></td></tr>
</table></td></tr></table>
</body></html>