import { employeeState } from './state.js';

export function showCurrentEmployee(){
    let currentEmployee = employeeState.currentEmployee;
    $('#update-employee-img-container').css('background-image','url("/images/erp/employees/'+currentEmployee.photo+'")');
    $('#update-employee-img-container .image-icon').css('display','none');
    $('#update-employee-uid').text(currentEmployee.uid);
    $('#update-employee-name').val(currentEmployee.name);
    $('#update-employee-last-name').val(currentEmployee.last_name);
    $('#update-employee-id-type').val(currentEmployee.id_type).trigger('change');
    $('#update-employee-identification').val(currentEmployee.identification);
    $('#update-employee-state').attr('value', currentEmployee.state);
    $('#update-employee-state .toggle-value[value="'+currentEmployee.state+'"]').click();
    if(currentEmployee.country == null){
        $('#update-employee-country').attr('item-id', '');
        $('#update-employee-country input').val('');
    }else{
        $('#update-employee-country').attr('item-id', currentEmployee.country.id);
        $('#update-employee-country input').val(currentEmployee.country.name);
    }
    $('#update-employee-phone').val(currentEmployee.phone);
    $('#update-employee-personal-email').val(currentEmployee.personal_email);
    $('#update-employee-work-email').val(currentEmployee.work_email);
    $('#hiring-employee-entry-date').val(currentEmployee.entry_date);
    $('#hiring-employee-payment-type').val(currentEmployee.payment_type).trigger('change');
    $('#hiring-employee-bank').val(currentEmployee.bank);
    $('#hiring-employee-account-number').val(currentEmployee.account_number);
    $('#hiring-employee-account-type').val(currentEmployee.account_type).trigger('change');
    $('#hiring-employee-salary').val(currentEmployee.salary);
    $('#hiring-employee-contract').val(currentEmployee.contract);
    $('#hiring-employee-department').val(currentEmployee.department_id).trigger('change');
    $('#hiring-employee-charge').val(currentEmployee.charge);
    $('#hiring-employee-eps').attr('item-id', currentEmployee.eps_id);
    $('#hiring-employee-eps input').val(currentEmployee.eps==null?'':currentEmployee.eps.name);
    $('#hiring-employee-afp').attr('item-id', currentEmployee.afp_id);
    $('#hiring-employee-afp input').val(currentEmployee.afp==null?'':currentEmployee.afp.name);
    $('#hiring-employee-arl').attr('item-id', currentEmployee.arl_id);
    $('#hiring-employee-arl input').val(currentEmployee.arl==null?'':currentEmployee.arl.name);
    $('#hiring-employee-retirement-date').val(currentEmployee.retirement_date);
    setEmployeeDisabledState(currentEmployee.deleted_at != null);
}

function setEmployeeDisabledState(isDeleted){
    if(isDeleted){
        $('#update-employee-button').addClass('d-none').removeClass('d-block');
        $('#update-employee-hiring-button').addClass('d-none').removeClass('d-block');
        $('#add-employee-documens-button').addClass('d-none').removeClass('d-block');
        $('#update-employee-documents').addClass('d-none').removeClass('d-block');
        $('#update-employee-delete').addClass('d-none').removeClass('d-block');
        $('#update-employee-restore').addClass('d-block').removeClass('d-none');
    }else{
        $('#update-employee-button').removeClass('d-none').addClass('d-block');
        $('#update-employee-hiring-button').removeClass('d-none').addClass('d-block');
        $('#add-employee-documens-button').removeClass('d-none').addClass('d-block');
        $('#update-employee-documents').removeClass('d-none').addClass('d-block');
        $('#update-employee-delete').removeClass('d-none').addClass('d-block');
        $('#update-employee-restore').removeClass('d-block').addClass('d-none');
    }
    const disabled = isDeleted;
    $('#update-employee-name, #update-employee-last-name, #update-employee-id-type, #update-employee-identification, #update-employee-country, #update-employee-phone, #update-employee-personal-email, #update-employee-work-email, #update-employee-state, #hiring-employee-entry-date, #hiring-employee-payment-type, #hiring-employee-bank, #hiring-employee-account-number, #hiring-employee-account-type, #hiring-employee-salary, #hiring-employee-contract, #hiring-employee-department, #hiring-employee-charge, #hiring-employee-eps, #hiring-employee-afp, #hiring-employee-arl, #hiring-employee-retirement-date').prop('disabled', disabled);
}

