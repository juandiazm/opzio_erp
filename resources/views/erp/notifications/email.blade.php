<section class="tab-pane fade show active notifications-pane" id="notifications-email-pane" role="tabpanel" aria-labelledby="notifications-email-tab">
    <div class="notifications-list-header">
        <div>
            <h2>Correos enviados</h2>
            <p>Historial y reenvio de notificaciones por correo.</p>
        </div>
        <button type="button" class="btn btn-primary" id="notifications-new-email"><i class="fa-solid fa-plus"></i> Nueva notificacion</button>
    </div>
    <div class="notifications-filter-bar">
        <label class="notifications-filter"><span>Buscar</span><input type="search" id="notifications-email-search" class="form-control" placeholder="Asunto o destinatario" aria-label="Buscar correos"></label>
        <label class="notifications-filter"><span>Estado</span><select id="notifications-email-status" class="form-select"><option value="">Todos</option><option value="0">Pendiente</option><option value="1">Enviado</option><option value="2">Fallido</option></select></label>
        <div class="notifications-date-range" id="notifications-email-date-range">
            <span class="notifications-date-label">Fecha</span>
            <label class="notifications-date-field"><span>Desde</span><input type="date" id="notifications-email-date-from" class="notifications-date-input" value="{{ now()->format('Y-m-d') }}" aria-label="Fecha desde"></label>
            <span class="notifications-date-separator">/</span>
            <label class="notifications-date-field"><span>Hasta</span><input type="date" id="notifications-email-date-to" class="notifications-date-input" value="{{ now()->format('Y-m-d') }}" aria-label="Fecha hasta"></label>
        </div>
        <button type="button" class="btn btn-light notifications-refresh" id="notifications-email-refresh" title="Actualizar correos" aria-label="Actualizar correos"><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle notifications-table">
            <thead><tr><th>Asunto</th><th>Destinatarios</th><th>Estado</th><th>Programado</th><th>Enviado</th><th class="notifications-actions text-end">Acciones</th></tr></thead>
            <tbody id="notifications-email-list"><tr><td colspan="6" class="notifications-empty">Cargando...</td></tr></tbody>
        </table>
    </div>
    <div class="notifications-pagination" id="notifications-email-pagination"></div>
</section>