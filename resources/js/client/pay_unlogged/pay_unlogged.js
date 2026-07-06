$(document).on('click', '#pay-unlogged-btn', startPaymentProcess);
$(document).on('click', '#pay-result-btn', finishedTransactionAction);
$(document).on('click', '#toggle-items-btn', toggleItems);
$(document).on('change', 'input[name="payment_gateway"]', onGatewayChange);
var processing_payment = false;
var interval = null;
var selected_gateway = 'bold'; // Bold por defecto
$(document).ready(function(){
    let url = new URL(window.location.href);
    let external = url.searchParams.get('external');
    if(external == 'true'){
        swallMessage(
            'Licencia vencida'
            , 'Tu licencia se encuentra vencida, por favor realiza el pago para continuar disfrutando de nuestros servicios'
            , 'warning'
            , 'Entendido'
            , null
            , null
            , null
            , null
        );
    }
    getIncomeData();
    // Inicializar gateway seleccionado
    selected_gateway = $('input[name="payment_gateway"]:checked').val() || 'bold';
});

function onGatewayChange(){
    selected_gateway = $('input[name="payment_gateway"]:checked').val();
}
function getIncomeData(){
    var DataSend = {
        unique_id: income_unique_id
    };
    PostMethodFunction('/client/payments/get-income-data',DataSend,null, function(response){
        if(response.data.able_to_pay == true){
            if(processing_payment == false){
                //can process the pagiment
                $('#pay-unlogged-centered').removeClass('d-none').addClass('d-flex');
                $('#pay-result-container').removeClass('d-flex').addClass('d-none');
                showIncomeData(response.data.income);
            }
        }else{
            if (interval != null) {
                clearInterval(interval);
            }
            //can't process the payment
            $('#pay-unlogged-centered').removeClass('d-flex').addClass('d-none');
            $('#pay-result-container').removeClass('d-none').addClass('d-flex');
            if(response.data.status == 2){
                //Income not fund
                $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-magnifying-glass text-primary');
                $('#pay-result-title').html('Error');
                $('#pay-result-description').html('No encontramos la orden de pago que buscas');
            }else if(response.data.status == 3){
                //Income is currently payed
                $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-circle-check text-secondary');
                $('#pay-result-title').html('¡Buenas noticias!');
                $('#pay-result-description').html('Ya realizaste el pago de esta orden');
             }else if(response.data.status == 4){
               //Income not available for payment
                $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-lock text-primary');
                $('#pay-result-title').html('Error');
                $('#pay-result-description').html('Esta orden de pago no se encuentra disponible para pago');
            }
        }
    }, null);
}
function showIncomeData(income){
    // Información en el header
    $('#client_name').html(income.client_name || 'N/A');
    $('#unique_id_header').html('<i class="fa-regular fa-copy copy-action" data-clipboard-text="'+income_unique_id+'"></i>'+income_unique_id);
    $('#cutoff_date_header').html(income.cutoff_date);
    
    // Total
    $('#total').html('COP $'+income.total);
    
    // Mostrar abonos si existen
    if(income.has_advances){
        $('#advances-container').show();
        $('#balance-container').show();
        $('#no-advances-total').hide();
        $('#total_advances').html('- COP $'+income.total_advances);
        $('#balance_pending').html('COP $'+income.balance_pending);
    }else{
        $('#advances-container').hide();
        $('#balance-container').hide();
        $('#no-advances-total').show();
        $('#total_to_pay').html('COP $'+income.total);
    }
    
    var html = '';
    $.each(income.licenses, function(index, value){
        html += '<li class="license-item">';
        html += '<div class="license-item-header">';
        html += '<span class="license-item-name">'+value.license_name+'</span>';
        html += '<span class="license-item-price">COP $'+value.total+'</span>';
        html += '</div>';
        html += '<div class="license-item-details">';
        html += '<span class="license-item-service">'+value.service_name+'</span>';
        if(value.tax_name != null){
            html += '<span class="license-item-tax">'+value.tax_name+' ('+value.tax_value+'%)</span>';
        }
        html += '</div>';
        html += '</li>';
    });
    $('#licences-list').html(html);
}
function toggleItems(){
    var itemsContainer = $('#items-list-container');
    var toggleBtn = $('#toggle-items-btn');
    
    if(itemsContainer.hasClass('items-list-expanded')){
        itemsContainer.removeClass('items-list-expanded').addClass('items-list-collapsed');
        toggleBtn.html('<i class="fas fa-chevron-down"></i>');
    }else{
        itemsContainer.removeClass('items-list-collapsed').addClass('items-list-expanded');
        toggleBtn.html('<i class="fas fa-chevron-up"></i>');
    }
}
function startPaymentProcess(){
    if(selected_gateway === 'bold'){
        startBoldPayment();
    } else {
        startWompiPayment();
    }
}

