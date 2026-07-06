$(document).on('click', '#unsubscribe', unsubscribe);
$(document).on('click', '#cancel', cancel);
$(document).ready(function(){
});
function unsubscribe(){
    $('#unsubscribe').attr('disabled', true);
    var dataSend = {
        unique_id: unique_id
        ,unsubscribe_reason: $('#unsubscribe_reason').val()
    };
    PostMethodFunction('/api/blog/unsubscribe',dataSend,null, function(response){
        $('#unsubscribe').attr('disabled', false);
        swallMessage(
            'Exito'
            , 'Gracias por ser parte de nosotros, esperamos verte pronto'
            , 'success'
            , 'Ok'
            , null
            , null
            , function(){
                location.href = home_url;
            }
            , function(){
                location.href = home_url;
            }
        );
    }, function(){
        $('#unsubscribe').attr('disabled', false);
        //location.href = home_url;
    });
}
function cancel(){
    $('#cancel').attr('disabled', true);
    swallMessage(
        'Exito'
        , 'Gracias por seguir con nosotros'
        , 'success'
        , null
        , null
        , 3000
        , null
        , function(){
            location.href = home_url;
        }
    );
}
