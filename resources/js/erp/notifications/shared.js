import { notificationState } from './state.js';

export function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || window.token || '';
}

export function selectedClientIds(channel) {
    return $('#notifications-'+channel+'-client-list input[data-client-id]:checked').map(function() {
        return String($(this).attr('data-client-id'));
    }).get();
}

function manualRecipients(channel) {
    return $('#notifications-'+channel+'-manual').val() || '';
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

export function renderClientList(channel) {
    const container = $('#notifications-'+channel+'-client-list');
    if (container.length === 0) return;
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
    container.find('input[data-client-id]').prop('disabled', $('#notifications-'+channel+'-all-clients').is(':checked'));
}

export function loadClients() {
    if (notificationState.clientsLoaded || notificationState.clientsLoading) return;
    notificationState.clientsLoading = true;
    PostMethodFunction('/admin/notifications/clients', {}, null, function(response) {
        notificationState.clients = response.clients || [];
        notificationState.clientsLoaded = true;
        notificationState.clientsLoading = false;
        renderClientList('email');
        renderClientList('sms');
    }, function() {
        notificationState.clientsLoading = false;
    });
}

export function formatDate(value) {
    if (!value) return 'Inmediato';
    const rawValue = String(value).trim();
    const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(rawValue);
    const normalizedValue = rawValue.replace(' ', 'T');
    const date = new Date(hasTimezone ? normalizedValue : normalizedValue+'-05:00');
    if (Number.isNaN(date.getTime())) return rawValue.replace('T', ' ').slice(0, 16);

    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Bogota',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date).reduce(function(result, part) {
        result[part.type] = part.value;
        return result;
    }, {});

    return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}`;
}

export function renderPagination(containerSelector, pagination, onPage) {
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
    html += '<button type="button" data-page="'+Math.min(totalPages, Number(pagination.page) + 1)+'"'+(pagination.page >= totalPages ? ' disabled' : '')+' title="Siguiente" aria-label="Siguiente pagina"><i class="fa-solid fa-chevron-right"></i></button>';
    container.html(html);
    container.find('button:not(:disabled)').on('click', function() { onPage(Number($(this).attr('data-page'))); });
}

export function showComposeModal(channel) {
    notificationState.activeChannel = channel;
    const isEmail = channel === 'email';
    $('#notifications-email-form').toggleClass('d-none', !isEmail);
    $('#notifications-sms-form').toggleClass('d-none', isEmail);
    $('#notifications-modal-channel').text(isEmail ? 'EMAIL' : 'SMS');
    $('#notifications-modal-title').text(isEmail ? (notificationState.resendEmailId ? 'Reenviar correo' : 'Nueva notificacion') : (notificationState.resendSmsId ? 'Reenviar SMS' : 'Nuevo SMS'));
    $('#notifications-compose-modal').removeClass('d-none');
    $('body').addClass('notifications-modal-open');
    loadClients();
}

export function closeComposeModal() {
    $('#notifications-compose-modal').addClass('d-none');
    if ($('#notifications-email-view-modal').hasClass('d-none')) $('body').removeClass('notifications-modal-open');
}

export function appendCommonRecipientData(formData, channel) {
    const selected = selectedClientPayload(channel);
    formData.append('client_ids', selected.client_ids);
    formData.append('all_clients', selected.all_clients);
    formData.append('recipients', manualRecipients(channel));
}

export function postFormData(url, formData, success) {
    formData.append('_token', csrfToken());
    PostMethodMultimediaFunctionData(url, formData, null, success, null);
}

export function resetClientSelectors(channel) {
    $('#notifications-'+channel+'-all-clients').prop('checked', false);
    $('#notifications-'+channel+'-client-search').val('');
    renderClientList(channel);
}