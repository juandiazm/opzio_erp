<div class="tab-pane fade contracts-pane" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
    <div class="contracts-update-header d-flex justify-content-between align-items-center">
        <div><span class="contracts-muted-label">ID</span><strong id="update-contract-unique-id"></strong></div>
        <span id="update-contract-status-label" class="contract-status-badge"></span>
    </div>
    <div class="contracts-form-grid">
        <div class="contracts-form-section">
            <h3>Datos del contrato</h3>
            <div class="input-container d-flex"><label for="update-contract-type" class="input-title align-self-center">Tipo</label><select id="update-contract-type" class="form-select input-value contract-type-select"></select></div>
            <div class="input-container d-flex"><label for="update-contract-template" class="input-title align-self-center">Plantilla</label><select id="update-contract-template" class="form-select input-value"></select></div>
            <div class="input-container d-flex"><label for="update-contract-name" class="input-title align-self-center">Nombre</label><input type="text" id="update-contract-name" class="form-control input-value"></div>
            <div class="input-container d-flex"><label for="update-contract-subject" class="input-title align-self-center">Asunto</label><input type="text" id="update-contract-subject" class="form-control input-value"></div>
            <div class="input-container d-flex"><label for="update-contract-status" class="input-title align-self-center">Estado</label><select id="update-contract-status" class="form-select input-value"><option value="draft">Borrador</option><option value="generated">Generado</option><option value="sent">Enviado</option><option value="signed">Firmado</option><option value="expired">Vencido</option><option value="cancelled">Cancelado</option></select></div>
        </div>
        <div class="contracts-form-section">
            <h3>Titular y vigencia</h3>
            <div class="input-container d-flex"><label for="update-contractable-type" class="input-title align-self-center">Fuente</label><select id="update-contractable-type" class="form-select input-value"></select></div>
            <div class="input-container d-flex"><label for="update-contractable-id" class="input-title align-self-center">Titular</label><select id="update-contractable-id" class="form-select input-value"></select></div>
            <div class="input-container d-flex"><label for="update-contract-start-date" class="input-title align-self-center">Inicio</label><input type="date" id="update-contract-start-date" class="form-control input-value"></div>
            <div class="input-container d-flex"><label for="update-contract-end-date" class="input-title align-self-center">Vencimiento</label><input type="date" id="update-contract-end-date" class="form-control input-value"></div>
            <div class="input-container d-flex"><label class="input-title align-self-center">Enviado</label><p id="update-contract-sent-at" class="input-value mb-0"></p></div>
        </div>
    </div>
    <div id="update-contract-variables-section" class="contracts-form-section contracts-contract-variables-section d-none">
        <h3>Variables del contrato</h3>
        <p class="contracts-help">Estos valores pertenecen a este contrato y se reutilizan al regenerar el contenido.</p>
        <div id="update-contract-variables" class="contracts-contract-variables"></div>
    </div>
    <div class="contracts-form-section contracts-editor-section">
        <div class="d-flex justify-content-between align-items-center"><h3>Contenido</h3><label class="form-check-label"><input type="checkbox" id="update-contract-generate" class="form-check-input"> Regenerar desde plantilla</label></div>
        <textarea id="update-contract-content" class="form-control" rows="12"></textarea>
        <textarea id="update-contract-notes" class="form-control mt-3" rows="3" placeholder="Notas internas"></textarea>
    </div>
    <div class="contracts-action-bar">
        <button class="btn btn-secondary" id="update-contract-button"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
        <button class="btn btn-outline-secondary" id="update-contract-generate-button"><i class="fa-solid fa-wand-magic-sparkles"></i> Generar</button>
        <button class="btn btn-outline-secondary" id="update-contract-send-button"><i class="fa-solid fa-paper-plane"></i> Enviar</button>
        <button class="btn btn-outline-danger" id="update-contract-delete"><i class="fa-solid fa-trash-can"></i> Eliminar</button>
        <button class="btn btn-outline-success d-none" id="update-contract-restore"><i class="fa-solid fa-rotate-left"></i> Restaurar</button>
    </div>
</div>