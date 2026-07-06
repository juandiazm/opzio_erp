$(document).on('click', '#nav-tab .nav-link', changeTab);
//////////
$(document).on('click', '#nav-update .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('change', '#nav-update .input-color',loadUpdateImageBorder);
$(document).on('click', '#update-button', updateUser);
////VAR TABS
var tabs_view = {
    'nav-update-tab': false,
}
////
$(document).ready(function(){
    changeTab();
    //check if permission array have a value
    if(permissions.length > 0){
        var result = $.grep(permissions, function(item) {
            return item['user_permission_id'] === 1;
        });
        if(result.length > 0){
            getAllUserPermissions();
        }else{
            $('#permissions-container').remove();
        }
    }else{
        $('#permissions-container').remove();
    }

});
function changeTab(){
    let tab = $('#nav-tab .active').attr('id');
    if(tab == 'nav-update-tab'){
        showCurrentUser();
    }
    tabs_view[tab] = true;    
}

//Update User functions
function showCurrentUser(){
    $('#update-user-id').text(String(current_user.id).padStart(5, "0"));
    $('#update-user-id-input').val(current_user.id);
    $('#update-user-identification').val(current_user.identification);
    $('#update-user-name').val(current_user.name);
    $('#update-user-lastname').val(current_user.lastname);
    $('#update-user-username').val(current_user.username);
    $('#update-user-email').val(current_user.email);
    $('#update-user-img-container').css('background-image','url("/images/erp/users/'+current_user.photo+'")');
    $('#update-user-img-container .image-icon').css('display','none');
    $('#update-user-color').val(current_user.color);
    $('#update-user-color').change();
}
function loadUpdateImageBorder(){
    var color = $(this).val();
    $(this).parent().parent().parent().find('#update-user-img-container').css('border-color',color);
}
function updateUser(){
    let container = $(this).parent();
    let flag = true;
    let identification = container.find('#update-user-identification').val();
    let name = container.find('#update-user-name').val();
    let lastname = container.find('#update-user-lastname').val();
    let username = container.find('#update-user-username').val();
    let email = container.find('#update-user-email').val();
    let color = container.find('#update-user-color').val();
    let current_permissons = [];
    if(identification==null || identification == ""){
        container.find('#update-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{
        container.find('#update-user-identification').removeClass('is-invalid');
    }
    if(name==null || name == ""){
        container.find('#update-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        container.find('#update-user-name').removeClass('is-invalid');
    }
    if(lastname==null || lastname==""){
        container.find('#update-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        container.find('#update-user-lastname').removeClass('is-invalid');
    }
    if(username==null || username==""){
        container.find('#update-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        container.find('#update-user-username').removeClass('is-invalid');
    }
    if(email==null || email==""){
        container.find('#update-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo electrónico');
        flag = false;
    }else{
        container.find('#update-user-email').removeClass('is-invalid');
    }
    if(color==null || color==""){
        container.find('#create-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        container.find('#create-user-color').removeClass('is-invalid');
    }
    $.each($('#nav-update .permission-input'),function(index,value){
        if($(value).is(':checked')){
            current_permissons.push($(value).attr('id').split('-')[1]);
        }
    });
    if(current_permissons.length == 0){
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
        PostMethodMultimediaFunction('/admin/my-profile/update', dinamicForm, null, function(response){
            $('#update-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Usuario actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#update-button').attr('disabled', false);});
    }
}
function getAllUserPermissions(){
    GetMethodFunction('/admin/users/permissions',null,function(response){
        let appendData = '';
        $.each(response.data,function(index,value){
            appendData += '<div class="col-12 col-md-6 permission-input-container d-flex">';
                appendData += '<input type="checkbox" class="align-self-center form-check-input permission-input" name="permissions['+value.id+']" id="permission-'+value.id+'">';
                appendData += '<label for="permission-'+value.id+'" class="align-self-center permission-label">'+value.name+'</label>';
            appendData += '</div>';
        });
        $('.permissions-list').html(appendData);
        $.each($('#nav-update .permission-input'),function(index,value){
            if(permissions.find(permission => permission.user_permission_id == $(value).attr('id').split('-')[1]) != undefined){
                $(value).prop('checked',true);
            }else{
                $(value).prop('checked',false);
            }
        });
    },null);
}