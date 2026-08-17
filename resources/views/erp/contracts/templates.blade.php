<div class="tab-pane fade contracts-pane" id="nav-templates" role="tabpanel" aria-labelledby="nav-templates-tab">
    <div class="contracts-form-section contracts-template-form">
        <h3 id="contract-template-form-title">Nueva plantilla</h3>
        <div class="contracts-form-grid">
            <div><div class="input-container d-flex"><label for="contract-template-type" class="input-title align-self-center">Tipo</label><select id="contract-template-type" class="form-select input-value contract-type-select"></select></div><div class="input-container d-flex"><label for="contract-template-name" class="input-title align-self-center">Nombre</label><input type="text" id="contract-template-name" class="form-control input-value" placeholder="Contrato estándar"></div></div>
            <div><div class="input-container d-flex"><label for="contract-template-subject" class="input-title align-self-center">Asunto</label><input type="text" id="contract-template-subject" class="form-control input-value" placeholder="Contrato de @{{contractable.name}}"></div><p class="contracts-help">Variables: @{{contractable.name}}, @{{contractable.email}}, @{{contractable.phone}}, @{{contractable.identification}}, @{{contract.start_date}}, @{{contract.end_date}}, @{{contract.type}}</p></div>
        </div>
        <textarea id="contract-template-content" class="form-control" rows="10" placeholder="Escribe aquí el contenido HTML o texto de la plantilla"></textarea>
        <div class="contracts-inline-form mt-3"><label class="form-check-label"><input type="checkbox" id="contract-template-active" class="form-check-input" checked> Activa</label><button class="btn btn-secondary" id="contract-template-save"><i class="fa-solid fa-plus"></i> Agregar</button><button class="btn btn-link d-none" id="contract-template-cancel">Cancelar</button></div>
    </div>
    <div class="table-responsive"><table id="contract-templates-table" class="table table-hover table-sm align-middle"><thead><tr><th>Nombre</th><th>Tipo</th><th>Versión</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="contract-templates-table-body"></tbody></table></div>
</div>