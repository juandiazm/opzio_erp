import { clientState } from './state.js';
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
    return Object.entries(channelLabels)
        .map(([value, label]) => '<option value="'+value+'"'+(selected.includes(value) ? ' selected' : '')+'>'+label+'</option>')
        .join('');
}

function syncContactChannels(container) {
    const type = String($(container).find('.client-contact-type').val() || 'email');
    const select = $(container).find('.client-contact-channels');
    const selected = ($(select).val() || []).map(String);
    const available = type === 'email' ? ['email'] : ['sms', 'whatsapp'];
    const nextSelected = selected.filter((channel) => available.includes(channel));
    if (!nextSelected.length) nextSelected.push(available[0]);
    select.html(channelOptions(nextSelected)).val(nextSelected);
}

function enhanceChannelSelects() {
    $('#add-client-contact-row .client-contact-channels, #client-contacts-table .update-client-contact-row .client-contact-channels').each(function() {
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
    html += '</div>';
    return html;
}

function validateContact(container) {
    const name = String($(container).find('.client-contact-name').val() || '').trim();
    const type = String($(container).find('.client-contact-type').val() || 'email');
    const value = String($(container).find('.client-contact-value').val() || '').trim();
    const channels = values($(container).find('.client-contact-channels'));
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
    return {name, type, value, channels, active: $(container).find('.client-contact-active').attr('value')};
}

function requestData(container) {
    const data = validateContact(container);
    if (!data) return null;
    return {
        client_id: clientState.currentClient.id,
        name: data.name,
        type: data.type,
        value: data.value,
        channels: JSON.stringify(data.channels),
        active: data.active,
    };
}

export function getClientContacts() {
    if (!clientState.currentClient) return;
    PostMethodFunction('/admin/clients/contacts/get', {client_id: clientState.currentClient.id}, null, renderContacts, null);
}

function renderContacts(response) {
    const contacts = response.contacts || [];
    let html = '';
    contacts.forEach((contact) => {
        const selectedChannels = (contact.channels || []).map(String);
        const type = contact.type || (contact.email ? 'email' : 'phone');
        const value = contact.value || contact.email || contact.phone || '';
        html += '<tr class="update-client-contact-row'+(contact.deleted_at ? ' deleted' : '')+'" data-contact-id="'+contact.id+'">';
        html += '<td><input type="text" class="form-control client-contact-name" value="'+escapeHtml(contact.name || '')+'"></td>';
        html += '<td><input type="text" class="form-control client-contact-value" value="'+escapeHtml(value)+'"></td>';
        html += '<td><select class="form-select client-contact-type"><option value="email"'+(type === 'email' ? ' selected' : '')+'>Correo</option><option value="phone"'+(type === 'phone' ? ' selected' : '')+'>Número</option></select></td>';
        html += '<td><select class="form-select client-contact-channels" multiple size="3">'+channelOptions(selectedChannels)+'</select></td>';
        html += '<td>'+renderContactTags(contact)+'</td>';
        html += '<td><div class="toggle-container row client-contact-active" value="'+(contact.active ? '1' : '0')+'"><div class="toggle-value d-flex justify-content-center col-6" value="1"><p>Activo</p></div><div class="toggle-value d-flex justify-content-center col-6" value="0"><p>Inactivo</p></div></div></td>';
        html += '<td class="text-end contact-actions-cell">';
        if (contact.deleted_at) {
            html += '<button type="button" class="btn btn-link restore-client-contact-btn" title="Restaurar contacto" aria-label="Restaurar contacto"><i class="fa-solid fa-trash-arrow-up"></i></button><button type="button" class="btn btn-link contact-delete-action force-delete-client-contact-btn" title="Eliminar permanentemente" aria-label="Eliminar permanentemente"><i class="fa-solid fa-trash-can"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-link update-client-contact-btn" title="Guardar contacto" aria-label="Guardar contacto"><i class="fa-solid fa-floppy-disk"></i></button><button type="button" class="btn btn-link contact-delete-action delete-client-contact-btn" title="Eliminar contacto" aria-label="Eliminar contacto"><i class="fa-solid fa-trash-can"></i></button>';
        }
        html += '</td></tr>';
    });
    $('#client-contacts-table tbody .update-client-contact-row').remove();
    $('#client-contacts-table tbody').append(html);
    $('#client-contacts-table .update-client-contact-row .client-contact-active').each(function() {
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
    syncContactChannels($('#add-client-contact-row'));
    $('#client-contacts-table .update-client-contact-row').each(function() { syncContactChannels(this); });
    enhanceChannelSelects();
}

export function addClientContact() {
    const row = $('#add-client-contact-row');
    const data = requestData(row);
    if (!data) return;
    $('#add-client-contact').prop('disabled', true);
    PostMethodFunction('/admin/clients/contacts/add', data, null, function(response) {
        $('#add-client-contact').prop('disabled', false);
        row.find('input').val('');
        row.find('.client-contact-type').val('email');
        row.find('.client-contact-channels').val([]);
        row.find('.client-contact-active').attr('value', '1');
        row.find('.client-contact-active .toggle-value[value="1"]').click();
        syncContactChannels(row);
        enhanceChannelSelects();
        alertSuccess(response.message || 'Contacto agregado');
        getClientContacts();
    }, function() { $('#add-client-contact').prop('disabled', false); });
}

export function updateClientContact() {
    const row = $(this).closest('.update-client-contact-row');
    const data = requestData(row);
    if (!data) return;
    delete data.client_id;
    data.id = row.attr('data-contact-id');
    $(this).prop('disabled', true);
    PostMethodFunction('/admin/clients/contacts/update', data, null, function(response) {
        $(this).prop('disabled', false);
        alertSuccess(response.message || 'Contacto actualizado');
        getClientContacts();
    }.bind(this), function() { $(this).prop('disabled', false); }.bind(this));
}

export function deleteClientContact() {
    const id = $(this).closest('.update-client-contact-row').attr('data-contact-id');
    swallMessage('Eliminar contacto', 'El contacto quedara disponible en la trazabilidad.', 'error', 'Si, eliminar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/clients/contacts/delete', {id}, null, function(response) {
            alertSuccess(response.message || 'Contacto eliminado');
            getClientContacts();
        }, null);
    }, null);
}

export function restoreClientContact() {
    const id = $(this).closest('.update-client-contact-row').attr('data-contact-id');
    PostMethodFunction('/admin/clients/contacts/restore', {id}, null, function(response) {
        alertSuccess(response.message || 'Contacto restaurado');
        getClientContacts();
    }, null);
}

export function forceDeleteClientContact() {
    const id = $(this).closest('.update-client-contact-row').attr('data-contact-id');
    swallMessage('Eliminar permanentemente', 'Esta accion no se puede deshacer.', 'error', 'Si, eliminar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/clients/contacts/force-delete', {id}, null, function(response) {
            alertSuccess(response.message || 'Contacto eliminado permanentemente');
            getClientContacts();
        }, null);
    }, null);
}

export function openClientContactTags() {
    const row = $(this).closest('.update-client-contact-row');
    const assigned = String(row.find('.contact-tag-badges').attr('data-tag-ids') || '').split(',').filter(Boolean);
    openContactTagManager(row.attr('data-contact-id'), assigned, getClientContacts);
}

export function unlinkClientContactTag() {
    const row = $(this).closest('.update-client-contact-row');
    unlinkContactTag(row.attr('data-contact-id'), $(this).attr('data-tag-id'), getClientContacts);
}

export function syncContactType() {
    syncContactChannels($(this).closest('#add-client-contact-row, .update-client-contact-row'));
}
