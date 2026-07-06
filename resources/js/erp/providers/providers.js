$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-provider-button', addProvider);
//////////
$(document).on('change', '#pagination-per-page', changePageSize);
$(document).on('click', '#pagination .page-item-number', changePage);
$(document).on('click', '#page-item-back', selectBackPage);
$(document).on('click', '#page-item-next', selectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('change', '#search-list-input', getProvidersPage);
$(document).on('click', '.list-delete-btn', function(){
    let provider_id = $(this).closest('.provider-row-info').attr('provider-id');
    current_provider = providers.find(provider => provider.id == provider_id);
    if(current_provider != null){
        deleteProvider();
    }
});
$(document).on('click', '.list-update-traceability', function(){
    current_provider = providers.find(provider => provider.id == $(this).closest('.provider-row-info').attr('provider-id'));
    goToProvidersTraceability('id%'+current_provider.id);
});
//////////
$(document).on('click', '#update-provider-button', updateProvider);
$(document).on('click', '#update-provider-delete', deleteProvider);
$(document).on('click', '#update-provider-restore', restoreProvider);
$(document).on('click', '#update-provider-go-traceability', function(){
    goToProvidersTraceability('id%'+current_provider.id);
});
//////
$(document).on('click','#add-provider-documens-button', addProviderDocument);
$(document).on('click', '.update-provider-file-btn', updateProviderDocument);
$(document).on('click', '.delete-provider-file-btn', deleteProviderDocument);

/////////
$(document).on('click', '#add-contact', addContact);
$(document).on('click', '.update-contact-btn', updateContact);
$(document).on('click', '.delete-contact-btn', deleteContact);
$(document).on('click', '.restore-contact-btn', restoreContact);
/////////
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var providers = [];
var current_provider = null;
var provider_id = null;
var current_tab = null;
$(document).ready(function(){
    changeTab();
});
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getProvidersPage();    
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    tabs_view[current_tab] = true;    
}
//List Provider Functions
var pagination = {
    page:1,
    per_page:10,
    total:0,
};
function showPagination(){
    var total_pages = Math.ceil(pagination.total/pagination.per_page);
    var AppenedContent = '';
    AppenedContent += '<li class="page-item align-self-center my-0 me-2">';
        AppenedContent += '<select id="pagination-per-page" class="form-select w-auto py-1" aria-label="Default select example">';
            if(pagination.total>5){
                AppenedContent += '<option value="5"'+((pagination.per_page==5)?' selected':'')+'>5</option>';
            }
            if(pagination.total>10){
                AppenedContent += '<option value="10"'+((pagination.per_page==10)?' selected':'')+'>10</option>';
            }
            if(pagination.total>50){
                AppenedContent += '<option value="50"'+((pagination.per_page==50)?' selected':'')+'>50</option>';
            }
            AppenedContent += '<option value="'+pagination.total+'"'+((pagination.per_page==pagination.total)?' selected':'')+'>'+pagination.total+'</option>';
        AppenedContent += '</select>';
    AppenedContent += '</li>';
    if(pagination.page>1){
        AppenedContent += '<li id="page-item-back" class="page-item align-self-center my-0" '+((pagination.page==1)?' disabled':'')+'>';
        AppenedContent += '<p class="page-link my-0" tabindex="-1"><<</p>';
    }
    AppenedContent += '</li>';
    for (let index = 1; index <= total_pages; index++) {
        if(pagination.page==index){
            AppenedContent += '<li class="page-item-number page-item align-self-center my-0 active" text="'+index+'">';
                AppenedContent += '<p class="page-link my-0">'+index+' <span class="sr-only">(current)</span></p>';
            AppenedContent += '</li>';
        }else{
            AppenedContent += '<li class="page-item-number page-item align-self-center my-0" text="'+index+'"><p class="page-link my-0">'+index+'</p></li>';
        }
    }
    if(pagination.page<total_pages){
        AppenedContent += '<li id="page-item-next" class="page-item align-self-center my-0" '+((pagination.page==total_pages)?' disabled':'')+'>';
            AppenedContent += '<p class="page-link my-0">>></p>';
        AppenedContent += '</li>';
    }
    $('#pagination').empty().append(AppenedContent);
}
function changePageSize(){
    pagination.per_page = $('#pagination-per-page').val();
    pagination.page = 1;
    getProvidersPage();
}
function changePage(){
    pagination.page = $(this).attr('text');
    getProvidersPage();
}
function selectBackPage(){
    pagination.page = parseInt(pagination.page)-1;
    getProvidersPage();
}
function selectNextPage(){
    pagination.page = parseInt(pagination.page)+1;
    getProvidersPage();
}
function getProvidersPage(){
    let DataSend = {
        pagination: pagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/providers/get-page',DataSend,null, showProvidersPage,null);
}
function goToUpdateTab(){
    let provider_id = $(this).parent().parent().attr('provider-id');
    current_provider = providers.find(provider => provider.id == provider_id);
    if(current_provider != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentProvider();
    }
}
function showProvidersPage(response){
    pagination = response.pagination;
    providers = response.data;
    let appendContent = '';
    $.each(providers,function(index,value){
        appendContent += '<tr provider-id='+value.id+' class="provider-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-photo text-center image-column">';
                appendContent += '<div class="image-column-container d-blox mx-auto" style="background-image:url(\'/images/erp/providers/'+value.photo+'\');">';
            appendContent += ' </td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'">'+(value.active?'Activo':'Inactivo')+'</p></td>';
            appendContent += '<td class="columns-identification text-left"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+(value.lastname==null?'':(' '+value.lastname))+'</p></td>';
            appendContent += '<td class="columns-phone text-center"><p>'+value.phone+'</p></td>';
            appendContent += '<td class="columns-email text-left email-col" title="'+value.email+'"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#provider-list-table #provider-list-table-body').empty().append(appendContent);
    showPagination();
    
}
//////////////////////////////////////////////////////
function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}
function addProvider(){
    let container = $(this).parent();
    let flag = true;
    let image = $('#create-provider-img').val();
    //let verified = $('#create-provider-verification').attr('value');
    let state = $('#create-provider-state').attr('value');
    let name = $('#create-provider-name').val();
    let id_type = $('#create-provider-id-type').val();
    let identification = $('#create-provider-identification').val();
    let country = $('#create-provider-country').attr('item-id');
    let address = $('#create-provider-address').val();
    let phone = $('#create-provider-phone').val();
    let email = $('#create-provider-email').val();
    let sector = $('#create-provider-sector').attr('item-id');
    let description = $('#create-provider-description').val();
    if(image == null || image == ''){
        $('#create-provider-img').addClass('is-invalid');
        alertWarning('Debe ingresar una imagen');
        flag = false;
    }
    /*if(verified == null || verified == ''){
        $('#create-provider-verification').addClass('is-invalid');
        alertWarning('Debe seleccionar una verificación');
        flag = false;
    }  */
    if(state == null || state == ''){
        $('#create-provider-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }      
    if(name == null || name == ''){
        $('#create-provider-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }
    if(id_type == null || id_type == ''){
        $('#create-provider-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }
    if(identification == null || identification == ''){
        $('#create-provider-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del proveedor');
        flag = false;
    }
    if(country == null || country == ''){
        $('#create-provider-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(address == null || address == ''){
        $('#create-provider-address').addClass('is-invalid');
        alertWarning('Debe ingresar la dirección del proveedor');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-provider-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del proveedor');
        flag = false;
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-provider-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del proveedor');
        flag = false;
    }
    if(sector == null || sector == ''){
        $('#create-provider-sector').addClass('is-invalid');
        alertWarning('Debe seleccionar un sector');
        flag = false;
    }
    if(flag){
        $('#add-provider-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        //dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+id_type+'">')[0]);
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
            $('#create-provider-verification').attr('value', '');
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
            swallMessage(
                'Exito'
                , 'Proveedor creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_provider = response.provider;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            showCurrentProvider();
        }, function(){$('#add-provider-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentProvider(){
    $('#update-provider-img-container').css('background-image','url("/images/erp/providers/'+current_provider.photo+'")');
    $('#update-provider-img-container .image-icon').css('display','none');
    $('#update-provider-unique-id').text(current_provider.unique_id);
    $('#update-provider-state').attr('value', current_provider.active);
    $('#update-provider-state  .toggle-value[value="'+current_provider.active+'"]').click();
    //$('#update-provider-verification').attr('value', current_provider.verified);
    //$('#update-provider-verification  .verification-input-icon[value="'+current_provider.verified+'"]').click();
    $('#update-provider-name').val(current_provider.name);
    $('#update-provider-lastname').val(current_provider.lastname);
    $('#update-provider-id-type').val(current_provider.identification_type);
    $('#update-provider-identification').val(current_provider.identification);
    if(current_provider.country == null){
        $('#update-provider-country').attr('item-id', '');
        $('#update-provider-country input').val('');
    }else{
        $('#update-provider-country').attr('item-id', current_provider.country.id);
        $('#update-provider-country input').val(current_provider.country.name);
    }
    $('#update-provider-address').val(current_provider.address);
    $('#update-provider-phone').val(current_provider.phone);
    $('#update-provider-email').val(current_provider.email);
    if(current_provider.sector == null){
        $('#update-provider-sector').attr('item-id', '');
        $('#update-provider-sector input').val('');
    }else{
        $('#update-provider-sector').attr('item-id', current_provider.sector.id);
        $('#update-provider-sector input').val(current_provider.sector.name);
    }
    
    $('#update-provider-description').val(current_provider.description);
    $('#update-provider-img').val('');
    if(current_provider.deleted_at != null){
        $('#update-provider-name').prop('disabled', true);
        $('#update-provider-lastname').prop('disabled', true);
        $('#update-provider-id-type').prop('disabled', true);
        $('#update-provider-identification').prop('disabled', true);
        $('#update-provider-country').prop('disabled', true);
        $('#update-provider-address').prop('disabled', true);
        $('#update-provider-phone').prop('disabled', true);
        $('#update-provider-email').prop('disabled', true);
        $('#update-provider-sector').prop('disabled', true);
        $('#update-provider-description').prop('disabled', true);
        $('#update-provider-img').prop('disabled', true);
        //
        $('#update-provider-button').removeClass('d-block').addClass('d-none');
        //
        $('#update-provider-delete').removeClass('d-flex').addClass('d-none');
        $('#update-provider-restore').removeClass('d-none').addClass('d-flex');
        //
        $('#provider-documents-add-container').removeClass('d-flex').addClass('d-none');
        //
        $('#add-contact-row').removeClass('d-row').addClass('d-none');
    }else{
        $('#update-provider-name').prop('disabled', false);
        $('#update-provider-lastname').prop('disabled', false);
        $('#update-provider-id-type').prop('disabled', false);
        $('#update-provider-identification').prop('disabled', false);
        $('#update-provider-country').prop('disabled', false);
        $('#update-provider-address').prop('disabled', false);
        $('#update-provider-phone').prop('disabled', false);
        $('#update-provider-email').prop('disabled', false);
        $('#update-provider-sector').prop('disabled', false);
        $('#update-provider-description').prop('disabled', false);
        $('#update-provider-img').prop('disabled', false);
        //
        $('#update-provider-button').removeClass('d-none').addClass('d-block');
        //
        $('#update-provider-delete').removeClass('d-none').addClass('d-flex');
        $('#update-provider-restore').removeClass('d-flex').addClass('d-none'); 
        //
        $('#provider-documents-add-container').removeClass('d-none').addClass('d-flex');
        //
        $('#add-contact-row').removeClass('d-none').addClass('d-row');
    }
    getProviderDocuments();
    getProviderContacts();
}
function updateProvider(){
    let container = $(this).parent();
    let flag = true;
    let image = $('#update-provider-img').val();
    //let verified = $('#update-provider-verification').attr('value');
    let state = $('#update-provider-state').attr('value');
    let name = $('#update-provider-name').val();
    let id_type = $('#update-provider-id-type').val();
    let identification = $('#update-provider-identification').val();
    let country = $('#update-provider-country').attr('item-id');
    let address = $('#update-provider-address').val();
    let phone = $('#update-provider-phone').val();
    let email = $('#update-provider-email').val();
    let sector = $('#update-provider-sector').attr('item-id');
    let description = $('#update-provider-description').val();
    /*if(verified == null || verified == ''){
        $('#create-provider-verification').addClass('is-invalid');
        alertWarning('Debe seleccionar una verificación');
        flag = false;
    }*/  
    if(state == null || state == ''){
        $('#create-provider-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }      
    if(name == null || name == ''){
        $('#create-provider-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }
    if(id_type == null || id_type == ''){
        $('#create-provider-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }
    if(identification == null || identification == ''){
        $('#create-provider-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del proveedor');
        flag = false;
    }
    if(country == null || country == ''){
        $('#create-provider-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(address == null || address == ''){
        $('#create-provider-address').addClass('is-invalid');
        alertWarning('Debe ingresar la dirección del proveedor');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-provider-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del proveedor');
        flag = false;
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-provider-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del proveedor');
        flag = false;
    }
    if(sector == null || sector == ''){
        $('#create-provider-sector').addClass('is-invalid');
        alertWarning('Debe seleccionar un sector');
        flag = false;
    }
    if(flag){
        $('#update-provider-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+current_provider.id+'">')[0]);
        //dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+id_type+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('#update-provider-name').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-identification').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-address').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-phone').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-email').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-description').clone(true)[0]);
        dinamicForm.appendChild($('#update-provider-img').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#update-provider-img')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/providers/update', dinamicForm, null, function(response){
            $('#update-provider-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Proveedor actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#update-provider-button').attr('disabled', false);});
    }  
}
function deleteProvider(){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este proveedor?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: current_provider.id
            };
            PostMethodFunction('/admin/providers/delete',DataSend,null, function(response){
                current_provider.deleted_at = 'deleted';
                tabs_view['nav-list-tab'] = false;
                if(current_tab == 'nav-update-tab'){
                    showCurrentProvider();
                }else if(current_tab == 'nav-list-tab'){
                    getProvidersPage();
                }
            },null);
        }
        , null
    );
}
function restoreProvider(){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de reactivar este proveedor?'
        , 'warning'
        , 'Si, reactivar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: current_provider.id
            };
            PostMethodFunction('/admin/providers/restore',DataSend,null, function(response){
                current_provider.deleted_at = null;
                tabs_view['nav-list-tab'] = false;
                showCurrentProvider();
            },null);
        }
        , null
    );
}
function addProviderDocument(){
    let container = $(this).parent();
    let name = container.find('.provider-document-input-name').val();
    let file = container.find('.provider-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.provider-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.provider-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-provider-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="provider_id" value="'+current_provider.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.provider-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.provider-document-input-file')[0].files =  container.find('.provider-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/providers/documents/add', dinamicForm, null, function(response){
            $('#add-provider-documens-button').attr('disabled', false);
            container.find('.provider-document-input-name').val('');
            container.find('.provider-document-input-file').val('');
            swallMessage(
                'Exito'
                , 'Documento agregado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getProviderDocuments();
        }, function(){$('#add-provider-documens-button').attr('disabled', false);});
    }
}
function getProviderDocuments(){
    let DataSend = {
        provider_id: current_provider.id
    };
    PostMethodFunction('/admin/providers/documents/get',DataSend,null, showProviderDocuments,null);
}
function showProviderDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="provider-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="provider-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                if(current_provider.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-pen-to-square update-provider-file-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-provider-file-btn"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#provider-documents-table #provider-documents-table-body').empty().append(appendContent);
}
function updateProviderDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.provider-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.provider-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let DataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/admin/providers/documents/update',DataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}
function deleteProviderDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este documento?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: id,
            };
            PostMethodFunction('/admin/providers/documents/delete',DataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
    
}
//Tolss
function addContact(){
    let container = $(this).parent().parent();
    let flag = true;
    let name = container.find('.contact-name').val();
    let email = container.find('.contact-email').val();
    let phone = container.find('.contact-phone').val();
    let position = container.find('.contact-position').val();
    if(name == null || name == ""){
        container.find('.contact-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del contacto');
        flag = false;
    }else{
        container.find('.contact-name').removeClass('is-invalid');
    }
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.contact-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del contacto');
        flag = false;
    }else{
        container.find('.contact-email').removeClass('is-invalid');
    }
    if(flag){
        $('#add-contact').attr('disabled',true);
        let DataSend = {
            provider_id: current_provider.id,
            name: name,
            email: email,
            phone: phone,
            position: position,
        };
        PostMethodFunction('/admin/providers/contacts/add',DataSend,null,function(response){
            $('#add-contact').attr('disabled', false);
            //restore inputs
            container.find('.contact-name').val('');
            container.find('.contact-email').val('');
            container.find('.contact-phone').val('');
            container.find('.contact-position').val('');
            ////////////////////
            swallMessage(
                'Exito'
                , 'Contacto creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            ////////////////////
            getProviderContacts();
        },null);
    }
}
function getProviderContacts(){
    let DataSend = {
        provider_id: current_provider.id
    };
    PostMethodFunction('/admin/providers/contacts/get',DataSend,null, showServiceContacts,null);
}
function showServiceContacts(response){
    let contacts = response.data;
    let appendContent = '';
    $.each(contacts,function(index,value){
        appendContent += '<tr contact-id='+value.id+' class="update-contact-row'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="text-left"><p><input type="text" class="text-center form-control align-self-center contact-name" placeholder="Nombre" value="'+value.name+'"></p></td>';
            appendContent += '<td class="text-center"><p><input type="email" class="text-center form-control align-self-center contact-email" placeholder="100000" value="'+value.email+'"></p></td>';
            appendContent += '<td class="text-center"><p><input type="number" class="text-center form-control align-self-center contact-phone" placeholder="100000" value="'+value.phone+'"></p></td>';
            appendContent += '<td class="text-center"><p><input type="text" class="text-center form-control align-self-center contact-position" placeholder="100000" value="'+value.position+'"></p></td>';
            appendContent += '<td class="text-center action-cell">';
                if(current_provider.deleted_at == null){
                    if(value.deleted_at == null){
                        appendContent += '<i class="fa-solid fa-pen-to-square update-contact-btn"></i>';
                        appendContent += '<i class="fa-solid fa-trash-can delete-contact-btn"></i>';
                    }else{
                        appendContent += '<i class="fa-solid fa-trash-arrow-up restore-contact-btn"></i>';
                    }
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#contacts-table tbody .update-contact-row').remove();
    $('#contacts-table tbody').append(appendContent);
    //click on state
    $('#contacts-table tbody .update-contact-row .contact-active').each(function(){
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
}
function updateContact(){
    let update_btn = $(this);
    let container = update_btn.parent().parent();
    let flag = true;
    let contact_id = container.attr('contact-id');
    let name = container.find('.contact-name').val();
    let email = container.find('.contact-email').val();
    let phone = container.find('.contact-phone').val();
    let position = container.find('.contact-position').val();
    if(name == null || name == ""){
        container.find('.contact-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del contacto');
        flag = false;
    }else{
        container.find('.contact-name').removeClass('is-invalid');
    }
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.contact-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del contacto');
        flag = false;
    }else{
        container.find('.contact-email').removeClass('is-invalid');
    }
    if(flag){
        update_btn.attr('disabled',true);
        let DataSend = {
            id: contact_id,
            name: name,
            email: email,
            phone: phone,
            position: position,
        };
        PostMethodFunction('/admin/providers/contacts/update',DataSend,null,function(response){
            update_btn.attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Contacto actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
        },null);
    }
}
function deleteContact(){
    let delete_btn = $(this);
    let container = delete_btn.parent().parent();
    let contact_id = container.attr('contact-id');
    swallMessage(
        '¿Seguro desea eliminar este contacto?'
        , 'Tenga en cuenta que aunque el contacto ya no estará disponible, todo su historial de trazabilidad en la aplicación se conservará.'
        , 'error'
        , 'Si, Eliminar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                id: contact_id
            };
            PostMethodFunction('/admin/providers/contacts/delete',DataSend,null, function(response){
                swallMessage(
                    'Exito'
                    , 'Contacto eliminado'
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                ////////////////////
                getProviderContacts();
            },null);
        }
        , null
    );
}
function restoreContact(){
    let restore_btn = $(this);
    let container = restore_btn.parent().parent();
    let contact_id = container.attr('contact-id');
    swallMessage(
        '¿Seguro desea reactivar este contacto?'
        , 'Tenga en cuenta que el contacto volverá a tener acceso a la aplicación.'
        , 'warning'
        , 'Si, Reactivar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                id: contact_id
            };
            PostMethodFunction('/admin/providers/contacts/restore',DataSend,null,function(response){
                swallMessage(
                    'Contacto REACTIVADO con éxito'
                    , null
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                ////////////////////
                getProviderContacts();
            },null);
        }
        , null
    );
}
function goToProvidersTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}