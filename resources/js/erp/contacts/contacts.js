import { initializeJiraMultiSelect } from '../jira/dashboard.js';
import { escapeHtml } from '../notifications/shared.js';
import { openContactTagManager, unlinkContactTag } from '../notifications/contact-tags.js';
import { renderEntityAvatar } from '../shared/list.js';

const state = {
    pagination: {page: 1, per_page: 10, total: 0, totalPages: 0},
    initialized: false,
    filtersLoaded: false,
    filters: null,
    contacts: new Map(),
    editInitialized: false,
    editingId: null,
    editingContact: null,
};

const channelLabels = {
    email: 'Email',
    sms: 'SMS',
    whatsapp: 'WhatsApp',
};

const typeLabels = {
    email: 'Correo',
    phone: 'Número',
};

function selectValues(selector) {
    return $(selector).val() || [];
}

function setOptions(selector, items, valueKey = 'id', labelKey = 'label', placeholder = null) {
    const select = $(selector);
    select.empty();
    if (placeholder !== null) select.append(new Option(placeholder, ''));
    (items || []).forEach((item) => {
        const option = new Option(item[labelKey] ?? item.name ?? item.value, item[valueKey] ?? item.id);
        if (item.client_id) option.setAttribute('data-client-id', item.client_id);
        select.append(option);
    });
}

function initializeMultiSelect(fieldSelector, config) {
    const field = document.querySelector(fieldSelector);
    if (field) initializeJiraMultiSelect(field, config);
}

function initializeFilters(filters) {
    state.filters = filters;
    setOptions('[data-contact-client-filter]', filters.clients);
    setOptions('[data-contact-license-filter]', filters.licenses);
    setOptions('[data-contact-type-filter]', filters.types, 'value', 'label');
    setOptions('[data-contact-channel-filter]', filters.channels, 'value', 'label');
    setOptions('[data-contact-tag-filter]', filters.tags, 'id', 'name');

    initializeMultiSelect('[data-contact-client-field]', {
        placeholder: 'Todos los clientes',
        searchPlaceholder: 'Buscar cliente...',
        emptyText: 'Sin clientes coincidentes',
        selectedLabel: 'clientes seleccionados',
        itemLabel: 'clientes',
    });
    initializeMultiSelect('[data-contact-license-field]', {
        placeholder: 'Todas las licencias',
        searchPlaceholder: 'Buscar licencia...',
        emptyText: 'Sin licencias coincidentes',
        selectedLabel: 'licencias seleccionadas',
        itemLabel: 'licencias',
    });
    initializeMultiSelect('[data-contact-type-field]', {
        placeholder: 'Todos los tipos',
        searchPlaceholder: 'Buscar tipo...',
        emptyText: 'Sin tipos coincidentes',
        selectedLabel: 'tipos seleccionados',
        itemLabel: 'tipos',
    });
    initializeMultiSelect('[data-contact-channel-field]', {
        placeholder: 'Todos los canales',
        searchPlaceholder: 'Buscar canal...',
        emptyText: 'Sin canales coincidentes',
        selectedLabel: 'canales seleccionados',
        itemLabel: 'canales',
    });
    initializeMultiSelect('[data-contact-tag-field]', {
        placeholder: 'Todas las etiquetas',
        searchPlaceholder: 'Buscar etiqueta...',
        emptyText: 'Sin etiquetas coincidentes',
        selectedLabel: 'etiquetas seleccionadas',
        itemLabel: 'etiquetas',
    });
    initializeEditModal();
    state.filtersLoaded = true;
}

function initializeEditModal() {
    if (state.editInitialized || !state.filters) return;
    setOptions('[data-contact-edit-client]', state.filters.clients, 'id', 'label', 'Seleccionar cliente');
    setOptions('[data-contact-edit-license]', state.filters.licenses, 'id', 'label', 'Seleccionar licencia');
    setOptions('[data-contact-edit-channels]', state.filters.channels, 'value', 'label');
    initializeMultiSelect('[data-contact-edit-channels-field]', {
        placeholder: 'Selecciona canales',
        searchPlaceholder: 'Buscar canal...',
        emptyText: 'Sin canales coincidentes',
        selectedLabel: 'canales seleccionados',
        itemLabel: 'canales',
    });
    $('[data-contact-edit-license]').on('change', function() {
        const option = $(this).find('option:selected');
        const clientId = option.attr('data-client-id');
        if (clientId) $('[data-contact-edit-client]').val(clientId);
    });
    $('[data-contact-edit-type]').on('change', syncEditChannels);
    $('[data-contact-edit-form]').on('submit', saveEditedContact);
    $('[data-contact-edit-close]').on('click', closeEditModal);
    $('[data-contact-edit-modal]').on('click', function(event) {
        if (event.target === this) closeEditModal();
    });
    state.editInitialized = true;
}

