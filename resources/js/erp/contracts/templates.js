import { contractState } from './state.js';
import { escapeHtml, loadCatalogs } from './shared.js';

export function getTemplates() {
    PostMethodFunction('/admin/contracts/templates/get', {}, null, renderTemplates, null);
}

function renderTemplates(response) {
    let html = '';
    (response.templates || []).forEach(function(template) {
        const deleted = template.deleted_at != null;
        html += '<tr class="contract-catalog-row'+(deleted ? ' deleted' : '')+'" data-template-id="'+template.id+'">';
        html += '<td>'+escapeHtml(template.name)+'</td><td>'+escapeHtml(template.type ? template.type.name : '')+'</td><td>'+template.version+'</td><td>'+(template.active ? 'Activa' : 'Inactiva')+'</td><td class="action-cell">';
        if(deleted){ html += '<i class="fa-solid fa-rotate-left contract-template-restore" title="Restaurar"></i>'; }
        else { html += '<i class="fa-solid fa-pen-to-square contract-template-edit" title="Editar"></i><i class="fa-solid fa-trash-can contract-template-delete" title="Eliminar"></i>'; }
        html += '</td></tr>';
    });
    $('#contract-templates-table-body').html(html);
}

function resetTemplateForm() {
    contractState.editingTemplateId = null;
    $('#contract-template-type').val('');
    $('#contract-template-name, #contract-template-subject, #contract-template-content').val('');
    $('#contract-template-active').prop('checked', true);
    $('#contract-template-form-title').text('Nueva plantilla');
    $('#contract-template-save').html('<i class="fa-solid fa-plus"></i> Agregar');
    $('#contract-template-cancel').addClass('d-none');
}

export function saveTemplate() {
    const data = {
        contract_type_id: $('#contract-template-type').val(),
        name: $('#contract-template-name').val(),
        subject: $('#contract-template-subject').val(),
        content: $('#contract-template-content').val(),
        active: $('#contract-template-active').is(':checked') ? 1 : 0,
    };
    const url = contractState.editingTemplateId ? '/admin/contracts/templates/update' : '/admin/contracts/templates/add';
    if(contractState.editingTemplateId) data.id = contractState.editingTemplateId;
    PostMethodFunction(url, data, null, function(){ alertSuccess('Plantilla guardada'); resetTemplateForm(); getTemplates(); loadCatalogs(); }, null);
}

export function editTemplate() {
    const id = $(this).closest('tr').attr('data-template-id');
    PostMethodFunction('/admin/contracts/templates/get', {}, null, function(response){
        const template = (response.templates || []).find(item => String(item.id) === String(id));
        fillTemplateForm(template);
    }, null);
}

function fillTemplateForm(template) {
    if(!template) return;
    contractState.editingTemplateId = template.id;
    $('#contract-template-type').val(template.contract_type_id);
    $('#contract-template-name').val(template.name);
    $('#contract-template-subject').val(template.subject);
    $('#contract-template-content').val(template.content);
    $('#contract-template-active').prop('checked', template.active == 1 || template.active === true);
    $('#contract-template-form-title').text('Actualizar plantilla');
    $('#contract-template-save').html('<i class="fa-solid fa-floppy-disk"></i> Actualizar');
    $('#contract-template-cancel').removeClass('d-none');
}

export function cancelTemplate() { resetTemplateForm(); }

export function deleteTemplate() {
    PostMethodFunction('/admin/contracts/templates/delete', {id: $(this).closest('tr').attr('data-template-id')}, null, function(){ alertSuccess('Plantilla eliminada'); getTemplates(); loadCatalogs(); }, null);
}

export function restoreTemplate() {
    PostMethodFunction('/admin/contracts/templates/restore', {id: $(this).closest('tr').attr('data-template-id')}, null, function(){ alertSuccess('Plantilla restaurada'); getTemplates(); loadCatalogs(); }, null);
}