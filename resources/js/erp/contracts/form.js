import { contractState } from './state.js';
import { contractableTypeKey, formatDate, setContractableOptions, setTemplateOptions } from './shared.js';

function formData(prefix) {
    const create = prefix === 'create';
    return {
        contract_type_id: $('#'+prefix+'-contract-type').val(),
        contract_template_id: $('#'+prefix+'-contract-template').val(),
        contractable_type: $('#'+(create ? 'create' : 'update')+'-contractable-type').val(),
        contractable_id: $('#'+(create ? 'create' : 'update')+'-contractable-id').val(),
        name: $('#'+prefix+'-contract-name').val(),
        subject: $('#'+prefix+'-contract-subject').val(),
        start_date: $('#'+prefix+'-contract-start-date').val(),
        end_date: $('#'+prefix+'-contract-end-date').val(),
        content: $('#'+prefix+'-contract-content').val(),
        notes: $('#'+prefix+'-contract-notes').val(),
    };
}

function validate(data, prefix, generate) {
    let valid = true;
    if(!data.contract_type_id){ $('#'+prefix+'-contract-type').addClass('is-invalid'); alertWarning('Debe seleccionar un tipo de contrato'); valid = false; }
    if(!data.contractable_type || !data.contractable_id){ $('#'+(prefix === 'create' ? 'create' : 'update')+'-contractable-id').addClass('is-invalid'); alertWarning('Debe seleccionar el titular del contrato'); valid = false; }
    if(!data.name){ $('#'+prefix+'-contract-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del contrato'); valid = false; }
    if(!generate && !data.content){ $('#'+prefix+'-contract-content').addClass('is-invalid'); alertWarning('Debe ingresar el contenido del contrato'); valid = false; }
    if(!generate && !data.subject && !data.contract_template_id){ $('#'+prefix+'-contract-subject').addClass('is-invalid'); alertWarning('Debe ingresar el asunto del contrato'); valid = false; }
    if(data.start_date && data.end_date && data.end_date < data.start_date){ alertWarning('La fecha final no puede ser anterior a la fecha inicial'); valid = false; }
    return valid;
}

function showUpdateActions(deleted) {
    $('#update-contract-type, #update-contract-template, #update-contractable-type, #update-contractable-id, #update-contract-name, #update-contract-subject, #update-contract-status, #update-contract-start-date, #update-contract-end-date, #update-contract-content, #update-contract-notes, #update-contract-generate').prop('disabled', deleted);
    $('#update-contract-button, #update-contract-generate-button, #update-contract-send-button, #update-contract-delete').toggleClass('d-none', deleted);
    $('#update-contract-restore').toggleClass('d-none', !deleted);
}

export function showCurrentContract() {
    const current = contractState.currentContract;
    if(!current) return;
    $('#update-contract-unique-id').text(current.unique_id || '');
    $('#update-contract-status-label').text(current.status_string || current.status || '').attr('class', 'contract-status-badge status-'+(current.status || 'draft'));
    $('#update-contract-type').val(current.contract_type_id).trigger('change');
    setTemplateOptions('#update-contract-type', '#update-contract-template', current.contract_template_id || '');
    $('#update-contract-template').val(current.contract_template_id || '');
    const type = contractableTypeKey(current.contractable_type);
    $('#update-contractable-type').val(type).trigger('change');
    setContractableOptions('#update-contractable-type', '#update-contractable-id', current.contractable_id);
    $('#update-contractable-id').val(current.contractable_id);
    $('#update-contract-name').val(current.name || '');
    $('#update-contract-subject').val(current.subject || '');
    $('#update-contract-status').val(current.status || 'draft');
    $('#update-contract-start-date').val(formatDate(current.start_date));
    $('#update-contract-end-date').val(formatDate(current.end_date));
    $('#update-contract-sent-at').text(current.sent_at ? String(current.sent_at).replace('T', ' ').slice(0, 16) : '');
    $('#update-contract-content').val(current.content || '');
    $('#update-contract-notes').val(current.notes || '');
    $('#update-contract-generate').prop('checked', false);
    showUpdateActions(current.deleted_at != null);
}