function setEditChannels(options) {
    const select = $('[data-contact-edit-channels]');
    const selected = select.val() || [];
    select.html(channelOptions(options)).val(selected.filter((channel) => options.some((option) => option.value === channel)));
    select.trigger('change');
}

function syncEditChannels() {
    const type = $('[data-contact-edit-type]').val() || 'email';
    const options = type === 'email'
        ? [{value: 'email', label: 'Email'}]
        : [{value: 'sms', label: 'SMS'}, {value: 'whatsapp', label: 'WhatsApp'}];
    const selected = $('[data-contact-edit-channels]').val() || [];
    setEditChannels(options);
    if (!selected.length) $('[data-contact-edit-channels]').val([options[0].value]).trigger('change');
}

function channelOptions(options) {
    return (options || []).map((option) => '<option value="'+escapeHtml(option.value)+'">'+escapeHtml(option.label)+'</option>').join('');
}

function openEditModal(contact) {
    state.editingId = contact.id;
    state.editingContact = contact;
    setEditMode(false);
    $('[data-contact-edit-name]').val(contact.name || '');
    $('[data-contact-edit-value]').val(contact.value || contact.email || contact.phone || '');
    $('[data-contact-edit-type]').val(contact.type || (contact.email ? 'email' : 'phone'));
    $('[data-contact-edit-client]').val(contact.client_id || '');
    $('[data-contact-edit-license]').val(contact.license_id || '');
    renderEditTags(contact.tags || []);
    $('[data-contact-edit-channels]').val((contact.channels || []).map(String));
    syncEditChannels();
    const active = contact.active ? '1' : '0';
    $('[data-contact-edit-active]').attr('value', active).find('.toggle-value[value="'+active+'"]').click();
    $('[data-contact-edit-modal]').removeClass('d-none');
    $('body').addClass('contacts-edit-open');
}

function setEditMode(isCreating) {
    $('[data-contact-edit-title]').text(isCreating ? 'Nuevo contacto' : 'Editar contacto');
    $('[data-contact-edit-submit-label]').text(isCreating ? 'Agregar contacto' : 'Guardar');
    $('[data-contact-edit-submit-icon]').attr('class', isCreating ? 'fa-solid fa-user-plus' : 'fa-solid fa-floppy-disk');
    $('[data-contact-edit-tags-field]').toggleClass('d-none', isCreating);
}

function openCreateModal() {
    state.editingId = null;
    state.editingContact = null;
    $('[data-contact-edit-form]')[0].reset();
    $('[data-contact-edit-name]').val('');
    $('[data-contact-edit-value]').val('');
    $('[data-contact-edit-type]').val('email');
    $('[data-contact-edit-client]').val('');
    $('[data-contact-edit-license]').val('');
    $('[data-contact-edit-active]').attr('value', '1').find('.toggle-value[value="1"]').click();
    setEditMode(true);
    syncEditChannels();
    $('[data-contact-edit-modal]').removeClass('d-none');
    $('body').addClass('contacts-edit-open');
    $('[data-contact-edit-name]').trigger('focus');
}

function closeEditModal() {
    $('[data-contact-edit-modal]').addClass('d-none');
    $('body').removeClass('contacts-edit-open');
    state.editingId = null;
    state.editingContact = null;
}

function renderEditTags(tags) {
    let html = '<div class="contact-tag-badges">';
    (tags || []).forEach((tag) => {
        const color = /^#[0-9a-f]{6}$/i.test(String(tag.color || '')) ? tag.color : '#64748b';
        html += '<span class="contact-tag-badge" style="--contact-tag-color:'+color+'">'+escapeHtml(tag.name)+'<button type="button" class="contact-tag-remove" data-contact-edit-tag-remove="'+tag.id+'" title="Desligar etiqueta" aria-label="Desligar etiqueta"><i class="fa-solid fa-xmark"></i></button></span>';
    });
    html += '<button type="button" class="contact-tag-add" data-contact-edit-tag-add title="Agregar etiqueta" aria-label="Agregar etiqueta"><i class="fa-solid fa-plus"></i></button></div>';
    $('[data-contact-edit-tags]').html(html);
}

