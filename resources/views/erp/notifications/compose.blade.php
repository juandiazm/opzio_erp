<div id="notifications-compose-modal" class="notifications-modal d-none" role="dialog" aria-modal="true" aria-labelledby="notifications-modal-title">
    <div class="notifications-modal-dialog">
        <div class="notifications-modal-header">
            <div><span class="notifications-modal-kicker" id="notifications-modal-channel">EMAIL</span><h2 id="notifications-modal-title">Nueva notificacion</h2></div>
            <button type="button" class="notifications-icon-button" id="notifications-close-modal" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="notifications-modal-body">
            @include('erp.notifications.email-compose')
            @include('erp.notifications.sms-compose')
        </div>
        <div class="notifications-modal-footer"><button type="button" class="btn btn-light" id="notifications-cancel-modal">Cancelar</button><button type="button" class="btn btn-primary" id="notifications-save-modal"><i class="fa-solid fa-paper-plane"></i> Registrar envio</button></div>
    </div>
</div>