import { licenseState } from './state.js';

export function addLicense(onCreated){
    let state = $('#create-license-state').attr('value');
    let clientId = $('#create-license-client').val();
    let name = $('#create-license-name').val();
    let serviceId = $('#create-license-service').attr('item-id');
    let employeeId = $('#create-license-employee').val();
    let value = $('#create-license-value').val();
    let description = $('#create-license-description').val();
    let flag = true;
    if(state == null || state == ''){
        $('#create-license-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }
    if(clientId == null || clientId == ''){
        $('#create-license-client').addClass('is-invalid');
        alertWarning('Debe seleccionar un cliente');
        flag = false;
    }else{
        $('#create-license-client').removeClass('is-invalid');
    }
    if(name == null || name == ''){
        $('#create-license-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }else{
        $('#create-license-name').removeClass('is-invalid');
    }
    if(serviceId == null || serviceId == ''){
        $('#create-license-service').addClass('is-invalid');
        alertWarning('Debe seleccionar un servicio');
        flag = false;
    }else{
        $('#create-license-service').removeClass('is-invalid');
    }
    if(value == null || value == ''){
        $('#create-license-value').addClass('is-invalid');
        alertWarning('Debe ingresar el valor del servicio');
        flag = false;
    }else{
        $('#create-license-value').removeClass('is-invalid');
    }
    if(flag){
        $('#add-license-button').prop('disabled', true);
        let dataSend = {
            state: state,
            client_id: clientId,
            name: name,
            service_id: serviceId,
            employee_id: employeeId,
            value: value,
            description: description,
        };
        PostMethodFunction('/admin/licenses/add',dataSend,null, function(response){
            $('#add-license-button').attr('disabled', false);
            $('#create-license-client').val('');
            $('#create-license-name').val('');
            $('#create-license-service').attr('item-id', '');
            $('#create-license-service input').val('');
            $('#create-license-employee').val('');
            $('#create-license-value').val('');
            $('#create-license-description').val('');
            swallMessage(
                'Exito'
                , 'Licencia agregada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            licenseState.tabsView['nav-list-tab'] = false;
            licenseState.currentLicense = response.license;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            onCreated();
        }, function(){$('#add-license-button').attr('disabled', false);});
    }
}