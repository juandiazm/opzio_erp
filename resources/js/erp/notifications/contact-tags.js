import { escapeHtml } from './shared.js';

const state = {
    contactId: null,
    assigned: new Set(),
    tags: [],
    onChanged: null,
};

function safeColor(color) {
    return /^#[0-9a-f]{6}$/i.test(String(color || '')) ? String(color) : '#64748b';
}

function modalMarkup() {
    return '<div id="contact-tag-manager-modal" class="contact-tag-manager-modal d-none" role="dialog" aria-modal="true" aria-labelledby="contact-tag-manager-title">'
        + '<div class="contact-tag-manager-dialog">'
        + '<header class="contact-tag-manager-header"><div><span class="contact-tag-manager-kicker">CONTACTOS</span><h2 id="contact-tag-manager-title">Gestionar etiquetas</h2></div><button type="button" class="contact-tag-manager-close" data-contact-tag-action="close" title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button></header>'
        + '<div class="contact-tag-manager-body"><label class="contact-tag-manager-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="contact-tag-manager-search-input" placeholder="Buscar etiqueta" autocomplete="off"></label><div id="contact-tag-manager-list" class="contact-tag-manager-list"></div></div>'
        + '<footer class="contact-tag-manager-footer"><button type="button" class="btn btn-light" data-contact-tag-action="close">Cerrar</button></footer>'
        + '</div></div>';
}

function ensureModal() {
    if ($('#contact-tag-manager-modal').length) return;
    $('body').append(modalMarkup());
    $(document).on('click.contactTagManager', '#contact-tag-manager-modal', function(event) {
        if (event.target === this) closeContactTagManager();
    });
    $(document).on('click.contactTagManager', '[data-contact-tag-action="close"]', closeContactTagManager);
    $(document).on('input.contactTagManager', '#contact-tag-manager-search-input', renderTagList);
    $(document).on('click.contactTagManager', '.contact-tag-manager-association', toggleAssociation);
    $(document).on('click.contactTagManager', '.contact-tag-manager-save', saveTag);
    $(document).on('click.contactTagManager', '.contact-tag-manager-delete', deleteTag);
    $(document).on('click.contactTagManager', '#contact-tag-manager-create', createTag);
}

function callChanged() {
    if (typeof state.onChanged === 'function') state.onChanged();
}

function renderTagList() {
    const search = String($('#contact-tag-manager-search-input').val() || '').trim().toLocaleLowerCase();
    const matches = state.tags.filter((tag) => String(tag.name || '').toLocaleLowerCase().includes(search));
    let html = '';
    matches.forEach((tag) => {
        const assigned = state.assigned.has(String(tag.id));
        html += '<div class="contact-tag-manager-row" data-tag-id="'+tag.id+'">';
        html += '<input type="color" class="contact-tag-manager-color" value="'+safeColor(tag.color)+'" title="Color de la etiqueta" aria-label="Color de la etiqueta">';
        html += '<input type="text" class="form-control contact-tag-manager-name" value="'+escapeHtml(tag.name)+'" maxlength="100" aria-label="Nombre de la etiqueta">';
        html += '<div class="contact-tag-manager-actions">';
        html += '<button type="button" class="btn btn-link contact-tag-manager-association'+(assigned ? ' is-associated' : '')+'" title="'+(assigned ? 'Desligar del contacto' : 'Asociar al contacto')+'" aria-label="'+(assigned ? 'Desligar del contacto' : 'Asociar al contacto')+'"><i class="fa-solid '+(assigned ? 'fa-link-slash' : 'fa-link')+'"></i></button>';
        html += '<button type="button" class="btn btn-link contact-tag-manager-save" title="Guardar etiqueta" aria-label="Guardar etiqueta"><i class="fa-solid fa-floppy-disk"></i></button>';
        html += '<button type="button" class="btn btn-link contact-tag-manager-delete" title="Eliminar etiqueta" aria-label="Eliminar etiqueta"><i class="fa-solid fa-trash-can"></i></button>';
        html += '</div></div>';
    });
    if (!matches.length && search) {
        html += '<div class="contact-tag-manager-create-row"><span>No hay coincidencias para <strong>'+escapeHtml(search)+'</strong></span><input type="color" id="contact-tag-manager-create-color" value="#64748b" title="Color de la etiqueta" aria-label="Color de la etiqueta"><button type="button" class="btn btn-primary" id="contact-tag-manager-create"><i class="fa-solid fa-plus"></i> Crear</button></div>';
    }
    if (!html) html = '<p class="contact-tag-manager-empty">No hay etiquetas creadas.</p>';
    $('#contact-tag-manager-list').html(html);
}

