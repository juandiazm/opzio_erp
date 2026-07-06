$(document).on('click', '#pay-result-btn', finishedTransactionAction);
var finished_transaction_url = '/client';
var refresh_counter = 0;
$(document).ready(function(){
    getIncomeData();
});
function getIncomeData(){
    var DataSend = {
        unique_id: unique_id
    };
    PostMethodFunction('/client/payments/get-income-payment-data',DataSend,null, function(response){
        //can't process the payment
        $('#payment-response-unlogged-centered').removeClass('d-flex').addClass('d-none');
        $('#pay-result-container').removeClass('d-none').addClass('d-flex');
        if(response.data.payment_state == 0){
            //Payment pending
            $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-spinner fa-spin');
            $('#pay-result-title').html('¡Estamos procesando tu pago!');
            $('#pay-result-description').html('Tu pago está siendo procesado, por favor espera unos minutos');
            $('#pay-result-btn').addClass('d-none');
            if(refresh_counter < 10){
                refresh_counter++;
                setTimeout(() => {
                    getIncomeData();
                }, 10000);
            }
        }else if(response.data.payment_state == 1){
            //Payment approved
            $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-circle-check');
            $('#pay-result-title').html('¡Pago aprobado!');
            $('#pay-result-description').html('Tu pago ha sido aprobado exitosamente');
            finished_transaction_url = '/client';
        }else if(response.data.payment_state == 2){
            //Payment denied
            $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-times');
            $('#pay-result-title').html('¡Pago rechazado!');
            $('#pay-result-description').html('Tu pago ha sido rechazado, por favor intenta nuevamente<br><br><small>'+response.data.payment_message+'</small>');
            finished_transaction_url = '/client/payments/pay/'+response.data.income.unique_id;
        }else{
            //Payment unknown
            $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-times');
            $('#pay-result-title').html('¡Pago desconocido!');
            $('#pay-result-description').html('Ha ocurrido un error desconocido, por favor intenta nuevamente');
            finished_transaction_url = '/client/payments/pay/'+response.data.income.unique_id;
        }
    }, null);
}
function finishedTransactionAction(){
    //chek if url parametters content close_tab
    window.location.href = finished_transaction_url;
}