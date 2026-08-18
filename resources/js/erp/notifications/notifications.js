const notificationState = {
    clients: [],
    clientsLoaded: false,
    emailPagination: {page: 1, size: 10, totalPages: 0},
    smsPagination: {page: 1, size: 10, totalPages: 0},
    activeChannel: 'email',
    resendEmailId: null,
    resendSmsId: null,
    emailEditor: null,
    savedEditorRange: null,
};

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || window.token || '';
}

function selectedClientIds(channel) {
    return $('#notifications-'+channel+'-client-list input[data-client-id]:checked').map(function() {
        return String($(this).attr('data-client-id'));
    }).get();
}

function manualRecipients(channel) {
    return $('#notifications-'+channel+'-'+(channel === 'email' ? 'manual' : 'manual')).val() || '';
}

function selectedClientPayload(channel) {
    return {
        client_ids: JSON.stringify(selectedClientIds(channel)),
        all_clients: $('#notifications-'+channel+'-all-clients').is(':checked') ? '1' : '0',
    };
}

function clientMatchesSearch(client, search) {
    const normalized = String(search || '').toLocaleLowerCase();
    return [client.name, client.lastname, client.email, client.phone].join(' ').toLocaleLowerCase().includes(normalized);
}

function renderClientList(channel) {
    const container = $('#notifications-'+channel+'-client-list');
    const search = $('#notifications-'+channel+'-client-search').val();
    const selected = new Set(selectedClientIds(channel));
    const clients = notificationState.clients.filter(function(client) {
        return clientMatchesSearch(client, search);
    });
    if (clients.length === 0) {
        container.html('<div class="notifications-empty">Sin clientes coincidentes</div>');
        return;
    }
    let html = '';
    clients.forEach(function(client) {
        const name = String(client.name || '')+(client.lastname ? ' '+client.lastname : '');
        const contact = channel === 'email' ? (client.email || 'Sin correo') : (client.phone || 'Sin telefono');
        html += '<label class="notifications-client-option" data-client-search="'+escapeHtml(name+' '+contact)+'">';
        html += '<input type="checkbox" class="form-check-input" data-client-id="'+client.id+'"'+(selected.has(String(client.id)) ? ' checked' : '')+'>'; 
        html += '<span>'+escapeHtml(name)+'<small>'+escapeHtml(contact)+'</small></span></label>';
    });
    container.html(html);
    const allSelected = $('#notifications-'+channel+'-all-clients').is(':checked');
    container.find('input[data-client-id]').prop('disabled', allSelected);
}

function loadClients() {
    if (notificationState.clientsLoaded) return;
    PostMethodFunction('/admin/notifications/clients', {}, null, function(response) {
        notificationState.clients = response.clients || [];
        notificationState.clientsLoaded = true;
        renderClientList('email');
        renderClientList('sms');
    }, null);
}

function formatDate(value) {
    if (!value) return 'Inmediato';
    return String(value).replace('T', ' ').slice(0, 16);
}

function renderPagination(containerSelector, pagination, onPage) {
    const container = $(containerSelector);
    const totalPages = Number(pagination.totalPages || 0);
    if (totalPages <= 1) {
        container.empty();
        return;
    }
    let html = '<button type="button" data-page="'+Math.max(1, pagination.page - 1)+'"'+(pagination.page <= 1 ? ' disabled' : '')+' title="Anterior" aria-label="Pagina anterior"><i class="fa-solid fa-chevron-left"></i></button>';
    for (let page = 1; page <= totalPages; page += 1) {
        if (totalPages > 8 && page > 3 && page < totalPages - 2 && Math.abs(page - pagination.page) > 1) {
            if (page === 4 || page === totalPages - 3) html += '<span>...</span>';
            continue;
        }
        html += '<button type="button" data-page="'+page+'" class="'+(page === Number(pagination.page) ? 'active' : '')+'">'+page+'</button>';
    }
    html += '<button type="button" data-page="'+Math.min(totalPages, Number(pagination.page) + 1)+'"'+(pagination.page >= totalPages ? ' disabled' : '')+' title="Siguiente" aria-label="Pagina siguiente"><i class="fa-solid fa-chevron-right"></i></button>';
    container.html(html);
    container.find('button:not(:disabled)').on('click', function() { onPage(Number($(this).attr('data-page'))); });
}