function reloadTags() {
    PostMethodFunction('/admin/notifications/tags/get', {}, null, function(response) {
        state.tags = response.tags || [];
        renderTagList();
    }, null);
}

function toggleAssociation() {
    const row = $(this).closest('.contact-tag-manager-row');
    const tagId = String(row.attr('data-tag-id'));
    const assigned = state.assigned.has(tagId);
    const url = assigned ? '/admin/notifications/contact-tags/detach' : '/admin/notifications/contact-tags/attach';
    $(this).prop('disabled', true);
    PostMethodFunction(url, {contact_id: state.contactId, tag_id: tagId}, null, function() {
        if (assigned) state.assigned.delete(tagId);
        else state.assigned.add(tagId);
        renderTagList();
        callChanged();
    }, function() {
        $(this).prop('disabled', false);
    }.bind(this));
}

function saveTag() {
    const row = $(this).closest('.contact-tag-manager-row');
    const name = String(row.find('.contact-tag-manager-name').val() || '').trim();
    if (!name) {
        alertWarning('Debe ingresar el nombre de la etiqueta');
        return;
    }
    const button = $(this).prop('disabled', true);
    PostMethodFunction('/admin/notifications/tags/update', {
        id: row.attr('data-tag-id'),
        name,
        color: row.find('.contact-tag-manager-color').val(),
    }, null, function(response) {
        button.prop('disabled', false);
        alertSuccess(response.message || 'Etiqueta actualizada');
        reloadTags();
        callChanged();
    }, function() { button.prop('disabled', false); });
}

function deleteTag() {
    const row = $(this).closest('.contact-tag-manager-row');
    const tagId = String(row.attr('data-tag-id'));
    swallMessage('Eliminar etiqueta', 'La etiqueta se quitara de todos los contactos asociados.', 'error', 'Si, eliminar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/notifications/tags/delete', {id: tagId}, null, function(response) {
            state.assigned.delete(tagId);
            alertSuccess(response.message || 'Etiqueta eliminada');
            reloadTags();
            callChanged();
        }, null);
    }, null);
}

function createTag() {
    const name = String($('#contact-tag-manager-search-input').val() || '').trim();
    if (!name) return;
    PostMethodFunction('/admin/notifications/tags/add', {
        name,
        color: $('#contact-tag-manager-create-color').val(),
    }, null, function(response) {
        const tag = response.tag || {};
        if (tag.id) {
            state.assigned.add(String(tag.id));
            PostMethodFunction('/admin/notifications/contact-tags/attach', {contact_id: state.contactId, tag_id: tag.id}, null, function() {
                callChanged();
            }, null);
        }
        alertSuccess(response.message || 'Etiqueta creada');
        $('#contact-tag-manager-search-input').val('');
        reloadTags();
    }, null);
}

export function openContactTagManager(contactId, assignedTagIds = [], onChanged = null) {
    ensureModal();
    state.contactId = contactId;
    state.assigned = new Set((Array.isArray(assignedTagIds) ? assignedTagIds : String(assignedTagIds || '').split(',')).filter(Boolean).map(String));
    state.onChanged = onChanged;
    $('#contact-tag-manager-search-input').val('');
    $('#contact-tag-manager-modal').removeClass('d-none');
    $('body').addClass('contact-tag-manager-open');
    reloadTags();
}

export function closeContactTagManager() {
    $('#contact-tag-manager-modal').addClass('d-none');
    $('body').removeClass('contact-tag-manager-open');
    state.contactId = null;
    state.onChanged = null;
}

export function unlinkContactTag(contactId, tagId, onChanged = null) {
    swallMessage('Desligar etiqueta', 'La etiqueta dejara de estar asociada a este contacto.', 'warning', 'Si, desligar', 'No, cancelar', null, function() {
        PostMethodFunction('/admin/notifications/contact-tags/detach', {contact_id: contactId, tag_id: tagId}, null, function(response) {
            alertSuccess(response.message || 'Etiqueta desligada');
            if (typeof onChanged === 'function') onChanged();
        }, null);
    }, null);
}