function startBoldPayment(){
    var dataSend = {
        unique_id: income_unique_id
    };
    PostMethodFunction('/client/payments/payment-gateway/bold/create', dataSend, null, function(response){
        let data = response.data;
        
        // Crear instancia de BoldCheckout con modo embebido
        var checkout = new BoldCheckout({
            orderId: data.bold.order_id,
            currency: data.bold.currency,
            amount: String(data.bold.amount),
            apiKey: data.bold.api_key,
            integritySignature: data.bold.integrity_signature,
            description: data.bold.description,
            redirectionUrl: data.bold.redirection_url,
            renderMode: 'embedded'  // Modo embebido - el usuario no sale de la página
        });
        
        // Abrir el widget de Bold embebido en un modal/iframe
        checkout.open();
        
        // Con el modo embebido, iniciamos verificación periódica suave sin bloquear la UI
        // Bold redirigirá automáticamente al completar el pago
        startPaymentStatusCheck();
        
    }, null);
}

function startPaymentStatusCheck(){
    // Verificar el estado cada 5 segundos sin bloquear la interfaz
    // Esto permite detectar si el pago se completó sin interrumpir al usuario
    processing_payment = true;
    interval = setInterval(function(){
        getIncomeData();
    }, 5000);
}

function startWompiPayment(){
    var dataSend = {
        unique_id: income_unique_id
    };
    PostMethodFunction('/client/payments/payment-gateway/wompi/create', dataSend, null, function(response){
        let data = response.data;
        let customerData = null;
        if(data.notifications.length>0){
            customerData= {
                email:data.notifications.length==0?null:data.notifications[0].email,
                fullName: data.client_name
            };
        }
        var checkout = new WidgetCheckout({
            currency: data.wompi.currency,
            amountInCents: data.wompi.amount,
            reference: data.wompi.reference,
            publicKey: data.wompi.public_key,
            signature: {integrity : data.wompi.integrity_signature},
            redirectUrl: data.wompi.redirection_url,
            taxInCents: {
                vat: 0,
                consumption: 0
            },
            customerData: customerData,
        });
        checkout.open(function ( result ) {
            showProcessingState();
        });
    }, null);
}

function showProcessingState(){
    processing_payment = true;
    $('#pay-unlogged-centered').removeClass('d-flex').addClass('d-none');
    $('#pay-result-container').removeClass('d-none').addClass('d-flex');
    $('#pay-result-icon').removeAttr('class').addClass('fa-regular fa-spinner fa-spin text-primary');
    $('#pay-result-title').html('Procesando pago');
    $('#pay-result-description').html('Estamos procesando tu pago, por favor espera un momento');
    let counter = 0;
    interval = setInterval(function(){
        if(counter == 3){
            clearInterval(interval);
            location.reload();
        }else{
            counter++;
        }
        getIncomeData();
    }, 3000);
}
function finishedTransactionAction(){
    //chek if url parametters content close_tab
    var url = new URL(window.location.href);
    var close_tab = url.searchParams.get("close_tab");
    if(close_tab == 'true'){
        window.close();
    }else{
        window.location.href = '/client';
    }
}