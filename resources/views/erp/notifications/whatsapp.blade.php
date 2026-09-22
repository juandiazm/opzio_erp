<section class="tab-pane fade show active notifications-pane" id="notifications-whatsapp-pane" role="tabpanel" aria-labelledby="notifications-whatsapp-tab">
    <div class="notifications-list-header notifications-whatsapp-header">
        <div>
            <h2>Conversaciones WhatsApp</h2>
            <p>Contactos y mensajes gestionados desde Twilio.</p>
        </div>
        <div class="notifications-whatsapp-header-actions">
            <button type="button" class="btn btn-light" id="notifications-whatsapp-templates"><i class="fa-solid fa-file-lines"></i> Plantillas</button>
            <button type="button" class="btn btn-primary" id="notifications-whatsapp-new"><i class="fa-solid fa-plus"></i> Nuevo chat</button>
        </div>
    </div>

    <div class="notifications-whatsapp-layout" id="notifications-whatsapp-layout">
        <aside class="notifications-whatsapp-list-panel" aria-label="Conversaciones">
            <div class="notifications-whatsapp-list-toolbar">
                <label class="notifications-whatsapp-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="notifications-whatsapp-search" placeholder="Buscar nombre o telefono" aria-label="Buscar conversaciones">
                </label>
                <button type="button" class="btn btn-light notifications-refresh" id="notifications-whatsapp-refresh" title="Actualizar conversaciones" aria-label="Actualizar conversaciones"><i class="fa-solid fa-rotate"></i></button>
            </div>
            <label class="notifications-whatsapp-unread-filter"><input type="checkbox" class="form-check-input" id="notifications-whatsapp-unread-only"> Solo no leidos</label>
            <div class="notifications-whatsapp-conversation-list" id="notifications-whatsapp-conversation-list"><div class="notifications-empty">Cargando...</div></div>
            <div class="notifications-pagination" id="notifications-whatsapp-pagination"></div>

            <form class="notifications-whatsapp-start d-none" id="notifications-whatsapp-start-form">
                <div class="notifications-section-heading"><h3>Nuevo chat</h3><button type="button" class="btn btn-link notifications-whatsapp-start-close" id="notifications-whatsapp-start-close" aria-label="Cerrar nuevo chat" title="Cerrar"><i class="fa-solid fa-xmark"></i></button></div>
                <label class="notifications-field"><span>Cliente</span><select class="form-select" id="notifications-whatsapp-client"><option value="">Seleccionar cliente</option></select></label>
                <label class="notifications-field"><span>Telefono</span><input type="tel" class="form-control" id="notifications-whatsapp-phone" placeholder="3000000000"></label>
                <label class="notifications-field"><span>Nombre visible</span><input type="text" class="form-control" id="notifications-whatsapp-display-name" maxlength="150"></label>
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-comments"></i> Abrir conversacion</button>
            </form>
        </aside>

        <section class="notifications-whatsapp-chat" aria-label="Chat de WhatsApp">
            <div class="notifications-whatsapp-chat-empty" id="notifications-whatsapp-chat-empty"><i class="fa-brands fa-whatsapp"></i><strong>Selecciona una conversacion</strong><span>El historial aparecera aqui.</span></div>
            <div class="notifications-whatsapp-chat-content d-none" id="notifications-whatsapp-chat-content">
                <header class="notifications-whatsapp-chat-header">
                    <button type="button" class="btn btn-link notifications-whatsapp-back" id="notifications-whatsapp-back" aria-label="Volver a conversaciones" title="Volver"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="notifications-whatsapp-avatar" id="notifications-whatsapp-chat-avatar">W</div>
                    <div class="notifications-whatsapp-chat-contact"><strong id="notifications-whatsapp-chat-name"></strong><span id="notifications-whatsapp-chat-phone"></span></div>
                    <div class="notifications-whatsapp-window" id="notifications-whatsapp-chat-window"></div>
                    <button type="button" class="btn btn-link notifications-action" id="notifications-whatsapp-chat-refresh" aria-label="Actualizar chat" title="Actualizar chat"><i class="fa-solid fa-rotate"></i></button>
                </header>
                <div class="notifications-whatsapp-messages" id="notifications-whatsapp-messages"></div>
                <form class="notifications-whatsapp-composer" id="notifications-whatsapp-message-form">
                    <div class="notifications-whatsapp-composer-tools">
                        <label class="notifications-whatsapp-template-select"><span>Plantilla</span><select id="notifications-whatsapp-template"><option value="">Mensaje libre</option></select></label>
                        <input type="hidden" id="notifications-whatsapp-variables" value="{}">
                    </div>
                    <div class="notifications-whatsapp-template-preview d-none" id="notifications-whatsapp-template-preview">
                        <div class="notifications-whatsapp-template-preview-header">
                            <div><strong>Vista previa del mensaje</strong><span id="notifications-whatsapp-template-preview-name"></span></div>
                            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                        </div>
                        <div class="notifications-whatsapp-template-preview-body" id="notifications-whatsapp-template-preview-body"></div>
                        <div class="notifications-whatsapp-template-variables" id="notifications-whatsapp-template-variables-form"></div>
                    </div>
                    <div class="notifications-whatsapp-composer-row">
                        <textarea id="notifications-whatsapp-body" rows="2" maxlength="4096" placeholder="Escribe un mensaje" aria-label="Mensaje de WhatsApp"></textarea>
                        <button type="submit" class="btn btn-primary" id="notifications-whatsapp-send" title="Enviar mensaje" aria-label="Enviar mensaje"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                    <small class="notifications-whatsapp-window-note" id="notifications-whatsapp-window-note"></small>
                </form>
            </div>
        </section>
    </div>
