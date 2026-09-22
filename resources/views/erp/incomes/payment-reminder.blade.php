<div id="income-payment-reminder-modal" class="income-payment-reminder-modal d-none" role="dialog" aria-modal="true" aria-labelledby="income-payment-reminder-title">
    <div class="income-payment-reminder-dialog">
        <div class="income-payment-reminder-header">
            <div>
                <span class="income-payment-reminder-kicker">RECORDATORIO DE PAGO</span>
                <h2 id="income-payment-reminder-title">Enviar recordatorio</h2>
                <p id="income-payment-reminder-summary" class="mb-0"></p>
            </div>
            <button type="button" class="income-payment-reminder-close" id="income-payment-reminder-close" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="income-payment-reminder-body">
            <label class="income-payment-reminder-field">
                <span>Canal</span>
                <select id="income-payment-reminder-channel" class="form-select">
                    <option value="sms">SMS</option>
                    <option value="whatsapp">WhatsApp</option>
                </select>
            </label>
            <label class="income-payment-reminder-field">
                <span>Numero guardado</span>
                <select id="income-payment-reminder-phone" class="form-select">
                    <option value="">Selecciona un numero</option>
                </select>
            </label>
            <label class="income-payment-reminder-field">
                <span>Otro numero</span>
                <input type="text" id="income-payment-reminder-manual-phone" class="form-control" inputmode="tel" placeholder="3000000000">
                <small>Si lo ingresas, se usara este numero.</small>
            </label>
            <p id="income-payment-reminder-empty" class="income-payment-reminder-empty d-none">No hay numeros etiquetados disponibles. Puedes escribir uno manualmente.</p>
        </div>
        <div class="income-payment-reminder-footer">
            <button type="button" class="btn btn-light" id="income-payment-reminder-cancel">Cancelar</button>
            <button type="button" class="btn btn-primary" id="income-payment-reminder-send"><i class="fa-solid fa-paper-plane"></i> Enviar</button>
        </div>
    </div>
</div>