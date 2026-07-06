$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-client-button', addClient);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getClientsPage();
});
//USERS
$(document).on('click', '#add-client-user-button', addClientUser);
$(document).on('click', '.restore-client-user-password-btn', restoreClientUserPassword);
$(document).on('click', '.delete-client-user-btn', deleteClientUser);
$(document).on('click', '.restore-client-user-btn', restoreClientUser);
$(document).on('click', '.list-client-user-traceability', goToUserTraceability);
//////////
$(document).on('click', '#update-client-button', updateClient);
$(document).on('click','#add-client-documens-button', addClientDocument);
$(document).on('click', '.update-client-file-btn', updateClientDocument);
$(document).on('click', '.delete-client-file-btn', deleteClientDocument);
//////////
$(document).on('click', '.go-to-license-btn', goToLicense);
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var clients = [];
var current_client = null;
var client_id = null;
$(document).ready(function(){
    changeTab();
});
function changeTab(){
    let tab = $('#nav-tab .active').attr('id');
    if(tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[tab]==false && tab == 'nav-list-tab'){
        getClientsPage();    
    }else if(tabs_view[tab]==false && tab == 'nav-create-tab'){
        
    }else if(tabs_view[tab]==false && tab == 'nav-traceability-tab'){
    }else if(tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    tabs_view[tab] = true;    
}
//List Client Functions
var db_pagination = {
    page:1,
    per_page:10,
    total:0,
};
function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
	paginationContainer.empty();
	
		let AppenedContent = '';
        AppenedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
		
        let closePage = null;
        let showPageSize = 3;
        let dots = {left: false, right: false};
        for (let index = 1; index <= db_pagination.totalPages; index++) {
        closePage = Math.abs(db_pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.left = true;
            }
        }else if(index >= db_pagination.totalPages - 2){
            if(!dots.right){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.right = true;
            }
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
        }
		
		AppenedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
        //page size
        if(db_pagination.total>5){
            AppenedContent += '<li class="page-item">';
                AppenedContent += "<p class='page-link'>";
                    AppenedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
                        AppenedContent += '<option value="5"'+((db_pagination.per_page==5)?' selected':'')+'>5</option>';
                        AppenedContent += '<option value="10"'+((db_pagination.per_page==10)?' selected':'')+'>10</option>';
                        AppenedContent += '<option value="50"'+((db_pagination.per_page==50)?' selected':'')+'>50</option>';
                    AppenedContent += '</select>';
                    AppenedContent += "</p>";
            AppenedContent += '</li>';
        }
		paginationContainer.append(AppenedContent);
	
}
function DBchangePageSize(){
    db_pagination.per_page = $('#db-pagination-per-page').val();
    db_pagination.page = 1;
    getClientsPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getClientsPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getClientsPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getClientsPage();
    }
}
function getClientsPage(){
    let DataSend = {
        pagination: db_pagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/clients/get-page',DataSend,null, showClientsPage,null);
}
function goToUpdateTab(){
    let client_id = $(this).parent().parent().attr('client-id');
    current_client = clients.find(client => client.id == client_id);
    if(current_client != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentClient();
    }
}
function showClientsPage(response){
    db_pagination = response.pagination;
    clients = response.data;
    let appendContent = '';
    $.each(clients,function(index,value){
        appendContent += '<tr client-id='+value.id+'>';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-logo text-center image-column">';
                appendContent += '<div class="image-column-container d-blox mx-auto" style="background-image:url(\'/'+(value.photo_path)+'\');">';
            appendContent += ' </td>';
            appendContent += '<td class="columns-identification text-left"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+(value.lastname==null?'':(' '+value.lastname))+'</p></td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'">'+(value.active?'Activo':'Inactivo')+'</p></td>';
            appendContent += '<td class="columns-phone text-center"><p>'+(value.phone==null?'':value.phone)+'</p></td>';
            appendContent += '<td class="columns-email text-left email-col" title="'+value.email+'"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-license text-center"><p>'+value.licenses_count+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                //appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-list-table #client-list-table-body').empty().append(appendContent);
    DBshowPagination();
    //go to update tab with first client
    /*if(clients.length>0){
        current_client = clients[0];
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentClient();
    }*/
}
//////////////////////////////////////////////////////
function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}
function addClient(){
    let container = $(this).parent();
    let flag = true;
    let image = $('#create-client-img').val();
    let verified = $('#create-client-verification').attr('value');
    let state = $('#create-client-state').attr('value');
    let electronic_invoice = $('#create-client-electronic-invoice').attr('value');
    let name = $('#create-client-name').val();
    let id_type = $('#create-client-id-type').val();
    let identification = $('#create-client-identification').val();
    let country = $('#create-client-country').attr('item-id');
    let address = $('#create-client-address').val();
    let phone = $('#create-client-phone').val();
    let email = $('#create-client-email').val();
    let sector = $('#create-client-sector').attr('item-id');
    let value_per_hour = $('#create-client-value-per-hour').val();
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
    if(id_type == null || id_type == ''){
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
    if(value_per_hour == null || value_per_hour == ''){
        $('#create-client-value-per-hour').addClass('is-invalid');
        alertWarning('Debe ingresar el valor por hora del cliente');
        flag = false;
    }
    if(flag){
        //$('#add-client-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="electronic_invoice" value="'+electronic_invoice+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+id_type+'">')[0]);
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
            tabs_view['nav-list-tab'] = false;
            current_client = response.client;
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            showCurrentClient();
        }, function(){$('#add-client-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentClient(){
    $('#update-client-img-container').css('background-image','url("/images/erp/clients/'+current_client.photo+'")');
    $('#update-client-img-container .image-icon').css('display','none');
    $('#update-client-unique-id').text(current_client.unique_id);
    $('#update-client-state').attr('value', current_client.active);
    $('#update-client-state  .toggle-value[value="'+current_client.active+'"]').click();
    $('#update-client-verification').attr('value', current_client.verified);
    $('#update-client-verification  .verification-input-icon[value="'+current_client.verified+'"]').click();
    $('#update-client-name').val(current_client.name);
    $('#update-client-lastname').val(current_client.lastname);
    $('#update-client-id-type').val(current_client.identification_type);
    $('#update-client-identification').val(current_client.identification);
    if(current_client.country == null){
        $('#update-client-country').attr('item-id', '');
        $('#update-client-country input').val('');
    }else{
        $('#update-client-country').attr('item-id', current_client.country_id);
        $('#update-client-country input').val(current_client.country.name);
    }
    
    $('#update-client-address').val(current_client.address);
    $('#update-client-phone').val(current_client.phone);
    $('#update-client-email').val(current_client.email);
    if(current_client.sector == null){
        $('#update-client-sector').attr('item-id', '');
        $('#update-client-sector input').val('');
    }else{
        $('#update-client-sector').attr('item-id', current_client.sector_id);
        $('#update-client-sector input').val(current_client.sector.name);
    }
    $('#update-client-value-per-hour').val(current_client.value_per_hour);
    $('#update-client-description').val(current_client.description);
    $('#update-client-electronic-invoice').attr('value', current_client.electronic_invoice);
    $('#update-client-electronic-invoice  .toggle-value[value="'+current_client.electronic_invoice+'"]').click();
    $('#update-client-img').val('');
    //get extra information
    getClientUsers();
    getClientDocuments();
    getClientLicenses();

    
}
function updateClient(){
    let container = $(this).parent();
    let flag = true;
    let image = $('#update-client-img').val();
    let verified = $('#update-client-verification').attr('value');
    let state = $('#update-client-state').attr('value');
    let electronic_invoice = $('#update-client-electronic-invoice').attr('value');
    let name = $('#update-client-name').val();
    let id_type = $('#update-client-id-type').val();
    let identification = $('#update-client-identification').val();
    let country = $('#update-client-country').attr('item-id');
    let address = $('#update-client-address').val();
    let phone = $('#update-client-phone').val();
    let email = $('#update-client-email').val();
    let sector = $('#update-client-sector').attr('item-id');
    let description = $('#update-client-description').val();
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
    if(id_type == null || id_type == ''){
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
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+current_client.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="verified" value="'+verified+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="electronic_invoice" value="'+electronic_invoice+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+id_type+'">')[0]);
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
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#update-client-button').attr('disabled', false);});
    }  
}
function addClientDocument(e) {
  e.preventDefault();
  const container = $('#client-documents-add-container');
  const fileInput = container.find('.client-document-input-file')[0];
  const files = fileInput.files;
  if (!files || files.length === 0) {
    container.find('.client-document-input-file').addClass('is-invalid');
    alertWarning('Debe seleccionar al menos un documento');
    return;
  }

  // Deshabilita botón mientras sube todo
  const btn = $('#add-client-documens-button').prop('disabled', true);
  let completed = 0;

  // Por cada archivo, enviamos una petición separada
  Array.from(files).forEach(file => {
    const publicName = file.name;
    // Creamos un form dinámico
    const dinamicForm = document.createElement('form');
    dinamicForm.classList.add('d-none');

    // hidden client_id
    const inputClient = document.createElement('input');
    inputClient.type = 'hidden';
    inputClient.name = 'client_id';
    inputClient.value = current_client.id;
    dinamicForm.appendChild(inputClient);

    // hidden name (nombre público)
    const inputName = document.createElement('input');
    inputName.type = 'hidden';
    inputName.name = 'name';
    inputName.value = publicName;
    dinamicForm.appendChild(inputName);

    // file input con un solo archivo
    const inputFile = document.createElement('input');
    inputFile.type = 'file';
    inputFile.name = 'file';
    // usamos DataTransfer para asignar el File al input
    const dt = new DataTransfer();
    dt.items.add(file);
    inputFile.files = dt.files;
    dinamicForm.appendChild(inputFile);

    // token CSRF
    const csrf = $('input[name="_token"]')[0].cloneNode();
    dinamicForm.appendChild(csrf);

    document.body.appendChild(dinamicForm);

    // Enviar
    PostMethodMultimediaFunction(
      '/admin/clients/documents/add',
      $(dinamicForm),
      null,
      function(response) {
        completed++;
        // si quieres procesar cada respuesta, hazlo aquí
        if (completed === files.length) {
          btn.prop('disabled', false);
          container.find('.client-document-input-file').val('');
          swallMessage('Éxito', 'Todos los documentos se han agregado', 'success', null, null, 3000);
          getClientDocuments();
        }
      },
      function() {
        completed++;
        if (completed === files.length) {
          btn.prop('disabled', false);
          swallMessage('Error', 'Hubo un problema al subir alguno de los documentos', 'error');
        }
      }
    );

    // limpiamos el form temporal
    document.body.removeChild(dinamicForm);
  });
}

function getClientDocuments(){
    let DataSend = {
        client_id: current_client.id
    };
    PostMethodFunction('/admin/clients/documents/get',DataSend,null, showClientDocuments,null);
}
function showClientDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="client-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="client-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-client-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-client-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-documents-table #client-documents-table-body').empty().append(appendContent);
}
function updateClientDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.client-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.client-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let DataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/admin/clients/documents/update',DataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}
function deleteClientDocument(){
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
            PostMethodFunction('/admin/clients/documents/delete',DataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
    
}
//Licenses
function getClientLicenses(){
    let DataSend = {
        client_id: current_client.id
    };
    PostMethodFunction('/admin/clients/licenses/get-by-client-id',DataSend,null, showClientLicenses,null);
}
function showClientLicenses(response){
    let appendContent = '';
    $.each(response.licenses,function(index,value){
        appendContent += '<tr class="client-license-row" license-id="'+value.id+'">';
            appendContent += '<td class="text-left"><p class="client-license-input-serivice=name align-self-end input-value">'+value.service.name+'</p></td>';
            appendContent += '<td class="text-left"><p class="client-license-input-name align-self-end input-value">'+value.name+'</p></td>';
            appendContent += '<td class="text-end action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square go-to-license-btn"></i>';
                appendContent += '<i class="fa-solid fa-bars-progress traceability-employee-license-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-licenses-table #client-licenses-table-body').empty().append(appendContent);
}
function goToLicense(){
    let license_id = $(this).parent().parent().attr('license-id');
    window.location.href = '/admin/licenses?license_id='+license_id;
}
//Users
function addClientUser(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#create-client-user-name').val();
    let lastname = $('#create-client-user-lastname').val();
    let username = $('#create-client-user-username').val();
    let email = $('#create-client-user-email').val();
    let phone = $('#create-client-user-phone').val();
    let position = $('#create-client-user-position').val();
    let color = $('#create-client-user-color').val();
    if(name == null || name == ''){
        $('#create-client-user-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del usuario');
        flag = false;
    }else{
        $('#create-client-user-name').removeClass('is-invalid');
    }
    if(lastname == null || lastname == ''){
        $('#create-client-user-lastname').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del usuario');
        flag = false;
    }else{
        $('#create-client-user-lastname').removeClass('is-invalid');
    }
    if(username == null || username == ''){
        $('#create-client-user-username').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre de usuario');
        flag = false;
    }else{
        $('#create-client-user-username').removeClass('is-invalid');
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-client-user-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del usuario');
        flag = false;
    }else{
        $('#create-client-user-email').removeClass('is-invalid');
    }
    if(phone == null || phone == ''){
        $('#create-client-user-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del usuario');
        flag = false;
    }else{
        $('#create-client-user-phone').removeClass('is-invalid');
    }
    if(position == null || position == ''){
        $('#create-client-user-position').addClass('is-invalid');
        alertWarning('Debe ingresar el cargo del usuario');
        flag = false;
    }else{
        $('#create-client-user-position').removeClass('is-invalid');
    }
    if(color == null || color == ''){
        $('#create-client-user-color').addClass('is-invalid');
        alertWarning('Debe seleccionar un color');
        flag = false;
    }else{
        $('#create-client-user-color').removeClass('is-invalid');
    }
    if(flag){
        $('#add-client-user-button').prop('disabled', true);
        let DataSend = {
            client_id: current_client.id,
            name: name,
            lastname: lastname,
            username: username,
            email: email,
            phone: phone,
            color: color,
            position: position,
            permissions:[4]
        };
        PostMethodFunction('/admin/clients/users/add',DataSend,null, function(response){
            $('#add-client-user-button').attr('disabled', false);
            $('#create-client-user-name').val('');
            $('#create-client-user-lastname').val('');
            $('#create-client-user-username').val('');
            $('#create-client-user-email').val('');
            $('#create-client-user-phone').val('');
            $('#create-client-user-position').val('');
            swallMessage(
                'Exito'
                , 'Usuario creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getClientUsers();
        }, function(){$('#add-client-user-button').attr('disabled', false);});
    }
}
function getClientUsers(){
    let DataSend = {
        client_id: current_client.id
    };
    PostMethodFunction('/admin/clients/users/get-by-client-id',DataSend,null, showClientUsers,null);
}
function showClientUsers(result){
    let appendContent = '';
    let complete_name = '';
    let name_initials = '';
    $.each(result.data,function(index,value){
        complete_name = value.name+(value.lastname==null?' ':value.lastname);
        name_initials = value.name.charAt(0)+(value.lastname==null?'':value.lastname.charAt(0));
        //
        appendContent += '<tr class="client-user-info'+(value.deleted_at==null?'':' deleted')+'" user-id="'+value.id+'">';
            appendContent += '<td class="user-column-id text-left" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="user-column-color text-center color-column"><div class="d-flex flex-column justify-content-center" style="background-color:'+value.color+'"><p class="client-user-input-color align-self-end input-value">'+name_initials+'</p></div></td>';
            appendContent += '<td class="user-column-name text-left"><p class="client-user-input-lastname align-self-end input-value">'+complete_name+'</p></td>';
            appendContent += '<td class="user-column-username text-left"><p class="client-user-input-username align-self-end input-value">'+value.username+'</p></td>';
            appendContent += '<td class="user-column-email text-left"><p class="client-user-input-email align-self-end input-value">'+value.email+'</p></td>';
            appendContent += '<td class="user-column-phone text-left"><p class="client-user-input-phone align-self-end input-value">'+(value.phone==null?'':value.phone)+'</p></td>';
            appendContent += '<td class="user-column-position text-left"><p class="client-user-input-position align-self-end input-value">'+value.position+'</p></td>';
            appendContent += '<td class="user-column-actions text-end action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-key restore-client-user-password-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-client-user-btn"></i>';
                    
                }else{
                    appendContent += '<i class="fa-solid fa-lightbulb restore-client-user-btn"></i>';
                }
                appendContent += '<i class="fa-solid fa-bars-progress list-client-user-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-users-table #client-users-table-body').empty().append(appendContent);
}
function restoreClientUserPassword(){
    let user_id = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar la contraseña de este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/admin/clients/users/restore-password',DataSend,null, function(response){
                alertSuccess('Contraseña restaurada');
                swallMessage(
                    'Contraseña temporal'
                    , '<i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+response.data+'"></i>'+response.data
                    , 'info'
                    , 'Entendido'
                    , null
                    , null
                    , null
                    , null
                );
            },null);
        }
        , null
    );
}
function deleteClientUser(){
    let user_id = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este usuario?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/admin/clients/users/delete',DataSend,null, function(response){
                alertSuccess('Usuario eliminado');
                getClientUsers();
            },null);
        }
        , null
    );
}
function restoreClientUser(){
    let btn = $(this);
    btn.attr('disabled', true);
    let user_id = $(this).closest('.client-user-info').attr('user-id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este usuario?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: user_id,
            };
            PostMethodFunction('/admin/clients/users/restore',DataSend,null, function(response){
                alertSuccess('Usuario restaurado');
                getClientUsers();
                btn.attr('disabled', false);
            },null);
        }
        , function(){btn.attr('disabled', false);}
    );
}
function goToUserTraceability(){
    let user_id = $(this).closest('.client-user-info').attr('user-id');
    $('#nav-traceability').attr('user-id',user_id);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}
