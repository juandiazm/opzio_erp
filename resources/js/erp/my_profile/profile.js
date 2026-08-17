import { profileState } from './state.js';

export function showCurrentUser(){
    const user = profileState.currentUser;
    $('#update-user-id').text(String(user.id).padStart(5, '0'));
    $('#update-user-id-input').val(user.id);
    $('#update-user-identification').val(user.identification);
    $('#update-user-name').val(user.name);
    $('#update-user-lastname').val(user.lastname);
    $('#update-user-username').val(user.username);
    $('#update-user-email').val(user.email);
    $('#update-user-img-container .image_preview').attr('src', getCurrentUserPhotoUrl()).css('display', 'block');
    $('#update-user-img-container .image-icon').css('display', 'none');
    $('#update-user-color').val(user.color);
    $('#update-user-color').change();
}

export function loadUpdateImageBorder(){
    const color = $(this).val();
    $(this).parent().parent().parent().find('#update-user-img-container').css('border-color', color);
}

export function getCurrentUserPhotoUrl(){
    return '/images/erp/users/' + profileState.currentUser.photo + '?v=' + Date.now();
}

export function updateHeaderProfile(){
    const user = profileState.currentUser;
    const fullName = [user.name, user.lastname].filter(Boolean).join(' ');
    $('#my-profile-image')
        .attr('src', getCurrentUserPhotoUrl())
        .attr('alt', 'Foto de ' + fullName)
        .css('border-color', user.color);
    $('#my-profile-name').text(fullName);
    $('#my-profile-role').text('Mi perfil');
}