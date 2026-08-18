<div class="tab-pane fade contracts-pane" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
    <div class="contracts-form-grid">
        <div class="contracts-form-section">
            <h3>Datos del contrato</h3>
            <div class="input-container d-flex">
                <label for="create-contract-type" class="input-title align-self-center">Tipo</label>
                <select id="create-contract-type" class="form-select input-value contract-type-select"></select>
            </div>
            <div class="input-container d-flex">
                <label for="create-contract-template" class="input-title align-self-center">Plantilla</label>
                <select id="create-contract-template" class="form-select input-value"></select>
            </div>
            <div class="input-container d-flex">
                <label for="create-contract-name" class="input-title align-self-center">Nombre</label>
                <input type="text" id="create-contract-name" class="form-control input-value" placeholder="Contrato de prestación de servicios">
            </div>
            <div class="input-container d-flex">
                <label for="create-contract-subject" class="input-title align-self-center">Asunto</label>
                <input type="text" id="create-contract-subject" class="form-control input-value" placeholder="Contrato para @{{contractable.name}}">
            </div>
        </div>
        <div class="contracts-form-section">
            <h3>Vigencia</h3>
            <div class="input-container d-flex">
                <label for="create-contract-start-date" class="input-title align-self-center">Inicio</label>
                <input type="date" id="create-contract-start-date" class="form-control input-value">
            </div>
            <div class="input-container d-flex">
                <label for="create-contract-end-date" class="input-title align-self-center">Vencimiento</label>
                <input type="date" id="create-contract-end-date" class="form-control input-value">
            </div>
        </div>
    </div>
    <div class="contracts-form-section contracts-recurrence-section">
        <h3>Recurrencia</h3>
        <label class="form-check-label"><input type="checkbox" id="create-contract-recurrence-enabled" class="form-check-input"> Activar recurrencia</label>
        <div id="create-contract-recurrence-fields" class="contracts-recurrence-fields" hidden>
            <div class="contracts-form-grid">
                <div>
                    <div class="input-container d-flex"><label for="create-contract-recurrence-frequency" class="input-title align-self-center">Frecuencia</label><select id="create-contract-recurrence-frequency" class="form-select input-value"><option value="daily">Diaria</option><option value="weekly">Semanal</option><option value="monthly" selected>Mensual</option><option value="yearly">Anual</option></select></div>
                    <div class="input-container d-flex"><label for="create-contract-recurrence-interval" class="input-title align-self-center">Cada</label><input type="number" min="1" id="create-contract-recurrence-interval" class="form-control input-value" value="1"></div>
                </div>
                <div>
                    <div class="input-container d-flex"><label for="create-contract-recurrence-next-at" class="input-title align-self-center">Próxima creación</label><input type="datetime-local" id="create-contract-recurrence-next-at" class="form-control input-value"></div>
                    <div class="input-container d-flex"><label for="create-contract-recurrence-ends-at" class="input-title align-self-center">Finaliza</label><input type="datetime-local" id="create-contract-recurrence-ends-at" class="form-control input-value"></div>
                </div>
            </div>
            <label class="form-check-label"><input type="checkbox" id="create-contract-recurrence-send" class="form-check-input"> Enviar automáticamente el nuevo contrato</label>
        </div>
    </div>
    <div id="create-contract-sources-section" class="contracts-form-section">
        <div class="contracts-source-header">
            <h3>Fuentes del contrato</h3>
            <button type="button" class="btn btn-outline-secondary" data-contract-add-source="create"><i class="fa-solid fa-plus"></i> Agregar fuente</button>
        </div>
        <p id="create-contract-source-requirements" class="contracts-help">Selecciona una plantilla para conocer sus fuentes requeridas.</p>
        <div id="create-contract-sources" class="contracts-contract-sources"></div>
    </div>
    <div id="create-contract-variables-section" class="contracts-form-section contracts-contract-variables-section d-none">
        <h3>Variables del contrato</h3>
        <p class="contracts-help">Completa los valores propios de esta plantilla. Se guardarán con el contrato para futuras regeneraciones.</p>
        <div id="create-contract-variables" class="contracts-contract-variables"></div>
    </div>
    <div class="contracts-form-section">
        <h3>Notas internas</h3>
        <textarea id="create-contract-notes" class="form-control mt-3" rows="3" placeholder="Notas internas"></textarea>
    </div>
    <button class="btn btn-secondary" id="add-contract-button"><i class="fa-solid fa-plus"></i> Guardar contrato</button>
</div>