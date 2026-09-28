<div class="tab-pane fade show active" id="github-overview" role="tabpanel" aria-labelledby="github-overview-tab">
    <section class="github-overview-hero github-panel">
        <div><span class="github-kicker">Centro de control</span><h2>Estado de entrega</h2><p>Una lectura rapida de la salud de la integracion y del trabajo autonomo.</p></div>
        <div class="github-overview-actions"><button type="button" class="btn btn-primary" data-github-refresh><i class="fa-light fa-arrows-rotate"></i><span>Actualizar</span></button><a class="btn btn-secondary" href="{{ url('/admin/jira') }}"><i class="fa-brands fa-jira"></i><span>Ir a Jira</span></a></div>
    </section>
    <section class="github-metric-grid" aria-label="Indicadores GitHub">
        <article class="github-metric-card"><span>Conexion</span><strong data-github-metric="connection">-</strong><small data-github-metric-detail="connection">Sin comprobar</small></article>
        <article class="github-metric-card"><span>Proyectos activos</span><strong data-github-metric="projects">0</strong><small>Con automatizacion habilitada</small></article>
        <article class="github-metric-card"><span>Aprobaciones pendientes</span><strong data-github-metric="approvals">0</strong><small>Esperando decision humana</small></article>
        <article class="github-metric-card"><span>Ejecuciones activas</span><strong data-github-metric="active">0</strong><small>Trabajando o esperando pipeline</small></article>
        <article class="github-metric-card github-metric-card-alert"><span>Bloqueadas</span><strong data-github-metric="blocked">0</strong><small>Requieren intervencion</small></article>
        <article class="github-metric-card"><span>Completadas</span><strong data-github-metric="completed">0</strong><small>Historico disponible</small></article>
    </section>
    <div class="github-overview-grid">
        <section class="github-panel"><div class="github-panel-heading"><div><span class="github-kicker">Actividad reciente</span><h2>Ultimos movimientos</h2></div><button type="button" class="github-icon-button" data-github-refresh title="Actualizar actividad"><i class="fa-light fa-arrows-rotate"></i></button></div><div class="github-activity-list" data-github-activity><div class="github-empty">Cargando actividad...</div></div></section>
        <section class="github-panel"><div class="github-panel-heading"><div><span class="github-kicker">Atencion</span><h2>Necesita accion</h2></div></div><div class="github-attention-list" data-github-attention><div class="github-empty">Cargando estado...</div></div></section>
    </div>
</div>