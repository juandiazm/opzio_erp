$(document).on('click', '#update-client-user-button', updateMyProfile);
$(document).ready(function(){
    if(permissions_flag) getClientUserPermissions();
});
function getClientUserPermissions(){
    GetMethodFunction('/client/users/permissions', null, function(response){
        let appendData = '';
        $.each(response.data,function(index,value){
            appendData += '<div class="col-12 col-md-6 permission-input-container d-flex">';
                appendData += '<input type="checkbox" class="align-self-center form-check-input permission-input" name="permissions['+value.id+']" id="permission-'+value.id+'">';
                appendData += '<label for="permission-'+value.id+'" class="align-self-center permission-label">'+value.name+'</label>';
            appendData += '</div>';
        });
        $('.permissions-list').html(appendData);
        $.each($('#my-profile-container .permission-input'),function(index,value){
            if(permissions.find(permission => permission.client_user_permission_id == $(value).attr('id').split('-')[1]) != undefined){
                $(value).prop('checked',true);
            }else{
                $(value).prop('checked',false);
            }
        });
    }, null);
}
function updateMyProfile(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#client-user-name').val();
    let lastname = $('#client-user-lastname').val();
    let username = $('#client-user-username').val();
    let email = $('#client-user-email').val();
    let phone = $('#client-user-phone').val();
    let position = $('#client-user-position').val();
    let color = $('#client-user-color').val();
    let password = $('#client-user-password').val();
    let confirm_password = $('#client-user-confirm-password').val();
    let current_permissons = [];
    if(name == null || name == ''){
        $('#client-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        $('#client-user-name').removeClass('is-invalid');
    }
    if(lastname == null || lastname == ''){
        $('#client-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        $('#client-user-lastname').removeClass('is-invalid');
    }
    if(username == null || username == ''){
        $('#client-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        $('#client-user-username').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#client-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del usuario');
        flag = false;
    }else{
        $('#client-user-email').removeClass('is-invalid');
    }
    if(phone == null || phone == ''){
        $('#client-user-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del usuario');
        flag = false;
    }else{
        $('#client-user-phone').removeClass('is-invalid');
    }
    if(position == null || position == ''){
        $('#client-user-position').addClass('is-invalid');
        alertWarning('Debe ingresar el cargo del usuario');
        flag = false;
    }else{
        $('#client-user-position').removeClass('is-invalid');
    }
    if(color == null || color == ''){
        $('#client-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        $('#client-user-color').removeClass('is-invalid');
    }
    if(password != confirm_password){
        $('#client-user-password').addClass('is-invalid');
        alertWarning('Las contraseñas no coinciden');
        flag = false;
    }
    $.each($('#my-profile-container .permission-input'),function(index,value){
        if($(value).is(':checked')){
            current_permissons.push($(value).attr('id').split('-')[1]);
        }
    });
    if(current_permissons.length == 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(flag){
        swallMessage(
            'Seguridad'
            , 'Se cerrará tu sesión, ¿Deseas continuar?'
            , 'warning'
            , 'Si, actualizar'
            , 'Cancelar'
            , null
            , function(){
                updateMyProfileAction(
                    name
                    , lastname
                    , username
                    , email
                    , phone
                    , color
                    , position
                    , password
                    ,current_permissons
                );
            }
            , null
        ); 
    }
}
function updateMyProfileAction(
    name
    , lastname
    , username
    , email
    , phone
    , color
    , position
    , password
    , current_permissons
){
    $('#update-client-user-button').prop('disabled', true);
    let DataSend = {
        name: name,
        lastname: lastname,
        username: username,
        email: email,
        phone: phone,
        color: color,
        position: position,
        password: password,
        permissions: current_permissons
    };
    PostMethodFunction('/client/profile/update',DataSend,null, function(response){
        $('#update-client-user-button').attr('disabled', false);
        swallMessage(
            'Exito'
            , 'Tu perfil ha sido actualizado correctamente'
            , 'success'
            , null
            , null
            , 3000
            , null
            , function(){
                location.href = '/client';
            }
        );
        getClientUsers();
    }, function(){$('#update-client-user-button').attr('disabled', false);});
}