function emailRecipientText(email) {
    const recipients = Array.isArray(email.to) ? email.to : [];
    const addresses = recipients.map(function(recipient) { return recipient.address || recipient.email || ''; }).filter(Boolean);
    return addresses.length > 0 ? addresses.join(', ') : 'Sin destinatarios';
}

function renderEmails(response) {
    const emails = response.emails || [];
    if (emails.length === 0) {
        $('#notifications-email-list').html('<tr><td colspan="6" class="notifications-empty">No hay correos registrados</td></tr>');
    } else {
        let html = '';
        emails.forEach(function(email) {
            const recipients = emailRecipientText(email);
            html += '<tr>';
            html += '<td><span class="notifications-subject" title="'+escapeHtml(email.subject)+'">'+escapeHtml(email.subject)+'</span></td>';
            html += '<td><span class="notifications-subject" title="'+escapeHtml(recipients)+'">'+escapeHtml(email.recipient_count+' destinatario(s)')+'</span></td>';
            html += '<td><span class="notifications-status status-'+email.status+'">'+escapeHtml(email.status_string)+'</span></td>';
            html += '<td>'+escapeHtml(formatDate(email.send_at))+'</td>';
            html += '<td>'+escapeHtml(formatDate(email.sent_at))+'</td>';
            html += '<td><button type="button" class="btn btn-link notifications-action notifications-resend-email" data-id="'+email.id+'" title="Reenviar" aria-label="Reenviar correo"><i class="fa-solid fa-reply"></i></button></td>';
            html += '</tr>';
        });
        $('#notifications-email-list').html(html);
    }
    notificationState.emailPagination = response.pagination || notificationState.emailPagination;
    renderPagination('#notifications-email-pagination', notificationState.emailPagination, function(page) {
        notificationState.emailPagination.page = page;
        loadEmails();
    });
}

function renderSms(response) {
    const messages = response.sms || [];
    if (messages.length === 0) {
        $('#notifications-sms-list').html('<tr><td colspan="7" class="notifications-empty">No hay SMS registrados</td></tr>');
    } else {
        let html = '';
        messages.forEach(function(sms) {
            html += '<tr>';
            html += '<td>'+escapeHtml(sms.recipient_name || sms.client_name || 'Destinatario')+'</td>';
            html += '<td>'+escapeHtml(sms.to)+'</td>';
            html += '<td><span class="notifications-message-preview" title="'+escapeHtml(sms.body)+'">'+escapeHtml(sms.body)+'</span></td>';
            html += '<td><span class="notifications-status status-'+sms.status+'">'+escapeHtml(sms.status_string)+'</span></td>';
            html += '<td>'+escapeHtml(formatDate(sms.send_at))+'</td>';
            html += '<td>'+escapeHtml(formatDate(sms.sent_at))+'</td>';
            html += '<td><button type="button" class="btn btn-link notifications-action notifications-resend-sms" data-id="'+sms.id+'" title="Reenviar" aria-label="Reenviar SMS"><i class="fa-solid fa-reply"></i></button></td>';
            html += '</tr>';
        });
        $('#notifications-sms-list').html(html);
    }
    notificationState.smsPagination = response.pagination || notificationState.smsPagination;
    renderPagination('#notifications-sms-pagination', notificationState.smsPagination, function(page) {
        notificationState.smsPagination.page = page;
        loadSms();
    });
}

function loadEmails() {
    PostMethodFunction('/admin/notifications/emails', {
        pagination: JSON.stringify(notificationState.emailPagination),
        search: $('#notifications-email-search').val() || '',
        status: $('#notifications-email-status').val() || '',
    }, null, renderEmails, null);
}

