import { profileState, setCurrentUser, setPermissions } from './state.js';
import { showCurrentUser, updateHeaderProfile } from './profile.js';

export function updateUser(){
    const container = $(this).parent();
    let flag = true;
    const identification = container.find('#update-user-identification').val();
    const name = container.find('#update-user-name').val();
    const lastname = container.find('#update-user-lastname').val();
    const username = container.find('#update-user-username').val();
    const email = container.find('#update-user-email').val();
    const color = container.find('#update-user-color').val();
    const currentPermissions = [];

    if(identification == null || identification === ''){
        container.find('#update-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else container.find('#update-user-identification').removeClass('is-invalid');
    if(name == null || name === ''){
        container.find('#update-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else container.find('#update-user-name').removeClass('is-invalid');
    if(lastname == null || lastname === ''){
        container.find('#update-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else container.find('#update-user-lastname').removeClass('is-invalid');
    if(username == null || username === ''){
        container.find('#update-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else container.find('#update-user-username').removeClass('is-invalid');
    if(email == null || email === ''){
        container.find('#update-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo electrónico');
        flag = false;
    }else container.find('#update-user-email').removeClass('is-invalid');
    if(color == null || color === ''){
        container.find('#update-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else container.find('#update-user-color').removeClass('is-invalid');

    $.each($('#nav-update .permission-input'), function(index, value){
        if($(value).is(':checked')) currentPermissions.push($(value).attr('id').split('-')[1]);
    });
    if(currentPermissions.length === 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(!flag) return;

    $('#update-button').attr('disabled', true);
    let dynamicForm = document.createElement('form');
    dynamicForm.setAttribute('id', 'temporal-form');
    dynamicForm.setAttribute('class', 'd-none');
    dynamicForm.appendChild(container.find('#update-user-id-input').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-identification').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-name').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-lastname').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-username').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-email').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-img').clone(true)[0]);
    dynamicForm.appendChild(container.find('#update-user-color').clone(true)[0]);
    $.each($('#nav-update .permission-input'), function(index, value){
        dynamicForm.appendChild($(value).clone(true)[0]);
    });
    dynamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
    document.body.appendChild(dynamicForm);
    dynamicForm = $('#temporal-form');
    dynamicForm.find('.input_image')[0].files = container.find('.input_image')[0].files;
    $('#temporal-form').remove();

    PostMethodMultimediaFunction('/admin/my-profile/update', dynamicForm, null, function(response){
        $('#update-button').attr('disabled', false);
        setCurrentUser(response.user);
        setPermissions(response.permissions || profileState.permissions);
        showCurrentUser();
        updateHeaderProfile();
        swallMessage('Exito', 'Usuario actualizado', 'success', null, null, 3000, null, null);
        profileState.tabsView['nav-list-tab'] = false;
    }, function(){ $('#update-button').attr('disabled', false); });
}