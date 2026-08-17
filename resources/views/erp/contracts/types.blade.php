<div class="tab-pane fade contracts-pane" id="nav-types" role="tabpanel" aria-labelledby="nav-types-tab">
    <div class="contracts-form-section contracts-catalog-form">
        <h3>Nuevo tipo de contrato</h3>
        <div class="contracts-inline-form">
            <input type="text" id="contract-type-name" class="form-control" placeholder="Nombre del tipo">
            <input type="text" id="contract-type-description" class="form-control" placeholder="Descripción">
            <label class="form-check-label"><input type="checkbox" id="contract-type-active" class="form-check-input" checked> Activo</label>
            <button class="btn btn-secondary" id="contract-type-save"><i class="fa-solid fa-plus"></i> Agregar</button>
            <button class="btn btn-link d-none" id="contract-type-cancel">Cancelar</button>
        </div>
    </div>
    <div class="table-responsive"><table id="contract-types-table" class="table table-hover table-sm align-middle"><thead><tr><th>Nombre</th><th>Descripción</th><th>Plantillas</th><th>Contratos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="contract-types-table-body"></tbody></table></div>
</div>