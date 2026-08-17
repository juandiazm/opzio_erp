import { clientState } from './state.js';
import { getClientDocuments } from './documents.js';
import { getClientLicenses } from './licenses.js';
import { getClientUsers } from './users.js';

export function showCurrentClient(){
    let currentClient = clientState.currentClient;
    $('#update-client-img-container').css('background-image','url("/images/erp/clients/'+currentClient.photo+'")');
    $('#update-client-img-container .image-icon').css('display','none');
    $('#update-client-unique-id').text(currentClient.unique_id);
    $('#update-client-state').attr('value', currentClient.active);
    $('#update-client-state  .toggle-value[value="'+currentClient.active+'"]').click();
    $('#update-client-verification').attr('value', currentClient.verified);
    $('#update-client-verification  .verification-input-icon[value="'+currentClient.verified+'"]').click();
    $('#update-client-name').val(currentClient.name);
    $('#update-client-lastname').val(currentClient.lastname);
    $('#update-client-id-type').val(currentClient.identification_type);
    $('#update-client-identification').val(currentClient.identification);
    if(currentClient.country == null){
        $('#update-client-country').attr('item-id', '');
        $('#update-client-country input').val('');
    }else{
        $('#update-client-country').attr('item-id', currentClient.country_id);
        $('#update-client-country input').val(currentClient.country.name);
    }
    $('#update-client-address').val(currentClient.address);
    $('#update-client-phone').val(currentClient.phone);
    $('#update-client-email').val(currentClient.email);
    if(currentClient.sector == null){
        $('#update-client-sector').attr('item-id', '');
        $('#update-client-sector input').val('');
    }else{
        $('#update-client-sector').attr('item-id', currentClient.sector_id);
        $('#update-client-sector input').val(currentClient.sector.name);
    }
    $('#update-client-value-per-hour').val(currentClient.value_per_hour);
    $('#update-client-description').val(currentClient.description);
    $('#update-client-electronic-invoice').attr('value', currentClient.electronic_invoice);
    $('#update-client-electronic-invoice  .toggle-value[value="'+currentClient.electronic_invoice+'"]').click();
    $('#update-client-img').val('');
    getClientUsers();
    getClientDocuments();
    getClientLicenses();
}

export function updateClient(){
    let verified = $('#update-client-verification').attr('value');
    let state = $('#update-client-state').attr('value');
    let electronicInvoice = $('#update-client-electronic-invoice').attr('value');
    let name = $('#update-client-name').val();
    let idType = $('#update-client-id-type').val();
    let identification = $('#update-client-identification').val();
    let country = $('#update-client-country').attr('item-id');
    let address = $('#update-client-address').val();
    let phone = $('#update-client-phone').val();
    let email = $('#update-client-email').val();
    let sector = $('#update-client-sector').attr('item-id');
    let description = $('#update-client-description').val();
    let flag = true;
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
    if(sector == null || sector == ''){
        $('#create-client-sector').addClass('is-invalid');
        alertWarning('Debe seleccionar un sector');
        flag = false;
    }
    if(flag){
        $('#update-client-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+clientState.currentClient.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="electronic_invoice" value="'+electronicInvoice+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('#update-client-name').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-identification').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-address').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-phone').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-email').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-value-per-hour').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-description').clone(true)[0]);
        dinamicForm.appendChild($('#update-client-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#update-client-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/clients/update', dinamicForm, null, function(response){
            $('#update-client-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Cliente actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            clientState.tabsView['nav-list-tab'] = false;
        }, function(){$('#update-client-button').attr('disabled', false);});
    }
}