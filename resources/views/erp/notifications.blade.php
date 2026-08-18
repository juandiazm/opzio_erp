@extends('erp.layouts.app')
@section('component_title', 'NOTIFICACIONES')
@section('erp-app-header')
@vite('resources/js/erp/notifications/notifications.js')
@vite('resources/sass/erp/notifications/notifications.scss')
@endsection
@section('erp-app-content')
<div class="notifications-page">
    <nav>
        <div class="nav nav-tabs principal-nav-tabs" id="notifications-tabs" role="tablist">
            <button class="nav-link active" id="notifications-email-tab" data-bs-toggle="tab" data-bs-target="#notifications-email-pane" type="button" role="tab" aria-controls="notifications-email-pane" aria-selected="true"><i class="fa-light fa-envelope"></i> Email</button>
            <button class="nav-link" id="notifications-sms-tab" data-bs-toggle="tab" data-bs-target="#notifications-sms-pane" type="button" role="tab" aria-controls="notifications-sms-pane" aria-selected="false"><i class="fa-light fa-comment-sms"></i> SMS</button>
        </div>
    </nav>

    <div class="tab-content" id="notifications-tab-content">
        <section class="tab-pane fade show active notifications-pane" id="notifications-email-pane" role="tabpanel" aria-labelledby="notifications-email-tab">
            <div class="notifications-list-header">
                <div>
                    <h2>Correos enviados</h2>
                    <p>Historial y reenvio de notificaciones por correo.</p>
                </div>
                <button type="button" class="btn btn-primary" id="notifications-new-email"><i class="fa-solid fa-plus"></i> Nueva notificacion</button>
            </div>
            <div class="notifications-filter-bar">
                <label class="notifications-filter"><span>Buscar</span><input type="search" id="notifications-email-search" class="form-control" placeholder="Asunto o destinatario" aria-label="Buscar correos"></label>
                <label class="notifications-filter"><span>Estado</span><select id="notifications-email-status" class="form-select"><option value="">Todos</option><option value="0">Pendiente</option><option value="1">Enviado</option><option value="2">Fallido</option></select></label>
                <button type="button" class="btn btn-light notifications-refresh" id="notifications-email-refresh" title="Actualizar correos" aria-label="Actualizar correos"><i class="fa-solid fa-rotate"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle notifications-table">
                    <thead><tr><th>Asunto</th><th>Destinatarios</th><th>Estado</th><th>Programado</th><th>Enviado</th><th>Acciones</th></tr></thead>
                    <tbody id="notifications-email-list"><tr><td colspan="6" class="notifications-empty">Cargando...</td></tr></tbody>
                </table>
            </div>
            <div class="notifications-pagination" id="notifications-email-pagination"></div>
        </section>

        <section class="tab-pane fade notifications-pane" id="notifications-sms-pane" role="tabpanel" aria-labelledby="notifications-sms-tab">
            <div class="notifications-list-header">
                <div>
                    <h2>SMS enviados</h2>
                    <p>Historial, estado y reenvio de mensajes de texto.</p>
                </div>
                <button type="button" class="btn btn-primary" id="notifications-new-sms"><i class="fa-solid fa-plus"></i> Nuevo SMS</button>
            </div>
            <div class="notifications-filter-bar">
                <label class="notifications-filter"><span>Buscar</span><input type="search" id="notifications-sms-search" class="form-control" placeholder="Mensaje, nombre o telefono" aria-label="Buscar SMS"></label>
                <label class="notifications-filter"><span>Estado</span><select id="notifications-sms-status" class="form-select"><option value="">Todos</option><option value="0">Pendiente</option><option value="1">Enviado</option><option value="2">Fallido</option></select></label>
                <button type="button" class="btn btn-light notifications-refresh" id="notifications-sms-refresh" title="Actualizar SMS" aria-label="Actualizar SMS"><i class="fa-solid fa-rotate"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle notifications-table">
                    <thead><tr><th>Destinatario</th><th>Telefono</th><th>Mensaje</th><th>Estado</th><th>Programado</th><th>Enviado</th><th>Acciones</th></tr></thead>
                    <tbody id="notifications-sms-list"><tr><td colspan="7" class="notifications-empty">Cargando...</td></tr></tbody>
                </table>
            </div>
            <div class="notifications-pagination" id="notifications-sms-pagination"></div>
        </section>
    </div>
</div>

