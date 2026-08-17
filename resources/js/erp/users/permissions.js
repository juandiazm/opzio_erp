import { userState } from './state.js';

export function getAllUserPermissions(onUserLoaded){
    GetMethodFunction('/admin/users/permissions',null,function(response){
        let appendData = '';
        $.each(response.data,function(index,value){
            appendData += '<div class="col-12 col-md-6 permission-input-container d-flex">';
                appendData += '<input type="checkbox" class="align-self-center form-check-input permission-input" name="permissions['+value.id+']" id="permission-'+value.id+'">';
                appendData += '<label for="permission-'+value.id+'" class="align-self-center permission-label">'+value.name+'</label>';
            appendData += '</div>';
        });
        $('.permissions-list').html(appendData);
        if(userState.userId){
            getUserById(userState.userId, onUserLoaded);
        }
    },null);
}

export function getUserById(userId, onLoaded){
    let dataSend = {
        user_id: userId
    };
    PostMethodFunction('/admin/users/get-by-id',dataSend,null,function(response){
        userState.currentUser = response.data;
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    },null);
}