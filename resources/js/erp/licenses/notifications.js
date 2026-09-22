import { licenseState } from './state.js';
import { escapeHtml } from '../notifications/shared.js';
import { openContactTagManager, unlinkContactTag } from '../notifications/contact-tags.js';
import { initializeJiraMultiSelect } from '../jira/dashboard.js';

const channelLabels = {
    email: 'Email',
    sms: 'SMS',
    whatsapp: 'WhatsApp',
};

function values(selector) {
    return ($(selector).val() || []).map((value) => String(value));
}

function channelOptions(selected = []) {
    return Object.entries(channelLabels).map(([value, label]) => '<option value="'+value+'"'+(selected.includes(value) ? ' selected' : '')+'>'+label+'</option>').join('');
}

function syncContactChannels(container) {
    const type = String($(container).find('.notification-type').val() || 'email');
    const select = $(container).find('.notification-channels');
    const selected = ($(select).val() || []).map(String);
    const available = type === 'email' ? ['email'] : ['sms', 'whatsapp'];
    const nextSelected = selected.filter((channel) => available.includes(channel));
    if (!nextSelected.length) nextSelected.push(available[0]);
    select.html(channelOptions(nextSelected)).val(nextSelected);
}

function enhanceChannelSelects() {
    $('#add-notification-row .notification-channels, #notifications-table .update-notification-row .notification-channels').each(function() {
        initializeJiraMultiSelect($(this).closest('td')[0], {
            placeholder: 'Selecciona canales',
            searchPlaceholder: 'Buscar canal',
            emptyText: 'Sin canales',
            selectedLabel: 'seleccionados',
            itemLabel: 'canales',
        });
    });
}

function renderContactTags(contact) {
    const tags = contact.tags || [];
    const editable = !contact.deleted_at;
    let html = '<div class="contact-tag-badges" data-tag-ids="'+tags.map((tag) => tag.id).join(',')+'">';
    tags.forEach((tag) => {
        const color = /^#[0-9a-f]{6}$/i.test(String(tag.color || '')) ? tag.color : '#64748b';
        html += '<span class="contact-tag-badge" style="--contact-tag-color:'+color+'">'+escapeHtml(tag.name)+(editable ? '<button type="button" class="contact-tag-remove" data-tag-id="'+tag.id+'" title="Desligar etiqueta" aria-label="Desligar etiqueta"><i class="fa-solid fa-xmark"></i></button>' : '')+'</span>';
    });
    if (editable) html += '<button type="button" class="contact-tag-add" title="Agregar etiqueta" aria-label="Agregar etiqueta"><i class="fa-solid fa-plus"></i></button>';
    return html+'</div>';
}

function validateContact(container) {
    const name = String($(container).find('.notification-name').val() || '').trim();
    const type = String($(container).find('.notification-type').val() || 'email');
    const value = String($(container).find('.notification-value').val() || '').trim();
    const channels = values($(container).find('.notification-channels'));
    if (!name) {
        alertWarning('Debe ingresar el nombre del contacto');
        return false;
    }
    if (type === 'email' && !validateEmail(value)) {
        alertWarning('El correo del contacto no es valido');
        return false;
    }
    if (type === 'phone' && !value) {
        alertWarning('Debe ingresar el numero del contacto');
        return false;
    }
    if ((type === 'email' && channels.some((channel) => channel !== 'email')) || (type === 'phone' && channels.includes('email'))) {
        alertWarning(type === 'email' ? 'Un contacto de correo solo puede usar Email' : 'Un contacto telefonico solo puede usar SMS o WhatsApp');
        return false;
    }
    if (!channels.length) {
        alertWarning('Debe seleccionar al menos un canal');
        return false;
    }
    return {name, type, value, channels, active: $(container).find('.notification-active').attr('value')};
}

export function syncContactType() {
    syncContactChannels($(this).closest('#add-notification-row, .update-notification-row'));
}

export function addnotification() {
    const container = $(this).closest('#add-notification-row');
    const data = validateContact(container);
    if (!data) return;
    $('#add-notification').prop('disabled', true);
    PostMethodFunction('/admin/licenses/notifications/add', {
        license_id: licenseState.currentLicense.id,
        name: data.name,
        type: data.type,
        value: data.value,
        channels: JSON.stringify(data.channels),
        state: data.active,
    }, null, function(response) {
        $('#add-notification').prop('disabled', false);
        container.find('input').val('');
        container.find('.notification-type').val('email');
        container.find('.notification-channels').val([]);
        container.find('.notification-active').attr('value', '1');
        container.find('.notification-active .toggle-value[value="1"]').click();
        syncContactChannels(container);
        enhanceChannelSelects();
        alertSuccess(response.message || 'Contacto agregado');
        getServiceNotifications();
    }, function() { $('#add-notification').prop('disabled', false); });
}

export function getServiceNotifications() {
    if (!licenseState.currentLicense) return;
    PostMethodFunction('/admin/licenses/notifications/get', {license_id: licenseState.currentLicense.id}, null, showServiceNotifications, null);
}