function refreshEditedContact() {
    loadContacts(function() {
        const refreshed = state.contacts.get(String(state.editingId));
        if (refreshed) {
            state.editingContact = refreshed;
            renderEditTags(refreshed.tags || []);
        }
    });
}

function openEditTags() {
    if (!state.editingContact) return;
    openContactTagManager(
        state.editingId,
        (state.editingContact.tags || []).map((tag) => String(tag.id)),
        refreshEditedContact
    );
}

function unlinkEditTag() {
    if (!state.editingContact) return;
    unlinkContactTag(state.editingId, $(this).attr('data-contact-edit-tag-remove'), refreshEditedContact);
}

function saveEditedContact(event) {
    event.preventDefault();
    const isCreating = !state.editingId;
    const type = $('[data-contact-edit-type]').val();
    const channels = $('[data-contact-edit-channels]').val() || [];
    const value = String($('[data-contact-edit-value]').val() || '').trim();
    if (type === 'email' && !validateEmail(value)) {
        alertWarning('El correo del contacto no es valido');
        return;
    }
    if (type === 'phone' && !value) {
        alertWarning('Debe ingresar el numero del contacto');
        return;
    }
    if ((type === 'email' && channels.some((channel) => channel !== 'email')) || (type === 'phone' && channels.includes('email'))) {
        alertWarning(type === 'email' ? 'Un contacto de correo solo puede usar Email' : 'Un contacto telefonico solo puede usar SMS o WhatsApp');
        return;
    }
    const clientId = $('[data-contact-edit-client]').val() || '';
    const licenseId = $('[data-contact-edit-license]').val() || '';
    if (isCreating && !clientId && !licenseId) {
        alertWarning('Debe seleccionar un cliente o una licencia');
        return;
    }
    const button = $('[data-contact-edit-form] button[type="submit"]').prop('disabled', true);
    const data = {
        name: $('[data-contact-edit-name]').val(),
        value,
        type,
        channels: JSON.stringify(channels),
        client_id: clientId,
        license_id: licenseId,
        active: $('[data-contact-edit-active]').attr('value') || '1',
    };
    if (!isCreating) data.id = state.editingId;
    PostMethodFunction(isCreating ? '/admin/contacts/add' : '/admin/contacts/update', data, null, function(response) {
        button.prop('disabled', false);
        alertSuccess(response.message || (isCreating ? 'Contacto agregado' : 'Contacto actualizado'));
        closeEditModal();
        if (isCreating) state.pagination.page = 1;
        loadContacts();
    }, function() { button.prop('disabled', false); });
}

function loadFilters() {
    PostMethodFunction('/admin/contacts/filters', {}, null, function(response) {
        initializeFilters(response);
        loadContacts();
    }, function() {
        setStatus('No fue posible cargar los filtros.', true);
    });
}

function setStatus(message, isError = false) {
    const status = $('[data-contact-status]');
    status.text(message || '');
    status.toggleClass('is-error', isError);
}

function getDirectoryFilterData() {
    return {
        search: $('[data-contact-search]').val() || '',
        owner: $('[data-contact-owner]').val() || '',
        client_ids: selectValues('[data-contact-client-filter]'),
        license_ids: selectValues('[data-contact-license-filter]'),
        types: selectValues('[data-contact-type-filter]'),
        channels: selectValues('[data-contact-channel-filter]'),
        tag_ids: selectValues('[data-contact-tag-filter]'),
    };
}

function getRequestData() {
    const filters = getDirectoryFilterData();
    return Object.assign({pagination: JSON.stringify(state.pagination)}, Object.fromEntries(
        Object.entries(filters).map(([key, value]) => [key, Array.isArray(value) ? JSON.stringify(value) : value])
    ));
}

function exportContacts() {
    closeContactTools();
    const params = new URLSearchParams();
    const filters = getDirectoryFilterData();
    Object.entries(filters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((item) => params.append(key+'[]', item));
        } else if (value !== '') {
            params.set(key, value);
        }
    });
    window.location.assign('/admin/contacts/export?'+params.toString());
}

