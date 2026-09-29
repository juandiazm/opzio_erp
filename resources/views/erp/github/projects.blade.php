<div class="tab-pane fade" id="github-projects" role="tabpanel" aria-labelledby="github-projects-tab">
    <div class="github-content-grid github-projects-layout">
        <section class="github-panel github-project-list-panel"><div class="github-panel-heading"><div><span class="github-kicker">Reglas de entrada</span><h2>Proyectos Jira</h2><p>Selecciona un proyecto para editar su mapping y allowlist.</p></div><span data-github-project-count>0</span></div><div class="github-project-filters"><label class="github-search-field"><i class="fa-light fa-magnifying-glass"></i><input type="search" placeholder="Buscar proyecto..." data-github-project-search></label><select data-github-project-filter aria-label="Filtrar proyectos"><option value="">Todos</option><option value="configured">Configurados</option><option value="enabled">Activos</option><option value="unconfigured">No configurados</option></select></div><div class="github-project-list" data-github-project-list><div class="github-empty">Cargando proyectos...</div></div><div class="github-pagination" data-github-project-pagination></div></section>
        <section class="github-panel github-project-editor" data-github-project-editor hidden>
            <div class="github-panel-heading"><div><span class="github-kicker">Configuracion seleccionada</span><h2 data-github-project-title>Proyecto</h2><p data-github-project-subtitle>Mapping Jira hacia GitHub.</p></div><span class="github-state-pill" data-github-project-state>Sin guardar</span></div>
            <form data-github-project-form>
                <input type="hidden" name="github_connection_id" data-github-connection-id>
                <input type="hidden" name="jira_project_id" data-github-project-id>
                <div class="github-form-grid">
                    <label class="github-check-card github-field-wide"><input type="checkbox" name="enabled" value="1"><span><strong>Habilitar automatizacion</strong><small>Solo las historias que cumplan todas las reglas podran solicitar aprobacion.</small></span></label>
                    <label class="github-field"><span>Owner GitHub</span><input name="github_owner" required></label><label class="github-field"><span>Repositorio</span><input name="github_repository" required></label>
                    <label class="github-field"><span>Rama base</span><input name="base_branch" value="qa" required></label><label class="github-field"><span>Agente por defecto</span><select name="default_agent_id" data-github-default-agent required></select></label>
                    <label class="github-field"><span>Max. intentos agente</span><input name="max_execution_attempts" type="number" min="1" max="50" value="5" required></label><label class="github-field"><span>Max. fallos CI/CD</span><input name="max_ci_attempts" type="number" min="1" max="3" value="3" required></label>
                    <label class="github-field"><span>Max. minutos</span><input name="max_execution_minutes" type="number" min="1" max="1440" value="120" required></label><label class="github-field"><span>Fallos consecutivos</span><input name="max_consecutive_failures" type="number" min="1" max="20" value="3" required></label>
                    <fieldset class="github-option-group github-field-wide"><legend>Tipos de issue habilitados</legend><div data-github-issue-types><span class="github-empty">Selecciona un proyecto.</span></div></fieldset>
                    <fieldset class="github-option-group github-field-wide"><legend>Assignees habilitados</legend><div data-github-assignees><span class="github-empty">Selecciona un proyecto.</span></div></fieldset>
                </div>
                <div class="github-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i><span>Guardar reglas</span></button><button class="btn btn-secondary" type="button" data-github-project-scan><i class="fa-light fa-radar"></i><span>Escanear candidatas</span></button></div>
            </form><p class="github-status-message" data-github-project-status role="status"></p>
        </section>
        <section class="github-panel github-project-empty" data-github-project-empty><i class="fa-light fa-diagram-project"></i><h2>Selecciona un proyecto</h2><p>Elige un proyecto Jira para configurar el repositorio y sus reglas de entrada.</p></section>
    </div>
</div>