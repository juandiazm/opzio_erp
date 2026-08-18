import { contractState } from './state.js';
import { collectContractSources, collectContractVariables, contractableTypeKey, formatDate, formatDateTimeInput, getTemplateSourceRequirements, renderContractSources, renderContractVariables, setTemplateOptions } from './shared.js';

function toggleRecurrenceFields(prefix) {
    const enabled = $('#'+prefix+'-contract-recurrence-enabled').is(':checked');
    const fields = $('#'+prefix+'-contract-recurrence-fields');
    fields.prop('hidden', !enabled);
    fields.find('input, select').prop('disabled', !enabled);
    if(enabled && !$('#'+prefix+'-contract-recurrence-next-at').val()){
        const endDate = $('#'+prefix+'-contract-end-date').val();
        if(endDate) $('#'+prefix+'-contract-recurrence-next-at').val(endDate+'T00:00');
    }
}

export function initializeRecurrenceForms() {
    ['create', 'update'].forEach(function(prefix) {
        $(document).on('change.contractRecurrence', '#'+prefix+'-contract-recurrence-enabled', function(){ toggleRecurrenceFields(prefix); });
        toggleRecurrenceFields(prefix);
    });
}

function formData(prefix) {
    const sources = collectContractSources(prefix);
    const primarySource = sources.find(function(source) { return source.type !== 'license'; }) || {};
    const licenseSource = sources.find(function(source) { return source.type === 'license'; }) || {};
    return {
        contract_type_id: $('#'+prefix+'-contract-type').val(),
        contract_template_id: $('#'+prefix+'-contract-template').val(),
        contractable_type: primarySource.type || '',
        contractable_id: primarySource.id || '',
        name: $('#'+prefix+'-contract-name').val(),
        subject: $('#'+prefix+'-contract-subject').val(),
        start_date: $('#'+prefix+'-contract-start-date').val(),
        end_date: $('#'+prefix+'-contract-end-date').val(),
        content: $('#'+prefix+'-contract-content').length ? $('#'+prefix+'-contract-content').val() : '',
        notes: $('#'+prefix+'-contract-notes').val(),
        license_id: licenseSource.id || '',
        recurrence_enabled: $('#'+prefix+'-contract-recurrence-enabled').is(':checked') ? 1 : 0,
        recurrence_frequency: $('#'+prefix+'-contract-recurrence-frequency').val(),
        recurrence_interval: $('#'+prefix+'-contract-recurrence-interval').val(),
        recurrence_next_at: $('#'+prefix+'-contract-recurrence-next-at').val(),
        recurrence_ends_at: $('#'+prefix+'-contract-recurrence-ends-at').val(),
        recurrence_send_automatically: $('#'+prefix+'-contract-recurrence-send').is(':checked') ? 1 : 0,
        sources: sources,
        custom_variables: collectContractVariables(prefix),
    };
}

