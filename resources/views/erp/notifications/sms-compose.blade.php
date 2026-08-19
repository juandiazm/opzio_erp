<form id="notifications-sms-form" class="notifications-compose-form d-none">
    <div class="notifications-form-grid">
        <label class="notifications-field"><span>Enviar el</span><input type="datetime-local" id="notifications-sms-send-at" class="form-control"></label>
        <div class="notifications-sms-counter"><strong id="notifications-sms-count">0</strong><span>destinatarios</span></div>
    </div>
    <div class="notifications-recipient-section">
        <div class="notifications-section-heading"><h3>Destinatarios</h3><label class="form-check-label"><input type="checkbox" id="notifications-sms-all-clients" class="form-check-input"> Todos los clientes activos</label></div>
        <input type="search" id="notifications-sms-client-search" class="form-control notifications-client-search" placeholder="Buscar cliente" aria-label="Buscar cliente para SMS">
        <div id="notifications-sms-client-list" class="notifications-client-list"></div>
        <label class="notifications-field"><span>Telefonos adicionales</span><textarea id="notifications-sms-manual" class="form-control" rows="2" placeholder="3000000000, +573000000000"></textarea></label>
    </div>
    <label class="notifications-field"><span>Mensaje</span><textarea id="notifications-sms-body" class="form-control notifications-sms-body" maxlength="1600" rows="8" required></textarea><small class="notifications-character-count"><span id="notifications-sms-character-count">0</span>/1600</small></label>
</form>