function importContacts() {
    const input = $('[data-contact-import-file]');
    const file = input.prop('files')[0];
    if (!file) return;

    const button = $('[data-contact-import]').prop('disabled', true);
    PostMethodMultimediaFunction('/admin/contacts/import', $('[data-contact-import-form]'), null, function(response) {
        button.prop('disabled', false);
        input.val('');
        loadContacts();
        if (Number(response.status) === 1) {
            alertSuccess(response.message || 'Contactos importados correctamente');
            return;
        }

        const errors = (response.errors || []).slice(0, 20).map((error) => '<div><strong>Fila '+escapeHtml(error.row)+'</strong>: '+escapeHtml(error.message)+'</div>').join('');
        const details = errors ? '<div class="contacts-import-errors">'+errors+'</div>' : '';
        swallMessage(
            'Importación con observaciones',
            '<p>'+(response.message || 'Algunas filas no pudieron importarse.')+'</p><p>Creados: '+Number(response.created || 0)+' · Actualizados: '+Number(response.updated || 0)+'</p>'+details,
            'warning',
            'Entendido',
            null,
            null,
            null,
            null
        );
    }, function() {
        button.prop('disabled', false);
        input.val('');
    });
}

function closeContactTools() {
    $('[data-contact-tools-menu]').prop('hidden', true);
    $('[data-contact-tools-toggle]').attr('aria-expanded', 'false');
}

function toggleContactTools() {
    const menu = $('[data-contact-tools-menu]');
    const isClosed = menu.prop('hidden');
    menu.prop('hidden', !isClosed);
    $('[data-contact-tools-toggle]').attr('aria-expanded', isClosed ? 'true' : 'false');
    if (isClosed) menu.find('[data-contact-import]').trigger('focus');
}

function channelBadges(channels) {
    return (channels || []).map((channel) => '<span class="contacts-channel-badge channel-'+escapeHtml(channel)+'">'+escapeHtml(channelLabels[channel] || channel)+'</span>').join('');
}

function tagBadges(tags) {
    return (tags || []).map((tag) => {
        const color = /^#[0-9a-f]{6}$/i.test(String(tag.color || '')) ? tag.color : '#64748b';
        return '<span class="contact-tag-badge" style="--contact-tag-color:'+color+'">'+escapeHtml(tag.name)+'</span>';
    }).join('') || '<span class="contacts-muted">Sin etiquetas</span>';
}

function contactTagBadges(contact) {
    const tags = contact.tags || [];
    let html = '<div class="contact-tag-badges">';
    tags.forEach((tag) => {
        const color = /^#[0-9a-f]{6}$/i.test(String(tag.color || '')) ? tag.color : '#64748b';
        html += '<span class="contact-tag-badge" style="--contact-tag-color:'+color+'">'+escapeHtml(tag.name)+'<button type="button" class="contact-tag-remove" data-directory-tag-id="'+tag.id+'" title="Desligar etiqueta" aria-label="Desligar etiqueta"><i class="fa-solid fa-xmark"></i></button></span>';
    });
    html += '<button type="button" class="contact-tag-add" data-directory-tag-add title="Agregar etiqueta" aria-label="Agregar etiqueta"><i class="fa-solid fa-plus"></i></button></div>';
    return html;
}

function contactTypeCell(contact) {
    const type = contact.type || '';
    const icon = type === 'email' ? 'fa-envelope' : 'fa-mobile-screen-button';
    return '<span class="contacts-type"><i class="fa-light '+icon+' contacts-type-icon" aria-hidden="true"></i><span>'+escapeHtml(typeLabels[type] || type || '-')+'</span></span>';
}

function contactClientCell(contact) {
    const clientName = contact.client_name || '-';
    return '<div class="erp-identity contacts-client-identity">'+renderEntityAvatar(contact.client || null, 'clients')+'<div class="erp-identity-copy"><p class="erp-identity-name" title="'+escapeHtml(clientName)+'">'+escapeHtml(clientName)+'</p></div></div>';
}

function contactStatusBadge(contact) {
    const active = Boolean(contact.active);
    const nextLabel = active ? 'Inactivar' : 'Activar';
    return '<button type="button" class="erp-status contacts-status '+(active ? 'is-active' : 'is-inactive')+'" data-directory-status-toggle="'+contact.id+'" data-current-status="'+(active ? '1' : '0')+'" title="'+nextLabel+' contacto" aria-label="'+nextLabel+' contacto"><span class="erp-status-label">'+(active ? 'Activo' : 'Inactivo')+'</span></button>';
}

