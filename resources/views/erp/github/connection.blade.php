<div class="tab-pane fade" id="github-connection" role="tabpanel" aria-labelledby="github-connection-tab">
    <div class="github-content-grid github-content-grid-narrow">
        <section class="github-panel">
            <div class="github-panel-heading"><div><span class="github-kicker">Integracion de codigo</span><h2>Conexion GitHub</h2><p>La credencial se cifra y nunca se devuelve al navegador.</p></div><span class="github-state-pill" data-github-connection-state>Sin configurar</span></div>
            <form class="github-form-grid" data-github-connection-form>
                <label class="github-field github-field-wide"><span>Nombre interno</span><input name="name" maxlength="150" required placeholder="GitHub principal"></label>
                <label class="github-field github-field-wide"><span>API base URL</span><input name="base_url" type="url" value="https://api.github.com" required></label>
                <label class="github-field github-field-wide"><span>Token de automatizacion</span><input name="token" type="password" autocomplete="new-password" placeholder="Dejalo vacio para conservarlo"><small>Usa el menor alcance posible para repositorios, pull requests y Actions.</small></label>
                <div class="github-form-actions github-field-wide"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i><span>Guardar conexion</span></button><button class="btn btn-secondary" type="button" data-github-connection-test><i class="fa-light fa-plug-circle-check"></i><span>Probar conexion</span></button></div>
            </form>
            <p class="github-status-message" data-github-connection-status role="status"></p>
        </section>
        <aside class="github-panel github-info-panel"><span class="github-kicker">Estado operativo</span><h2>Antes de activar proyectos</h2><ul><li>Configura y prueba la conexion.</li><li>Comprueba que el worker `ai-development` este activo.</li><li>Configura los repositorios desde Proyectos.</li><li>No compartas el token con agentes ni prompts.</li></ul><div class="github-connection-facts" data-github-connection-facts><div><span>Ultima prueba</span><strong>-</strong></div><div><span>Token</span><strong>-</strong></div></div></aside>
    </div>
</div>