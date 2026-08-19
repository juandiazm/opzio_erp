<div id="notifications-email-view-modal" class="notifications-modal d-none" role="dialog" aria-modal="true" aria-labelledby="notifications-email-view-title">
    <div class="notifications-modal-dialog notifications-email-view-dialog">
        <div class="notifications-modal-header">
            <div><span class="notifications-modal-kicker">EMAIL</span><h2 id="notifications-email-view-title">Detalle del correo</h2></div>
            <button type="button" class="notifications-icon-button" id="notifications-close-email-view" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="notifications-modal-body">
            <div class="notifications-email-view-meta">
                <div><span>Asunto</span><strong id="notifications-email-view-subject"></strong></div>
                <div><span>Destinatarios</span><strong id="notifications-email-view-recipients"></strong></div>
                <div><span>Remitente</span><strong id="notifications-email-view-from"></strong></div>
                <div><span>Estado</span><strong id="notifications-email-view-status"></strong></div>
                <div><span>Programado</span><strong id="notifications-email-view-send-at"></strong></div>
                <div><span>Enviado</span><strong id="notifications-email-view-sent-at"></strong></div>
            </div>
            <div class="notifications-email-view-content">
                <div class="notifications-section-heading"><h3>Contenido</h3></div>
                <article id="notifications-email-view-body" class="notifications-email-preview"></article>
            </div>
            <small id="notifications-email-view-attachments" class="notifications-existing-files"></small>
        </div>
        <div class="notifications-modal-footer">
            <button type="button" class="btn btn-primary d-none" id="notifications-edit-email-view"><i class="fa-solid fa-reply"></i> Modificar y reenviar</button>
        </div>
    </div>
</div>