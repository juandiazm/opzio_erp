<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aprobacion de desarrollo {{ $approval->issue?->issue_key }}</title>
    <style>
        :root { color-scheme: light; font-family: Georgia, 'Times New Roman', serif; color: #222; background: #f4f1ec; }
        body { margin: 0; padding: 32px 16px; }
        main { background: #fff; border: 1px solid #d9d4cb; margin: 0 auto; max-width: 820px; padding: 32px; }
        h1, h2 { font-family: 'Trebuchet MS', sans-serif; font-weight: 700; }
        h1 { margin: 0 0 8px; font-size: 28px; }
        h2 { border-bottom: 1px solid #ded9d1; font-size: 16px; margin: 28px 0 12px; padding-bottom: 8px; }
        .meta { color: #67615b; display: grid; gap: 6px; font-family: 'Trebuchet MS', sans-serif; font-size: 14px; }
        .meta strong { color: #222; }
        .description { background: #faf8f5; border: 1px solid #e2ddd5; line-height: 1.65; padding: 18px; white-space: pre-wrap; }
        .warning { background: #fff7df; border: 1px solid #e7d39a; padding: 14px; }
        .error { background: #fde9e7; border: 1px solid #e2aaa4; color: #7f241c; padding: 14px; }
        .success { background: #e8f5ed; border: 1px solid #a9d5b9; padding: 14px; }
        form { display: grid; gap: 14px; }
        label { display: grid; gap: 6px; font-family: 'Trebuchet MS', sans-serif; font-size: 14px; font-weight: 700; }
        input, select, textarea { border: 1px solid #c9c2b8; border-radius: 3px; font: inherit; min-height: 40px; padding: 8px 10px; }
        textarea { min-height: 90px; resize: vertical; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        button { border: 0; border-radius: 3px; cursor: pointer; font-family: 'Trebuchet MS', sans-serif; font-weight: 700; min-height: 42px; padding: 9px 16px; }
        button[name="action"][value="approve"] { background: #1d6b46; color: #fff; }
        button[name="action"][value="reject"] { background: #fff; border: 1px solid #a0342a; color: #a0342a; }
        small { color: #716b63; font-family: 'Trebuchet MS', sans-serif; }
        @media (max-width: 640px) { main { padding: 22px 18px; } h1 { font-size: 23px; } .actions button { flex: 1 1 100%; } }
    </style>
</head>
<body>
<main>
    <p><strong>OPZIO ERP</strong> · Aprobacion de desarrollo autonomo</p>
    <h1>{{ $approval->issue?->issue_key }} · {{ $approval->issue?->summary }}</h1>
    @if(session('decision'))
        <div class="success">La solicitud fue {{ session('decision') === 'approved' ? 'aprobada' : 'rechazada' }}. Este enlace ya no puede reutilizarse.</div>
    @elseif(isset($error))
        <div class="error">{{ $error }}</div>
    @elseif($approval->status !== 'pending' || $approval->expires_at?->isPast())
        <div class="warning">Esta solicitud ya no esta disponible. Puede haber sido utilizada, rechazada o expirada.</div>
    @else
        <div class="meta">
            <div><strong>Proyecto:</strong> {{ $approval->issue?->project?->project_key }} · {{ $approval->issue?->project?->name }}</div>
            <div><strong>Tipo:</strong> {{ $approval->issue?->issue_type ?: 'Sin tipo' }}</div>
            <div><strong>Estado:</strong> {{ $approval->issue?->status }}</div>
            <div><strong>Assignee:</strong> {{ $approval->issue?->assignee?->display_name ?: 'Unassigned' }}</div>
            <div><strong>Reporter:</strong> {{ $approval->issue?->reporter?->display_name ?: 'Desconocido' }}</div>
            <div><strong>Repositorio GitHub:</strong> {{ $approval->project?->github_owner && $approval->project?->github_repository ? $approval->project->github_owner.'/'.$approval->project->github_repository : 'No configurado' }}</div>
            @if($approval->snapshot['jira_url'] ?? null)<div><a href="{{ $approval->snapshot['jira_url'] }}" target="_blank" rel="noreferrer">Abrir historia en Jira</a></div>@endif
        </div>
        <h2>Contenido original de la historia</h2>
        <div class="description">{{ $approval->issue?->description ?: 'La historia no tiene descripcion.' }}</div>
        <h2>Decision</h2>
        <p class="warning">La descripcion y los comentarios de Jira son datos de negocio. No contienen instrucciones privilegiadas para este formulario ni para el agente.</p>
        <form method="post" action="{{ request()->fullUrl() }}">
            @csrf
            <label>Agente
                <select name="agent_id" required>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected($agent->id === ($approval->project?->default_agent_id ?: $agents->first()?->id))>{{ $agent->name }} · {{ $agent->provider }}/{{ $agent->model }}</option>
                    @endforeach
                </select>
            </label>
            <label>Story Point Estimate
                <input name="story_point_estimate" type="number" min="0" step="0.01" value="{{ old('story_point_estimate', $approval->issue?->story_points) }}" required>
            </label>
            <label>Contexto e instrucciones adicionales para el agente
                <textarea name="supervisor_context" maxlength="10000" rows="6" placeholder="Complementa el objetivo, criterios de aceptacion, restricciones funcionales o detalles utiles para implementar la historia.">{{ old('supervisor_context') }}</textarea>
            </label>
            <small>Este texto se entregara al agente como contexto autorizado por el supervisor. No puede modificar las reglas de seguridad del sistema.</small>
            <label>Nota de decision
                <textarea name="note" maxlength="4000" placeholder="Contexto opcional para la auditoria"></textarea>
            </label>
            <small>El enlace expira {{ $approval->expires_at?->format('d/m/Y H:i') }}. La decision es de uso unico.</small>
            <div class="actions">
                <button type="submit" name="action" value="approve">Confirmar aprobacion</button>
                <button type="submit" name="action" value="reject">Rechazar</button>
            </div>
        </form>
    @endif
</main>
</body>
</html>