function openCreatedContract(response) {
    contractState.currentContract = response.contract;
    contractState.tabsView['nav-list-tab'] = false;
    $('#nav-update-tab').removeClass('d-none').tab('show').trigger('click');
    showCurrentContract();
}

export function addContract() {
    const data = formData('create');
    const generate = $('#create-contract-generate').is(':checked');
    if(!validate(data, 'create', generate)) return;
    data.generate = generate ? 1 : 0;
    $('#add-contract-button').prop('disabled', true);
    PostMethodFunction('/admin/contracts/add', data, null, function(response) {
        $('#add-contract-button').prop('disabled', false);
        alertSuccess('Contrato creado');
        openCreatedContract(response);
    }, function(){ $('#add-contract-button').prop('disabled', false); });
}

export function updateContract() {
    if(!contractState.currentContract) return;
    const data = formData('update');
    const generate = $('#update-contract-generate').is(':checked');
    if(!validate(data, 'update', generate)) return;
    data.id = contractState.currentContract.id;
    data.status = $('#update-contract-status').val();
    data.generate = generate ? 1 : 0;
    $('#update-contract-button').prop('disabled', true);
    PostMethodFunction('/admin/contracts/update', data, null, function(response) {
        $('#update-contract-button').prop('disabled', false);
        contractState.currentContract = response.contract;
        contractState.tabsView['nav-list-tab'] = false;
        alertSuccess('Contrato actualizado');
        showCurrentContract();
    }, function(){ $('#update-contract-button').prop('disabled', false); });
}

export function generateContract() {
    if(!contractState.currentContract) return;
    PostMethodFunction('/admin/contracts/generate', {id: contractState.currentContract.id}, null, function(response) {
        contractState.currentContract = response.contract;
        contractState.tabsView['nav-list-tab'] = false;
        alertSuccess('Contrato generado');
        showCurrentContract();
    }, null);
}

export function sendContract(id = null) {
    const contractId = id || (contractState.currentContract || {}).id;
    if(!contractId) return;
    const send = function() {
        PostMethodFunction('/admin/contracts/send', {id: contractId}, null, function(response) {
            contractState.currentContract = response.contract;
            contractState.tabsView['nav-list-tab'] = false;
            alertSuccess('Contrato enviado');
            if(contractState.currentTab === 'nav-update-tab') showCurrentContract();
            else $('#contract-search').trigger('change');
        }, null);
    };
    if(typeof swallMessage === 'function'){
        swallMessage('Confirmar envío', '¿Desea enviar este contrato al titular?', 'warning', 'Enviar', 'Cancelar', null, send, null);
    }else{
        send();
    }
}

export function deleteContract(id = null, onListRefresh = null) {
    const contractId = id || (contractState.currentContract || {}).id;
    if(!contractId) return;
    const remove = function() {
        PostMethodFunction('/admin/contracts/delete', {id: contractId}, null, function(response) {
            alertSuccess('Contrato eliminado');
            if(contractState.currentTab === 'nav-update-tab'){
                contractState.currentContract.deleted_at = response.contract.deleted_at || 'deleted';
                showCurrentContract();
            }else if(onListRefresh){
                onListRefresh();
            }
        }, null);
    };
    if(typeof swallMessage === 'function'){
        swallMessage('Advertencia', '¿Está seguro de eliminar este contrato?', 'error', 'Si, eliminar', 'No', null, remove, null);
    }else{
        remove();
    }
}

export function restoreContract() {
    if(!contractState.currentContract) return;
    PostMethodFunction('/admin/contracts/restore', {id: contractState.currentContract.id}, null, function(response) {
        contractState.currentContract.deleted_at = null;
        contractState.tabsView['nav-list-tab'] = false;
        alertSuccess('Contrato restaurado');
        showCurrentContract();
    }, null);
}