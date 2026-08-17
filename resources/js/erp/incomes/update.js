import { incomeState } from './state.js';
import { showLicensesItems } from './create.js';
import { getIncomesPage } from './list.js';
import { showIncomeOrder } from './order.js';

export function showCurrentIncome(){
    incomeState.currentLicencesList = [];
    incomeState.currentContainer.find('.state-input').removeClass('selected');
    incomeState.currentContainer.find('.state-input[value="'+incomeState.currentIncome.state+'"]').addClass('selected');
    incomeState.currentContainer.find('.input-client').val(incomeState.currentIncome.client_id).change();
    incomeState.currentContainer.find('.input-identification').text(incomeState.currentIncome.client_identification);
    incomeState.currentContainer.find('.input-timely-payment').val(incomeState.currentIncome.timely_payment);
    incomeState.currentContainer.find('.input-cutoff-date').val(incomeState.currentIncome.cutoff_date);
    incomeState.currentContainer.find('.input-description').val(incomeState.currentIncome.description);
    incomeState.currentContainer.find('.input-bill-name').val(incomeState.currentIncome.bill_name);
    incomeState.currentContainer.find('.input-bill-final-value').val(incomeState.currentIncome.bill_final_value);
    if(incomeState.currentIncome.state == 0 || incomeState.currentIncome.state == 1 || incomeState.currentIncome.state == 2){
        incomeState.currentContainer.find('#update-income-button').css('display', 'block');
        incomeState.currentContainer.find('.input-client').attr('disabled', false);
        incomeState.currentContainer.find('.input-timely-payment').attr('disabled', false);
        incomeState.currentContainer.find('.input-cutoff-date').attr('disabled', false);
        incomeState.currentContainer.find('.input-description').attr('disabled', false);
        incomeState.currentContainer.find('.input-bill-name').attr('disabled', false);
        incomeState.currentContainer.find('.input-bill-final-value').attr('disabled', false);
        incomeState.currentContainer.find('.order-licenses-list-item-update').css('display', 'flex');
    }else{
        incomeState.currentContainer.find('#update-income-button').css('display', 'none');
        incomeState.currentContainer.find('.input-client').attr('disabled', true);
        incomeState.currentContainer.find('.input-timely-payment').attr('disabled', true);
        incomeState.currentContainer.find('.input-cutoff-date').attr('disabled', true);
        incomeState.currentContainer.find('.input-description').attr('disabled', true);
        incomeState.currentContainer.find('.input-bill-name').attr('disabled', true);
        incomeState.currentContainer.find('.input-bill-final-value').attr('disabled', true);
        incomeState.currentContainer.find('.order-licenses-list-item-update').css('display', 'none');
    }
    getIncomeLicenses();
}

function getIncomeLicenses(){ PostMethodFunction('/admin/incomes/get-licenses', {income_id: incomeState.currentIncome.id}, null, showIncomeLicenses, null); }
function showIncomeLicenses(response){ incomeState.currentLicencesList = response.data; showLicensesItems(); }

