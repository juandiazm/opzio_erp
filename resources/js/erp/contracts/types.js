import { contractState } from './state.js';
import { escapeHtml, loadCatalogs } from './shared.js';

export function getTypes() {
    PostMethodFunction('/admin/contracts/types/get', {}, null, renderTypes, null);
}

function renderTypes(response) {
    const types = response.types || [];
    let html = '';
    types.forEach(function(type) {
        const deleted = type.deleted_at != null;
        html += '<tr class="contract-catalog-row'+(deleted ? ' deleted' : '')+'" data-type-id="'+type.id+'">';
        html += '<td><input class="form-control contract-type-row-name" value="'+escapeHtml(type.name)+'" '+(deleted ? 'disabled' : '')+'></td>';
        html += '<td><input class="form-control contract-type-row-description" value="'+escapeHtml(type.description || '')+'" '+(deleted ? 'disabled' : '')+'></td>';
        html += '<td>'+type.templates_count+'</td><td>'+type.contracts_count+'</td><td>'+(type.active ? 'Activo' : 'Inactivo')+'</td><td class="action-cell">';
        if(deleted){ html += '<i class="fa-solid fa-rotate-left contract-type-restore" title="Restaurar"></i>'; }
        else { html += '<i class="fa-solid fa-floppy-disk contract-type-update" title="Guardar"></i><i class="fa-solid fa-trash-can contract-type-delete" title="Eliminar"></i>'; }
        html += '</td></tr>';
    });
    $('#contract-types-table-body').html(html);
}

function resetTypeForm() {
    contractState.editingTypeId = null;
    $('#contract-type-name, #contract-type-description').val('');
    $('#contract-type-active').prop('checked', true);
    $('#contract-type-save').html('<i class="fa-solid fa-plus"></i> Agregar');
    $('#contract-type-cancel').addClass('d-none');
}

export function saveType() {
    const data = {name: $('#contract-type-name').val(), description: $('#contract-type-description').val(), active: $('#contract-type-active').is(':checked') ? 1 : 0};
    const url = contractState.editingTypeId ? '/admin/contracts/types/update' : '/admin/contracts/types/add';
    if(contractState.editingTypeId) data.id = contractState.editingTypeId;
    PostMethodFunction(url, data, null, function(){ alertSuccess('Tipo guardado'); resetTypeForm(); getTypes(); loadCatalogs(); }, null);
}

export function editType() {
    const row = $(this).closest('tr');
    contractState.editingTypeId = row.attr('data-type-id');
    $('#contract-type-name').val(row.find('.contract-type-row-name').val());
    $('#contract-type-description').val(row.find('.contract-type-row-description').val());
    $('#contract-type-save').html('<i class="fa-solid fa-floppy-disk"></i> Actualizar');
    $('#contract-type-cancel').removeClass('d-none');
}

export function cancelType() { resetTypeForm(); }

export function deleteType() {
    PostMethodFunction('/admin/contracts/types/delete', {id: $(this).closest('tr').attr('data-type-id')}, null, function(){ alertSuccess('Tipo eliminado'); getTypes(); loadCatalogs(); }, null);
}

export function restoreType() {
    PostMethodFunction('/admin/contracts/types/restore', {id: $(this).closest('tr').attr('data-type-id')}, null, function(){ alertSuccess('Tipo restaurado'); getTypes(); loadCatalogs(); }, null);
}