import { profileState } from './state.js';

export function initializePermissions(){
    if(profileState.permissions.length > 0){
        const result = $.grep(profileState.permissions, function(item){
            return item.user_permission_id === 1;
        });
        if(result.length > 0) getAllUserPermissions();
        else $('#permissions-container').remove();
    }else{
        $('#permissions-container').remove();
    }
}

export function getAllUserPermissions(){
    GetMethodFunction('/admin/users/permissions', null, function(response){
        let html = '';
        $.each(response.data, function(index, value){
            html += '<div class="col-12 col-md-6 permission-input-container d-flex">';
            html += '<input type="checkbox" class="align-self-center form-check-input permission-input" name="permissions[' + value.id + ']" id="permission-' + value.id + '">';
            html += '<label for="permission-' + value.id + '" class="align-self-center permission-label">' + value.name + '</label>';
            html += '</div>';
        });
        $('.permissions-list').html(html);
        $.each($('#nav-update .permission-input'), function(index, value){
            const permissionId = $(value).attr('id').split('-')[1];
            $(value).prop('checked', profileState.permissions.find(permission => permission.user_permission_id == permissionId) !== undefined);
        });
    }, null);
}