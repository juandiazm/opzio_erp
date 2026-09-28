<div class="tab-pane fade" id="github-agents" role="tabpanel" aria-labelledby="github-agents-tab">
    <section class="github-panel">
        <div class="github-panel-heading">
            <div>
                <span class="github-kicker">Catalogo de ejecucion</span>
                <h2>Agentes disponibles</h2>
                <p>Luna es el valor inicial; Terra y Sol ofrecen distintos niveles de capacidad y costo.</p>
            </div>
            <span data-github-agent-count>0</span>
        </div>
        <div class="github-content-grid github-agents-layout">
            <form class="github-form-grid github-agent-form" data-github-agent-form>
                <label class="github-field github-field-wide"><span>Agente existente</span><select name="id" data-github-agent-selector><option value="">Nuevo agente</option></select><small>Selecciona un agente para revisar o actualizar su configuracion.</small></label>
                <label class="github-field"><span>Nombre</span><input name="name" maxlength="120" required placeholder="Luna"></label>
                <label class="github-field"><span>Provider</span><select name="provider" required><option value="command">Runner autorizado</option></select></label>
                <label class="github-field"><span>Modelo</span><select name="model" data-github-model required><option value="gpt-5.6-luna">gpt-5.6-luna · Luna · Bajo costo</option><option value="gpt-5.6-terra">gpt-5.6-terra · Terra · Costo medio</option><option value="gpt-5.6-sol">gpt-5.6-sol · Sol · Alto costo</option></select></label>
                <label class="github-field github-field-wide"><span>Descripcion breve</span><input name="description" maxlength="255" placeholder="Para que conviene usar este modelo"></label>
                <label class="github-field"><span>Nivel de costo</span><select name="cost_tier" required><option value="low">Bajo costo</option><option value="medium" selected>Costo medio</option><option value="high">Alto costo</option></select></label>
                <label class="github-field github-field-wide"><span>Comando del runner</span><input name="command" maxlength="4000" placeholder="Comando local o runner autorizado"><small>Se guarda cifrado y recibe `OPZIO_AI_MODEL` para seleccionar el modelo.</small></label>
                <label class="github-check-card"><input type="checkbox" name="enabled" value="1" checked><span><strong>Habilitado</strong><small>Puede ser seleccionado en aprobaciones.</small></span></label>
                <label class="github-check-card"><input type="checkbox" name="is_default" value="1"><span><strong>Predeterminado</strong><small>Se selecciona por defecto en nuevos proyectos.</small></span></label>
                <div class="github-form-actions github-field-wide"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i><span>Guardar agente</span></button><button class="btn btn-secondary" type="button" data-github-agent-clear>Limpiar</button></div>
                <p class="github-status-message github-field-wide" data-github-agent-status role="status"></p>
            </form>
            <div class="github-table-wrap">
                <table class="table github-table">
                    <thead><tr><th>Agente</th><th>Modelo</th><th>Descripcion</th><th>Costo</th><th>Estado</th><th>Runner</th><th></th></tr></thead>
                    <tbody data-github-agents><tr><td colspan="7" class="github-empty">Cargando agentes...</td></tr></tbody>
                </table>
            </div>
        </div>
    </section>
</div>
