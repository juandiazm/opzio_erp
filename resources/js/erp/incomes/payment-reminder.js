import { incomeState } from './state.js';

let recipients = [];
let selectedIncome = null;

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function channelLabel(channel) {
    return channel === 'whatsapp' ? 'WhatsApp' : 'SMS';
}

function recipientAllows(recipient, channel) {
    const channels = (recipient.channels || []).map((value) => String(value).toLowerCase());
    return channel === 'whatsapp'
        ? channels.includes('whatsapp') || channels.includes('sms_whatsapp')
        : channels.includes('sms') || channels.includes('sms_whatsapp') || channels.length === 0;
}

function renderRecipientOptions() {
    const select = $('#income-payment-reminder-phone');
    let html = '<option value="">Selecciona un numero</option>';
    recipients.forEach(function(recipient) {
        html += '<option value="'+escapeHtml(recipient.phone)+'">'+escapeHtml((recipient.name || 'Contacto')+' - '+recipient.phone)+'</option>';
    });
    select.html(html);
    $('#income-payment-reminder-empty').toggleClass('d-none', recipients.length > 0);
    updateChannelOptions();
}

function updateChannelOptions() {
    const phone = $('#income-payment-reminder-phone').val();
    const recipient = recipients.find((item) => item.phone === phone);
    const channelSelect = $('#income-payment-reminder-channel');
    channelSelect.find('option').each(function() {
        $(this).prop('disabled', Boolean(recipient) && !recipientAllows(recipient, $(this).val()));
    });
    if (recipient && !recipientAllows(recipient, channelSelect.val())) {
        const firstAvailable = ['sms', 'whatsapp'].find((channel) => recipientAllows(recipient, channel));
        if (firstAvailable) channelSelect.val(firstAvailable);
    }
}

function closeModal() {
    $('#income-payment-reminder-modal').addClass('d-none');
    $('body').removeClass('income-payment-reminder-open');
    selectedIncome = null;
    recipients = [];
}

export function openPaymentReminder() {
    const incomeId = $(this).closest('.income-row-info').attr('income-id');
    selectedIncome = incomeState.incomes.find((income) => String(income.id) === String(incomeId));
    if (!selectedIncome) return;
    $('#income-payment-reminder-summary').text((selectedIncome.client_name || 'Cliente')+' · '+(selectedIncome.unique_id || ''));
    $('#income-payment-reminder-phone').html('<option value="">Cargando numeros...</option>');
    $('#income-payment-reminder-manual-phone').val('');
    $('#income-payment-reminder-channel').val('sms');
    $('#income-payment-reminder-send').prop('disabled', true);
    $('#income-payment-reminder-modal').removeClass('d-none');
    $('body').addClass('income-payment-reminder-open');
    PostMethodFunction('/admin/incomes/payment-reminder-recipients', {income_id: selectedIncome.id}, null, function(response) {
        recipients = response.recipients || [];
        renderRecipientOptions();
        $('#income-payment-reminder-send').prop('disabled', false);
    }, function() {
        $('#income-payment-reminder-send').prop('disabled', false);
    });
}

export function sendPaymentReminder() {
    if (!selectedIncome) return;
    const manualPhone = String($('#income-payment-reminder-manual-phone').val() || '').trim();
    const selectedPhone = String($('#income-payment-reminder-phone').val() || '').trim();
    const phone = manualPhone || selectedPhone;
    if (!phone) {
        alertWarning('Selecciona un numero o digita uno manualmente');
        return;
    }
    const channel = $('#income-payment-reminder-channel').val() || 'sms';
    const button = $('#income-payment-reminder-send');
    button.prop('disabled', true);
    PostMethodFunction('/admin/incomes/send-payment-reminder', {
        income_id: selectedIncome.id,
        phone,
        channel,
    }, null, function(response) {
        button.prop('disabled', false);
        alertSuccess(response.message || ('Recordatorio enviado por '+channelLabel(channel)));
        closeModal();
    }, function() {
        button.prop('disabled', false);
    });
}

export function closePaymentReminder() {
    closeModal();
}

export function syncRecipientChannel() {
    updateChannelOptions();
}