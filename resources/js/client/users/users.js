$(document).on('click', '#nav-tab .nav-link', changeTab);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('click', '.list-update-traceability', goToTraceabilityTab);
$(document).on('click', '.list-restore-password-btn', restorePasswordUser);
$(document).on('click', '.list-delete-user-btn', deleteUser);
//////////
$(document).on('click', '#nav-create .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('click', '#create-user-button', createUser);
/////////
$(document).on('click', '#nav-update .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('click', '#update-user-button', updateUser);
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var allUsers = [];
var users = [];
var current_user = null;
var user_id = null;
var trought_user = false;
////
$(document).ready(function(){
    //check if url has user_id parameter
    getAllUserPermissions();
    changeTab();
});
function changeTab(){
    let tab = $('#nav-tab .active').attr('id');
    if(tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[tab]==false && tab == 'nav-list-tab'){
        getUsersPage();    
    }else if(tabs_view[tab]==false && tab == 'nav-create-tab'){
        
    }else if(tab == 'nav-traceability-tab'){
        if(trought_user == false){
            user_id = null;
        }
        trought_user = false;
    }else if(tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    tabs_view[tab] = true;    
}
function getAllUserPermissions(){
    GetMethodFunction('/client/users/permissions',null,function(response){
        let appendData = '';
        $.each(response.data,function(index,value){
            appendData += '<div class="col-12 col-md-6 permission-input-container d-flex">';
                appendData += '<input type="checkbox" class="align-self-center form-check-input permission-input" name="permissions['+value.id+']" id="permission-'+value.id+'">';
                appendData += '<label for="permission-'+value.id+'" class="align-self-center permission-label">'+value.name+'</label>';
            appendData += '</div>';
        });
        $('.permissions-list').html(appendData);
        if(user_id){
            getUserById(user_id);
        }
    },null);
}
function getUserById(user_id){
    let DataSend = {
        user_id: user_id
    };
    PostMethodFunction('/client/users/get-by-id',DataSend,null,function(response){
        current_user = response.data;
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentUser();
    }
    ,null);
}
//List User Functions
var db_pagination = {
    page:1,
    per_page:10,
    total:0,
};
function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
	paginationContainer.empty();
	
		let AppenedContent = '';
        AppenedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
		
        let closePage = null;
        let showPageSize = 3;
        let dots = {left: false, right: false};
        for (let index = 1; index <= db_pagination.totalPages; index++) {
        closePage = Math.abs(db_pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.left = true;
            }
        }else if(index >= db_pagination.totalPages - 2){
            if(!dots.right){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.right = true;
            }
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
        }
		
		AppenedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
        //page size
        if(db_pagination.total>5){
            AppenedContent += '<li class="page-item">';
                AppenedContent += "<p class='page-link'>";
                    AppenedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
                        AppenedContent += '<option value="5"'+((db_pagination.per_page==5)?' selected':'')+'>5</option>';
                        AppenedContent += '<option value="10"'+((db_pagination.per_page==10)?' selected':'')+'>10</option>';
                        AppenedContent += '<option value="50"'+((db_pagination.per_page==50)?' selected':'')+'>50</option>';
                    AppenedContent += '</select>';
                    AppenedContent += "</p>";
            AppenedContent += '</li>';
        }
		paginationContainer.append(AppenedContent);
	
}
function DBchangePageSize(){
    db_pagination.per_page = $('#db-pagination-per-page').val();
    db_pagination.page = 1;
    getUsersPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getUsersPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getUsersPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getUsersPage();
    }
}
function getUsersPage(){
    let DataSend = {
        pagination: db_pagination
    };
    PostMethodFunction('/client/users/get-page',DataSend,null, showUsersPage,null);
}
function goToUpdateTab(){
    user_id = $(this).parent().parent().attr('user-id');
    current_user = users.find(user => user.id == user_id);
    if(current_user != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentUser();
    }
}
function goToTraceabilityTab(){
    user_id = $(this).parent().parent().attr('user-id');
    current_user = users.find(user => user.id == user_id);
    if(current_user != null){
        trought_user = true;
        $('#nav-traceability-tab').tab('show');
        $('#nav-traceability-tab').trigger('click');
    }
}
function showUsersPage(response){
    db_pagination = response.pagination;
    users = response.data;
    let appendContent = '';
    let complete_name = '';
    let name_initials = '';
    $.each(users,function(index,value){
        complete_name = value.name+(value.lastname==null?' ':value.lastname);
        name_initials = value.name.charAt(0)+(value.lastname==null?'':value.lastname.charAt(0));
        appendContent += '<tr class="user-row" user-id='+value.id+'>';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-color text-center color-column"><div class="d-flex flex-column justify-content-center" style="background-color:'+value.color+'"><p class="client-user-input-color align-self-end input-value">'+name_initials+'</p></div></td>';
            appendContent += '<td class="columns-name text-left"><p class="client-user-input-lastname align-self-end input-value">'+complete_name+'</p></td>';
            appendContent += '<td class="columns-username text-left"><p class="client-user-input-username align-self-end input-value">'+value.username+'</p></td>';
            appendContent += '<td class="columns-email text-left"><p class="client-user-input-email align-self-end input-value">'+value.email+'</p></td>';
            appendContent += '<td class="columns-phone text-left"><p class="client-user-input-phone align-self-end input-value">'+value.phone+'</p></td>';
            appendContent += '<td class="columns-position text-center"><p class="client-user-input-position align-self-end input-value">'+value.position+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-key list-restore-password-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can list-delete-user-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-lightbulb restore-user-btn"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#user-list-table #user-list-table-body').empty().append(appendContent);
    DBshowPagination();
}
//Create User functions
function createUser(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#create-user-name').val();
    let lastname = $('#create-user-lastname').val();
    let username = $('#create-user-username').val();
    let email = $('#create-user-email').val();
    let phone = $('#create-user-phone').val();
    let position = $('#create-user-position').val();
    let color = $('#create-user-color').val();
    let permissions = [];
    
    if(name == null || name == ''){
        $('#create-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        $('#create-user-name').removeClass('is-invalid');
    }
    if(lastname == null || lastname == ''){
        $('#create-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        $('#create-user-lastname').removeClass('is-invalid');
    }
    if(username == null || username == ''){
        $('#create-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        $('#create-user-username').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del usuario');
        flag = false;
    }else{
        $('#create-user-email').removeClass('is-invalid');
    }
    if(phone == null || phone == ''){
        $('#create-user-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del usuario');
        flag = false;
    }else{
        $('#create-user-phone').removeClass('is-invalid');
    }
    if(position == null || position == ''){
        $('#create-user-position').addClass('is-invalid');
        alertWarning('Debe ingresar el cargo del usuario');
        flag = false;
    }else{
        $('#create-user-position').removeClass('is-invalid');
    }
    if(color == null || color == ''){
        $('#create-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        $('#create-user-color').removeClass('is-invalid');
    }
    $.each($('#nav-create .permission-input'),function(index,value){
        if($(value).is(':checked')){
            permissions.push($(value).attr('id').split('-')[1]);
        }
    });
    if(permissions.length == 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(flag){
        $('#create-user-button').prop('disabled', true);
        let DataSend = {
            name: name,
            lastname: lastname,
            username: username,
            email: email,
            phone: phone,
            color: color,
            position: position,
            permissions: permissions
        };
        PostMethodFunction('/client/users/add',DataSend,null, function(response){
            $('#create-user-button').attr('disabled', false);
            $('#create-user-name').val('');
            $('#create-user-lastname').val('');
            $('#create-user-username').val('');
            $('#create-user-email').val('');
            $('#create-user-phone').val('');
            $('#create-user-position').val('');
            $('#nav-create .permission-input').prop('checked', false);
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
            //go to update tab
            tabs_view['nav-list-tab'] = false;
            current_user = response.data.client_user;
            showCurrentUser();
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
        }, function(){$('#create-user-button').attr('disabled', false);});
    }

}
//Update User functions
function showCurrentUser(){
    $('#update-user-name').val(current_user.name);
    $('#update-user-lastname').val(current_user.lastname);
    $('#update-user-username').val(current_user.username);
    $('#update-user-email').val(current_user.email);
    $('#update-user-phone').val(current_user.phone);
    $('#update-user-position').val(current_user.position);
    $('#update-user-color').val(current_user.color);
    $('#update-user-color').change();
    let dataSend = {
        client_user_id: current_user.id
    }
    PostMethodFunction('/client/users/permissions-by-user',dataSend,null,function(response){
        $.each($('#nav-update .permission-input'),function(index,value){
            if(response.data.find(permission => permission.client_user_permission_id == $(value).attr('id').split('-')[1]) != undefined){
                $(value).prop('checked',true);
            }else{
                $(value).prop('checked',false);
            }
        });
    },null);
   
}
function updateUser(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#update-user-name').val();
    let lastname = $('#update-user-lastname').val();
    let username = $('#update-user-username').val();
    let email = $('#update-user-email').val();
    let phone = $('#update-user-phone').val();
    let position = $('#update-user-position').val();
    let color = $('#update-user-color').val();
    let permissions = [];
    
    if(name == null || name == ''){
        $('#update-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        $('#update-user-name').removeClass('is-invalid');
    }
    if(lastname == null || lastname == ''){
        $('#update-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        $('#update-user-lastname').removeClass('is-invalid');
    }
    if(username == null || username == ''){
        $('#update-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        $('#update-user-username').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#update-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del usuario');
        flag = false;
    }else{
        $('#update-user-email').removeClass('is-invalid');
    }
    if(phone == null || phone == ''){
        $('#update-user-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del usuario');
        flag = false;
    }else{
        $('#update-user-phone').removeClass('is-invalid');
    }
    if(position == null || position == ''){
        $('#update-user-position').addClass('is-invalid');
        alertWarning('Debe ingresar el cargo del usuario');
        flag = false;
    }else{
        $('#update-user-position').removeClass('is-invalid');
    }
    if(color == null || color == ''){
        $('#update-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        $('#update-user-color').removeClass('is-invalid');
    }
    $.each($('#nav-update .permission-input'),function(index,value){
        if($(value).is(':checked')){
            permissions.push($(value).attr('id').split('-')[1]);
        }
    });
    if(permissions.length == 0){
        alertWarning('Debe seleccionar al menos un permiso');
        flag = false;
    }
    if(flag){
        $('#update-user-button').prop('disabled', true);
        let DataSend = {
            id: current_user.id,
            name: name,
            lastname: lastname,
            username: username,
            email: email,
            phone: phone,
            color: color,
            position: position,
            permissions: permissions
        };
        PostMethodFunction('/client/users/update',DataSend,null, function(response){
            $('#update-user-button').attr('disabled', false);
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
            //go to update tab
            tabs_view['nav-list-tab'] = false;
            current_user = response.data;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
        }, function(){$('#update-user-button').attr('disabled', false);});
    }

}
function restorePasswordUser(){
    let user_id = $(this).closest('.user-row').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar la contraseña de este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/client/users/restore-password',DataSend,null, function(response){
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
function deleteUser(){
    let user_id = $(this).closest('.user-row').attr('user-id');
    swallMessage(
        'Eliminar'
        , '¿Está seguro de eliminar este usuario?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/client/users/delete',DataSend,null, function(response){
                alertWarning('Usuario eliminado');
                getUsersPage();
            },null);
        }
        , null
    );
}