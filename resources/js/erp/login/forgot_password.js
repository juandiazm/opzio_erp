export function sendForgotPassword(){
    let flag = true;
    const identification = $('#login-identification').val();
    if(identification == null || identification == ''){
        $('#login-identification').addClass('is-invalid');
        alertWarning('Debe ingresar un correo/identificación válida');
        flag = false;
    }else{
        $('#login-identification').removeClass('is-invalid');
    }
    if(flag){
        $('#forgot-password').attr('disabled', true);
        const dataSend = {
            identification: identification
        };
        PostMethodFunction('/admin/forgot-password', dataSend, null, function(){
            swallMessage(
                'Exito',
                'Recibirás un correo con una contraseña temporal',
                'success',
                'Entendido',
                null,
                null,
                null,
                null
            );
        }, function(){ $('#forgot-password').attr('disabled', false); });
    }
}