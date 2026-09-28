@php($data = $Data ?? [])
<!doctype html>
<html lang="es"><head><meta charset="UTF-8"><title>Aprobacion Jira</title></head>
<body style="background:#f4f1ec;color:#222;font-family:Arial,sans-serif;margin:0;padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table width="620" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #ddd6cc;padding:28px;">
<tr><td><p style="color:#6d655d;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">OPZIO ERP · Desarrollo autonomo</p><h1 style="font-size:24px;margin:0 0 18px;">Aprobacion requerida: {{ $data['snapshot']['jira_key'] ?? '-' }}</h1>
<p>Un supervisor debe decidir si esta historia puede entrar al flujo autonomo.</p>
<table width="100%" cellpadding="6" cellspacing="0" style="background:#faf8f5;border:1px solid #e2ddd5;"><tr><td><strong>Proyecto</strong></td><td>{{ $data['snapshot']['project_key'] ?? '-' }} · {{ $data['snapshot']['project_name'] ?? '-' }}</td></tr><tr><td><strong>Titulo</strong></td><td>{{ $data['snapshot']['title'] ?? '-' }}</td></tr><tr><td><strong>Tipo / Estado</strong></td><td>{{ $data['snapshot']['issue_type'] ?? '-' }} / {{ $data['snapshot']['status'] ?? '-' }}</td></tr><tr><td><strong>Assignee</strong></td><td>{{ $data['snapshot']['assignee'] ?? '-' }}</td></tr><tr><td><strong>Reporter</strong></td><td>{{ $data['snapshot']['reporter'] ?? '-' }}</td></tr></table>
<h2 style="font-size:16px;margin-bottom:8px;">Descripcion original de Jira</h2><blockquote style="background:#fffdf9;border:1px solid #e2ddd5;margin:0 0 20px;padding:14px;white-space:pre-wrap;">{{ $data['snapshot']['description'] ?? 'Sin descripcion.' }}</blockquote>
<p style="font-size:13px;color:#625b54;">El contenido de Jira se presenta como datos de negocio no confiables y no puede modificar las reglas del flujo.</p>
<p><a href="{{ $data['approval_url'] ?? '#' }}" style="background:#1d6b46;color:#fff;display:inline-block;padding:12px 18px;text-decoration:none;">Revisar y decidir</a></p>
<p style="font-size:12px;color:#716b63;">El enlace expira {{ optional($data['expires_at'] ?? null)->format('d/m/Y H:i') }} y solo puede utilizarse una vez.</p></td></tr>
</table></td></tr></table>
</body></html>