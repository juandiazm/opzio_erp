<div class="tab-pane fade show active" id="jira-configuration" role="tabpanel" aria-labelledby="jira-configuration-tab">
    <div class="jira-config-layout jira-single-config-layout">
        <form class="jira-panel jira-config-form" data-jira-connection-form>
            <div class="jira-form-grid">
                <label class="jira-field jira-field-wide"><span>Nombre interno</span><input name="name" maxlength="150" required value="{{ old('name', $connection?->name) }}" placeholder="Workspace principal"></label>
                <label class="jira-field jira-field-wide"><span>URL del sitio Jira</span><input name="site_url" type="url" required value="{{ old('site_url', $connection?->site_url) }}" placeholder="https://empresa.atlassian.net"></label>
                <label class="jira-field"><span>Correo del usuario tecnico</span><input name="email" type="email" required value="{{ old('email', $connection?->credential('email')) }}" autocomplete="off"></label>
                <label class="jira-field"><span>API token</span><input name="api_token" type="password" autocomplete="new-password"><small>En edicion, dejalo vacio para conservar el token.</small></label>
                <label class="jira-field"><span>Zona horaria</span><input name="timezone" value="{{ old('timezone', data_get($connection?->settings, 'timezone', 'America/Bogota')) }}" maxlength="80"></label>
            </div>
            <div class="jira-sync-count" role="status"><span>Historias de usuario sincronizadas</span><strong>{{ number_format($syncedStories) }}</strong></div>
            <div class="jira-sync-progress" data-jira-sync-progress hidden aria-live="polite">
                <div class="jira-sync-progress-meta"><span data-jira-sync-progress-label>Preparando sincronizacion...</span><strong data-jira-sync-progress-count>0</strong></div>
                <div class="jira-sync-progress-track" aria-hidden="true"><span data-jira-sync-progress-bar></span></div>
            </div>
            <div class="jira-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-light fa-floppy-disk"></i> Guardar conexion</button><button class="btn btn-secondary" type="button" data-jira-test @disabled(! $connection)><i class="fa-light fa-plug-circle-check"></i> Probar</button><button class="btn btn-secondary" type="button" data-jira-sync @disabled(! $connection)><i class="fa-light fa-arrows-rotate"></i> Sincronizar</button></div>
            <p class="jira-status" data-jira-config-status role="status"></p>
        </form>
    </div>
</div>
