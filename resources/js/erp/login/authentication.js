export function loginUser(){
    let flag = true;
    const container = $(this).parent();
    const identification = $('#login-identification').val();
    const password = $('#login-password').val();
    if(identification == null || identification == ''){
        container.find('#create-user-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del usuario');
        flag = false;
    }else{
        container.find('#create-user-identification').removeClass('is-invalid');
    }
    if(password == null || password == ''){
        container.find('#create-user-password').addClass('is-invalid');
        alertWarning('Debe ingresar la contraseña');
        flag = false;
    }
    if(flag){
        $('#login-btn').attr('disabled', true);
        const dataSend = {
            identification: identification,
            password: password
        };
        PostMethodFunction('/admin/login', dataSend, null, function(){
            window.location.href = '/admin/dashboard';
        }, function(){ $('#login-btn').attr('disabled', false); });
    }
}