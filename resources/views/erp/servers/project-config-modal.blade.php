<div id="servers-project-config-modal" class="servers-project-config-modal" hidden aria-hidden="true">
    <div class="servers-project-config-dialog" role="dialog" aria-modal="true" aria-labelledby="servers-project-config-title">
        <div class="servers-project-config-header">
            <div>
                <span class="servers-config-eyebrow">Proyecto de servidor</span>
                <h2 id="servers-project-config-title">Configuración de notificaciones</h2>
                <p id="servers-project-config-project" class="servers-project-config-project"></p>
            </div>
            <button type="button" id="servers-project-config-close" class="servers-modal-icon-button" aria-label="Cerrar configuración" title="Cerrar">
                <i class="fa-light fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div id="servers-project-config-status" class="servers-project-config-status" role="status" aria-live="polite"></div>
        <form id="servers-project-config-form">
            <div class="servers-project-config-field">
                <label for="servers-project-config-client-select">Cliente asociado</label>
                <select id="servers-project-config-client-select" class="form-select" data-search-placeholder="Buscar cliente..."></select>
            </div>
            <div class="servers-project-config-field">
                <label for="servers-project-config-notification-name">Nombre en notificaciones</label>
                <input type="text" id="servers-project-config-notification-name" class="form-control" maxlength="255" autocomplete="off" placeholder="Opcional; usa el nombre técnico si queda vacío">
                <small>Este nombre aparecerá en el asunto, el correo y el PDF del reporte mensual.</small>
            </div>
            <section class="servers-project-config-recipients" aria-labelledby="servers-project-config-recipients-title">
                <div class="servers-project-config-section-heading">
                    <div>
                        <h3 id="servers-project-config-recipients-title">Destinatarios del proyecto</h3>
                        <p id="servers-project-config-recipient-description">Contactos directos y notificadores activos de las licencias del cliente.</p>
                    </div>
                    <span id="servers-project-config-recipient-count" class="servers-project-config-count">0 seleccionados</span>
                </div>
                <div id="servers-project-config-recipient-list" class="servers-recipient-list" role="group" aria-label="Destinatarios disponibles"></div>
                <p id="servers-project-config-recipient-empty" class="servers-project-config-empty" hidden>Selecciona un cliente para consultar sus notificadores.</p>
                <div id="servers-project-notification-crud" class="servers-project-notification-crud" hidden>
                    <div class="servers-project-notification-form-heading">
                        <h4 id="servers-project-notification-form-title">Agregar destinatario</h4>
                        <button type="button" id="servers-project-notification-cancel-edit" class="servers-notification-cancel" hidden>Cancelar edición</button>
                    </div>
                    <div id="servers-project-notification-form" class="servers-project-notification-form">
                        <label class="servers-notification-field" for="servers-project-notification-channel">
                            <span>Canal</span>
                            <select id="servers-project-notification-channel" data-searchable-dropdown="false">
                                <option value="email">Correo</option>
                                <option value="phone">Teléfono</option>
                            </select>
                        </label>
                        <label class="servers-notification-field" for="servers-project-notification-value">
                            <span>Contacto</span>
                            <input type="text" id="servers-project-notification-value" class="form-control" maxlength="255" autocomplete="off">
                        </label>
                        <label class="servers-notification-field" for="servers-project-notification-name">
                            <span>Nombre</span>
                            <input type="text" id="servers-project-notification-name" class="form-control" maxlength="255" autocomplete="off" placeholder="Opcional">
                        </label>
                        <button type="button" id="servers-project-notification-submit" class="btn btn-secondary">
                            <i class="fa-light fa-plus" aria-hidden="true"></i>
                            <span id="servers-project-notification-submit-label">Agregar</span>
                        </button>
                    </div>
                </div>
            </section>
            <label class="servers-project-config-toggle" for="servers-project-config-notifications-enabled">
                <span>
                    <strong>Notificaciones activas</strong>
                    <small>El proyecto podrá usar estos destinatarios cuando se generen alertas.</small>
                </span>
                <input type="checkbox" id="servers-project-config-notifications-enabled">
                <span class="servers-toggle-track" aria-hidden="true"></span>
            </label>
            <div class="servers-project-config-actions">
                <button type="button" id="servers-project-config-cancel" class="btn btn-light">Cancelar</button>
                <button type="submit" id="servers-project-config-save" class="btn btn-primary">
                    <i class="fa-light fa-floppy-disk" aria-hidden="true"></i>
                    <span>Guardar configuración</span>
                </button>
            </div>
        </form>
    </div>
</div>