<div id="notifications-compose-modal" class="notifications-modal d-none" role="dialog" aria-modal="true" aria-labelledby="notifications-modal-title">
    <div class="notifications-modal-dialog">
        <div class="notifications-modal-header">
            <div><span class="notifications-modal-kicker" id="notifications-modal-channel">EMAIL</span><h2 id="notifications-modal-title">Nueva notificacion</h2></div>
            <button type="button" class="notifications-icon-button" id="notifications-close-modal" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="notifications-modal-body">
            <form id="notifications-email-form" class="notifications-compose-form">
                <div class="notifications-form-grid">
                    <label class="notifications-field"><span>Modo de envio</span><select id="notifications-email-mode" class="form-select"><option value="individual">Uno a uno</option><option value="massive">Un correo a todos</option></select></label>
                    <label class="notifications-field"><span>Enviar el</span><input type="datetime-local" id="notifications-email-send-at" class="form-control"></label>
                </div>
                <div class="notifications-recipient-section">
                    <div class="notifications-section-heading"><h3>Destinatarios</h3><label class="form-check-label"><input type="checkbox" id="notifications-email-all-clients" class="form-check-input"> Todos los clientes activos</label></div>
                    <input type="search" id="notifications-email-client-search" class="form-control notifications-client-search" placeholder="Buscar cliente" aria-label="Buscar cliente para email">
                    <div id="notifications-email-client-list" class="notifications-client-list"></div>
                    <label class="notifications-field"><span>Correos adicionales</span><textarea id="notifications-email-manual" class="form-control" rows="2" placeholder="correo@ejemplo.com, otro@ejemplo.com"></textarea></label>
                </div>
                <div class="notifications-form-grid">
                    <label class="notifications-field"><span>Asunto</span><input type="text" id="notifications-email-subject" class="form-control" maxlength="255" required></label>
                    <div class="notifications-form-grid notifications-form-grid-nested">
                        <label class="notifications-field"><span>Remitente</span><input type="email" id="notifications-email-from" class="form-control" value="{{ config('mail.from.address') }}" required></label>
                        <label class="notifications-field"><span>Reply-To</span><input type="email" id="notifications-email-reply-to" class="form-control"></label>
                    </div>
                </div>
                <label class="notifications-field"><span>Nombre del remitente</span><input type="text" id="notifications-email-from-name" class="form-control" value="{{ config('mail.from.name') }}" maxlength="100"></label>
                <div class="notifications-editor-layout">
                    <section class="notifications-editor-panel">
                        <div class="notifications-editor-toolbar" id="notifications-email-toolbar" role="toolbar" aria-label="Formato del email">
                            <button type="button" class="btn btn-light" data-notification-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="underline" title="Subrayado" aria-label="Subrayado"><i class="fa-solid fa-underline"></i></button>
                            <span></span>
                            <button type="button" class="btn btn-light" data-notification-command="justifyLeft" title="Alinear a la izquierda" aria-label="Alinear a la izquierda"><i class="fa-solid fa-align-left"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="justifyCenter" title="Centrar" aria-label="Centrar"><i class="fa-solid fa-align-center"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="justifyRight" title="Alinear a la derecha" aria-label="Alinear a la derecha"><i class="fa-solid fa-align-right"></i></button>
                            <select id="notifications-email-block-format" class="form-select" aria-label="Formato de parrafo"><option value="p">Parrafo</option><option value="h2">Titulo</option><option value="h3">Subtitulo</option><option value="blockquote">Cita</option></select>
                            <button type="button" class="btn btn-light" data-notification-command="insertUnorderedList" title="Lista con vinetas" aria-label="Lista con vinetas"><i class="fa-solid fa-list-ul"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="insertOrderedList" title="Lista numerada" aria-label="Lista numerada"><i class="fa-solid fa-list-ol"></i></button>
                            <button type="button" class="btn btn-light" data-notification-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button>
                        </div>
                        <div id="notifications-email-editor" class="notifications-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="Escribe el contenido del correo..."></div>
                        <textarea id="notifications-email-content" class="d-none" aria-hidden="true"></textarea>
                    </section>
                    <section class="notifications-preview-panel"><div class="notifications-preview-subject" id="notifications-email-preview-subject"></div><article id="notifications-email-preview" class="notifications-email-preview"></article></section>
                </div>
                <label class="notifications-field"><span>Adjuntos</span><input type="file" id="notifications-email-attachments" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.zip"><small id="notifications-existing-attachments" class="notifications-existing-files"></small></label>
            </form>
            <form id="notifications-sms-form" class="notifications-compose-form d-none">
                <div class="notifications-form-grid">
                    <label class="notifications-field"><span>Enviar el</span><input type="datetime-local" id="notifications-sms-send-at" class="form-control"></label>
                    <div class="notifications-sms-counter"><strong id="notifications-sms-count">0</strong><span>destinatarios</span></div>
                </div>
                <div class="notifications-recipient-section">
                    <div class="notifications-section-heading"><h3>Destinatarios</h3><label class="form-check-label"><input type="checkbox" id="notifications-sms-all-clients" class="form-check-input"> Todos los clientes activos</label></div>
                    <input type="search" id="notifications-sms-client-search" class="form-control notifications-client-search" placeholder="Buscar cliente" aria-label="Buscar cliente para SMS">
                    <div id="notifications-sms-client-list" class="notifications-client-list"></div>
                    <label class="notifications-field"><span>Telefonos adicionales</span><textarea id="notifications-sms-manual" class="form-control" rows="2" placeholder="3000000000, +573000000000"></textarea></label>
                </div>
                <label class="notifications-field"><span>Mensaje</span><textarea id="notifications-sms-body" class="form-control notifications-sms-body" maxlength="1600" rows="8" required></textarea><small class="notifications-character-count"><span id="notifications-sms-character-count">0</span>/1600</small></label>
            </form>
        </div>
        <div class="notifications-modal-footer"><button type="button" class="btn btn-light" id="notifications-cancel-modal">Cancelar</button><button type="button" class="btn btn-primary" id="notifications-save-modal"><i class="fa-solid fa-paper-plane"></i> Registrar envio</button></div>
    </div>
</div>
@endsection