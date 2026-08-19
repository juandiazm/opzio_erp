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