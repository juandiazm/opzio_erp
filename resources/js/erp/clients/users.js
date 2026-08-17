import { clientState } from './state.js';

export function addClientUser(){
    let name = $('#create-client-user-name').val();
    let lastname = $('#create-client-user-lastname').val();
    let username = $('#create-client-user-username').val();
    let email = $('#create-client-user-email').val();
    let phone = $('#create-client-user-phone').val();
    let position = $('#create-client-user-position').val();
    let color = $('#create-client-user-color').val();
    let flag = true;
    if(name == null || name == ''){
        $('#create-client-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        $('#create-client-user-name').removeClass('is-invalid');
    }
    if(lastname == null || lastname == ''){
        $('#create-client-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        $('#create-client-user-lastname').removeClass('is-invalid');
    }
    if(username == null || username == ''){
        $('#create-client-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        $('#create-client-user-username').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-client-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del usuario');
        flag = false;
    }else{
        $('#create-client-user-email').removeClass('is-invalid');
    }
    if(phone == null || phone == ''){
        $('#create-client-user-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del usuario');
        flag = false;
    }else{
        $('#create-client-user-phone').removeClass('is-invalid');
    }
    if(position == null || position == ''){
        $('#create-client-user-position').addClass('is-invalid');
        alertWarning('Debe ingresar el cargo del usuario');
        flag = false;
    }else{
        $('#create-client-user-position').removeClass('is-invalid');
    }
    if(color == null || color == ''){
        $('#create-client-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        $('#create-client-user-color').removeClass('is-invalid');
    }
    if(flag){
        $('#add-client-user-button').prop('disabled', true);
        let dataSend = {
            client_id: clientState.currentClient.id,
            name: name,
            lastname: lastname,
            username: username,
            email: email,
            phone: phone,
            color: color,
            position: position,
            permissions:[4]
        };
        PostMethodFunction('/admin/clients/users/add',dataSend,null, function(response){
            $('#add-client-user-button').attr('disabled', false);
            $('#create-client-user-name').val('');
            $('#create-client-user-lastname').val('');
            $('#create-client-user-username').val('');
            $('#create-client-user-email').val('');
            $('#create-client-user-phone').val('');
            $('#create-client-user-position').val('');
            swallMessage(
                'Exito'
                , 'Usuario creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getClientUsers();
        }, function(){$('#add-client-user-button').attr('disabled', false);});
    }
}

export function getClientUsers(){
    let dataSend = {
        client_id: clientState.currentClient.id
    };
    PostMethodFunction('/admin/clients/users/get-by-client-id',dataSend,null, showClientUsers,null);
}

function showClientUsers(result){
    let appendContent = '';
    let completeName = '';
    let nameInitials = '';
    $.each(result.data,function(index,value){
        completeName = value.name+(value.lastname==null?' ':value.lastname);
        nameInitials = value.name.charAt(0)+(value.lastname==null?'':value.lastname.charAt(0));
        appendContent += '<tr class="client-user-info'+(value.deleted_at==null?'':' deleted')+'" user-id="'+value.id+'">';
            appendContent += '<td class="user-column-id text-left" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="user-column-color text-center color-column"><div class="d-flex flex-column justify-content-center" style="background-color:'+value.color+'"><p class="client-user-input-color align-self-end input-value">'+nameInitials+'</p></div></td>';
            appendContent += '<td class="user-column-name text-left"><p class="client-user-input-lastname align-self-end input-value">'+completeName+'</p></td>';
            appendContent += '<td class="user-column-username text-left"><p class="client-user-input-username align-self-end input-value">'+value.username+'</p></td>';
            appendContent += '<td class="user-column-email text-left"><p class="client-user-input-email align-self-end input-value">'+value.email+'</p></td>';
            appendContent += '<td class="user-column-phone text-left"><p class="client-user-input-phone align-self-end input-value">'+(value.phone==null?'':value.phone)+'</p></td>';
            appendContent += '<td class="user-column-position text-left"><p class="client-user-input-position align-self-end input-value">'+value.position+'</p></td>';
            appendContent += '<td class="user-column-actions text-end action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-key restore-client-user-password-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-client-user-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-lightbulb restore-client-user-btn"></i>';
                }
                appendContent += '<i class="fa-solid fa-bars-progress list-client-user-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-users-table #client-users-table-body').empty().append(appendContent);
}

export function restoreClientUserPassword(){
    let userId = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar la contraseña de este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: userId,
            };
            PostMethodFunction('/admin/clients/users/restore-password',dataSend,null, function(response){
                alertSuccess('Contraseña restaurada');
                swallMessage(
                    'Contraseña temporal'
                    , '<i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+response.data+'"></i>'+response.data
                    , 'info'
                    , 'Entendido'
                    , null
                    , null
                    , null
                    , null
                );
            },null);
        }
        , null
    );
}

export function deleteClientUser(){
    let userId = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este usuario?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: userId,
            };
            PostMethodFunction('/admin/clients/users/delete',dataSend,null, function(response){
                alertSuccess('Usuario eliminado');
                getClientUsers();
            },null);
        }
        , null
    );
}

export function restoreClientUser(){
    let button = $(this);
    button.attr('disabled', true);
    let userId = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: userId,
            };
            PostMethodFunction('/admin/clients/users/restore',dataSend,null, function(response){
                alertSuccess('Usuario restaurado');
                getClientUsers();
                button.attr('disabled', false);
            },null);
        }
        , function(){button.attr('disabled', false);}
    );
}

export function goToUserTraceability(){
    let userId = $(this).closest('.client-user-info').attr('user-id');
    $('#nav-traceability').attr('user-id',userId);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}