function contactMessageActions(contact) {
    if (!contact.active) return '';
    const channels = new Set((contact.channels || []).map((channel) => String(channel)));
    const actions = [];
    if (channels.has('email') && contact.email) {
        actions.push('<button type="button" class="btn btn-link contacts-action-button contacts-message-email" data-contact-message-channel="email" data-contact-id="'+contact.id+'" title="Enviar correo" aria-label="Enviar correo"><i class="fa-light fa-envelope"></i></button>');
    }
    if (channels.has('sms') && contact.phone) {
        actions.push('<button type="button" class="btn btn-link contacts-action-button contacts-message-sms" data-contact-message-channel="sms" data-contact-id="'+contact.id+'" title="Enviar SMS" aria-label="Enviar SMS"><i class="fa-light fa-comment-sms"></i></button>');
    }
    if (channels.has('whatsapp') && contact.phone) {
        actions.push('<button type="button" class="btn btn-link contacts-action-button contacts-message-whatsapp" data-contact-message-channel="whatsapp" data-contact-id="'+contact.id+'" title="Enviar WhatsApp" aria-label="Enviar WhatsApp"><i class="fa-brands fa-whatsapp"></i></button>');
    }
    return actions.join('');
}

function renderContacts(response) {
    state.pagination = response.pagination || state.pagination;
    const contacts = response.contacts || [];
    const body = $('[data-contact-table-body]');
    state.contacts.clear();
    let html = '';
    contacts.forEach((contact) => {
        state.contacts.set(String(contact.id), contact);
        html += '<tr data-contact-id="'+contact.id+'">';
        html += '<td><strong>'+escapeHtml(contact.name || 'Contacto')+'</strong></td>';
        html += '<td class="contacts-value">'+escapeHtml(contact.value || contact.email || contact.phone || '')+'</td>';
        html += '<td>'+contactTypeCell(contact)+'</td>';
        html += '<td><div class="contacts-badge-list">'+channelBadges(contact.channels)+'</div></td>';
        html += '<td class="erp-identity-cell">'+contactClientCell(contact)+'</td>';
        html += '<td>'+escapeHtml(contact.license_name || '-')+'</td>';
        html += '<td><div class="contacts-badge-list">'+contactTagBadges(contact)+'</div></td>';
        html += '<td class="text-center">'+contactStatusBadge(contact)+'</td>';
        html += '<td class="text-end"><div class="contacts-action-group">'+contactMessageActions(contact)+'<button type="button" class="btn btn-link contacts-action-button contacts-edit-button" data-contact-id="'+contact.id+'" title="Editar contacto" aria-label="Editar contacto"><i class="fa-solid fa-pen-to-square"></i></button></div></td>';
        html += '</tr>';
    });
    if (!html) html = '<tr><td colspan="9" class="contacts-empty">No hay contactos que coincidan con los filtros.</td></tr>';
    body.html(html);
    renderPagination();
}

function renderPagination() {
    const container = $('[data-contact-pagination]');
    const totalPages = Number(state.pagination.totalPages || 0);
    const showPageSize = Number(state.pagination.total || 0) > 5;
    if (totalPages <= 1 && !showPageSize) {
        container.empty();
        return;
    }
    let html = '';
    if (totalPages > 1) {
        html += '<li class="page-item '+(state.pagination.page <= 1 ? 'disabled' : '')+'"><button type="button" class="page-link" data-contact-page="'+Math.max(1, state.pagination.page - 1)+'"'+(state.pagination.page <= 1 ? ' disabled' : '')+'><i class="fa-solid fa-chevron-left"></i></button></li>';
        for (let page = 1; page <= totalPages; page += 1) {
            if (totalPages > 8 && page > 3 && page < totalPages - 2 && Math.abs(page - state.pagination.page) > 1) {
                if (page === 4 || page === totalPages - 3) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                continue;
            }
            html += '<li class="page-item"><button type="button" class="page-link'+(page === Number(state.pagination.page) ? ' active' : '')+'" data-contact-page="'+page+'">'+page+'</button></li>';
        }
        html += '<li class="page-item '+(state.pagination.page >= totalPages ? 'disabled' : '')+'"><button type="button" class="page-link" data-contact-page="'+Math.min(totalPages, Number(state.pagination.page) + 1)+'"'+(state.pagination.page >= totalPages ? ' disabled' : '')+'><i class="fa-solid fa-chevron-right"></i></button></li>';
    }
    if (showPageSize) {
        html += '<li class="page-item contacts-page-size"><span class="contacts-page-size-control"><select data-contact-page-size aria-label="Registros por página"><option value="5"'+(Number(state.pagination.per_page) === 5 ? ' selected' : '')+'>5</option><option value="10"'+(Number(state.pagination.per_page) === 10 ? ' selected' : '')+'>10</option><option value="50"'+(Number(state.pagination.per_page) === 50 ? ' selected' : '')+'>50</option></select><i class="fa-light fa-chevron-down" aria-hidden="true"></i></span></li>';
    }
    container.html(html);
}