export function updateEmployee(){
    let name = $('#update-employee-name').val();
    let lastName = $('#update-employee-last-name').val();
    let idType = $('#update-employee-id-type').val();
    let identification = $('#update-employee-identification').val();
    let country = $('#update-employee-country').attr('item-id');
    let phone = $('#update-employee-phone').val();
    let personalEmail = $('#update-employee-personal-email').val();
    let workEmail = $('#update-employee-work-email').val();
    let state = $('#update-employee-state').attr('value');
    let flag = true;
    if(name == null || name == ''){ $('#update-employee-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del empleado'); flag = false; }else{ $('#update-employee-name').removeClass('is-invalid'); }
    if(lastName == null || lastName == ''){ $('#update-employee-last-name').addClass('is-invalid'); alertWarning('Debe ingresar el apellido del empleado'); flag = false; }else{ $('#update-employee-last-name').removeClass('is-invalid'); }
    if(idType == null || idType == ''){ $('#update-employee-id-type').addClass('is-invalid'); alertWarning('Debe seleccionar un tipo de identificación'); flag = false; }else{ $('#update-employee-id-type').removeClass('is-invalid'); }
    if(identification == null || identification == ''){ $('#update-employee-identification').addClass('is-invalid'); alertWarning('Debe ingresar la identificación del empleado'); flag = false; }else{ $('#update-employee-identification').removeClass('is-invalid'); }
    if(state == null || state == ''){ $('#update-employee-state').addClass('is-invalid'); alertWarning('Debe seleccionar un estado'); flag = false; }
    if(country == null || country == ''){ $('#update-employee-country').addClass('is-invalid'); alertWarning('Debe seleccionar un país'); flag = false; }
    if(phone == null || phone == ''){ $('#update-employee-phone').addClass('is-invalid'); alertWarning('Debe ingresar el teléfono del empleado'); flag = false; }
    if(personalEmail == null || personalEmail == '' || !validateEmail(personalEmail)){ $('#update-employee-personal-email').addClass('is-invalid'); alertWarning('Debe ingresar el correo del empleado'); flag = false; }else{ $('#update-employee-personal-email').removeClass('is-invalid'); }
    if(workEmail == null || workEmail == '' || !validateEmail(workEmail)){ $('#update-employee-work-email').addClass('is-invalid'); alertWarning('Debe ingresar el correo del empleado'); flag = false; }else{ $('#update-employee-work-email').removeClass('is-invalid'); }
    if(flag){
        $('#update-employee-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+employeeState.currentEmployee.id+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="last_name" value="'+lastName+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="id_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="identification" value="'+identification+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="phone" value="'+phone+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="personal_email" value="'+personalEmail+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="work_email" value="'+workEmail+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('#update-employee-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#update-employee-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/employees/update',dinamicForm,null, function(response){
            $('#update-employee-button').prop('disabled', false);
            swallMessage('Exito', 'Empleado actualizado', 'success', null, null, 3000, null, null);
            employeeState.tabsView['nav-list-tab'] = false;
        }, function(){$('#update-employee-button').attr('disabled', false);});
    }
}

export function updateHiringData(){
    $('#update-employee-hiring-button').attr('disabled', true);
    let dataSend = {
        id: employeeState.currentEmployee.id,
        entry_date: $('#hiring-employee-entry-date').val(),
        payment_type: $('#hiring-employee-payment-type').val(),
        bank: $('#hiring-employee-bank').val(),
        account_number: $('#hiring-employee-account-number').val(),
        account_type: $('#hiring-employee-account-type').val(),
        salary: $('#hiring-employee-salary').val(),
        contract: $('#hiring-employee-contract').val(),
        department_id: $('#hiring-employee-department').val(),
        charge: $('#hiring-employee-charge').val(),
        eps_id: $('#hiring-employee-eps').attr('item-id'),
        afp_id: $('#hiring-employee-afp').attr('item-id'),
        arl_id: $('#hiring-employee-arl').attr('item-id'),
        retirement_date: $('#hiring-employee-retirement-date').val(),
    };
    PostMethodFunction('/admin/employees/hiring/update',dataSend,null, function(response){
        $('#update-employee-hiring-button').attr('disabled', false);
        swallMessage('Exito', 'Datos de contratación actualizados', 'success', null, null, 3000, null, null);
        employeeState.tabsView['nav-list-tab'] = false;
    }, function(){$('#update-employee-hiring-button').attr('disabled', false);});
}

export function deleteEmployee(employeeId, onListRefresh){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este empleado?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {id: employeeId};
            PostMethodFunction('/admin/employees/delete',dataSend,null, function(response){
                alertSuccess('Empleado eliminado');
                if(employeeState.currentTab == 'nav-update-tab'){
                    employeeState.currentEmployee.deleted_at = response.data.deleted_at;
                    showCurrentEmployee();
                }else{
                    onListRefresh();
                }
                employeeState.tabsView['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}

export function restoreEmployee(employeeId, onListRefresh){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este empleado?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {id: employeeId};
            PostMethodFunction('/admin/employees/restore',dataSend,null, function(response){
                alertSuccess('Empleado restaurado');
                if(employeeState.currentTab == 'nav-update-tab'){
                    employeeState.currentEmployee.deleted_at = null;
                    showCurrentEmployee();
                }else{
                    onListRefresh();
                }
                employeeState.tabsView['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}