function showServiceNotifications(response) {
    let html = '';
    (response.data || []).forEach(function(contact) {
        const selectedChannels = (contact.channels || []).map(String);
        const type = contact.type || (contact.email ? 'email' : 'phone');
        const value = contact.value || contact.email || contact.phone || '';
        html += '<tr notification-id="'+contact.id+'" class="update-notification-row'+(contact.deleted_at == null ? '' : ' deleted')+'">';
        html += '<td><input type="text" class="form-control notification-name" value="'+escapeHtml(contact.name || '')+'"></td>';
        html += '<td><input type="text" class="form-control notification-value" value="'+escapeHtml(value)+'"></td>';
        html += '<td><select class="form-select notification-type"><option value="email"'+(type === 'email' ? ' selected' : '')+'>Correo</option><option value="phone"'+(type === 'phone' ? ' selected' : '')+'>Número</option></select></td>';
        html += '<td><select class="form-select notification-channels" multiple size="3">'+channelOptions(selectedChannels)+'</select></td>';
        html += '<td>'+renderContactTags(contact)+'</td>';
        html += '<td><div class="toggle-container row notification-active" value="'+(contact.active ? '1' : '0')+'"><div class="toggle-value d-flex justify-content-center col-6" value="1"><p>Activo</p></div><div class="toggle-value d-flex justify-content-center col-6" value="0"><p>Inactivo</p></div></div></td>';
        html += '<td class="text-end contact-actions-cell action-cell">';
        if (contact.deleted_at == null) {
            html += '<button type="button" class="btn btn-link update-notification-btn" title="Guardar contacto" aria-label="Guardar contacto"><i class="fa-solid fa-floppy-disk"></i></button><button type="button" class="btn btn-link contact-delete-action delete-notification-btn" title="Eliminar contacto" aria-label="Eliminar contacto"><i class="fa-solid fa-trash-can"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-link restore-notification-btn" title="Restaurar contacto" aria-label="Restaurar contacto"><i class="fa-solid fa-trash-arrow-up"></i></button><button type="button" class="btn btn-link contact-delete-action force-delete-notification-btn" title="Eliminar permanentemente" aria-label="Eliminar permanentemente"><i class="fa-solid fa-trash-can"></i></button>';
        }
        html += '</td></tr>';
    });
    $('#notifications-table tbody .update-notification-row').remove();
    $('#notifications-table tbody').append(html);
    $('#notifications-table .update-notification-row .notification-active').each(function() {
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
    syncContactChannels($('#add-notification-row'));
    $('#notifications-table .update-notification-row').each(function() { syncContactChannels(this); });
    enhanceChannelSelects();
}

export function changeNotificationPosition(container, direction) {
    const notificationId = container.parent().parent().attr('notification-id');
    PostMethodFunction('/admin/licenses/notifications/change-position', {notification_id: notificationId, direction}, null, function() {
        getServiceNotifications();
    }, null);
}

export function updateNotification() {
    const button = $(this);
    const container = button.closest('.update-notification-row');
    const data = validateContact(container);
    if (!data) return;
    button.prop('disabled', true);
    PostMethodFunction('/admin/licenses/notifications/update', {
        id: container.attr('notification-id'),
        name: data.name,
        type: data.type,
        value: data.value,
        channels: JSON.stringify(data.channels),
        state: data.active,
    }, null, function(response) {
        button.prop('disabled', false);
        alertSuccess(response.message || 'Contacto actualizado');
        getServiceNotifications();
    }, function() { button.prop('disabled', false); });
}

export function deleteNotification() {
    const id = $(this).closest('.update-notification-row').attr('notification-id');
    swallMessage('Eliminar contacto', 'El contacto quedara disponible en la trazabilidad.', 'error', 'Si, eliminar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/licenses/notifications/delete', {id}, null, function(response) {
            alertSuccess(response.message || 'Contacto eliminado');
            getServiceNotifications();
        }, null);
    }, null);
}

export function restoreNotification() {
    const id = $(this).closest('.update-notification-row').attr('notification-id');
    PostMethodFunction('/admin/licenses/notifications/restore', {id}, null, function(response) {
        alertSuccess(response.message || 'Contacto restaurado');
        getServiceNotifications();
    }, null);
}

export function forceDeleteNotification() {
    const id = $(this).closest('.update-notification-row').attr('notification-id');
    swallMessage('Eliminar permanentemente', 'Esta accion no se puede deshacer.', 'error', 'Si, eliminar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/licenses/notifications/force-delete', {id}, null, function(response) {
            alertSuccess(response.message || 'Contacto eliminado permanentemente');
            getServiceNotifications();
        }, null);
    }, null);
}

export function openLicenseContactTags() {
    const row = $(this).closest('.update-notification-row');
    const assigned = String(row.find('.contact-tag-badges').attr('data-tag-ids') || '').split(',').filter(Boolean);
    openContactTagManager(row.attr('notification-id'), assigned, getServiceNotifications);
}

export function unlinkLicenseContactTag() {
    const row = $(this).closest('.update-notification-row');
    unlinkContactTag(row.attr('notification-id'), $(this).attr('data-tag-id'), getServiceNotifications);
}
