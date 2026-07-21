$(document).on('click', '#nav-tab .nav-link', changeTab);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('change', '#search-list-input', getUsersPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('click', '.list-update-traceability', function(){
    goToTraceabilityTab('id%'+$(this).parent().parent().attr('user-id'));
});
//////////
$(document).on('click', '#nav-create .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('change', '#nav-create .input-color',loadCreateImageBorder);
$(document).on('click', '#add-button', createUser);
/////////
$(document).on('click', '#nav-update .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('change', '#nav-update .input-color',loadUpdateImageBorder);
$(document).on('click', '#update-button', updateUser);
$(document).on('click', '#update-user-delete',function(){
    deleteUser(current_user.id);
});
$(document).on('click', '#update-user-restore',function(){
    restoreUser(current_user.id);
});
$(document).on('click', '#update-user-go-traceability',function(){
    goToTraceabilityTab('id%'+current_user.id);
});

////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
};
var current_tab = null;
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
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getUsersPage();    
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        getUserNextId();
    }else if(current_tab == 'nav-traceability-tab'){
        if(trought_user && current_user != null){
            trought_user = false;
            $('#nav-traceability').attr('user-id',current_user.id);
        }
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    tabs_view[current_tab] = true;    
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
        if(user_id){
            getUserById(user_id);
        }
    },null);
}
function getUserById(user_id){
    let DataSend = {
        user_id: user_id
    };
    PostMethodFunction('/admin/users/get-by-id',DataSend,null,function(response){
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
        ,search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/users/get-page',DataSend,null, showUsersPage,null);
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
function goToTraceabilityTab(user_id){
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
    $.each(users,function(index,value){
        appendContent += '<tr user-id='+value.id+' class="'+(value.deleted_at==null?'':'deleted')+'">';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-photo text-center image-column">';
                appendContent += '<div class="image-column-container d-blox mx-auto" style="background-image:url(\'/images/erp/users/'+value.photo+'\');border-color:'+value.color+';">';
            appendContent += ' </td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+(value.lastname==null?'':(' '+value.lastname))+'</p></td>';
            appendContent += '<td class="columns-username text-center"><p>'+value.username+'</p></td>';
            appendContent += '<td class="columns-identification text-center"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-email text-left"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                }
                appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#user-list-table #user-list-table-body').empty().append(appendContent);
    DBshowPagination();
}
//Create User functions
function getUserNextId(){
    GetMethodFunction('/admin/users/next-id',null,function(response){
        $('#create-user-id').text(String(response.data).padStart(5, "0"));
        
    },null);
}
function loadCreateImageBorder(){
    var color = $(this).val();
    $(this).parent().parent().parent().find('#create-user-img-container').css('border-color',color);
}
function createUser(){
    let container = $(this).parent();
    let flag = true;
    let identification = container.find('#create-user-identification').val();
    let name = container.find('#create-user-name').val();
    let lastname = container.find('#create-user-lastname').val();
    let username = container.find('#create-user-username').val();
    let email = container.find('#create-user-email').val();
    let password = container.find('#create-user-password').val();
    let password_confirmation = container.find('#create-user-password-confirmation').val();
    let image = container.find('#create-user-img').val();
    let color = container.find('#create-user-color').val();
    let permissions = [];
    if(identification==null || identification == ""){
        container.find('#create-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{
        container.find('#create-user-identification').removeClass('is-invalid');
    }
    if(name==null || name == ""){
        container.find('#create-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        container.find('#create-user-name').removeClass('is-invalid');
    }
    if(lastname==null || lastname==""){
        container.find('#create-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        container.find('#create-user-lastname').removeClass('is-invalid');
    }
    if(username==null || username==""){
        container.find('#create-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        container.find('#create-user-username').removeClass('is-invalid');
    }
    if(email==null || email==""){
        container.find('#create-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo electrónico');
        flag = false;
    }else{
        container.find('#create-user-email').removeClass('is-invalid');
    }
    if(password == null || password == ""){
        container.find('#create-user-password').addClass('is-invalid');
        alertWarning('Debe ingresar la contraseña');
        flag = false;
    }else{
        container.find('#create-user-password').removeClass('is-invalid');
    }
    if(password != password_confirmation){
        container.find('#create-user-password-confirmation').addClass('is-invalid');
        alertWarning('Las contraseñas no coinciden');
        flag = false;
    }else{
        container.find('#create-user-password-confirmation').removeClass('is-invalid');
    }
    if(image == null || image == ""){
        container.find('#create-user-img').addClass('is-invalid');
        alertWarning('Debe seleccionar una imagen');
        flag = false;
    }else{
        container.find('#create-user-img').removeClass('is-invalid');
    }
    if(color==null || color==""){
        container.find('#create-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        container.find('#create-user-color').removeClass('is-invalid');
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
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#add-button').attr('disabled', false);});
    }

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
    $('#update-user-img-container .image_preview').attr('src', '/images/erp/users/'+current_user.photo).css('display','block');
    $('#update-user-img-container .image-icon').css('display','none');
    $('#update-user-color').val(current_user.color);
    $('#update-user-color').change();
    $.each($('#nav-update .permission-input'),function(index,value){
        if(current_user.permissions.find(permission => permission.user_permission_id == $(value).attr('id').split('-')[1]) != undefined){
            $(value).prop('checked',true);
        }else{
            $(value).prop('checked',false);
        }
    });
    if(current_user.deleted_at != null){
        $('#update-user-identification').attr('disabled',true);
        $('#update-user-name').attr('disabled',true);
        $('#update-user-lastname').attr('disabled',true);
        $('#update-user-username').attr('disabled',true);
        $('#update-user-email').attr('disabled',true);
        $('#update-user-img').attr('disabled',true);
        $('#update-user-color').attr('disabled',true);
        $('#nav-update .permission-input').attr('disabled',true);
        $('#update-button').removeClass('d-block').addClass('d-none');
        //
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
        //
        $('#update-user-delete').removeClass('d-none').addClass('d-block');
        $('#update-user-restore').removeClass('d-block').addClass('d-none');
    
    }
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
    let permissions = [];
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
            permissions.push($(value).attr('id').split('-')[1]);
        }
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
function deleteUser(user_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro que desea eliminar este usuario?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/admin/users/delete',DataSend,null, function(response){
                alertSuccess('Usuario eliminado');
                if(current_tab == 'nav-update-tab'){
                    current_user.deleted_at = 'deleted';
                    showCurrentUser();
                }else{
                    getUsersPage();
                }
                tabs_view['nav-list-tab'] = false;
                
            },null);
        }
        , null
    );
}
function restoreUser(user_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro que desea restaurar este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/admin/users/restore',DataSend,null, function(response){
                alertSuccess('Usuario restaurado');
                if(current_tab == 'nav-update-tab'){
                    current_user.deleted_at = null;
                    showCurrentUser();
                }else{
                    getUsersPage();
                }
                tabs_view['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}