import { incomeState } from './state.js';

export function changeCreateOrderState(){ incomeState.currentContainer.find('.state-input').removeClass('selected'); $(this).addClass('selected'); }

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
    let html = '';
    $.each(incomeState.clientsList, function(i, client){ html += '<option value="'+client.id+'">'+client.name+'</option>'; });
    $('.input-client').append(html);
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
    if(incomeState.currentClient == undefined){ alertWarning('El cliente no existe'); return; }
    incomeState.currentContainer.find('.input-identification').text(incomeState.currentClient.identification);
    getClientLicenses();
}

function getClientLicenses(){ PostMethodFunction('/admin/clients/licenses/get-by-client-id', {client_id: incomeState.currentClient.id}, null, showClientLicenses, null); }

function showClientLicenses(response){
    incomeState.createCurrentLicenses = response.licenses;
    let html = '<option value="0" selected disabled>Seleccione una licencia</option>';
    $.each(incomeState.createCurrentLicenses, function(i, license){ html += '<option value="'+license.id+'">'+license.name+'</option>'; });
    incomeState.currentContainer.find('.input-item-license').html(html);
}

export function loadLicenseData(){
    let container = incomeState.currentContainer.find('.order-licenses-list-item');
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
    try{ let container = $(this).parent().parent(); let comission = container.find('.input-item-comission').val(); let value = container.find('.input-item-value').val(); container.find('.input-item-total-comission').text('$'+((comission/100)*value).toLocaleString('es-CO')); }
    catch(e){ incomeState.currentContainer.find('.input-item-comission').val('0').change(); }
}

export function addLicenseItem(){
    let flag = true;
    let value = incomeState.currentContainer.find('.input-item-value').val();
    let comission = incomeState.currentContainer.find('.input-item-comission').val();
    let description = incomeState.currentContainer.find('.input-item-description').val();
    let hours = incomeState.currentContainer.find('.input-item-hours').val();
    if(incomeState.selectedLicense == null){ alertWarning('Debes seleccionar una licencia'); flag = false; }
    if(value == ''){ alertWarning('Debes ingresar un valor'); flag = false; }
    if(hours == '' || hours == null || hours < 0) alertWarning('Debes ingresar las horas invertidas');
    if(flag){
        let taxValue = 0;
        let taxName = '';
        if(incomeState.selectedLicense.service.tax_id != null){ taxValue = incomeState.selectedLicense.service.tax.value; taxName = incomeState.selectedLicense.service.tax.name; }
        incomeState.currentLicencesList.push({license_id: incomeState.selectedLicense.id, license_name: incomeState.selectedLicense.name, service_id: incomeState.selectedLicense.service_id, service_name: incomeState.selectedLicense.service.name, recurrence_months: (incomeState.selectedLicense.type == 2 || incomeState.selectedLicense.recurrence_months == null) ? null : incomeState.selectedLicense.recurrence_months, value: value, employee_id: incomeState.selectedLicense.employee_id, employee_name: incomeState.selectedLicense.employee == null ? '' : incomeState.selectedLicense.employee.name+(incomeState.selectedLicense.employee.last_name == null ? '' : ' '+incomeState.selectedLicense.employee.last_name), tax_id: incomeState.selectedLicense.service.tax_id, tax_value: taxValue, tax_name: taxName, comission: comission, total: value*(1+parseFloat(taxValue)), hours: hours, description: description});
        resetLicenseInputs();
        alertSuccess('Licencia agregada correctamente');
        showLicensesItems();
    }
}

export function resetLicenseInputs(){
    incomeState.currentContainer.find('.add-row .input-item-value').val('0').select().focus();
    incomeState.currentContainer.find('.add-row .input-item-description').val('');
    incomeState.currentContainer.find('.add-row .input-item-employee').text('');
    incomeState.currentContainer.find('.add-row .input-item-comission').val('0');
    incomeState.currentContainer.find('.add-row .input-item-total-comission').text('0');
    incomeState.currentContainer.find('.add-row .input-item-hours').val('0');
}

