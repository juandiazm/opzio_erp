@php($data = $Data ?? [])
<!doctype html>
<html lang="es"><head><meta charset="UTF-8"><title>Revision QA requerida</title></head>
<body style="background:#f4f1ec;color:#222;font-family:Arial,sans-serif;margin:0;padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table width="620" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #ddd6cc;padding:28px;">
<tr><td><p style="color:#6d655d;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">OPZIO ERP - Desarrollo autonomo</p><h1 style="font-size:24px;margin:0 0 18px;">Revision QA requerida: {{ $data['issue']->issue_key ?? '-' }}</h1>
<p>La implementacion ya fue desplegada en QA y esta lista para revision. Este mensaje es informativo y no requiere una decision de aprobacion.</p>
<table width="100%" cellpadding="6" cellspacing="0" style="background:#faf8f5;border:1px solid #e2ddd5;"><tr><td><strong>Proyecto</strong></td><td>{{ $data['issue']->project?->project_key ?? '-' }} - {{ $data['issue']->project?->name ?? '-' }}</td></tr><tr><td><strong>Titulo</strong></td><td>{{ $data['issue']->summary ?? '-' }}</td></tr><tr><td><strong>Tipo / Estado</strong></td><td>{{ $data['issue']->issue_type ?? '-' }} / {{ $data['issue']->status ?? 'QA' }}</td></tr><tr><td><strong>Agente</strong></td><td>{{ $data['execution']->agent?->name ?? '-' }}</td></tr><tr><td><strong>Branch</strong></td><td>{{ $data['execution']->feature_branch ?? '-' }}</td></tr><tr><td><strong>QA pipeline</strong></td><td>{{ $data['execution']->qa_workflow_run_id ?? '-' }}</td></tr></table>
<p style="font-size:13px;color:#625b54;">Por favor valida el comportamiento de la historia en el ambiente QA y registra cualquier observacion en Jira.</p>
@if (!empty($data['issue_url']))<p><a href="{{ $data['issue_url'] }}" style="background:#1d6b46;color:#fff;display:inline-block;padding:12px 18px;text-decoration:none;">Abrir historia en Jira</a></p>@endif
</td></tr>
</table></td></tr></table>
</body></html>