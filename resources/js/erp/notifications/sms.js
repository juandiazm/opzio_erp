import { notificationState } from './state.js';
import { appendCommonRecipientData, escapeHtml, formatDate, postFormData, renderPagination, resetClientSelectors, selectedClientIds, showComposeModal } from './shared.js';

export function renderSms(response) {
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
            html += '<td>'+escapeHtml(formatDate(sms.send_at_local || sms.send_at))+'</td>';
            html += '<td>'+escapeHtml(formatDate(sms.sent_at_local || sms.sent_at))+'</td>';
            html += '<td class="notifications-actions text-end"><div class="notifications-action-group"><button type="button" class="btn btn-link notifications-action notifications-resend-sms" data-id="'+escapeHtml(sms.id)+'" title="Reenviar" aria-label="Reenviar SMS"><i class="fa-solid fa-reply"></i></button></div></td>';
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

export function loadSms() {
    PostMethodFunction('/admin/notifications/sms', {
        pagination: JSON.stringify(notificationState.smsPagination),
        search: $('#notifications-sms-search').val() || '',
        status: $('#notifications-sms-status').val() || '',
    }, null, renderSms, null);
}

function manualPhoneCount() {
    return String($('#notifications-sms-manual').val() || '').split(/[\s,;]+/).filter(Boolean).length;
}

export function updateSmsCounter() {
    $('#notifications-sms-character-count').text(String($('#notifications-sms-body').val() || '').length);
    $('#notifications-sms-count').text(selectedClientIds('sms').length + manualPhoneCount());
}

function resetSmsForm() {
    document.getElementById('notifications-sms-form').reset();
    notificationState.resendSmsId = null;
    $('#notifications-sms-body').val('');
    $('#notifications-sms-manual').val('');
    resetClientSelectors('sms');
    updateSmsCounter();
}

export function openNewSmsModal() {
    resetSmsForm();
    showComposeModal('sms');
}

export function saveSms() {
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
        $('#notifications-compose-modal').addClass('d-none');
        $('body').removeClass('notifications-modal-open');
        notificationState.smsPagination.page = 1;
        loadSms();
    });
}

function fillSmsForResend(sms) {
    resetSmsForm();
    notificationState.resendSmsId = sms.id;
    showComposeModal('sms');
    $('#notifications-sms-manual').val(sms.to || '');
    $('#notifications-sms-body').val(sms.body || '');
    $('#notifications-sms-send-at').val(sms.send_at_local || sms.send_at ? String(sms.send_at_local || sms.send_at).replace(' ', 'T').slice(0, 16) : '');
    $('#notifications-modal-title').text('Reenviar SMS');
    updateSmsCounter();
}

export function resendSms() {
    const id = $(this).attr('data-id');
    PostMethodFunction('/admin/notifications/sms-by-id', {id: id}, null, function(response) { fillSmsForResend(response.sms); }, null);
}