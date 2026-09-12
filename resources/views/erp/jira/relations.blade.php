<div class="tab-pane fade" id="jira-relations" role="tabpanel" aria-labelledby="jira-relations-tab">
    <section class="jira-panel jira-relations-context">
        <div class="jira-panel-title"><div><span class="jira-panel-kicker">Contexto de relación</span><h2>Proyecto de trabajo</h2></div><span class="jira-context-state" data-jira-context-state>Sin seleccionar</span></div>
        <div class="jira-context-row">
            <label class="jira-field"><span>Proyecto Jira</span><select data-jira-project-context><option value="">Selecciona un proyecto sincronizado</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->project_key }} · {{ $project->name }}</option>@endforeach</select></label>
            <div class="jira-context-summary" data-jira-context-summary hidden><strong data-jira-context-name></strong><div><span><b data-jira-context-clients>0</b> clientes</span><span><b data-jira-context-licenses>0</b> licencias</span><span><b data-jira-context-epics>0</b> épicas</span></div></div>
        </div>
    </section>

    <div class="jira-relations-grid">
        <section class="jira-panel jira-relation-workspace" data-jira-project-workspace hidden>
            <div class="jira-panel-title"><div><span class="jira-panel-kicker">Alcance comercial</span><h2>Clientes y licencias</h2></div><span data-jira-project-save-state>Sin cambios</span></div>
            <form data-jira-project-relation-form>
                <input type="hidden" name="jira_project_id" data-jira-project-id>
                <div class="jira-relation-block"><div class="jira-relation-block-heading"><h3>Clientes relacionados</h3><span data-jira-client-count>0 seleccionados</span></div><label class="jira-search-field"><i class="fa-light fa-magnifying-glass"></i><input type="search" data-jira-client-search placeholder="Buscar cliente"></label><div class="jira-choice-list" data-jira-client-list></div></div>
                <div class="jira-relation-block"><div class="jira-relation-block-heading"><h3>Licencias del alcance</h3><span data-jira-license-count>0 seleccionadas</span></div><label class="jira-search-field"><i class="fa-light fa-magnifying-glass"></i><input type="search" data-jira-license-search placeholder="Buscar licencia"></label><div class="jira-choice-list" data-jira-license-list></div></div>
                <div class="jira-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i> Guardar alcance</button></div>
            </form>
            <section class="jira-relation-block jira-epic-workspace" data-jira-epic-workspace hidden>
                <div class="jira-relation-block-heading"><div><span class="jira-panel-kicker">Asignación específica</span><h3>Épicas del proyecto</h3></div><span data-jira-epic-save-state>Sin cambios</span></div>
                <form class="jira-epic-form" data-jira-epic-relation-form>
                    <input type="hidden" name="jira_issue_id" data-jira-epic-id>
                    <label class="jira-field"><span>Épica del proyecto</span><select data-jira-epic-select required><option value="">Selecciona una épica</option></select></label>
                    <label class="jira-field"><span>Licencia específica</span><select name="license_id" data-jira-epic-license-select><option value="">Hereda el alcance del proyecto</option></select></label>
                    <div class="jira-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i> Guardar relación</button></div>
                </form>
            </section>
        </section>

        <section class="jira-panel jira-relation-workspace jira-employees-workspace">
            <div class="jira-panel-title"><div><span class="jira-panel-kicker">Atribución de trabajo</span><h2>Empleados y usuarios Jira</h2></div><span data-jira-employee-count>0 asignados</span></div>
            <label class="jira-search-field jira-employee-search"><i class="fa-light fa-magnifying-glass"></i><input type="search" data-jira-employee-search placeholder="Buscar empleado"></label>
            <div class="jira-table-wrap jira-relations-table-wrap"><table class="table table-sm align-middle jira-table jira-employees-table"><thead><tr><th>Empleado ERP</th><th>Usuario Jira asignado</th><th>Estado</th></tr></thead><tbody data-jira-employee-mappings><tr><td colspan="3" class="jira-empty">Cargando empleados...</td></tr></tbody></table></div>
        </section>
    </div>

</div>
