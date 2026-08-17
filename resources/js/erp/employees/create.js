import { employeeState } from './state.js';

export function addEmployee(button, onCreated){
    let container = $(button).parent();
    let flag = true;
    let name = $('#create-employee-name').val();
    let lastName = $('#create-employee-last-name').val();
    let idType = $('#create-employee-id-type').val();
    let identification = $('#create-employee-identification').val();
    let country = $('#create-employee-country').attr('item-id');
    let phone = $('#create-employee-phone').val();
    let personalEmail = $('#create-employee-personal-email').val();
    let workEmail = $('#create-employee-work-email').val();
    let state = $('#create-employee-state').attr('value');
    let image = $('#create-employee-img').val();
    if(image == null || image == ''){
        $('#create-employee-img').addClass('is-invalid');
        alertWarning('Debe ingresar una imagen');
        flag = false;
    }
    if(name == null || name == ''){
        $('#create-employee-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del empleado');
        flag = false;
    }else{ $('#create-employee-name').removeClass('is-invalid'); }
    if(lastName == null || lastName == ''){
        $('#create-employee-last-name').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del empleado');
        flag = false;
    }else{ $('#create-employee-last-name').removeClass('is-invalid'); }
    if(idType == null || idType == ''){
        $('#create-employee-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }else{ $('#create-employee-id-type').removeClass('is-invalid'); }
    if(identification == null || identification == ''){
        $('#create-employee-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del empleado');
        flag = false;
    }else{ $('#create-employee-identification').removeClass('is-invalid'); }
    if(state == null || state == ''){
        $('#create-employee-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }
    if(country == null || country == ''){
        $('#create-employee-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-employee-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del empleado');
        flag = false;
    }
    if(personalEmail == null || personalEmail == '' || !validateEmail(personalEmail)){
        $('#create-employee-personal-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{ $('#create-employee-personal-email').removeClass('is-invalid'); }
    if(workEmail == null || workEmail == '' || !validateEmail(workEmail)){
        $('#create-employee-work-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{ $('#create-employee-work-email').removeClass('is-invalid'); }
    if(flag){
        $('#add-employee-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="text" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="last_name" value="'+lastName+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="id_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="identification" value="'+identification+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="phone" value="'+phone+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="personal_email" value="'+personalEmail+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="work_email" value="'+workEmail+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('#create-employee-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#create-employee-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/employees/add',dinamicForm,null, function(response){
            $('#add-employee-button').prop('disabled', false);
            $(container).find('.image_preview').css('display', 'inline-block');
            $(container).find('.image-container').css('background-image', 'none');
            $(container).find('.image-icon').css('display', 'inline-block');
            $('#create-employee-img').val('');
            $('#create-employee-name').val('');
            $('#create-employee-last-name').val('');
            $('#create-employee-identification').val('');
            $('#create-employee-phone').val('');
            $('#create-employee-personal-email').val('');
            $('#create-employee-work-email').val('');
            swallMessage('Exito', 'Empleado creado', 'success', null, null, 3000, null, null);
            employeeState.tabsView['nav-list-tab'] = false;
            employeeState.currentEmployee = response.employee;
            onCreated();
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
        }, function(){$('#add-employee-button').attr('disabled', false);});
    }
}