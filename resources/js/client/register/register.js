$(document).on('click', '#register-btn', register);
$(document).ready(function(){
    getCountries();
});
function getCountries(){
    //get contries from json in public/json/countries.json
    $.getJSON('/json/countries.json', function(data){
        var countries = data;
        var countrySelect = $('#country');
        countrySelect.empty();
        $.each(countries, function(index, country){
            countrySelect.append('<option value="'+country.name+'"'+(country.code=="CO"?' selected':'')+'>'+country.name+'</option>');
        });
        
    });
}
function register(){
    let flag = true;
    let name = $('#name').val();
    let identification_type = $('#identification-type').val();
    let identification = $('#identification').val();
    let email = $('#email').val();
    let country_id = $('#country').val();
    if(name == null || name == ''){
        flag = false;
        $('#name').addClass('is-invalid');
        alertWarning('El nombre es requerido');
    }else{
        $('#name').removeClass('is-invalid');
    }
    if(identification_type == null || identification_type == ''){
        flag = false;
        $('#identification-type').addClass('is-invalid');
        alertWarning('El tipo de identificación es requerido');
    }else{
        $('#identification-type').removeClass('is-invalid');
    }
    if(identification == null || identification == ''){
        flag = false;
        $('#identification').addClass('is-invalid');
        alertWarning('La identificación es requerida');
    }else{
        $('#identification').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        flag = false;
        $('#email').addClass('is-invalid');
        alertWarning('El email es requerido');
    }else{
        $('#email').removeClass('is-invalid');
    }
    if(country_id == null || country_id == ''){
        flag = false;
        $('#country').addClass('is-invalid');
        alertWarning('El país es requerido');
    }else{
        $('#country').removeClass('is-invalid');
    }
    if(flag){
        $('#register-btn').attr('disabled',true);
        let dataSend = {
            name: name,
            identification_type: identification_type,
            identification: identification,
            email: email,
            country_id: country_id
        };
        PostMethodFunction('/client/register',dataSend,null, function(response){
            $('#register-btn').attr('disabled',false);
            swallMessage(
                'Registro exitoso'
                , 'Creamos tu cuenta, ahora puedes iniciar sesión con  tu email y el código que te enviamos'
                , 'success'
                , 'Entendido'
                , null
                , null
                ,function(){
                    ClientRegisterFinished(response);
                }
                , function(){
                    ClientRegisterFinished(response);
                }
            );
        },function(){$('#register-btn').attr('disabled',false);});
    }
}
function ClientRegisterFinished(response){
    if(response.data.userResponse.status == 1){
        swallMessage(
            'Contraseña temporal'
            , '<i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+response.data.userResponse.data.password+'"></i>'+response.data.userResponse.data.password
            , 'info'
            , 'Entendido'
            , null
            , null
            , function(){
                window.location.href = '/';
            }
            , function(){
                window.location.href = '/';
            }
        );
        setTimeout(() => {
            window.location.href = '/';
        }, 3000);
    }else{
        window.location.href = '/';
    }
}
