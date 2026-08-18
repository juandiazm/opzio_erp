<div class="tab-pane fade contracts-pane" id="nav-types" role="tabpanel" aria-labelledby="nav-types-tab">
    <div class="contracts-form-section contracts-catalog-form contracts-collapsible-form">
        <div class="contracts-collapsible-form-header">
            <h3>Nuevo tipo de contrato</h3>
            <button type="button" id="contract-type-toggle" class="contracts-collapsible-toggle" data-collapsed-label="Agregar tipo de contrato" aria-label="Agregar tipo de contrato" title="Agregar tipo de contrato" aria-expanded="false" aria-controls="contract-type-form-body">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
        </div>
        <div id="contract-type-form-body" class="contracts-collapsible-form-body" hidden>
        <div class="contracts-inline-form">
            <input type="text" id="contract-type-name" class="form-control" placeholder="Nombre del tipo">
            <input type="text" id="contract-type-description" class="form-control" placeholder="Descripción">
            <label class="form-check-label"><input type="checkbox" id="contract-type-active" class="form-check-input" checked> Activo</label>
            <button class="btn btn-secondary" id="contract-type-save"><i class="fa-solid fa-plus"></i> Agregar</button>
            <button class="btn btn-link d-none" id="contract-type-cancel">Cancelar</button>
        </div>
        </div>
    </div>
    <div class="table-responsive"><table id="contract-types-table" class="table table-hover table-sm align-middle"><thead><tr><th>Nombre</th><th>Descripción</th><th>Plantillas</th><th>Contratos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="contract-types-table-body"></tbody></table></div>
</div>