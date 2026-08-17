import { clientState } from './state.js';

export function addClient(button, onCreated){
    let container = $(button).parent();
    let flag = true;
    let image = $('#create-client-img').val();
    let verified = $('#create-client-verification').attr('value');
    let state = $('#create-client-state').attr('value');
    let electronicInvoice = $('#create-client-electronic-invoice').attr('value');
    let name = $('#create-client-name').val();
    let idType = $('#create-client-id-type').val();
    let identification = $('#create-client-identification').val();
    let country = $('#create-client-country').attr('item-id');
    let address = $('#create-client-address').val();
    let phone = $('#create-client-phone').val();
    let email = $('#create-client-email').val();
    let sector = $('#create-client-sector').attr('item-id');
    let valuePerHour = $('#create-client-value-per-hour').val();
    let description = $('#create-client-description').val();
    if(image == null || image == ''){
        $('#create-client-img').addClass('is-invalid');
        alertWarning('Debe ingresar una imagen');
        flag = false;
    }
    if(verified == null || verified == ''){
        $('#create-client-verification').addClass('is-invalid');
        alertWarning('Debe seleccionar una verificación');
        flag = false;
    }
    if(state == null || state == ''){
        $('#create-client-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }
    if(name == null || name == ''){
        $('#create-client-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del cliente');
        flag = false;
    }
    if(idType == null || idType == ''){
        $('#create-client-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }
    if(identification == null || identification == ''){
        $('#create-client-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del cliente');
        flag = false;
    }
    country = 1;
    if(country == null || country == ''){
        $('#create-client-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(address == null || address == ''){
        $('#create-client-address').addClass('is-invalid');
        alertWarning('Debe ingresar la dirección del cliente');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-client-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del cliente');
        flag = false;
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-client-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del cliente');
        flag = false;
    }
    sector = 1;
    if(sector == null || sector == ''){
        $('#create-client-sector').addClass('is-invalid');
        alertWarning('Debe seleccionar un sector');
        flag = false;
    }
    if(valuePerHour == null || valuePerHour == ''){
        $('#create-client-value-per-hour').addClass('is-invalid');
        alertWarning('Debe ingresar el valor por hora del cliente');
        flag = false;
    }
    if(flag){
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="electronic_invoice" value="'+electronicInvoice+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('#create-client-name').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-identification').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-address').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-phone').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-email').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-description').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-value-per-hour').clone(true)[0]);
        dinamicForm.appendChild($('#create-client-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#create-client-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/clients/add', dinamicForm, null, function(response){
            $('#add-client-button').attr('disabled', false);
            $(container).find('.image_preview').css('display', 'inline-block');
            $(container).find('.image-container').css('background-image', 'none');
            $(container).find('.image-icon').css('display', 'inline-block');
            $('#create-client-img').val('');
            $('#create-client-verification').attr('value', '');
            $('#create-client-state').attr('value', '');
            $('#create-client-name').val('');
            $('#create-client-id-type').val('0');
            $('#create-client-identification').val('');
            $('#create-client-country').val('CO');
            $('#create-client-address').val('');
            $('#create-client-phone').val('');
            $('#create-client-email').val('');
            $('#create-client-sector').val('1');
            $('#create-client-description').val('');
            swallMessage(
                'Exito'
                , 'Cliente creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            clientState.tabsView['nav-list-tab'] = false;
            clientState.currentClient = response.client;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            onCreated();
        }, function(){$('#add-client-button').attr('disabled', false);});
    }
}