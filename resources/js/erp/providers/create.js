import { providerState } from './state.js';

export function addProvider(button, onCreated){
    let container = $(button).parent();
    let flag = true;
    let image = $('#create-provider-img').val();
    let state = $('#create-provider-state').attr('value');
    let name = $('#create-provider-name').val();
    let idType = $('#create-provider-id-type').val();
    let identification = $('#create-provider-identification').val();
    let country = $('#create-provider-country').attr('item-id');
    let address = $('#create-provider-address').val();
    let phone = $('#create-provider-phone').val();
    let email = $('#create-provider-email').val();
    let sector = $('#create-provider-sector').attr('item-id');
    let description = $('#create-provider-description').val();
    if(image == null || image == ''){ $('#create-provider-img').addClass('is-invalid'); alertWarning('Debe ingresar una imagen'); flag = false; }
    if(state == null || state == ''){ $('#create-provider-state').addClass('is-invalid'); alertWarning('Debe seleccionar un estado'); flag = false; }
    if(name == null || name == ''){ $('#create-provider-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del proveedor'); flag = false; }
    if(idType == null || idType == ''){ $('#create-provider-id-type').addClass('is-invalid'); alertWarning('Debe seleccionar un tipo de identificación'); flag = false; }
    if(identification == null || identification == ''){ $('#create-provider-identification').addClass('is-invalid'); alertWarning('Debe ingresar la identificación del proveedor'); flag = false; }
    if(country == null || country == ''){ $('#create-provider-country').addClass('is-invalid'); alertWarning('Debe seleccionar un país'); flag = false; }
    if(address == null || address == ''){ $('#create-provider-address').addClass('is-invalid'); alertWarning('Debe ingresar la dirección del proveedor'); flag = false; }
    if(phone == null || phone == ''){ $('#create-provider-phone').addClass('is-invalid'); alertWarning('Debe ingresar el teléfono del proveedor'); flag = false; }
    if(email == null || email == '' || !validateEmail(email)){ $('#create-provider-email').addClass('is-invalid'); alertWarning('Debe ingresar el correo del proveedor'); flag = false; }
    if(sector == null || sector == ''){ $('#create-provider-sector').addClass('is-invalid'); alertWarning('Debe seleccionar un sector'); flag = false; }
    if(flag){
        $('#add-provider-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('#create-provider-name').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-identification').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-address').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-phone').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-email').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-description').clone(true)[0]);
        dinamicForm.appendChild($('#create-provider-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#create-provider-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/providers/add', dinamicForm, null, function(response){
            $('#add-provider-button').attr('disabled', false);
            $(container).find('.image_preview').css('display', 'inline-block');
            $(container).find('.image-container').css('background-image', 'none');
            $(container).find('.image-icon').css('display', 'inline-block');
            $('#create-provider-img').val('');
            $('#create-provider-state').attr('value', '');
            $('#create-provider-name').val('');
            $('#create-provider-id-type').val('0');
            $('#create-provider-identification').val('');
            $('#create-provider-country').val('CO');
            $('#create-provider-address').val('');
            $('#create-provider-phone').val('');
            $('#create-provider-email').val('');
            $('#create-provider-sector').val('1');
            $('#create-provider-description').val('');
            swallMessage('Exito', 'Proveedor creado', 'success', null, null, 3000, null, null);
            providerState.tabsView['nav-list-tab'] = false;
            providerState.currentProvider = response.provider;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            onCreated();
        }, function(){$('#add-provider-button').attr('disabled', false);});
    }
}