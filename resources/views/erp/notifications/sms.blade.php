<section class="tab-pane fade notifications-pane" id="notifications-sms-pane" role="tabpanel" aria-labelledby="notifications-sms-tab">
    <div class="notifications-list-header">
        <div>
            <h2>SMS enviados</h2>
            <p>Historial, estado y reenvio de mensajes de texto.</p>
        </div>
        <button type="button" class="btn btn-primary" id="notifications-new-sms"><i class="fa-solid fa-plus"></i> Nuevo SMS</button>
    </div>
    <div class="notifications-filter-bar">
        <label class="notifications-filter"><span>Buscar</span><input type="search" id="notifications-sms-search" class="form-control" placeholder="Mensaje, nombre o telefono" aria-label="Buscar SMS"></label>
        <label class="notifications-filter"><span>Estado</span><select id="notifications-sms-status" class="form-select"><option value="">Todos</option><option value="0">Pendiente</option><option value="1">Enviado</option><option value="2">Fallido</option></select></label>
        <div class="notifications-date-range" id="notifications-sms-date-range">
            <span class="notifications-date-label">Fecha</span>
            <label class="notifications-date-field"><span>Desde</span><input type="date" id="notifications-sms-date-from" class="notifications-date-input" value="{{ now()->format('Y-m-d') }}" aria-label="Fecha desde"></label>
            <span class="notifications-date-separator">/</span>
            <label class="notifications-date-field"><span>Hasta</span><input type="date" id="notifications-sms-date-to" class="notifications-date-input" value="{{ now()->format('Y-m-d') }}" aria-label="Fecha hasta"></label>
        </div>
        <button type="button" class="btn btn-light notifications-refresh" id="notifications-sms-refresh" title="Actualizar SMS" aria-label="Actualizar SMS"><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle notifications-table">
            <thead><tr><th>Destinatario</th><th>Telefono</th><th>Mensaje</th><th>Estado</th><th>Programado</th><th>Enviado</th><th class="notifications-actions text-end">Acciones</th></tr></thead>
            <tbody id="notifications-sms-list"><tr><td colspan="7" class="notifications-empty">Cargando...</td></tr></tbody>
        </table>
    </div>
    <div class="notifications-pagination" id="notifications-sms-pagination"></div>
</section>