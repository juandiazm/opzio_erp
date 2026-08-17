<div class="tab-pane fade contracts-pane" id="nav-templates" role="tabpanel" aria-labelledby="nav-templates-tab">
    <div class="contracts-form-section contracts-template-form">
        <div class="contracts-template-form-header">
            <h3 id="contract-template-form-title">Nueva plantilla</h3>
            <span class="contracts-help">El contenido se guarda como HTML seguro.</span>
        </div>
        <div class="contracts-form-grid">
            <div>
                <div class="input-container d-flex">
                    <label for="contract-template-type" class="input-title align-self-center">Tipo</label>
                    <select id="contract-template-type" class="form-select input-value contract-type-select"></select>
                </div>
                <div class="input-container d-flex">
                    <label for="contract-template-name" class="input-title align-self-center">Nombre</label>
                    <input type="text" id="contract-template-name" class="form-control input-value" placeholder="Contrato estándar">
                </div>
            </div>
            <div>
                <div class="input-container d-flex">
                    <label for="contract-template-subject" class="input-title align-self-center">Asunto</label>
                    <input type="text" id="contract-template-subject" class="form-control input-value" placeholder="Contrato de @{{contractable.name}}">
                </div>
                <p class="contracts-help">Puedes usar variables del catálogo o crear variables propias para completar al crear el contrato.</p>
            </div>
        </div>

        <div class="contracts-template-workspace">
            <section class="contracts-template-editor-panel" aria-labelledby="contract-template-editor-title">
                <div class="contracts-template-panel-header">
                    <h4 id="contract-template-editor-title">Editor</h4>
                    <span class="contracts-help">Formato y alineación</span>
                </div>
                <div id="contract-template-toolbar" class="contracts-rich-toolbar" role="toolbar" aria-label="Formato del contenido">
                    <button type="button" class="btn btn-light" data-template-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="underline" title="Subrayado" aria-label="Subrayado"><i class="fa-solid fa-underline"></i></button>
                    <span class="contracts-toolbar-divider" aria-hidden="true"></span>
                    <button type="button" class="btn btn-light" data-template-command="justifyLeft" title="Alinear a la izquierda" aria-label="Alinear a la izquierda"><i class="fa-solid fa-align-left"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="justifyCenter" title="Centrar" aria-label="Centrar"><i class="fa-solid fa-align-center"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="justifyRight" title="Alinear a la derecha" aria-label="Alinear a la derecha"><i class="fa-solid fa-align-right"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="justifyFull" title="Justificar" aria-label="Justificar"><i class="fa-solid fa-align-justify"></i></button>
                    <span class="contracts-toolbar-divider" aria-hidden="true"></span>
                    <select id="contract-template-block-format" class="form-select" aria-label="Formato de párrafo">
                        <option value="p">Párrafo</option>
                        <option value="h2">Título</option>
                        <option value="h3">Subtítulo</option>
                        <option value="blockquote">Cita</option>
                    </select>
                    <button type="button" class="btn btn-light" data-template-command="insertUnorderedList" title="Lista con viñetas" aria-label="Lista con viñetas"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="insertOrderedList" title="Lista numerada" aria-label="Lista numerada"><i class="fa-solid fa-list-ol"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button>
                    <span class="contracts-toolbar-divider" aria-hidden="true"></span>
                    <button type="button" class="btn btn-light" data-template-command="undo" title="Deshacer" aria-label="Deshacer"><i class="fa-solid fa-rotate-left"></i></button>
                    <button type="button" class="btn btn-light" data-template-command="redo" title="Rehacer" aria-label="Rehacer"><i class="fa-solid fa-rotate-right"></i></button>
                </div>
                <div class="contracts-template-variable-palette-header">
                    <span>Insertar variable</span>
                    <span class="contracts-help">Se conserva la sintaxis <code>@{{variable}}</code></span>
                </div>
                <div id="contract-template-variable-palette" class="contracts-template-variable-palette" aria-label="Variables disponibles"></div>
                <div id="contract-template-content-editor" class="contracts-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Contenido de la plantilla" data-placeholder="Escribe el contenido del contrato..."></div>
                <textarea id="contract-template-content" class="d-none" aria-hidden="true" tabindex="-1"></textarea>
            </section>

            <section class="contracts-template-preview-panel" aria-labelledby="contract-template-preview-title">
                <div class="contracts-template-panel-header">
                    <h4 id="contract-template-preview-title">Vista previa</h4>
                    <span class="contracts-help">Valores de ejemplo</span>
                </div>
                <div class="contracts-template-preview-subject" id="contract-template-preview-subject"></div>
                <article id="contract-template-preview" class="contracts-template-preview"></article>
            </section>
        </div>

        <section class="contracts-custom-variables" aria-labelledby="contract-template-custom-title">
            <div class="contracts-template-panel-header">
                <div>
                    <h4 id="contract-template-custom-title">Variables propias</h4>
                    <p class="contracts-help mb-0">Define campos que no existen en la base de datos. Su valor se solicitará al crear cada contrato.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" id="contract-template-add-variable"><i class="fa-solid fa-plus"></i> Agregar variable</button>
            </div>
            <div id="contract-template-custom-variables" class="contracts-custom-variable-list"></div>
        </section>

        <div class="contracts-inline-form mt-3">
            <label class="form-check-label"><input type="checkbox" id="contract-template-active" class="form-check-input" checked> Activa</label>
            <button class="btn btn-secondary" id="contract-template-save"><i class="fa-solid fa-plus"></i> Agregar</button>
            <button class="btn btn-link d-none" id="contract-template-cancel">Cancelar</button>
        </div>
    </div>
    <div class="table-responsive"><table id="contract-templates-table" class="table table-hover table-sm align-middle"><thead><tr><th>Nombre</th><th>Tipo</th><th>Versión</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="contract-templates-table-body"></tbody></table></div>
</div>