function validate(data, prefix, generate) {
    let valid = true;
    if(!data.contract_type_id){ $('#'+prefix+'-contract-type').addClass('is-invalid'); alertWarning('Debe seleccionar un tipo de contrato'); valid = false; }
    if(prefix === 'create' && !data.contract_template_id){ $('#'+prefix+'-contract-template').addClass('is-invalid'); alertWarning('Debe seleccionar una plantilla para generar el contrato'); valid = false; }
    if(!data.contractable_type || !data.contractable_id){ $('#'+prefix+'-contract-sources').addClass('is-invalid'); alertWarning('Debe seleccionar al menos una fuente del contrato'); valid = false; }
    if(!data.name){ $('#'+prefix+'-contract-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del contrato'); valid = false; }
    if(!generate && !data.content){ $('#'+prefix+'-contract-content').addClass('is-invalid'); alertWarning('Debe ingresar el contenido del contrato'); valid = false; }
    if(!generate && !data.subject && !data.contract_template_id){ $('#'+prefix+'-contract-subject').addClass('is-invalid'); alertWarning('Debe ingresar el asunto del contrato'); valid = false; }
    if(generate){
        $('#'+prefix+'-contract-variables [data-contract-variable-key][required]').each(function() {
            const input = $(this);
            if(String(input.val() || '').trim() === ''){
                input.addClass('is-invalid');
                alertWarning('Debe completar las variables obligatorias del contrato');
                valid = false;
            }
        });
    }
    getTemplateSourceRequirements(prefix).forEach(function(requirement) {
        if(requirement === 'contractable' && !data.sources.some(function(source){ return source.type !== 'license'; })){
            alertWarning('Debe seleccionar una fuente titular para la plantilla');
            valid = false;
        }
        if(['client', 'employee', 'provider'].includes(requirement) && !data.sources.some(function(source){ return source.type === requirement; })){
            alertWarning('Debe seleccionar el '+(requirement === 'client' ? 'cliente' : (requirement === 'employee' ? 'empleado' : 'proveedor'))+' requerido por la plantilla');
            valid = false;
        }
        if(requirement === 'license' && !data.license_id){
            $('#'+prefix+'-contract-sources').addClass('is-invalid');
            alertWarning('Debe seleccionar la licencia requerida por la plantilla');
            valid = false;
        }
    });
    if(data.start_date && data.end_date && data.end_date < data.start_date){ alertWarning('La fecha final no puede ser anterior a la fecha inicial'); valid = false; }
    if(data.recurrence_enabled){
        if(!data.start_date || !data.end_date){ alertWarning('La recurrencia requiere fechas de inicio y finalización'); valid = false; }
        if(data.recurrence_ends_at && data.recurrence_next_at && data.recurrence_ends_at < data.recurrence_next_at){ alertWarning('El límite de recurrencia no puede ser anterior a la próxima creación'); valid = false; }
    }
    return valid;
}

function showUpdateActions(deleted) {
    $('#update-contract-type, #update-contract-template, #update-contract-name, #update-contract-subject, #update-contract-status, #update-contract-start-date, #update-contract-end-date, #update-contract-content, #update-contract-notes, #update-contract-generate, #update-contract-recurrence-enabled, #update-contract-recurrence-frequency, #update-contract-recurrence-interval, #update-contract-recurrence-next-at, #update-contract-recurrence-ends-at, #update-contract-recurrence-send, #update-contract-variables input, #update-contract-sources select, #update-contract-sources input, #update-contract-sources button, #update-contract-sources-section [data-contract-add-source]').prop('disabled', deleted);
    $('#update-contract-button, #update-contract-generate-button, #update-contract-send-button, #update-contract-delete').toggleClass('d-none', deleted);
    $('#update-contract-restore').toggleClass('d-none', !deleted);
}