// Sincronización con Siigo
$(document).ready(function() {
    let isSyncing = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function getClientErrorRow(errorText) {
        const raw = String(errorText || '');
        const regex = /cliente\s+(\d+)\s*(?:\(([^)]*)\))?\s*:\s*(.*)$/i;
        const match = raw.match(regex);
        if (!match) {
            return {
                clientId: 'N/A',
                code: 'N/A',
                field: 'N/A',
                detail: raw
            };
        }

        let code = 'N/A';
        let field = 'N/A';
        if (match[2]) {
            const metaParts = match[2].split(':');
            code = (metaParts[0] || '').trim() || 'N/A';
            field = (metaParts[1] || '').trim() || 'N/A';
        }

        return {
            clientId: (match[1] || 'N/A').trim(),
            code,
            field,
            detail: (match[3] || '').trim() || raw
        };
    }

    function renderSyncResult(response) {
        const syncedCount = Number(response.synced_count || 0);
        const rawErrors = Array.isArray(response.errors) ? response.errors : [];
        const rows = rawErrors.map(getClientErrorRow);

        let html = '';
        html += `<div class="alert ${syncedCount > 0 ? 'alert-success' : 'alert-info'} mb-3">${escapeHtml(response.message || 'Proceso finalizado')}</div>`;
        html += `<div class="sync-summary mb-3">`;
        html += `<span class="badge bg-success me-2">Sincronizados: ${syncedCount}</span>`;
        html += `<span class="badge bg-danger">Con error: ${rows.length}</span>`;
        html += `</div>`;

        if (rows.length > 0) {
            html += `<div class="table-responsive sync-errors-table-container">`;
            html += `<table class="table table-sm table-striped align-middle sync-errors-table">`;
            html += `<thead><tr><th>Cliente ID</th><th>Código</th><th>Campo</th><th>Detalle</th></tr></thead><tbody>`;
            rows.forEach(row => {
                html += `<tr>`;
                html += `<td><span class="badge bg-secondary">${escapeHtml(row.clientId)}</span></td>`;
                html += `<td>${escapeHtml(row.code)}</td>`;
                html += `<td>${escapeHtml(row.field)}</td>`;
                html += `<td>${escapeHtml(row.detail)}</td>`;
                html += `</tr>`;
            });
            html += `</tbody></table></div>`;
        }

        $('#syncResultMessage').html(html);
    }

    function showSyncError(message) {
        $('#syncResultMessage').html(`
            <div class="alert alert-danger mb-0">${escapeHtml(message || 'Error durante la sincronización')}</div>
        `);
    }

    // Botones de cierre del modal
    $('#syncResultCloseBtn, #syncResultCloseFooterBtn').on('click', function() {
        $('#syncResultModal').modal('hide');
    });

    $('#sync-siigo-btn').on('click', function() {
        if (isSyncing) return;
        
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        // Iniciar animación de carga
        isSyncing = true;
        $btn.prop('disabled', true);
        $icon.addClass('fa-spin');
        
        // Llamar al endpoint de sincronización usando postMethod
        PostMethodFunction('/admin/clients/sincronize', {}, null, function(response) {
            renderSyncResult(response);
            
            // Mostrar el modal
            $('#syncResultModal').modal('show');
            
            // Si se sincronizaron clientes, recargar la tabla
            if (response.synced_count > 0) {
                getClientsPage();
            }

            // Detener animación de carga
            isSyncing = false;
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        }, function(xhr) {
            let errorMessage = 'Error durante la sincronización';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {}
            
            showSyncError(errorMessage);
            $('#syncResultModal').modal('show');

            // Detener animación de carga
            isSyncing = false;
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        });
    });

    // Manejar el cierre del modal
    $('#syncResultModal').on('hidden.bs.modal', function () {
        isSyncing = false;
        $('#sync-siigo-btn').prop('disabled', false).find('i').removeClass('fa-spin');
    });
});