import { userState } from './state.js';
import { getUsersPage } from './list.js';

export function showCurrentUser(){
    let currentUser = userState.currentUser;
    $('#sub-nav-outcomes').attr('data-outcome-association-id', currentUser.id);
    if(window.AssociatedOutcomes) window.AssociatedOutcomes.setContext('user_id', currentUser.id);
    $('#update-user-id').text(String(currentUser.id).padStart(5, "0"));
    $('#update-user-id-input').val(currentUser.id);
    $('#update-user-identification').val(currentUser.identification);
    $('#update-user-name').val(currentUser.name);
    $('#update-user-lastname').val(currentUser.lastname);
    $('#update-user-username').val(currentUser.username);
    $('#update-user-email').val(currentUser.email);
    $('#update-user-img-container .image_preview').attr('src', '/storage/images/erp/users/'+currentUser.photo).css('display','block');
    $('#update-user-img-container .image-icon').css('display','none');
    $('#update-user-color').val(currentUser.color);
    $('#update-user-color').change();
    $.each($('#nav-update .permission-input'),function(index,value){
        if(currentUser.permissions.find(permission => permission.user_permission_id == $(value).attr('id').split('-')[1]) != undefined){
            $(value).prop('checked',true);
        }else{
            $(value).prop('checked',false);
        }
    });
    if(currentUser.deleted_at != null){
        $('#update-user-identification').attr('disabled',true);
        $('#update-user-name').attr('disabled',true);
        $('#update-user-lastname').attr('disabled',true);
        $('#update-user-username').attr('disabled',true);
        $('#update-user-email').attr('disabled',true);
        $('#update-user-img').attr('disabled',true);
        $('#update-user-color').attr('disabled',true);
        $('#nav-update .permission-input').attr('disabled',true);
        $('#update-button').removeClass('d-block').addClass('d-none');
        $('#update-user-delete').removeClass('d-block').addClass('d-none');
        $('#update-user-restore').removeClass('d-none').addClass('d-block');
    }else{
        $('#update-user-identification').attr('disabled',false);
        $('#update-user-name').attr('disabled',false);
        $('#update-user-lastname').attr('disabled',false);
        $('#update-user-username').attr('disabled',false);
        $('#update-user-email').attr('disabled',false);
        $('#update-user-img').attr('disabled',false);
        $('#update-user-color').attr('disabled',false);
        $('#nav-update .permission-input').attr('disabled',false);
        $('#update-button').removeClass('d-none').addClass('d-block');
        $('#update-user-delete').removeClass('d-none').addClass('d-block');
        $('#update-user-restore').removeClass('d-block').addClass('d-none');
    }
}

export function loadUpdateImageBorder(){
    let color = $(this).val();
    $(this).parent().parent().parent().find('#update-user-img-container').css('border-color',color);
}

export function updateUser(){
    let container = $(this).parent();
    let flag = true;
    let identification = container.find('#update-user-identification').val();
    let name = container.find('#update-user-name').val();
    let lastname = container.find('#update-user-lastname').val();
    let username = container.find('#update-user-username').val();
    let email = container.find('#update-user-email').val();
    let color = container.find('#update-user-color').val();
    let permissions = [];
    if(identification==null || identification == ""){
        container.find('#update-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{ container.find('#update-user-identification').removeClass('is-invalid'); }
    if(name==null || name == ""){
        container.find('#update-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{ container.find('#update-user-name').removeClass('is-invalid'); }
    if(lastname==null || lastname==""){
        container.find('#update-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{ container.find('#update-user-lastname').removeClass('is-invalid'); }
    if(username==null || username==""){
        container.find('#update-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{ container.find('#update-user-username').removeClass('is-invalid'); }
    if(email==null || email==""){
        container.find('#update-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo electrónico');
        flag = false;
    }else{ container.find('#update-user-email').removeClass('is-invalid'); }
    if(color==null || color==""){
        container.find('#create-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{ container.find('#create-user-color').removeClass('is-invalid'); }
    $.each($('#nav-update .permission-input'),function(index,value){
        if($(value).is(':checked')) permissions.push($(value).attr('id').split('-')[1]);
    });
    if(permissions.length == 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(flag){
        $('#update-button').attr('disabled',true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild(container.find('#update-user-id-input').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-identification').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-name').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-lastname').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-username').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-email').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-img').clone(true)[0]);
        dinamicForm.appendChild(container.find('#update-user-color').clone(true)[0]);
        $.each($('#nav-update .permission-input'),function(index,value){
            dinamicForm.appendChild($(value).clone(true)[0]);
        });
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = container.find('.input_image')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/users/update', dinamicForm, null, function(response){
            $('#update-button').attr('disabled', false);
            swallMessage('Exito', 'Usuario actualizado', 'success', null, null, 3000, null, null);
            userState.tabsView['nav-list-tab'] = false;
        }, function(){$('#update-button').attr('disabled', false);});
    }
}

export function deleteUser(userId){
    swallMessage(
        'Advertencia'
        , '¿Está seguro que desea eliminar este usuario?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {id: userId};
            PostMethodFunction('/admin/users/delete',dataSend,null, function(response){
                alertSuccess('Usuario eliminado');
                if(userState.currentTab == 'nav-update-tab'){
                    userState.currentUser.deleted_at = 'deleted';
                    showCurrentUser();
                }else{
                    getUsersPage();
                }
                userState.tabsView['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}

export function restoreUser(userId){
    swallMessage(
        'Advertencia'
        , '¿Está seguro que desea restaurar este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {id: userId};
            PostMethodFunction('/admin/users/restore',dataSend,null, function(response){
                alertSuccess('Usuario restaurado');
                if(userState.currentTab == 'nav-update-tab'){
                    userState.currentUser.deleted_at = null;
                    showCurrentUser();
                }else{
                    getUsersPage();
                }
                userState.tabsView['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}