export function showLicensesItems(){
    let html = '';
    let total = 0;
    $.each(incomeState.currentLicencesList, function(index, item){
        item.total = parseFloat(item.total); item.tax_value = parseFloat(item.tax_value); item.comission = parseFloat(item.comission); item.value = parseFloat(item.value);
        html += '<li class="update-income-licenses-list-item order-licenses-list-item row" index="'+index+'"><div class="col-12 col-md-6 d-flex flex-column justify-content-center">';
        html += '<div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-license">Licencia</span><p class="form-control input-value input-item-license">'+item.license_name+'</p></div><div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-service">Servicio</span><p class="form-control input-value input-item-service">'+item.service_name+'</p></div><div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-recurrence">Recurrencia</span><p class="form-control input-value input-item-recurrence">'+(item.recurrence_months == null ? '' : item.recurrence_months)+'</p></div>';
        html += '<div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-value">Valor</span>'+(incomeState.currentTab != 'nav-update-tab' || incomeState.currentIncome.state == 0 ? '<input type="number" class="form-control input-value input-item-value" name="input-item-value" value="'+item.value+'">' : '<p class="form-control input-value input-item-value" name="input-item-value">'+item.value+'</p>')+'</div>';
        html += '<div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-hours">Horas</span><input type="number" class="form-control input-value input-item-hours" name="input-item-hours" value="'+item.hours+'"></div><div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-employee">Empleado</span><p class="form-control input-value input-item-employee">'+(item.employee_name == null ? '' : item.employee_name)+'</p></div>';
        html += '<div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-comission">Comisión</span>'+(incomeState.currentTab != 'nav-update-tab' || incomeState.currentIncome.state == 0 ? '<input type="number" class="form-control input-value input-item-comission" name="input-item-comission" value="'+item.comission+'">' : '<p class="form-control input-value input-item-comission" name="input-item-comission">'+item.comission+'</p>')+'</div><div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-total-comission">Total Comisión</span><p class="input-value input-item-total-comission">$'+((item.comission/100)*item.value).toLocaleString('es-CO')+'</p></div><div class="input-container d-flex justify-content-start"><span class="input-title align-self-center" for="input-item-tax">Impuesto</span><p class="input-value align-self-center input-item-tax" name="item-tax">'+item.tax_value*100+'%</p></div></div>';
        html += '<div class="col-12 col-md-6 d-flex flex-column justify-content-center"><div class="input-container d-flex flex-column justify-content-center description-container"><span class="input-title align-self-start" for="input-license-description">Descripción</span>'+(incomeState.currentTab != 'nav-update-tab' || incomeState.currentIncome.state == 0 ? '<textarea class="form-control input-value input-item-description" name="description">'+(item.description == null ? '' : item.description)+'</textarea>' : '<p class="form-control input-value input-item-description" name="description">'+(item.description == null ? '' : item.description)+'</p>')+'</div></div>';
        if(incomeState.currentTab != 'nav-update-tab' || incomeState.currentIncome.state == 0) html += '<div class="d-flex justify-content-end align-items-center"><i class="fas fa-pen-to-square update-license-button"></i><i class="fas fa-trash-can delete-license-button"></i></div>';
        html += '</li>';
        total += item.total;
    });
    incomeState.currentContainer.find('.update-income-licenses-list-item').remove();
    incomeState.currentContainer.find('.income-licenses-list').append(html);
    incomeState.currentContainer.find('.input-total-value').html('<strong>$'+total.toLocaleString('es-CO')+'</strong>');
}

export function deleteLicenseItem(){
    let index = $(this).parent().parent().attr('index');
    swallMessage('Eliminar licencia', '¿Estás seguro de eliminar esta licencia?', 'error', 'Si, eliminar', 'No, Cancelar', null, function(){ incomeState.currentLicencesList.splice(index, 1); alertWarning('Licencia eliminada correctamente'); showLicensesItems(); }, null);
}

export function updateLicenseItem(){
    let flag = true;
    let container = $(this).closest('.update-income-licenses-list-item');
    let index = container.attr('index');
    let value = container.find('.input-item-value').val();
    let description = container.find('.input-item-description').val();
    let comission = container.find('.input-item-comission').val();
    let hours = container.find('.input-item-hours').val();
    if(value == ''){ alertWarning('Debes ingresar un valor'); flag = false; }
    if(hours == '' || hours == null || hours < 0){ alertWarning('Debes ingresar las horas invertidas'); flag = false; }
    if(flag){ incomeState.currentLicencesList[index].value = value; incomeState.currentLicencesList[index].comission = comission; incomeState.currentLicencesList[index].description = description; incomeState.currentLicencesList[index].total = value*(1+incomeState.currentLicencesList[index].tax_value); incomeState.currentLicencesList[index].hours = hours; alertSuccess('Licencia actualizada correctamente'); showLicensesItems(); }
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
    let description = incomeState.currentContainer.find('.input-description').val();
    let state = incomeState.currentContainer.find('.state-input.selected').attr('value');
    if(clientId == null || clientId == ''){ incomeState.currentContainer.find('.input-client').addClass('is-invalid'); alertWarning('Debes seleccionar un cliente'); flag = false; }else incomeState.currentContainer.find('.input-client').removeClass('is-invalid');
    if(timelyPayment == null || timelyPayment == ''){ incomeState.currentContainer.find('.input-timely-payment').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de pago'); flag = false; }else incomeState.currentContainer.find('.input-timely-payment').removeClass('is-invalid');
    if(cutoffDate == null || cutoffDate == ''){ incomeState.currentContainer.find('.input-cutoff-date').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de corte'); flag = false; }else incomeState.currentContainer.find('.input-cutoff-date').removeClass('is-invalid');
    if(incomeState.currentLicencesList.length == 0){ alertWarning('Debes ingresar al menos una licencia'); flag = false; }
    if(flag){
        $('#create-income-button').attr('disabled', true);
        let dataSend = {state: state, client_id: clientId, client_identification: incomeState.currentClient.identification, client_name: incomeState.currentClient.name+(incomeState.currentClient.last_name == null ? '' : ' '+incomeState.currentClient.last_name), timely_payment: timelyPayment, cutoff_date: cutoffDate, description: description, licenses: incomeState.currentLicencesList};
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
    incomeState.currentContainer.find('.input-description').val('');
    incomeState.currentContainer.find('.state-input').removeClass('selected');
    incomeState.currentContainer.find('.state-input[value="0"]').addClass('selected');
    incomeState.currentIncome = response.data.income;
    incomeState.tabsView['nav-list-tab'] = false;
    incomeState.tabsView['nav-update-tab'] = false;
    $('#nav-update-tab').tab('show');
    $('#nav-update-tab').trigger('click');
}