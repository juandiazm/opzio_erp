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
            <h3>Titular y vigencia</h3>
            <div class="input-container d-flex">
                <label for="create-contractable-type" class="input-title align-self-center">Fuente</label>
                <select id="create-contractable-type" class="form-select input-value"></select>
            </div>
            <div class="input-container d-flex">
                <label for="create-contractable-id" class="input-title align-self-center">Titular</label>
                <select id="create-contractable-id" class="form-select input-value"></select>
            </div>
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
    <div id="create-contract-variables-section" class="contracts-form-section contracts-contract-variables-section d-none">
        <h3>Variables del contrato</h3>
        <p class="contracts-help">Completa los valores propios de esta plantilla. Se guardarán con el contrato para futuras regeneraciones.</p>
        <div id="create-contract-variables" class="contracts-contract-variables"></div>
    </div>
    <div class="contracts-form-section contracts-editor-section">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Contenido</h3>
            <label class="form-check-label"><input type="checkbox" id="create-contract-generate" class="form-check-input" checked> Generar desde plantilla</label>
        </div>
        <textarea id="create-contract-content" class="form-control" rows="12" placeholder="Contenido del contrato o variables de plantilla"></textarea>
        <textarea id="create-contract-notes" class="form-control mt-3" rows="3" placeholder="Notas internas"></textarea>
    </div>
    <button class="btn btn-secondary" id="add-contract-button"><i class="fa-solid fa-plus"></i> Guardar contrato</button>
</div>