export function updateIncome(){
    let flag = true;
    let clientId = incomeState.currentContainer.find('.input-client').val();
    let timelyPayment = incomeState.currentContainer.find('.input-timely-payment').val();
    let cutoffDate = incomeState.currentContainer.find('.input-cutoff-date').val();
    let description = incomeState.currentContainer.find('.input-description').val();
    let state = incomeState.currentContainer.find('.state-input.selected').attr('value');
    let billName = incomeState.currentContainer.find('.input-bill-name').val();
    let billFinalValue = incomeState.currentContainer.find('.input-bill-final-value').val();
    if(clientId == null || clientId == ''){ incomeState.currentContainer.find('.input-client').addClass('is-invalid'); alertWarning('Debes seleccionar un cliente'); flag = false; }else incomeState.currentContainer.find('.input-client').removeClass('is-invalid');
    if(timelyPayment == null || timelyPayment == ''){ incomeState.currentContainer.find('.input-timely-payment').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de pago'); flag = false; }else incomeState.currentContainer.find('.input-timely-payment').removeClass('is-invalid');
    if(cutoffDate == null || cutoffDate == ''){ incomeState.currentContainer.find('.input-cutoff-date').addClass('is-invalid'); alertWarning('Debes ingresar una fecha de corte'); flag = false; }else incomeState.currentContainer.find('.input-cutoff-date').removeClass('is-invalid');
    if(incomeState.currentLicencesList.length == 0){ alertWarning('Debes ingresar al menos una licencia'); flag = false; }
    if(state == 3){ if(billName == '' || billName == null){ alertWarning('Debes ingresar el nombre de la factura'); flag = false; } if(billFinalValue == '' || billFinalValue == null){ alertWarning('Debes ingresar el valor de la factura'); flag = false; } }
    if(flag){
        $('#update-income-button').attr('disabled', true);
        $('.state-input-container').addClass('d-none');
        let dataSend = {id: incomeState.currentIncome.id, state: state, client_id: clientId, client_identification: incomeState.currentClient.identification, client_name: incomeState.currentClient.name+(incomeState.currentClient.last_name == null ? '' : ' '+incomeState.currentClient.last_name), timely_payment: timelyPayment, cutoff_date: cutoffDate, description: description, bill_name: billName, bill_final_value: billFinalValue, licenses: incomeState.currentLicencesList};
        PostMethodFunction('/admin/incomes/update', dataSend, null, successUpdateIncome, function(){ $('#update-income-button').attr('disabled', false); $('.state-input-container').removeClass('d-none'); });
    }
}

function successUpdateIncome(response){
    $('#update-income-button').attr('disabled', false);
    $('.state-input-container').removeClass('d-none');
    alertSuccess('Ingreso actualizado correctamente');
    incomeState.currentIncome = response.data;
    incomeState.currentContainer.find('.state-input').removeClass('selected');
    incomeState.currentContainer.find('.state-input[value="'+incomeState.currentIncome.state+'"]').addClass('selected');
    showIncomeOrder();
    incomeState.tabsView['nav-list-tab'] = false;
    getIncomesPage();
}

export function changePayState(){
    let flag = true;
    let billName = incomeState.currentContainer.find('.input-bill-name').val();
    let billFinalValue = incomeState.currentContainer.find('.input-bill-final-value').val();
    if(incomeState.currentIncome.state != 3){
        if(billName == '' || billName == null){ alertWarning('Debes ingresar el nombre de la factura'); incomeState.currentContainer.find('.input-bill-name').addClass('is-invalid'); flag = false; }else incomeState.currentContainer.find('.input-bill-name').removeClass('is-invalid');
        if(billFinalValue == '' || billFinalValue == null){ alertWarning('Debes ingresar el valor de la factura'); incomeState.currentContainer.find('.input-bill-final-value').addClass('is-invalid'); flag = false; }else incomeState.currentContainer.find('.input-bill-final-value').removeClass('is-invalid');
    }else flag = false;
    if(flag){
        Swal.fire({title: '<span style="color:#484848 !important;">Pago</span>', html: '¿Está a punto de cambiar el estado de pago de este ingreso a pagado?<br><br><div class="form-check d-flex justify-content-center align-items-center gap-2"><input class="form-check-input mt-0" type="checkbox" id="swal-notify-client"><label class="form-check-label" for="swal-notify-client">Enviar correo de agradecimiento</label></div>', icon: 'success', iconColor: '#220245', showConfirmButton: true, confirmButtonText: 'Si, cambiar', confirmButtonColor: '#220245', showCancelButton: true, cancelButtonColor: '#C4C4C4', cancelButtonText: 'No, Cancelar', reverseButtons: true, width: (window.innerWidth > 768 ? '768px' : '90%'), preConfirm: () => ({notify: document.getElementById('swal-notify-client').checked})}).then((result) => {
            if(result.isConfirmed) PostMethodFunction('/admin/incomes/change-state-to-pay', {income_id: incomeState.currentIncome.id, bill_name: billName, bill_final_value: billFinalValue, notify_client: result.value.notify}, null, function(){ alertSuccess('Estado de pago cambiado correctamente'); incomeState.currentIncome.state = 3; incomeState.currentIncome.bill_name = billName; incomeState.currentIncome.bill_final_value = billFinalValue; showCurrentIncome(); incomeState.tabsView['nav-list-tab'] = false; }, null);
        });
    }else{
        setTimeout(() => { $('#update-income-container').find('.state-input').removeClass('selected'); $('#update-income-container').find('.state-input[value="'+incomeState.currentIncome.state+'"]').click(); }, 500);
    }
}

export function changeInputState(){
    let state = $(this).attr('value');
    swallMessage('Advertencia', '¿Estás seguro de cambiar el estado de este ingreso?', 'warning', 'Si, cambiar', 'No, Cancelar', null, function(){
        PostMethodFunction('/admin/incomes/change-state', {income_id: incomeState.currentIncome.id, state: state}, null, function(){ alertSuccess('Estado cambiado correctamente'); incomeState.currentIncome.state = state; showCurrentIncome(); incomeState.tabsView['nav-list-tab'] = false; getIncomesPage(); }, null);
    }, null);
}