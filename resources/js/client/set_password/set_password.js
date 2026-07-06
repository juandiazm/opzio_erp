$(document).on('click', '#set-password-btn',setClientUserPasword);
$(document).ready(function(){
});
function setClientUserPasword(){
    var password = $('#set-password').val();
    var confirm_password = $('#set-confirm-password').val();
    let flag = true;
    if(password == ''){
        $('#set-password').addClass('is-invalid');
        alertWarning('La contraseña no puede estar vacía');
        flag = false;
    }else if(password != confirm_password){
        $('#set-password').addClass('is-invalid');
        $('#set-confirm-password').addClass('is-invalid');
        alertWarning('Las contraseñas no coinciden');
        flag = false;
    }
    if(flag){
        $('#set-password-btn').attr('disabled',true);
        $('#set-password').removeClass('is-invalid');
        $('#set-confirm-password').removeClass('is-invalid');
        var DataSend = {
            password: password
        };
        PostMethodFunction('/client/profile/set-password',DataSend,null, function(response){
            window.location.href = "/client/dashboard";
        },function(){$('#set-password-btn').attr('disabled',false);});
    }
}