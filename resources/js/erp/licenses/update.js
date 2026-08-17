import { licenseState } from './state.js';
import { getLicenseDocuments } from './documents.js';
import { getServiceNotifications } from './notifications.js';

export function showCurrentLicense(){
    let currentLicense = licenseState.currentLicense;
    $('#update-license-unique-id').text(currentLicense.unique_id);
    $('#update-license-state').attr('value', currentLicense.active);
    $('#update-license-state .toggle-value[value="'+currentLicense.active+'"]').click();
    $('#update-license-client').val(currentLicense.client_id);
    $('#update-license-name').val(currentLicense.name);
    $('#update-license-service').attr('item-id', currentLicense.service_id);
    $('#update-license-service input').val(currentLicense.service.name);
    $('#update-license-employee').val(currentLicense.employee_id);
    $('#update-license-value').val(currentLicense.value);
    $('#update-license-description').val(currentLicense.description);
    $('#update-license-type').val(currentLicense.type);
    $('#update-license-recurrence-months').val(currentLicense.recurrence_months);
    $('#update-license-billing-day').val(currentLicense.billing_day);
    $('#update-license-days-to-expire').val(currentLicense.days_to_expire);
    $('#update-license-last-payed-date').val(currentLicense.last_payed_date);
    $('#update-license-next-billing-date').val(currentLicense.next_billing_date);
    $('#update-license-user-key').text(currentLicense.user_key);
    $('#copy-update-license-user-key').attr('data-clipboard-text', currentLicense.user_key);
    $('#update-license-password-key').text(currentLicense.password_key);
    $('#copy-update-license-password-key').attr('data-clipboard-text', currentLicense.password_key);
    $('#update-license-last-billing-date').text(currentLicense.last_billing_date);
    $('#update-license-last-payed-date').text(currentLicense.last_payed_date);
    $('#update-license-remaining-days').text(currentLicense.remaining_days);
    if(currentLicense.deleted_at == null){
        $('#update-license-delete').addClass('d-block').removeClass('d-none');
        $('#update-license-restore').addClass('d-none').removeClass('d-block');
        $('#update-license-button').addClass('d-block').removeClass('d-none');
        $('#update-license-details-button').addClass('d-block').removeClass('d-none');
        $('#update-license-state').prop('disabled', false);
        $('#update-license-client').prop('disabled', false);
        $('#update-license-name').prop('disabled', false);
        $('#update-license-service').prop('disabled', false);
        $('#update-license-employee').prop('disabled', false);
        $('#update-license-value').prop('disabled', false);
        $('#update-license-type').prop('disabled', false);
        $('#update-license-recurrence-months').prop('disabled', false);
        $('#update-license-billing-day').prop('disabled', false);
        $('#update-license-days-to-expire').prop('disabled', false);
    }else{
        $('#update-license-delete').addClass('d-none').removeClass('d-block');
        $('#update-license-restore').addClass('d-block').removeClass('d-none');
        $('#update-license-button').addClass('d-none').removeClass('d-block');
        $('#update-license-details-button').addClass('d-none').removeClass('d-block');
        $('#update-license-state').prop('disabled', true);
        $('#update-license-client').prop('disabled', true);
        $('#update-license-name').prop('disabled', true);
        $('#update-license-service').prop('disabled', true);
        $('#update-license-employee').prop('disabled', true);
        $('#update-license-value').prop('disabled', true);
        $('#update-license-type').prop('disabled', true);
        $('#update-license-recurrence-months').prop('disabled', true);
        $('#update-license-billing-day').prop('disabled', true);
        $('#update-license-days-to-expire').prop('disabled', true);
    }
    getLicenseDocuments();
    getServiceNotifications();
}

export function updateLicense(){
    let state = $('#update-license-state').attr('value');
    let clientId = $('#update-license-client').val();
    let name = $('#update-license-name').val();
    let serviceId = $('#update-license-service').attr('item-id');
    let employeeId = $('#update-license-employee').val();
    let value = $('#update-license-value').val();
    let description = $('#update-license-description').val();
    let flag = true;
    if(state == null || state == ''){
        $('#update-license-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }else{
        $('#update-license-state').removeClass('is-invalid');
    }
    if(clientId == null || clientId == ''){
        $('#update-license-client').addClass('is-invalid');
        alertWarning('Debe seleccionar un cliente');
        flag = false;
    }else{
        $('#update-license-client').removeClass('is-invalid');
    }
    if(name == null || name == ''){
        $('#update-license-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }else{
        $('#update-license-name').removeClass('is-invalid');
    }
    if(serviceId == null || serviceId == ''){
        $('#update-license-service').addClass('is-invalid');
        alertWarning('Debe seleccionar un servicio');
        flag = false;
    }else{
        $('#update-license-service').removeClass('is-invalid');
    }
    if(value == null || value == ''){
        $('#update-license-value').addClass('is-invalid');
        alertWarning('Debe ingresar el valor del servicio');
        flag = false;
    }else{
        $('#update-license-value').removeClass('is-invalid');
    }
    if(flag){
        $('#update-license-button').prop('disabled', true);
        let dataSend = {
            id: licenseState.currentLicense.id,
            state: state,
            client_id: clientId,
            name: name,
            service_id: serviceId,
            employee_id: employeeId,
            value: value,
            description: description,
        };
        PostMethodFunction('/admin/licenses/update',dataSend,null, function(response){
            $('#update-license-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Licencia actualizada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            licenseState.tabsView['nav-list-tab'] = false;
            licenseState.currentLicense = response.license;
        }, function(){$('#update-license-button').attr('disabled', false);});
    }
}

export function deleteLicense(licenseId, onListRefresh){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar esta licencia?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: licenseId,
            };
            PostMethodFunction('/admin/licenses/delete',dataSend,null, function(response){
                alertSuccess('Licencia eliminada');
                if(licenseState.currentTab == 'nav-list-tab'){
                    onListRefresh();
                }else{
                    licenseState.currentLicense.deleted_at = response.data.deleted_at;
                    showCurrentLicense();
                }
            },null);
        }
        , null
    );
}

export function restoreLicense(){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar esta licencia?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: licenseState.currentLicense.id,
            };
            PostMethodFunction('/admin/licenses/restore',dataSend,null, function(response){
                alertSuccess('Licencia restaurada');
                licenseState.tabsView['nav-list-tab'] = false;
                licenseState.currentLicense.deleted_at = null;
                showCurrentLicense();
            },null);
        }
        , null
    );
}