</section>

<div id="notifications-whatsapp-templates-modal" class="notifications-modal d-none" role="dialog" aria-modal="true" aria-labelledby="notifications-whatsapp-templates-title">
    <div class="notifications-modal-dialog notifications-whatsapp-templates-dialog">
        <div class="notifications-modal-header">
            <div><span class="notifications-modal-kicker">WHATSAPP</span><h2 id="notifications-whatsapp-templates-title">Plantillas de contenido</h2></div>
            <button type="button" class="notifications-icon-button" id="notifications-whatsapp-templates-close" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="notifications-modal-body">
            <div class="notifications-whatsapp-templates-grid">
                <div class="notifications-whatsapp-template-list" id="notifications-whatsapp-template-list"><div class="notifications-empty">Cargando...</div></div>
                <form id="notifications-whatsapp-template-form" class="notifications-whatsapp-template-form">
                    <input type="hidden" id="notifications-whatsapp-template-sid">
                    <div class="notifications-section-heading"><h3 id="notifications-whatsapp-template-form-title">Nueva plantilla</h3></div>
                    <label class="notifications-field"><span>Nombre visible</span><input type="text" class="form-control" id="notifications-whatsapp-template-name" maxlength="100" required></label>
                    <label class="notifications-field"><span>Idioma</span><input type="text" class="form-control" id="notifications-whatsapp-template-language" value="es" maxlength="20" required></label>
                    <label class="notifications-field"><span>Cuerpo de texto</span><textarea class="form-control" id="notifications-whatsapp-template-body" rows="6" placeholder="Hola {{1}}"></textarea></label>
                    <label class="notifications-field"><span>Variables de ejemplo JSON</span><textarea class="form-control" id="notifications-whatsapp-template-variables" rows="3" placeholder='{"1":"Cliente"}'></textarea></label>
                    <label class="notifications-field"><span>Categoria de aprobacion</span><select class="form-select" id="notifications-whatsapp-template-category"><option value="UTILITY">UTILITY</option><option value="MARKETING">MARKETING</option><option value="AUTHENTICATION">AUTHENTICATION</option></select></label>
                    <div class="notifications-whatsapp-template-form-actions"><button type="button" class="btn btn-light" id="notifications-whatsapp-template-reset">Limpiar</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button></div>
                </form>
            </div>
        </div>
    </div>
</div>