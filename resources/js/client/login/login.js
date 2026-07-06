$(document).on('click', '#login-btn',loginUser);
$(document).on('click', '#forgot-password', sendForgotPassword);
$(document).ready(function(){
    //check if url has restore-email and restore-code parameter
    let url = new URL(window.location.href);
    let restoreEmail = url.searchParams.get('restore-email');
    let restoreCode = url.searchParams.get('restore-code');
    if(restoreEmail != null && restoreEmail != null){
        $('#login-identification').val(restoreEmail);
        $('#login-password').val(restoreCode);
    }
});
function loginUser(){
    let flag = true;
    let container = $(this).parent();
    let identification = $('#login-identification').val();
    let password = $('#login-password').val();
    if(identification==null || identification == ""){
        container.find('#create-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{
        container.find('#create-user-identification').removeClass('is-invalid');
    }
    if(password == null || password == ""){
        container.find('#create-user-password').addClass('is-invalid');
        alertWarning('Debe ingresar la contraseña');
        flag = false;
    }
    if(flag){
        $('#login-btn').attr('disabled',true);
        let DataSend ={
            identification:identification,
            password:password
        }
        PostMethodFunction('/client/login',DataSend,null, function(response){
            window.location.href = "/client/payments";
        },function(){$('#login-btn').attr('disabled',false);});
    }
}
function sendForgotPassword(){
    let flag = true;
    let email = $('#login-identification').val();
    if(email==null || email == "" || !validateEmail(email)){
        $('#login-identification').addClass('is-invalid');
        alertWarning('Debe ingresar un correo electrónico válido');
        flag = false;
    }else{
        $('#login-identification').removeClass('is-invalid');
    }
    if(flag){
        $('#forgot-password').attr('disabled',true);
        let DataSend ={
            email:email
        }
        PostMethodFunction('/client/forgot-password',DataSend,null, function(response){
            swallMessage(
                'Exito'
                , 'Recibirás un correo con una contraseña temporal'
                , 'success'
                , 'Entendido'
                , null
                , null
                , null
                , null
            );
        },function(){$('#forgot-password').attr('disabled',false);});
    }
}