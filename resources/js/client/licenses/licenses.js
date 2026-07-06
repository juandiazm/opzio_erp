$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-license-button', addLicense);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-view-btn', goToUpdateTab);
$(document).on('click', '.list-delete-btn', function(){deleteLicense($(this).parent().parent().attr('license-id'));});
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getLicensesPage();
});
$(document).on('change', '#state-list-input', function(){
    db_pagination.page = 1;
    getLicensesPage();
});
$(document).on('click', '.list-view-traceability', function(){
    current_license = licenses.find(license => license.id == $(this).closest('.license-row-info').attr('license-id'));
    goToLicensesTraceability('id%'+current_license.id);
});
//////////
$(document).on('click', '#view-license-button', viewLicense);
$(document).on('click', '#view-license-delete', function(){deleteLicense(current_license.id);});
$(document).on('click', '#view-license-restore', restoreLicense);
$(document).on('click', '#view-license-details-button', viewLicenseDetails);
$(document).on('change', '#view-license-type', licenseTypeChange);
$(document).on('click', '#view-license-go-traceability', function(){
    goToLicensesTraceability('id%'+current_license.id);
});
//////////
$(document).on('click','#add-license-documens-button', addLicenseDocument);
$(document).on('click', '.view-license-file-btn', viewLicenseDocument);
$(document).on('click', '.delete-license-file-btn', deleteLicenseDocument);
/////////
$(document).on('click', '#add-notification', addnotification);
$(document).on('click', '.notification-position-up-buttons', function(){changeNotificationPosition($(this),'up');});
$(document).on('click', '.notification-position-down-buttons', function(){changeNotificationPosition($(this),'down');});
$(document).on('click', '.view-notification-btn', viewNotification);
$(document).on('click', '.delete-notification-btn', deleteNotification);
$(document).on('click', '.restore-notification-btn', restoreNotification);
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-view-tab': false,
}
var licenses = [];
var current_license = null;
var license_id = null;
var url_license_id = null;
var current_tab = null;
$(document).ready(function(){
    //get license_id from url params
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    url_license_id = urlParams.get('license_id');
    if(url_license_id != null){
        //remove license_id from url params
        window.history.replaceState({}, document.title, "/" + "client/licenses");
    }
    changeTab();
});
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-view-tab') $('#nav-view-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        if(url_license_id == null){
            getLicensesPage();    
        }else{
            getLicenseById(url_license_id);
        }
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
    }else if(current_tab == 'nav-view-tab'){
        $('#nav-view-tab').removeClass('d-none');
    }
    tabs_view[current_tab] = true;    
}
//List License Functions
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
    getLicensesPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getLicensesPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getLicensesPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getLicensesPage();
    }
}
function getLicensesPage(){
    let DataSend = {
        pagination: db_pagination,
        search: $('#search-list-input').val(),
        state: $('#state-list-input').val(),
    };
    PostMethodFunction('/client/licenses/get-page',DataSend,null, showLicensesPage,null);
}
function goToUpdateTab(){
    let license_id = $(this).parent().parent().attr('license-id');
    current_license = licenses.find(license => license.id == license_id);
    if(current_license != null){
        $('#nav-view-tab').tab('show');
        $('#nav-view-tab').trigger('click');
        showCurrentLicense();
    }
}
function showLicensesPage(response){
    db_pagination = response.pagination;
    licenses = response.licenses;
    let appendContent = '';
    $.each(licenses,function(index,value){
        //Value as money string
        value.value_string = value.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        appendContent += '<tr license-id='+value.id+' class="license-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-center" title="'+value.unique_id+'"><p><i class="fa-regular fa-copy copy-action" id="copy-view-license-password-key" data-clipboard-text="'+value.unique_id+'"></i>&nbsp;'+value.unique_id.substr(value.unique_id.length - 5)+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+'</p></td>';
            appendContent += '<td class="columns-service text-left"><p>'+value.service.name+'</p></td>';
            appendContent += '<td class="columns-type text-center"><p>'+value.type_string+'</p></td>';
            appendContent += '<td class="columns-value text-end" title="'+value.value+'"><p>$'+value.value_string+'</p></td>';
            appendContent += '<td class="columns-last-billing-date text-center"><p>'+(value.last_billing_date==null?'':value.last_billing_date)+'</p></td>';
            appendContent += '<td class="columns-last-payed_date text-center"><p>'+(value.last_payed_date==null?'':value.last_payed_date)+'</p></td>';
            appendContent += '<td class="columns-remaining-days text-center"><p>'+(value.remaining_days==null?'':value.remaining_days)+'</p></td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'"></p></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
            appendContent += '<i class="fa-solid fa-eye list-view-btn"></i>';
            appendContent += '<i class="fa-solid fa-bars-progress list-view-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#license-list-table #license-list-table-body').empty().append(appendContent);
    DBshowPagination();
}
function getLicenseById(license_id){
    let DataSend = {
        license_id: license_id
    };
    PostMethodFunction('/client/licenses/get-by-id',DataSend,null, function(response){
        current_license = response.license;
        $('#nav-view-tab').tab('show');
        $('#nav-view-tab').trigger('click');
        showCurrentLicense();
    },null);

}
//////////////////////////////////////////////////////
function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}
function addLicense(){
    let container = $(this).parent();
    let flag = true;
    let state = $('#create-license-state').attr('value');
    let client_id = $('#create-license-client').val();
    let name = $('#create-license-name').val();
    let service_id = $('#create-license-service').attr('item-id');
    let employee_id = $('#create-license-employee').val();
    let value = $('#create-license-value').val();
    if(state == null || state == ''){
        $('#create-license-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }
    if(client_id == null || client_id == ''){
        $('#create-license-client').addClass('is-invalid');
        alertWarning('Debe seleccionar un cliente');
        flag = false;
    }else{
        $('#create-license-client').removeClass('is-invalid');
    }
    if(name == null || name == ''){
        $('#create-license-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }else{
        $('#create-license-name').removeClass('is-invalid');
    }
    if(service_id == null || service_id == ''){
        $('#create-license-service').addClass('is-invalid');
        alertWarning('Debe seleccionar un servicio');
        flag = false;
    }else{
        $('#create-license-service').removeClass('is-invalid');
    }
    if(value == null || value == ''){
        $('#create-license-value').addClass('is-invalid');
        alertWarning('Debe ingresar el valor del servicio');
        flag = false;
    }else{
        $('#create-license-value').removeClass('is-invalid');
    }
    if(flag){
        $('#add-license-button').prop('disabled', true);
        let dataSend = {
            state: state,
            client_id: client_id,
            name: name,
            service_id: service_id,
            employee_id: employee_id,
            value: value,
        };
        PostMethodFunction('/client/licenses/add',dataSend,null, function(response){
            $('#add-license-button').attr('disabled', false);
            //empty inputs
            $('#create-license-client').val('');
            $('#create-license-name').val('');
            $('#create-license-service').attr('item-id', '');
            $('#create-license-service input').val('');
            $('#create-license-employee').val('');
            $('#create-license-value').val('');
            swallMessage(
                'Exito'
                , 'Licencia agregada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_license = response.license;
            $('#nav-view-tab').tab('show');
            $('#nav-view-tab').trigger('click');
            showCurrentLicense();
        }, function(){$('#add-license-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentLicense(){
    $('#view-license-unique-id').html('<i class="fa-regular fa-copy copy-action" id="copy-view-license-password-key" data-clipboard-text="'+current_license.unique_id+'"></i>'+current_license.unique_id);
    $('#view-license-state').attr('value', current_license.active);
    $('#view-license-state .toggle-value[value="'+current_license.active+'"]').removeClass('d-none').addClass('d-flex').click();
    $('#view-license-state .toggle-value[value="'+(current_license.active==0?1:0)+'"]').removeClass('d-flex').addClass('d-none');
    $('#view-license-client').text(current_license.client_id);
    $('#view-license-name').text(current_license.name);
    $('#view-license-service').text(current_license.service.name);
    $('#view-license-value').text(current_license.value);
    $('#view-license-type').text(current_license.type_string);
    $('#view-license-recurrence-months').text(current_license.recurrence_months);
    $('#view-license-billing-day').text(current_license.billing_day);
    $('#view-license-days-to-expire').text(current_license.days_to_expire);
    $('#view-license-next-billing-date').text(current_license.next_billing_date);
    $('#view-license-user-key').text(current_license.user_key);
    $('#copy-view-license-user-key').attr('data-clipboard-text', current_license.user_key);
    $('#view-license-password-key').text(current_license.password_key);
    $('#copy-view-license-password-key').attr('data-clipboard-text', current_license.password_key);
    $('#view-license-last-billing-date').text(current_license.last_billing_date);
    $('#view-license-last-payed-date').text(current_license.last_payed_date);
    $('#view-license-remaining-days').text(current_license.remaining_days);
    if(current_license.deleted_at == null){
        //buttons
        $('#view-license-delete').addClass('d-block').removeClass('d-none');
        $('#view-license-restore').addClass('d-none').removeClass('d-block');
        $('#view-license-button').addClass('d-block').removeClass('d-none');
        $('#view-license-details-button').addClass('d-block').removeClass('d-none');
        //disabled
        $('#view-license-state').prop('disabled', false);
        $('#view-license-client').prop('disabled', false);
        $('#view-license-name').prop('disabled', false);
        $('#view-license-service').prop('disabled', false);
        $('#view-license-employee').prop('disabled', false);
        $('#view-license-value').prop('disabled', false);
        $('#view-license-type').prop('disabled', false);
        $('#view-license-recurrence-months').prop('disabled', false);
        $('#view-license-billing-day').prop('disabled', false);
        $('#view-license-days-to-expire').prop('disabled', false);
    }else{
        //buttons
        $('#view-license-delete').addClass('d-none').removeClass('d-block');
        $('#view-license-restore').addClass('d-block').removeClass('d-none');
        $('#view-license-button').addClass('d-none').removeClass('d-block');
        $('#view-license-details-button').addClass('d-none').removeClass('d-block');
        //disabled
        $('#view-license-state').prop('disabled', true);
        $('#view-license-client').prop('disabled', true);
        $('#view-license-name').prop('disabled', true);
        $('#view-license-service').prop('disabled', true);
        $('#view-license-employee').prop('disabled', true);
        $('#view-license-value').prop('disabled', true);
        $('#view-license-type').prop('disabled', true);
        $('#view-license-recurrence-months').prop('disabled', true);
        $('#view-license-billing-day').prop('disabled', true);
        $('#view-license-days-to-expire').prop('disabled', true);
    }
    getLicenseDocuments();
    getServiceNotifications();
}
function viewLicense(){
    let container = $(this).parent();
    let flag = true;
    let state = $('#view-license-state').attr('value');
    let client_id = $('#view-license-client').val();
    let name = $('#view-license-name').val();
    let service_id = $('#view-license-service').attr('item-id');
    let employee_id = $('#view-license-employee').val();
    let value = $('#view-license-value').val();
    if(state == null || state == ''){
        $('#view-license-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }else{
        $('#view-license-state').removeClass('is-invalid');
    }
    if(client_id == null || client_id == ''){
        $('#view-license-client').addClass('is-invalid');
        alertWarning('Debe seleccionar un cliente');
        flag = false;
    }else{
        $('#view-license-client').removeClass('is-invalid');
    }
    if(name == null || name == ''){
        $('#view-license-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }else{
        $('#view-license-name').removeClass('is-invalid');
    }
    if(service_id == null || service_id == ''){
        $('#view-license-service').addClass('is-invalid');
        alertWarning('Debe seleccionar un servicio');
        flag = false;
    }else{
        $('#view-license-service').removeClass('is-invalid');
    }
    if(value == null || value == ''){
        $('#view-license-value').addClass('is-invalid');
        alertWarning('Debe ingresar el valor del servicio');
        flag = false;
    }else{
        $('#view-license-value').removeClass('is-invalid');
    }
    if(flag){
        $('#view-license-button').prop('disabled', true);
        let dataSend = {
            id: current_license.id,
            state: state,
            client_id: client_id,
            name: name,
            service_id: service_id,
            employee_id: employee_id,
            value: value,
        };
        PostMethodFunction('/client/licenses/view',dataSend,null, function(response){
            $('#view-license-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Licencia actualizada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_license = response.license;
        }, function(){$('#view-license-button').attr('disabled', false);});
    }
}
function deleteLicense(license_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar esta licencia?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: license_id,
            };
            PostMethodFunction('/client/licenses/delete',DataSend,null, function(response){
                alertSuccess('Licencia eliminada');
                if(current_tab == 'nav-list-tab'){
                    getLicensesPage();
                }else{
                    current_license.deleted_at = response.data.deleted_at;
                    showCurrentLicense();
                }
            },null);
        }
        , null
    );
}
function restoreLicense(){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar esta licencia?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: current_license.id,
            };
            PostMethodFunction('/client/licenses/restore',DataSend,null, function(response){
                alertSuccess('Licencia restaurada');
                tabs_view['nav-list-tab'] = false;
                current_license.deleted_at = null;
                showCurrentLicense();
            },null);
        }
        , null
    );
}
function viewLicenseDetails(){
    let container = $(this).parent();
    let flag = true;
    let type = $('#view-license-type').val();
    let recurrence_months = $('#view-license-recurrence-months').val();
    let billing_day = $('#view-license-billing-day').val();
    let days_to_expire = $('#view-license-days-to-expire').val();
    if(type == null || type == ''){
        $('#view-license-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de licencia');
        flag = false;
    }else{
        $('#view-license-type').removeClass('is-invalid');
        if(type == '1'){
            if(recurrence_months == null || recurrence_months == ''){
                $('#view-license-monthly-frequency').addClass('is-invalid');
                alertWarning('Debe ingresar la frecuencia mensual');
                flag = false;
            }else{
                $('#view-license-monthly-frequency').removeClass('is-invalid');
            }
            if(billing_day == null || billing_day == ''){
                $('#view-license-billing-day').addClass('is-invalid');
                alertWarning('Debe ingresar el dia de facturación');
                flag = false;
            }
        }
    }
    if(flag){
        $('#view-license-details-button').prop('disabled', true);
        let dataSend = {
            id: current_license.id,
            type: type,
            recurrence_months: recurrence_months,
            billing_day: billing_day,
            days_to_expire: days_to_expire,
        };
        PostMethodFunction('/client/licenses/view-details',dataSend,null, function(response){
            $('#view-license-details-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Detalles de licencia actualizados'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_license = response.license;
        }, function(){$('#view-license-details-button').attr('disabled', false);});
    }
    
}
function licenseTypeChange(){
    var value = $(this).val();
    if(value == '1'){
        $('#view-license-recurrence-months').attr('disabled', false);
        $('#view-license-billing-day').attr('disabled', false);
        $('#view-license-days-to-expire').attr('disabled', false);
    }else{
        $('#view-license-recurrence-months').attr('disabled', true);
        $('#view-license-billing-day').attr('disabled', true);
        $('#view-license-days-to-expire').attr('disabled', true);
    }
}
function addLicenseDocument(){
    let container = $(this).parent();
    let name = container.find('.license-document-input-name').val();
    let file = container.find('.license-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.license-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.license-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-license-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="license_id" value="'+current_license.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.license-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.license-document-input-file')[0].files =  container.find('.license-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/client/licenses/documents/add', dinamicForm, null, function(response){
            $('#add-license-documens-button').attr('disabled', false);
            container.find('.license-document-input-name').val('');
            container.find('.license-document-input-file').val('');
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
            getLicenseDocuments();
        }, function(){$('#add-license-documens-button').attr('disabled', false);});
    }
}
function getLicenseDocuments(){
    let DataSend = {
        license_id: current_license.id
    };
    PostMethodFunction('/client/licenses/documents/get',DataSend,null, showLicenseDocuments,null);
}
function showLicenseDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="license-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="license-document-input-link">'+value.document_private_name+'</a></td>';
        appendContent += '</tr>';
    });
    $('#license-documents-table #license-documents-table-body').empty().append(appendContent);
}
function viewLicenseDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.license-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.license-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let DataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/client/licenses/documents/view',DataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}
function deleteLicenseDocument(){
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
            PostMethodFunction('/client/licenses/documents/delete',DataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
    
}
//notifications region
function addnotification(){
    let container = $(this).parent().parent();
    let flag = true;
    let email = container.find('.notification-email').val();
    let phone = container.find('.notification-phone').val();
    let state = container.find('.notification-active').attr('value');
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.notificatin-email').addClass('is-invalid');
        alertWarning('Debe ingresar el email del licencia');
        flag = false;
    }else{
        container.find('.notification-email').removeClass('is-invalid');
    }
    /*if(phone == null || phone == ""){
        container.find('.notification-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del licencia');
        flag = false;
    }else{
        container.find('.notification-phone').removeClass('is-invalid');
    }*/
    if(flag){
        $('#add-notification').attr('disabled',true);
        let DataSend = {
            license_id: current_license.id,
            email: email,
            phone: phone,
            state: state,
        };
        PostMethodFunction('/client/licenses/notifications/add',DataSend,null,function(response){
            $('#add-notification').attr('disabled', false);
            //restore inputs
            container.find('.notification-email').val('');
            container.find('.notification-phone').val('');
            container.find('.notification-active').attr('value', '1');
            container.find('.notification-active .toggle-value[value="1"]').click();
            //
            ////////////////////
            swallMessage(
                'Exito'
                , 'Notificación agregada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            ////////////////////
            getServiceNotifications();
        },null);
    }
}
function getServiceNotifications(){
    let DataSend = {
        license_id: current_license.id
    };
    PostMethodFunction('/client/licenses/notifications/get',DataSend,null, showServiceNotifications,null);
}
function showServiceNotifications(response){
    let notifications = response.data;
    let appendContent = '';
    $.each(notifications,function(index,value){
        appendContent += '<tr notification-id='+value.id+' class="view-notification-row'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td>';
                if(value.deleted_at == null){
                    if(index!=0){
                        appendContent += '<i class="d-block notification-position-up-buttons my-1 fa-solid fa-arrow-up"></i>';
                    }
                    if(index!=notifications.length-1){
                        appendContent += '<i class="d-block notification-position-down-buttons my-1 fa-solid fa-arrow-down"></i>';
                    }
                }
            appendContent += '</td>';
            appendContent += '<td class="columns-notification-email text-left"><input type="text" class="form-control align-self-center notification-email text-left" placeholder="license@gmail.com" value="'+value.email+'"></td>';
            appendContent += '<td class="columns-notification-phone text-left"><p><input type="number" class="form-control align-self-center notification-phone text-left" placeholder="573191425639" value="'+value.phone+'"></p></td>';
            appendContent += '<td class="columns-notification-state text-center active-col">'
                appendContent += '<div class="toggle-container row notification-active" value="'+value.active+'">';
                    appendContent += '<div class="toggle-value d-flex justify-content-center col-6" value="1">';
                        appendContent += '<p>Activo</p>';
                    appendContent += '</div>';
                    appendContent += '<div class="toggle-value d-flex justify-content-center col-6" value="0">';
                        appendContent += '<p>Inactivo</p>';
                    appendContent += '</div>';
                appendContent += ' </div>';
            appendContent += '</td>';
            appendContent += '<td class="columns-notification-actions text-center action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-pen-to-square view-notification-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-notification-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-trash-arrow-up restore-notification-btn"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#notifications-table tbody .view-notification-row').remove();
    $('#notifications-table tbody').append(appendContent);
    //click on state
    $('#notifications-table tbody .view-notification-row .notification-active').each(function(){
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
    //clone #view-license-city-from .crud-list
    let city_from_list = $('#view-license-city-from').parent().find('.crud-list').clone();
    $('#notifications-table tbody .view-notification-row .crud-input-container').append(city_from_list);
}
function changeNotificationPosition(container, direction){
    let notification_id = container.parent().parent().attr('notification-id');
    data = {
        notification_id: notification_id,
        direction: direction
    };
    PostMethodFunction('/client/licenses/notifications/change-position',data,null,function(response){
        getServiceNotifications();
    },null);
}
function viewNotification(){
    let view_btn = $(this);
    let container = view_btn.parent().parent();
    let notification_id = container.attr('notification-id');
    let email = container.find('.notification-email').val();
    let phone = container.find('.notification-phone').val();
    let state = container.find('.notification-active').attr('value');
    let flag = true;
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.notification-email').addClass('is-invalid');
        alertWarning('Debe ingresar el email del licencia');
        flag = false;
    }else{
        container.find('.notification-email').removeClass('is-invalid');
    }
    /*if(phone == null || phone == ""){
        container.find('.notification-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del licencia');
        flag = false;
    }else{
        container.find('.notification-phone').removeClass('is-invalid');
    }*/
    if(flag){
        let DataSend = {
            id: notification_id,
            email: email,
            phone: phone,
            state: state,
        };
        PostMethodFunction('/client/licenses/notifications/update',DataSend,null,function(response){
            swallMessage(
                'Exito'
                , 'Notificación actualizada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            ////////////////////
            getServiceNotifications();
        },null);
    }
}
function deleteNotification(){
    let delete_btn = $(this);
    let container = delete_btn.parent().parent();
    let notification_id = container.attr('notification-id');
    swallMessage(
        'Eliminar'
        , '¿Seguro deseas eliminar esta notificación?'
        , 'error'
        , 'Si, Eliminar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                id: notification_id
            };
            PostMethodFunction('/client/licenses/notifications/delete',DataSend,null, function(response){
                swallMessage(
                    'Exito'
                    , 'Notificación eliminada'
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                ////////////////////
                getServiceNotifications();
            },null);
        }
        , null
    );
}
function restoreNotification(){
    let restore_btn = $(this);
    let container = restore_btn.parent().parent();
    let notification_id = container.attr('notification-id');
    swallMessage(
        'Reactivar'
        , '¿Seguro deseas reactivar esta notificación?'
        , 'warning'
        , 'Si, Reactivar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                id: notification_id
            };
            PostMethodFunction('/client/licenses/notifications/restore',DataSend,null,function(response){
                swallMessage(
                    'Notificación REACTIVADA con éxito'
                    , null
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                ////////////////////
                getServiceNotifications();
            },null);
        }
        , null
    );
}
function goToLicensesTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}