function loadSms() {
    PostMethodFunction('/admin/notifications/sms', {
        pagination: JSON.stringify(notificationState.smsPagination),
        search: $('#notifications-sms-search').val() || '',
        status: $('#notifications-sms-status').val() || '',
    }, null, renderSms, null);
}

function sanitizePreviewHtml(value) {
    const template = document.createElement('template');
    template.innerHTML = String(value || '');
    template.content.querySelectorAll('script,style,iframe,object,embed,form,input,button,meta,link').forEach(function(node) { node.remove(); });
    template.content.querySelectorAll('*').forEach(function(node) {
        Array.from(node.attributes).forEach(function(attribute) {
            const name = attribute.name.toLowerCase();
            if (name.indexOf('on') === 0 || name === 'srcdoc') node.removeAttribute(attribute.name);
            if (name === 'href' && !/^(https?:|mailto:|\/|#)/i.test(attribute.value.trim())) node.removeAttribute(attribute.name);
        });
    });
    return template.content;
}

function rememberEditorSelection() {
    const editor = notificationState.emailEditor;
    if (!editor) return;
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    if (editor.contains(range.commonAncestorContainer)) notificationState.savedEditorRange = range.cloneRange();
}

function restoreEditorSelection() {
    if (!notificationState.emailEditor || !notificationState.savedEditorRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(notificationState.savedEditorRange);
}

function renderEmailPreview() {
    const subject = $('#notifications-email-subject').val() || '';
    $('#notifications-email-preview-subject').text(subject);
    const preview = document.getElementById('notifications-email-preview');
    if (preview) preview.replaceChildren(sanitizePreviewHtml(notificationState.emailEditor ? notificationState.emailEditor.innerHTML : ''));
}

function syncEmailEditor() {
    if (!notificationState.emailEditor) return;
    $('#notifications-email-content').val(notificationState.emailEditor.innerHTML);
    renderEmailPreview();
}

function setEmailEditorContent(content) {
    if (notificationState.emailEditor) notificationState.emailEditor.replaceChildren(sanitizePreviewHtml(content));
    $('#notifications-email-content').val(content || '');
    renderEmailPreview();
}

function resetClientSelectors(channel) {
    $('#notifications-'+channel+'-all-clients').prop('checked', false);
    $('#notifications-'+channel+'-client-search').val('');
    renderClientList(channel);
}

function resetEmailForm() {
    document.getElementById('notifications-email-form').reset();
    notificationState.resendEmailId = null;
    $('#notifications-email-mode').val('individual');
    $('#notifications-existing-attachments').empty();
    $('#notifications-email-attachments').val('');
    setEmailEditorContent('');
    resetClientSelectors('email');
}

function resetSmsForm() {
    document.getElementById('notifications-sms-form').reset();
    notificationState.resendSmsId = null;
    $('#notifications-sms-body').val('');
    $('#notifications-sms-manual').val('');
    resetClientSelectors('sms');
    updateSmsCounter();
}

function showChannel(channel) {
    notificationState.activeChannel = channel;
    const isEmail = channel === 'email';
    $('#notifications-email-form').toggleClass('d-none', !isEmail);
    $('#notifications-sms-form').toggleClass('d-none', isEmail);
    $('#notifications-modal-channel').text(isEmail ? 'EMAIL' : 'SMS');
    $('#notifications-modal-title').text(isEmail ? (notificationState.resendEmailId ? 'Reenviar correo' : 'Nueva notificacion') : (notificationState.resendSmsId ? 'Reenviar SMS' : 'Nuevo SMS'));
}

function openNewModal(channel) {
    if (channel === 'email') resetEmailForm();
    else resetSmsForm();
    showChannel(channel);
    $('#notifications-compose-modal').removeClass('d-none');
    $('body').addClass('notifications-modal-open');
    loadClients();
}

function closeModal() {
    $('#notifications-compose-modal').addClass('d-none');
    $('body').removeClass('notifications-modal-open');
}

function appendCommonRecipientData(formData, channel) {
    const selected = selectedClientPayload(channel);
    formData.append('client_ids', selected.client_ids);
    formData.append('all_clients', selected.all_clients);
    formData.append('recipients', manualRecipients(channel));
}

function postFormData(url, formData, success) {
    formData.append('_token', csrfToken());
    PostMethodMultimediaFunctionData(url, formData, null, success, null);
}

function saveEmail() {
    syncEmailEditor();
    const formData = new FormData();
    appendCommonRecipientData(formData, 'email');
    formData.append('recipient_mode', $('#notifications-email-mode').val());
    formData.append('subject', $('#notifications-email-subject').val() || '');
    formData.append('content', $('#notifications-email-content').val() || '');
    formData.append('from', $('#notifications-email-from').val() || '');
    formData.append('from_name', $('#notifications-email-from-name').val() || '');
    formData.append('reply_to', $('#notifications-email-reply-to').val() || '');
    formData.append('send_at', $('#notifications-email-send-at').val() || '');
    Array.from($('#notifications-email-attachments')[0].files || []).forEach(function(file) { formData.append('attachments[]', file); });
    const url = notificationState.resendEmailId ? '/admin/notifications/email/resend' : '/admin/notifications/email/add';
    if (notificationState.resendEmailId) formData.append('id', notificationState.resendEmailId);
    $('#notifications-save-modal').prop('disabled', true);
    postFormData(url, formData, function(response) {
        $('#notifications-save-modal').prop('disabled', false);
        alertSuccess(response.message || 'Correo registrado');
        closeModal();
        notificationState.emailPagination.page = 1;
        loadEmails();
    });
}

function manualPhoneCount() {
    return String($('#notifications-sms-manual').val() || '').split(/[\s,;]+/).filter(Boolean).length;
}

function updateSmsCounter() {
    $('#notifications-sms-character-count').text(String($('#notifications-sms-body').val() || '').length);
    $('#notifications-sms-count').text(selectedClientIds('sms').length + manualPhoneCount());
}

function saveSms() {
    const formData = new FormData();
    appendCommonRecipientData(formData, 'sms');
    formData.append('body', $('#notifications-sms-body').val() || '');
    formData.append('send_at', $('#notifications-sms-send-at').val() || '');
    const url = notificationState.resendSmsId ? '/admin/notifications/sms/resend' : '/admin/notifications/sms/add';
    if (notificationState.resendSmsId) formData.append('id', notificationState.resendSmsId);
    $('#notifications-save-modal').prop('disabled', true);
    postFormData(url, formData, function(response) {
        $('#notifications-save-modal').prop('disabled', false);
        alertSuccess(response.message || 'SMS registrado');
        closeModal();
        notificationState.smsPagination.page = 1;
        loadSms();
    });
}

function fillEmailForResend(email) {
    openNewModal('email');
    notificationState.resendEmailId = email.id;
    showChannel('email');
    $('#notifications-email-subject').val(email.subject || '');
    $('#notifications-email-from').val(email.from || '');
    $('#notifications-email-from-name').val(email.from_name || '');
    $('#notifications-email-reply-to').val(email.reply_to || '');
    $('#notifications-email-manual').val((email.recipients || []).map(function(recipient) { return recipient.address || recipient.email || ''; }).filter(Boolean).join(', '));
    $('#notifications-email-send-at').val(email.send_at || '');
    $('#notifications-existing-attachments').text((email.attachments || []).map(function(attachment) { return attachment.name; }).join(', '));
    setEmailEditorContent(email.content || '');
    $('#notifications-email-mode').val('individual');
    $('#notifications-modal-title').text('Reenviar correo');
}

function resendEmail() {
    const id = $(this).attr('data-id');
    PostMethodFunction('/admin/notifications/email', {id: id}, null, function(response) { fillEmailForResend(response.email); }, null);
}

function fillSmsForResend(sms) {
    openNewModal('sms');
    notificationState.resendSmsId = sms.id;
    showChannel('sms');
    $('#notifications-sms-manual').val(sms.to || '');
    $('#notifications-sms-body').val(sms.body || '');
    $('#notifications-sms-send-at').val(sms.send_at ? String(sms.send_at).replace(' ', 'T').slice(0, 16) : '');
    $('#notifications-modal-title').text('Reenviar SMS');
    updateSmsCounter();
}

function resendSms() {
    const id = $(this).attr('data-id');
    PostMethodFunction('/admin/notifications/sms-by-id', {id: id}, null, function(response) { fillSmsForResend(response.sms); }, null);
}

function initializeEditor() {
    notificationState.emailEditor = document.getElementById('notifications-email-editor');
    if (!notificationState.emailEditor) return;
    notificationState.emailEditor.addEventListener('input', syncEmailEditor);
    notificationState.emailEditor.addEventListener('keyup', rememberEditorSelection);
    notificationState.emailEditor.addEventListener('mouseup', rememberEditorSelection);
    notificationState.emailEditor.addEventListener('blur', rememberEditorSelection);
    $(document).on('mousedown.notificationsEditor', '#notifications-email-toolbar button', function(event) { event.preventDefault(); });
    $(document).on('click.notificationsEditor', '#notifications-email-toolbar [data-notification-command]', function() {
        restoreEditorSelection();
        notificationState.emailEditor.focus();
        document.execCommand($(this).attr('data-notification-command'), false, null);
        rememberEditorSelection();
        syncEmailEditor();
    });
    $(document).on('change.notificationsEditor', '#notifications-email-block-format', function() {
        restoreEditorSelection();
        notificationState.emailEditor.focus();
        document.execCommand('formatBlock', false, $(this).val());
        rememberEditorSelection();
        syncEmailEditor();
    });
    $(document).on('input.notificationsEditor', '#notifications-email-subject', renderEmailPreview);
    syncEmailEditor();
}

$(document).on('shown.bs.tab', '#notifications-sms-tab', function() { notificationState.activeChannel = 'sms'; loadSms(); });
$(document).on('shown.bs.tab', '#notifications-email-tab', function() { notificationState.activeChannel = 'email'; loadEmails(); });
$(document).on('click', '#notifications-new-email', function() { openNewModal('email'); });
$(document).on('click', '#notifications-new-sms', function() { openNewModal('sms'); });
$(document).on('click', '#notifications-close-modal, #notifications-cancel-modal', closeModal);
$(document).on('click', '#notifications-compose-modal', function(event) { if (event.target === this) closeModal(); });
$(document).on('click', '#notifications-save-modal', function() { notificationState.activeChannel === 'email' ? saveEmail() : saveSms(); });
$(document).on('click', '.notifications-resend-email', resendEmail);
$(document).on('click', '.notifications-resend-sms', resendSms);
$(document).on('click', '#notifications-email-refresh', loadEmails);
$(document).on('click', '#notifications-sms-refresh', loadSms);
$(document).on('change', '#notifications-email-status, #notifications-sms-status', function() {
    const channel = $(this).attr('id').includes('email') ? 'email' : 'sms';
    notificationState[channel+'Pagination'].page = 1;
    channel === 'email' ? loadEmails() : loadSms();
});
$(document).on('change', '#notifications-email-client-list input[data-client-id], #notifications-sms-client-list input[data-client-id], #notifications-email-all-clients, #notifications-sms-all-clients', function() {
    const elementId = String($(this).attr('id') || '');
    const channel = elementId.includes('email') || $(this).closest('#notifications-email-client-list').length ? 'email' : 'sms';
    if ($(this).is('[id$="all-clients"]')) renderClientList(channel);
    if (channel === 'sms') updateSmsCounter();
});
$(document).on('input', '#notifications-email-client-search, #notifications-sms-client-search', function() {
    renderClientList($(this).attr('id').includes('email') ? 'email' : 'sms');
});
$(document).on('input', '#notifications-sms-body, #notifications-sms-manual', updateSmsCounter);
$(document).on('keydown', function(event) { if (event.key === 'Escape' && !$('#notifications-compose-modal').hasClass('d-none')) closeModal(); });

$(document).ready(function() {
    initializeEditor();
    loadClients();
    loadEmails();
});