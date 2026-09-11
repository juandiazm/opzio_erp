import { incomeState } from './state.js';
import { getRichTextHtml, getRichTextPlainText, initRichTextEditors, setRichTextContent } from './rich-text.js';

function escapeHtml(value){
    return String(value ?? '').replace(/[&<>'"]/g, function(character){ return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]; });
}

function richToolbarHtml(){
    return '<div class="income-rich-toolbar" data-rich-toolbar><button type="button" data-rich-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button><button type="button" data-rich-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button><button type="button" data-rich-command="insertUnorderedList" title="Lista" aria-label="Lista"><i class="fa-solid fa-list-ul"></i></button><button type="button" data-rich-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button></div>';
}

export function changeCreateOrderState(){
    incomeState.currentContainer.find('.state-input').removeClass('selected');
    $(this).addClass('selected');
    incomeState.currentContainer.find('.quotation-totalize-container').toggle($(this).attr('value') == '0');
}

export function updateClientReadiness(client = incomeState.currentClient){
    const status = incomeState.currentContainer?.find('[data-client-readiness]');
    if(!status || !status.length) return;
    if(!client){ status.removeClass('is-ready is-pending').empty().hide(); return; }
    const missing = client.siigo_missing_fields || [];
    status.removeClass('is-ready is-pending').addClass(client.siigo_ready ? 'is-ready' : 'is-pending').html(client.siigo_ready ? '<i class="fa-solid fa-circle-check"></i> Cliente listo para Siigo' : '<i class="fa-solid fa-circle-exclamation"></i> Pendiente: '+escapeHtml(missing.join(', '))).show();
}

export function getAllClients(onLoaded = null){
    if(incomeState.clientsList.length == 0) PostMethodFunction('/admin/clients/get-all', {}, null, function(response){ showAllClients(response, onLoaded); }, null);
    else if(onLoaded) onLoaded();
}

function showAllClients(response, onLoaded = null){
    incomeState.clientsList = response.data;
    incomeState.currentClient = null;
    incomeState.createCurrentLicenses = [];
    incomeState.selectedLicense = null;
    incomeState.currentLicencesList = [];
    updateClientReadiness(null);
    const options = [
        {value: '0', label: 'Seleccione un cliente', disabled: true},
        ...incomeState.clientsList.map(function(client){ return {value: client.id, label: client.name}; }),
    ];
    $('.input-client').each(function(){
        if(window.SearchableDropdown){
            window.SearchableDropdown.setOptions(this, options);
            this.selectedIndex = 0;
            window.SearchableDropdown.init(this);
        }else{
            let html = '';
            $.each(options, function(i, option){ html += '<option value="'+option.value+'"'+(option.disabled ? ' selected disabled' : '')+'>'+option.label+'</option>'; });
            $(this).html(html);
        }
    });
    if(onLoaded) onLoaded();
}

export function loadClientData(){
    resetLicenseInputs();
    if(incomeState.currentLicencesList.length > 0) swallMessage('Cambio de cliente', '¿Estás seguro de cambiar de cliente?<br>Si cambias de cliente se perderán los datos de las licencias', 'warning', 'Si, cambiar', 'No, Cancelar', null, function(){ getClientData(); incomeState.currentLicencesList = []; showLicensesItems(); }, null);
    else getClientData();
}

function getClientData(){
    let clientId = incomeState.currentContainer.find('.input-client').val();
    incomeState.currentClient = incomeState.clientsList.find(client => client.id == clientId);
    if(incomeState.currentClient == undefined){ updateClientReadiness(null); alertWarning('El cliente no existe'); return; }
    incomeState.currentContainer.find('.input-identification').text(incomeState.currentClient.identification);
    updateClientReadiness(incomeState.currentClient);
    getClientLicenses();
}

function getClientLicenses(){ PostMethodFunction('/admin/clients/licenses/get-by-client-id', {client_id: incomeState.currentClient.id}, null, showClientLicenses, null); }

function showClientLicenses(response){
    incomeState.createCurrentLicenses = response.licenses;
    const select = incomeState.currentContainer.find('.income-items-table .input-item-license')[0];
    const options = [
        {value: '0', label: 'Seleccione una licencia', disabled: true},
        ...incomeState.createCurrentLicenses.map(function(license){ return {value: license.id, label: license.name}; }),
    ];
    if(select && window.SearchableDropdown){
        window.SearchableDropdown.setOptions(select, options);
        select.selectedIndex = 0;
        window.SearchableDropdown.init(select);
    }else if(select){
        let html = '';
        $.each(options, function(i, option){ html += '<option value="'+option.value+'"'+(option.disabled ? ' selected disabled' : '')+'>'+option.label+'</option>'; });
        $(select).html(html);
    }
}

export function loadLicenseData(){
    let container = incomeState.currentContainer.find('.income-items-table .add-row');
    let licenseId = $(this).val();
    incomeState.selectedLicense = incomeState.createCurrentLicenses.find(license => license.id == licenseId);
    if(incomeState.selectedLicense == null){ alertWarning('La licencia no existe'); return; }
    container.find('.input-item-service').text(incomeState.selectedLicense.service.name);
    container.find('.input-item-recurrence').text((incomeState.selectedLicense.type == 2 || incomeState.selectedLicense.recurrence_months == null) ? '' : incomeState.selectedLicense.recurrence_months+' meses');
    container.find('.input-item-value').val(incomeState.selectedLicense.value);
    container.find('.input-item-employee').text(incomeState.selectedLicense.employee == null ? '' : incomeState.selectedLicense.employee.name+(incomeState.selectedLicense.employee.last_name == null ? '' : ' '+incomeState.selectedLicense.employee.last_name));
    container.find('.input-item-comission').val(incomeState.selectedLicense.comission == null ? '0' : incomeState.selectedLicense.comission).change();
    container.find('.input-item-tax').text((incomeState.selectedLicense.service.tax_id == null ? '0' : incomeState.selectedLicense.service.tax.value*100)+'%');
}

export function getComissionValue(){
    try{ let container = $(this).closest('tr'); let comission = container.find('.input-item-comission').val(); let value = container.find('.input-item-value').val(); container.find('.input-item-total-comission').text('$'+((comission/100)*value).toLocaleString('es-CO')); }
    catch(e){ incomeState.currentContainer.find('.input-item-comission').val('0').change(); }
}

export function addLicenseItem(){
    let flag = true;
    let row = incomeState.currentContainer.find('.income-items-table .add-row');
    let value = row.find('.input-item-value').val();
    let comission = row.find('.input-item-comission').val();
    let descriptionHtml = getRichTextHtml(row, '.input-item-description-editor');
    let description = getRichTextPlainText(descriptionHtml);
    let hours = row.find('.input-item-hours').val();
    if(incomeState.selectedLicense == null){ alertWarning('Debes seleccionar una licencia'); flag = false; }
    if(value == ''){ alertWarning('Debes ingresar un valor'); flag = false; }
    if(hours == '' || hours == null || hours < 0) alertWarning('Debes ingresar las horas invertidas');
    if(flag){
        let taxValue = 0;
        let taxName = '';
        if(incomeState.selectedLicense.service.tax_id != null){ taxValue = incomeState.selectedLicense.service.tax.value; taxName = incomeState.selectedLicense.service.tax.name; }
        incomeState.currentLicencesList.push({license_id: incomeState.selectedLicense.id, license_name: incomeState.selectedLicense.name, service_id: incomeState.selectedLicense.service_id, service_name: incomeState.selectedLicense.service.name, recurrence_months: (incomeState.selectedLicense.type == 2 || incomeState.selectedLicense.recurrence_months == null) ? null : incomeState.selectedLicense.recurrence_months, value: value, employee_id: incomeState.selectedLicense.employee_id, employee_name: incomeState.selectedLicense.employee == null ? '' : incomeState.selectedLicense.employee.name+(incomeState.selectedLicense.employee.last_name == null ? '' : ' '+incomeState.selectedLicense.employee.last_name), tax_id: incomeState.selectedLicense.service.tax_id, tax_value: taxValue, tax_name: taxName, comission: comission, total: value*(1+parseFloat(taxValue)), hours: hours, description: description, description_html: descriptionHtml});
        resetLicenseInputs();
        alertSuccess('Licencia agregada correctamente');
        showLicensesItems();
    }
}

export function resetLicenseInputs(){
    const row = incomeState.currentContainer.find('.income-items-table .add-row');
    row.find('.input-item-license').val('0');
    row.find('.input-item-service, .input-item-recurrence').text('');
    row.find('.input-item-value').val('0').select().focus();
    setRichTextContent(row, '.input-item-description-editor', '');
    row.find('.input-item-comission').val('0');
    row.find('.input-item-total-comission').text('$0');
    row.find('.input-item-hours').val('0');
    row.find('.input-item-tax').text('0%');
    incomeState.selectedLicense = null;
}

export function showLicensesItems(){
    let html = '';
    let total = 0;
    const isUpdate = incomeState.currentTab == 'nav-update-tab';
    const canEdit = !isUpdate || incomeState.currentIncome?.state == 0 || incomeState.currentIncome?.state == '0';
    $.each(incomeState.currentLicencesList, function(index, item){
        item.total = parseFloat(item.total) || 0; item.tax_value = parseFloat(item.tax_value) || 0; item.comission = parseFloat(item.comission) || 0; item.value = parseFloat(item.value) || 0; item.hours = parseFloat(item.hours) || 0;
        const descriptionHtml = item.description_html || escapeHtml(item.description || '');
        const descriptionPlain = escapeHtml(getRichTextPlainText(item.description_html || item.description || ''));
        html += '<tr class="income-item-saved-row" index="'+index+'">';
        html += '<td data-label="Licencia / servicio"><strong>'+escapeHtml(item.license_name)+'</strong><small>'+escapeHtml(item.service_name)+'</small>'+(item.recurrence_months == null ? '' : '<small>'+escapeHtml(item.recurrence_months+' meses')+'</small>')+(item.employee_name ? '<small>Empleado: '+escapeHtml(item.employee_name)+'</small>' : '')+'</td>';
        html += '<td data-label="Valor">'+(canEdit ? '<input type="number" min="0" step="0.01" class="form-control input-item-value" value="'+item.value+'">' : '<span class="income-readonly-value input-item-value">$'+item.value.toLocaleString('es-CO')+'</span>')+'</td>';
        html += '<td data-label="Horas">'+(canEdit ? '<input type="number" min="0" class="form-control input-item-hours" value="'+item.hours+'">' : '<span class="income-readonly-value input-item-hours">'+item.hours+'</span>')+'</td>';
        html += '<td data-label="Comisión">'+(canEdit ? '<input type="number" min="0" step="0.01" class="form-control input-item-comission" value="'+item.comission+'">' : '<span class="income-readonly-value input-item-comission">'+item.comission+'%</span>')+'</td>';
        html += '<td data-label="Impuesto"><span class="income-item-tax input-item-tax">'+(item.tax_value*100)+'%</span></td>';
        html += '<td data-label="Descripción">'+(canEdit ? '<div class="income-rich-text" data-rich-text>'+richToolbarHtml()+'<div class="income-rich-editor input-item-description-editor" contenteditable="true" data-rich-editor>'+descriptionHtml+'</div><textarea class="d-none input-item-description" data-rich-plain tabindex="-1">'+descriptionPlain+'</textarea></div>' : '<div class="income-rich-preview">'+descriptionHtml+'</div>')+'</td>';
        html += '<td class="income-item-actions" data-label="Acciones">'+(canEdit ? '<button type="button" class="btn update-license-button" title="Guardar cambios" aria-label="Guardar cambios"><i class="fa-solid fa-check"></i></button><button type="button" class="btn delete-license-button" title="Eliminar ítem" aria-label="Eliminar ítem"><i class="fa-solid fa-trash-can"></i></button>' : '')+'</td></tr>';
        total += item.total;
    });
    const body = incomeState.currentContainer.find('.income-items-table .income-items-body');
    body.find('.income-item-saved-row').remove();
    body.append(html);
    incomeState.currentContainer.find('.income-items-empty-state').toggle(incomeState.currentLicencesList.length == 0);
    incomeState.currentContainer.find('.input-total-value').each(function(){ $(this).is('strong') ? $(this).text('$'+total.toLocaleString('es-CO')) : $(this).html('<strong>$'+total.toLocaleString('es-CO')+'</strong>'); });
    initRichTextEditors(incomeState.currentContainer[0]);
}

export function deleteLicenseItem(){
    let index = $(this).closest('.income-item-saved-row').attr('index');
    swallMessage('Eliminar ítem', '¿Estás seguro de eliminar este ítem?', 'error', 'Si, eliminar', 'No, Cancelar', null, function(){ incomeState.currentLicencesList.splice(index, 1); alertWarning('Ítem eliminado correctamente'); showLicensesItems(); }, null);
}

export function updateLicenseItem(){
    let flag = true;
    let container = $(this).closest('.income-item-saved-row');
    let index = container.attr('index');
    let value = container.find('.input-item-value').val();
    let descriptionHtml = getRichTextHtml(container, '.input-item-description-editor');
    let description = getRichTextPlainText(descriptionHtml);
    let comission = container.find('.input-item-comission').val();
    let hours = container.find('.input-item-hours').val();
    if(value == ''){ alertWarning('Debes ingresar un valor'); flag = false; }
    if(hours == '' || hours == null || hours < 0){ alertWarning('Debes ingresar las horas invertidas'); flag = false; }
    if(flag){ incomeState.currentLicencesList[index].value = value; incomeState.currentLicencesList[index].comission = comission; incomeState.currentLicencesList[index].description = description; incomeState.currentLicencesList[index].description_html = descriptionHtml; incomeState.currentLicencesList[index].total = value*(1+incomeState.currentLicencesList[index].tax_value); incomeState.currentLicencesList[index].hours = hours; alertSuccess('Ítem actualizado correctamente'); showLicensesItems(); }
}

export function changeTimelyPayment(){
    let timelyPayment = $(this).val();
    let cutoffDate = new Date(timelyPayment);
    cutoffDate.setDate(cutoffDate.getDate()+15);
    incomeState.currentContainer.find('.input-cutoff-date').val(cutoffDate.toISOString().split('T')[0]);
}

export function createIncome(){
    let flag = true;
    let clientId = incomeState.currentContainer.find('.input-client').val();
    let timelyPayment = incomeState.currentContainer.find('.input-timely-payment').val();
    let cutoffDate = incomeState.currentContainer.find('.input-cutoff-date').val();
    let descriptionHtml = getRichTextHtml(incomeState.currentContainer, '.input-description-editor');
    let description = getRichTextPlainText(descriptionHtml);
    let state = incomeState.currentContainer.find('.state-input.selected').attr('value');
    if(clientId == null || clientId == ''){ incomeState.currentContainer.find('.input-client').addClass('is-invalid'); alertWarning('Debes seleccionar un cliente'); flag = false; }else incomeState.currentContainer.find('.input-client').removeClass('is-invalid');
    if(timelyPayment == null || timelyPayment == ''){ incomeState.currentContainer.find('.input-timely-payment').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de pago'); flag = false; }else incomeState.currentContainer.find('.input-timely-payment').removeClass('is-invalid');
    if(cutoffDate == null || cutoffDate == ''){ incomeState.currentContainer.find('.input-cutoff-date').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de corte'); flag = false; }else incomeState.currentContainer.find('.input-cutoff-date').removeClass('is-invalid');
    if(incomeState.currentLicencesList.length == 0){ alertWarning('Debes ingresar al menos una licencia'); flag = false; }
    if(flag){
        $('#create-income-button').attr('disabled', true);
        let dataSend = {state: state, client_id: clientId, client_identification: incomeState.currentClient.identification, client_name: incomeState.currentClient.name+(incomeState.currentClient.last_name == null ? '' : ' '+incomeState.currentClient.last_name), timely_payment: timelyPayment, cutoff_date: cutoffDate, description: description, description_html: descriptionHtml, quotation_totalize: state == '0' ? incomeState.currentContainer.find('.input-quotation-totalize').is(':checked') : true, licenses: incomeState.currentLicencesList};
        PostMethodFunction('/admin/incomes/create', dataSend, null, successCreateIncome, function(){ $('#create-income-button').attr('disabled', false); });
    }
}

function successCreateIncome(response){
    $('#create-income-button').attr('disabled', false);
    alertSuccess('Ingreso creado correctamente');
    incomeState.currentLicencesList = [];
    showLicensesItems();
    incomeState.currentContainer.find('.input-client').val('');
    incomeState.currentContainer.find('.input-identification').text('');
    incomeState.currentContainer.find('.input-timely-payment').val('');
    incomeState.currentContainer.find('.input-cutoff-date').val('');
    setRichTextContent(incomeState.currentContainer, '.input-description-editor', '');
    incomeState.currentContainer.find('.input-quotation-totalize').prop('checked', true);
    incomeState.currentContainer.find('.quotation-totalize-container').show();
    incomeState.currentContainer.find('.state-input').removeClass('selected');
    incomeState.currentContainer.find('.state-input[value="0"]').addClass('selected');
    incomeState.currentIncome = response.data.income;
    incomeState.tabsView['nav-list-tab'] = false;
    incomeState.tabsView['nav-update-tab'] = false;
    $('#nav-update-tab').tab('show');
    $('#nav-update-tab').trigger('click');
}