import { notificationState } from './state.js';
import { appendCommonRecipientData, escapeHtml, formatDate, postFormData, renderPagination, resetClientSelectors, showComposeModal } from './shared.js';

function emailRecipientText(email) {
    const recipients = Array.isArray(email.to) ? email.to : [];
    const addresses = recipients.map(function(recipient) {
        if (typeof recipient === 'string') return recipient;
        const address = recipient.address || recipient.email || '';
        const name = recipient.name || '';
        return name && address ? name+' <'+address+'>' : address;
    }).filter(Boolean);
    return addresses.length > 0 ? addresses.join(', ') : 'Sin destinatarios';
}

export function renderEmails(response) {
    const emails = response.emails || [];
    if (emails.length === 0) {
        $('#notifications-email-list').html('<tr><td colspan="6" class="notifications-empty">No hay correos registrados</td></tr>');
    } else {
        let html = '';
        emails.forEach(function(email) {
            const recipients = emailRecipientText(email);
            html += '<tr>';
            html += '<td><span class="notifications-subject" title="'+escapeHtml(email.subject)+'">'+escapeHtml(email.subject)+'</span></td>';
            html += '<td><div class="notifications-recipient-cell" title="'+escapeHtml(recipients)+'">'+escapeHtml(recipients)+'</div></td>';
            html += '<td><span class="notifications-status status-'+email.status+'">'+escapeHtml(email.status_string)+'</span></td>';
            html += '<td>'+escapeHtml(formatDate(email.send_at_local || email.send_at))+'</td>';
            html += '<td>'+escapeHtml(formatDate(email.sent_at_local || email.sent_at))+'</td>';
            html += '<td class="notifications-actions text-end"><div class="notifications-action-group">';
            html += '<button type="button" class="btn btn-link notifications-action notifications-view-email" data-id="'+escapeHtml(email.id)+'" title="Visualizar" aria-label="Visualizar correo"><i class="fa-solid fa-eye"></i></button>';
            if (email.can_resend !== false) html += '<button type="button" class="btn btn-link notifications-action notifications-resend-email" data-id="'+escapeHtml(email.id)+'" title="Reenviar" aria-label="Reenviar correo"><i class="fa-solid fa-reply"></i></button>';
            html += '</div></td></tr>';
        });
        $('#notifications-email-list').html(html);
    }
    notificationState.emailPagination = response.pagination || notificationState.emailPagination;
    renderPagination('#notifications-email-pagination', notificationState.emailPagination, function(page) {
        notificationState.emailPagination.page = page;
        loadEmails();
    });
}

export function loadEmails() {
    PostMethodFunction('/admin/notifications/emails', {
        pagination: JSON.stringify(notificationState.emailPagination),
        search: $('#notifications-email-search').val() || '',
        status: $('#notifications-email-status').val() || '',
        date_from: $('#notifications-email-date-from').val() || '',
        date_to: $('#notifications-email-date-to').val() || '',
    }, null, renderEmails, null);
}

export function sanitizePreviewHtml(value) {
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

function renderEmailHtml(element, content) {
    if (!element) return;
    element.replaceChildren(sanitizePreviewHtml(content));
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
    renderEmailHtml(document.getElementById('notifications-email-preview'), notificationState.emailEditor ? notificationState.emailEditor.innerHTML : '');
}

function syncEmailEditor() {
    if (!notificationState.emailEditor) return;
    $('#notifications-email-content').val(notificationState.emailEditor.innerHTML);
    renderEmailPreview();
}

function setEmailEditorContent(content) {
    renderEmailHtml(notificationState.emailEditor, content);
    $('#notifications-email-content').val(content || '');
    renderEmailPreview();
}

function resetEmailForm() {
    document.getElementById('notifications-email-form').reset();
    notificationState.resendEmailId = null;
    notificationState.emailDetail = null;
    $('#notifications-existing-attachments').empty();
    $('#notifications-email-attachments').val('');
    setEmailEditorContent('');
    resetClientSelectors('email');
}

export function openNewEmailModal() {
    resetEmailForm();
    showComposeModal('email');
}

export function saveEmail() {
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
    const attachments = document.getElementById('notifications-email-attachments');
    Array.from(attachments ? attachments.files : []).forEach(function(file) { formData.append('attachments[]', file); });
    const url = notificationState.resendEmailId ? '/admin/notifications/email/resend' : '/admin/notifications/email/add';
    if (notificationState.resendEmailId) formData.append('id', notificationState.resendEmailId);
    $('#notifications-save-modal').prop('disabled', true);
    postFormData(url, formData, function(response) {
        $('#notifications-save-modal').prop('disabled', false);
        alertSuccess(response.message || 'Correo registrado');
        $('#notifications-compose-modal').addClass('d-none');
        $('body').removeClass('notifications-modal-open');
        notificationState.emailPagination.page = 1;
        loadEmails();
    });
}

function fillEmailForResend(email) {
    resetEmailForm();
    notificationState.resendEmailId = email.id;
    notificationState.emailDetail = email;
    showComposeModal('email');
    $('#notifications-email-subject').val(email.subject || '');
    $('#notifications-email-from').val(email.from || '');
    $('#notifications-email-from-name').val(email.from_name || '');
    $('#notifications-email-reply-to').val(email.reply_to || '');
    $('#notifications-email-manual').val((email.recipients || []).map(function(recipient) { return recipient.address || recipient.email || ''; }).filter(Boolean).join(', '));
    $('#notifications-email-send-at').val(email.send_at_local || email.send_at || '');
    $('#notifications-existing-attachments').text((email.attachments || []).map(function(attachment) { return attachment.name; }).join(', '));
    setEmailEditorContent(email.content || '');
    $('#notifications-email-mode').val('individual');
    $('#notifications-modal-title').text('Reenviar correo');
}

export function viewEmail() {
    const id = $(this).attr('data-id');
    PostMethodFunction('/admin/notifications/email', {id: id}, null, function(response) {
        const email = response.email || {};
        notificationState.emailDetail = email;
        $('#notifications-email-view-subject').text(email.subject || 'Sin asunto');
        $('#notifications-email-view-recipients').text((email.recipients || []).map(function(recipient) { return recipient.address || recipient.email || ''; }).filter(Boolean).join(', ') || 'Sin destinatarios');
        $('#notifications-email-view-from').text(email.from || '');
        $('#notifications-email-view-status').text(email.status_string || '');
        $('#notifications-email-view-send-at').text(formatDate(email.send_at_local || email.send_at));
        $('#notifications-email-view-sent-at').text(formatDate(email.sent_at_local || email.sent_at));
        renderEmailHtml(document.getElementById('notifications-email-view-body'), email.content || email.body || '');
        $('#notifications-email-view-attachments').text((email.attachments || []).map(function(attachment) { return attachment.name; }).join(', '));
        $('#notifications-edit-email-view').toggleClass('d-none', email.can_resend === false);
        $('#notifications-email-view-modal').removeClass('d-none');
        $('body').addClass('notifications-modal-open');
    }, null);
}

export function closeEmailView() {
    $('#notifications-email-view-modal').addClass('d-none');
    if ($('#notifications-compose-modal').hasClass('d-none')) $('body').removeClass('notifications-modal-open');
}

export function editEmailFromView() {
    const email = notificationState.emailDetail;
    closeEmailView();
    if (email) fillEmailForResend(email);
}

export function resendEmail() {
    const id = $(this).attr('data-id');
    PostMethodFunction('/admin/notifications/email', {id: id}, null, function(response) { fillEmailForResend(response.email); }, null);
}

export function initializeEditor() {
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