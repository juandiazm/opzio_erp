$(document).on('click', '#reset-password-btn',setAdminUserPasword);
$(document).ready(function(){
});
function setAdminUserPasword(){
    var password = $('#reset-password').val();
    var confirm_password = $('#set-confirm-password').val();
    let flag = true;
    if(password == ''){
        $('#reset-password').addClass('is-invalid');
        alertWarning('La contraseña no puede estar vacía');
        flag = false;
    }else if(password != confirm_password){
        $('#reset-password').addClass('is-invalid');
        $('#set-confirm-password').addClass('is-invalid');
        alertWarning('Las contraseñas no coinciden');
        flag = false;
    }
    if(flag){
        $('#reset-password-btn').attr('disabled',true);
        $('#reset-password').removeClass('is-invalid');
        $('#set-confirm-password').removeClass('is-invalid');
        var DataSend = {
            password: password
        };
        PostMethodFunction('/admin/reset-password',DataSend,null, function(response){
            window.location.href = "/admin";
        },function(){$('#reset-password-btn').attr('disabled',false);});
    }
}