function loadContacts(onLoaded = null) {
    if (!state.filtersLoaded) return;
    setStatus('Cargando contactos...');
    PostMethodFunction('/admin/contacts/get-page', getRequestData(), null, function(response) {
        setStatus('');
        renderContacts(response);
        if (typeof onLoaded === 'function') onLoaded();
    }, function() {
        setStatus('No fue posible cargar los contactos.', true);
    });
}

function resetFilters() {
    $('[data-contact-directory-form]')[0].reset();
    $('[data-contact-client-filter], [data-contact-license-filter], [data-contact-type-filter], [data-contact-channel-filter], [data-contact-tag-filter]').val([]).trigger('change');
    state.pagination.page = 1;
    loadContacts();
}

function initialize() {
    if (state.initialized) return;
    state.initialized = true;
    $('[data-contact-directory-form]').on('submit', function(event) {
        event.preventDefault();
        state.pagination.page = 1;
        loadContacts();
    });
    $('[data-contact-reset]').on('click', resetFilters);
    $('[data-contact-tools-toggle]').on('click', function(event) {
        event.stopPropagation();
        toggleContactTools();
    });
    $('[data-contact-tools-menu]').on('click', function(event) {
        event.stopPropagation();
    });
    $(document).on('click.contactsDirectoryTools', closeContactTools);
    $(document).on('keydown.contactsDirectoryTools', function(event) {
        if (event.key === 'Escape') closeContactTools();
    });
    $('[data-contact-export]').on('click', exportContacts);
    $('[data-contact-create]').on('click', function() {
        closeContactTools();
        openCreateModal();
    });
    $('[data-contact-import]').on('click', function() {
        closeContactTools();
        $('[data-contact-import-file]').trigger('click');
    });
    $('[data-contact-import-file]').on('change', importContacts);
    $('[data-contact-edit-tags]').on('click', '[data-contact-edit-tag-add]', openEditTags);
    $('[data-contact-edit-tags]').on('click', '[data-contact-edit-tag-remove]', unlinkEditTag);
    $('[data-contact-table-body]').on('click', '.contacts-edit-button', function() {
        const contact = state.contacts.get(String($(this).attr('data-contact-id')));
        if (contact) openEditModal(contact);
    });
    $('[data-contact-table-body]').on('click', '[data-contact-message-channel]', function() {
        const contact = state.contacts.get(String($(this).attr('data-contact-id')));
        const channel = String($(this).attr('data-contact-message-channel') || '');
        if (!contact || !['email', 'sms', 'whatsapp'].includes(channel)) return;
        const params = new URLSearchParams({contact_id: String(contact.id), contact_channel: channel});
        window.location.assign('/admin/notifications?'+params.toString());
    });
    $('[data-contact-pagination]').on('click', '[data-contact-page]:not(:disabled)', function() {
        state.pagination.page = Number($(this).attr('data-contact-page')) || 1;
        loadContacts();
    });
    $('[data-contact-pagination]').on('change', '[data-contact-page-size]', function() {
        state.pagination.per_page = Number($(this).val()) || 10;
        state.pagination.page = 1;
        loadContacts();
    });
    $('[data-contact-table-body]').on('click', '[data-directory-status-toggle]', function() {
        const toggle = $(this).closest('[data-directory-status-toggle]');
        const id = toggle.attr('data-directory-status-toggle');
        const active = toggle.attr('data-current-status') === '1';
        toggle.prop('disabled', true);
        PostMethodFunction('/admin/contacts/toggle-status', {id, active: active ? 0 : 1}, null, function(response) {
            const updated = response.contact;
            if (updated) state.contacts.set(String(updated.id), updated);
            loadContacts();
        }, function() {
            loadContacts();
        });
    });
    $('[data-contact-table-body]').on('click', '[data-directory-tag-add]', function() {
        const contact = state.contacts.get(String($(this).closest('tr').attr('data-contact-id')));
        if (!contact) return;
        openContactTagManager(contact.id, (contact.tags || []).map((tag) => String(tag.id)), loadContacts);
    });
    $('[data-contact-table-body]').on('click', '[data-directory-tag-id]', function() {
        const contact = state.contacts.get(String($(this).closest('tr').attr('data-contact-id')));
        if (!contact) return;
        unlinkContactTag(contact.id, $(this).attr('data-directory-tag-id'), loadContacts);
    });
    loadFilters();
}

$(document).ready(initialize);