export function showCurrentContract() {
    const current = contractState.currentContract;
    if(!current) return;
    $('#update-contract-unique-id').text(current.unique_id || '');
    $('#update-contract-status-label').text(current.status_string || current.status || '').attr('class', 'contract-status-badge status-'+(current.status || 'generated'));
    $('#update-contract-type').val(current.contract_type_id).trigger('change');
    setTemplateOptions('#update-contract-type', '#update-contract-template', current.contract_template_id || '');
    $('#update-contract-template').val(current.contract_template_id || '');
    renderContractVariables('update', current.generation_data && current.generation_data.custom_variables ? current.generation_data.custom_variables : {});
    const currentSources = current.sources && current.sources.length
        ? current.sources
        : [{type: contractableTypeKey(current.contractable_type), id: current.contractable_id}];
    renderContractSources('update', currentSources);
    $('#update-contract-name').val(current.name || '');
    $('#update-contract-subject').val(current.subject || '');
    $('#update-contract-status').val(current.status || 'generated');
    $('#update-contract-start-date').val(formatDate(current.start_date));
    $('#update-contract-end-date').val(formatDate(current.end_date));
    $('#update-contract-sent-at').text(current.sent_at ? String(current.sent_at).replace('T', ' ').slice(0, 16) : '');
    $('#update-contract-send-status').text(current.send_status_string || current.send_status || 'No enviado');
    $('#update-contract-recurrence-enabled').prop('checked', current.recurrence_enabled == 1 || current.recurrence_enabled === true);
    $('#update-contract-recurrence-frequency').val(current.recurrence_frequency || 'monthly');
    $('#update-contract-recurrence-interval').val(current.recurrence_interval || 1);
    $('#update-contract-recurrence-next-at').val(formatDateTimeInput(current.recurrence_next_at));
    $('#update-contract-recurrence-ends-at').val(formatDateTimeInput(current.recurrence_ends_at));
    $('#update-contract-recurrence-send').prop('checked', current.recurrence_send_automatically == 1 || current.recurrence_send_automatically === true);
    toggleRecurrenceFields('update');
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
    if(!validate(data, 'create', true)) return;
    data.generate = 1;
    data.force_generate = 1;
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

function normalizeContractRecipients(value) {
    const recipients = [];
    String(value || '').split(/[\s,;]+/).map(function(email) {
        return email.trim();
    }).filter(Boolean).forEach(function(email) {
        if(!recipients.some(function(recipient) { return recipient.toLowerCase() === email.toLowerCase(); })) {
            recipients.push(email);
        }
    });
    return recipients;
}

export function sendContract(id = null) {
    const contractId = id || (contractState.currentContract || {}).id;
    if(!contractId) return;
    const contractKey = String(contractId);
    if(contractState.sendingContractIds[contractKey]) return;
    contractState.sendingContractIds[contractKey] = true;
    if(String((contractState.currentContract || {}).id || '') === contractKey){
        $('#update-contract-send-button').prop('disabled', true);
    }
    const releaseSendLock = function() {
        delete contractState.sendingContractIds[contractKey];
        if(String((contractState.currentContract || {}).id || '') === contractKey){
            $('#update-contract-send-button').prop('disabled', false);
        }
    };
    const send = function(recipients = null) {
        const data = {id: contractId};
        if(Array.isArray(recipients)) data.recipients = recipients;
        PostMethodFunction('/admin/contracts/send', data, null, function(response) {
            releaseSendLock();
            contractState.currentContract = response.contract;
            contractState.tabsView['nav-list-tab'] = false;
            alertSuccess('Contrato enviado');
            if(contractState.currentTab === 'nav-update-tab') showCurrentContract();
            else $('#contract-search').trigger('change');
        }, releaseSendLock);
    };

    const swal = window.Swal;
    if(!swal){
        send();
        return;
    }

    PostMethodFunction('/admin/contracts/send-options', {id: contractId}, null, function(response) {
        const defaultRecipients = Array.isArray(response.default_recipients) ? response.default_recipients : [];
        const sourceMessage = defaultRecipients.length
            ? 'Se precargaron los destinatarios configurados. Puedes modificarlos antes de enviar.'
            : 'No hay un destinatario configurado. Escribe uno o varios correos para continuar.';
        swal.fire({
            title: 'Confirmar envío',
            text: sourceMessage,
            input: 'textarea',
            inputValue: defaultRecipients.join(', '),
            inputPlaceholder: 'correo@dominio.com, otro@dominio.com',
            inputAttributes: {
                'aria-label': 'Correos destinatarios',
                rows: '4',
            },
            icon: 'warning',
            iconColor: '#220245',
            showConfirmButton: true,
            confirmButtonText: 'Enviar',
            confirmButtonColor: '#220245',
            showCancelButton: true,
            cancelButtonColor: '#C4C4C4',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusConfirm: false,
            width: (window.innerWidth > 768 ? '768px' : '90%'),
            preConfirm: function(value) {
                const recipients = normalizeContractRecipients(value);
                if(!recipients.length){
                    swal.showValidationMessage('Debe indicar al menos un correo destinatario');
                    return false;
                }
                const invalidRecipient = recipients.find(function(email) {
                    return !/^\S+@\S+\.\S+$/.test(email);
                });
                if(invalidRecipient){
                    swal.showValidationMessage('El correo '+invalidRecipient+' no es válido');
                    return false;
                }
                return recipients;
            },
        }).then(function(result) {
            if(result.isConfirmed) send(result.value);
            else releaseSendLock();
        }).catch(releaseSendLock);
    }, releaseSendLock);
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