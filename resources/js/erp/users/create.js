import { userState } from './state.js';

export function getUserNextId(){
    GetMethodFunction('/admin/users/next-id',null,function(response){
        $('#create-user-id').text(String(response.data).padStart(5, "0"));
    },null);
}

export function loadCreateImageBorder(){
    let color = $(this).val();
    $(this).parent().parent().parent().find('#create-user-img-container').css('border-color',color);
}

export function createUser(){
    let container = $(this).parent();
    let flag = true;
    let identification = container.find('#create-user-identification').val();
    let name = container.find('#create-user-name').val();
    let lastname = container.find('#create-user-lastname').val();
    let username = container.find('#create-user-username').val();
    let email = container.find('#create-user-email').val();
    let password = container.find('#create-user-password').val();
    let passwordConfirmation = container.find('#create-user-password-confirmation').val();
    let image = container.find('#create-user-img').val();
    let color = container.find('#create-user-color').val();
    let permissions = [];
    if(identification==null || identification == ""){
        container.find('#create-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{ container.find('#create-user-identification').removeClass('is-invalid'); }
    if(name==null || name == ""){
        container.find('#create-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{ container.find('#create-user-name').removeClass('is-invalid'); }
    if(lastname==null || lastname==""){
        container.find('#create-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{ container.find('#create-user-lastname').removeClass('is-invalid'); }
    if(username==null || username==""){
        container.find('#create-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{ container.find('#create-user-username').removeClass('is-invalid'); }
    if(email==null || email==""){
        container.find('#create-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo electrónico');
        flag = false;
    }else{ container.find('#create-user-email').removeClass('is-invalid'); }
    if(password == null || password == ""){
        container.find('#create-user-password').addClass('is-invalid');
        alertWarning('Debe ingresar la contraseña');
        flag = false;
    }else{ container.find('#create-user-password').removeClass('is-invalid'); }
    if(password != passwordConfirmation){
        container.find('#create-user-password-confirmation').addClass('is-invalid');
        alertWarning('Las contraseñas no coinciden');
        flag = false;
    }else{ container.find('#create-user-password-confirmation').removeClass('is-invalid'); }
    if(image == null || image == ""){
        container.find('#create-user-img').addClass('is-invalid');
        alertWarning('Debe seleccionar una imagen');
        flag = false;
    }else{ container.find('#create-user-img').removeClass('is-invalid'); }
    if(color==null || color==""){
        container.find('#create-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{ container.find('#create-user-color').removeClass('is-invalid'); }
    $.each($('#nav-create .permission-input'),function(index,value){
        if($(value).is(':checked')) permissions.push($(value).attr('id').split('-')[1]);
    });
    if(permissions.length == 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(flag){
        $('#add-button').attr('disabled',true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild(container.find('#create-user-identification').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-name').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-lastname').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-username').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-email').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-password').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-password-confirmation').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-img').clone(true)[0]);
        dinamicForm.appendChild(container.find('#create-user-color').clone(true)[0]);
        $.each($('#nav-create .permission-input'),function(index,value){
            dinamicForm.appendChild($(value).clone(true)[0]);
        });
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = container.find('.input_image')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/users/add', dinamicForm, null, function(response){
            $('#add-button').attr('disabled', false);
            container.find('input').val('');
            $('#nav-create .permission-input').prop('checked',false);
            container.find('#create-user-color').val('#707070');
            container.find('input').change();
            $(container).find('.image_preview').attr('src', '').css('display', 'none');
            $(container).find('.image-container').css('background-image', 'none');
            $(container).find('.image-icon').css('display', 'inline-block');
            $(container).find('.color-icon').css('display', 'inline-block');
            $(container).find('.color-container').attr('style', '');
            $('#create-user-id').text(String(response.nextId).padStart(5, "0"));
            swallMessage('Exito', 'Usuario creado', 'success', null, null, 3000, null, null);
            userState.tabsView['nav-list-tab'] = false;
        }, function(){$('#add-button').attr('disabled', false);});
    }
}