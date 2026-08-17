import { providerState } from './state.js';
import { getProvidersPage } from './list.js';
import { getProviderDocuments } from './documents.js';
import { getProviderContacts } from './contacts.js';

export function showCurrentProvider(){
    let currentProvider = providerState.currentProvider;
    $('#sub-nav-contracts').attr('data-contractable-id', currentProvider.id);
    if(window.ContractAssociations) window.ContractAssociations.load('provider', currentProvider.id);
    $('#update-provider-img-container').css('background-image','url("/images/erp/providers/'+currentProvider.photo+'")');
    $('#update-provider-img-container .image-icon').css('display','none');
    $('#update-provider-unique-id').text(currentProvider.unique_id);
    $('#update-provider-state').attr('value', currentProvider.active);
    $('#update-provider-state .toggle-value[value="'+currentProvider.active+'"]').click();
    $('#update-provider-name').val(currentProvider.name);
    $('#update-provider-id-type').val(currentProvider.identification_type).trigger('change');
    $('#update-provider-identification').val(currentProvider.identification);
    if(currentProvider.country == null){ $('#update-provider-country').attr('item-id',''); $('#update-provider-country input').val(''); }
    else { $('#update-provider-country').attr('item-id', currentProvider.country.id); $('#update-provider-country input').val(currentProvider.country.name); }
    $('#update-provider-address').val(currentProvider.address);
    $('#update-provider-phone').val(currentProvider.phone);
    $('#update-provider-email').val(currentProvider.email);
    if(currentProvider.sector == null){ $('#update-provider-sector').attr('item-id',''); $('#update-provider-sector input').val(''); }
    else { $('#update-provider-sector').attr('item-id', currentProvider.sector.id); $('#update-provider-sector input').val(currentProvider.sector.name); }
    $('#update-provider-description').val(currentProvider.description);
    $('#update-provider-img').val('');
    const disabled = currentProvider.deleted_at != null;
    $('#update-provider-name,#update-provider-id-type,#update-provider-identification,#update-provider-country,#update-provider-address,#update-provider-phone,#update-provider-email,#update-provider-sector,#update-provider-description,#update-provider-img').prop('disabled', disabled);
    $('#update-provider-button').toggleClass('d-none', disabled).toggleClass('d-block', !disabled);
    $('#update-provider-delete').toggleClass('d-none', disabled).toggleClass('d-flex', !disabled);
    $('#update-provider-restore').toggleClass('d-flex', disabled).toggleClass('d-none', !disabled);
    $('#provider-documents-add-container').toggleClass('d-none', disabled).toggleClass('d-flex', !disabled);
    $('#add-contact-row').toggleClass('d-none', disabled).toggleClass('d-row', !disabled);
    getProviderDocuments();
    getProviderContacts();
}

export function updateProvider(){
    let state = $('#update-provider-state').attr('value');
    let name = $('#update-provider-name').val();
    let idType = $('#update-provider-id-type').val();
    let identification = $('#update-provider-identification').val();
    let country = $('#update-provider-country').attr('item-id');
    let address = $('#update-provider-address').val();
    let phone = $('#update-provider-phone').val();
    let email = $('#update-provider-email').val();
    let sector = $('#update-provider-sector').attr('item-id');
    let description = $('#update-provider-description').val();
    let flag = true;
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
        $('#update-provider-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id','temporal-form'); dinamicForm.setAttribute('class','d-none');
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+providerState.currentProvider.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+idType+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('#update-provider-name').clone(true)[0]); dinamicForm.appendChild($('#update-provider-identification').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-address').clone(true)[0]); dinamicForm.appendChild($('#update-provider-phone').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-email').clone(true)[0]); dinamicForm.appendChild($('#update-provider-description').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-img').clone(true)[0]); dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm); dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#update-provider-img')[0].files; $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/providers/update',dinamicForm,null,function(){
            $('#update-provider-button').attr('disabled', false); swallMessage('Exito','Proveedor actualizado','success',null,null,3000,null,null); providerState.tabsView['nav-list-tab'] = false;
        },function(){$('#update-provider-button').attr('disabled', false);});
    }
}

export function deleteProvider(){
    swallMessage('Advertencia','¿Está seguro de eliminar este proveedor?','error','Si, eliminar','No',null,function(){
        PostMethodFunction('/admin/providers/delete',{id: providerState.currentProvider.id},null,function(){
            providerState.currentProvider.deleted_at = 'deleted';
            providerState.tabsView['nav-list-tab'] = false;
            if(providerState.currentTab == 'nav-update-tab') showCurrentProvider();
            else getProvidersPage();
        },null);
    },null);
}
export function restoreProvider(){
    swallMessage('Advertencia','¿Está seguro de reactivar este proveedor?','warning','Si, reactivar','No',null,function(){
        PostMethodFunction('/admin/providers/restore',{id: providerState.currentProvider.id},null,function(){
            providerState.currentProvider.deleted_at = null; providerState.tabsView['nav-list-tab'] = false; showCurrentProvider();
        },null);
    },null);
}