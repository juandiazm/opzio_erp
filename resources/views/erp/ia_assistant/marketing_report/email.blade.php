<div id="ia-send-modal" class="ia-modal-overlay d-none">
    <div class="ia-modal">
        <div class="ia-modal__header">
            <p class="ia-modal__title">Enviar Reporte por Correo</p>
            <button type="button" id="ia-send-modal-close" class="ia-modal__close">
                <i class="fa-light fa-xmark"></i>
            </button>
        </div>
        <div class="ia-modal__body">
            <div class="ia-form-group">
                <label class="ia-label">Correo electrónico del destinatario</label>
                <input type="email" id="ia-send-email-input" class="ia-input" placeholder="correo@ejemplo.com">
            </div>
            <p class="ia-send-hint">El reporte también se enviará a tu correo registrado.</p>
        </div>
        <div class="ia-modal__footer">
            <button type="button" id="ia-send-cancel-btn" class="ia-btn ia-btn--secondary">Cancelar</button>
            <button type="button" id="ia-send-confirm-btn" class="ia-btn ia-btn--primary">
                <i class="fa-light fa-paper-plane"></i>
                <span>Enviar</span>
            </button>
        </div>
    </div>
</div>