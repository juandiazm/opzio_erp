$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-license-button', addLicense);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('click', '.list-delete-btn', function(){deleteLicense($(this).parent().parent().attr('license-id'));});
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getLicensesPage();
});
$(document).on('change', '#state-list-input', function(){
    db_pagination.page = 1;
    getLicensesPage();
});
$(document).on('click', '.list-update-traceability', function(){
    current_license = licenses.find(license => license.id == $(this).closest('.license-row-info').attr('license-id'));
    goToLicensesTraceability('id%'+current_license.id);
});
//////////
$(document).on('click', '#update-license-button', updateLicense);
$(document).on('click', '#update-license-delete', function(){deleteLicense(current_license.id);});
$(document).on('click', '#update-license-restore', restoreLicense);
$(document).on('click', '#update-license-details-button', updateLicenseDetails);
$(document).on('change', '#update-license-type', licenseTypeChange);
$(document).on('click', '#update-license-go-traceability', function(){
    goToLicensesTraceability('id%'+current_license.id);
});
//////////
$(document).on('click','#add-license-documens-button', addLicenseDocument);
$(document).on('click', '.update-license-file-btn', updateLicenseDocument);
$(document).on('click', '.delete-license-file-btn', deleteLicenseDocument);
/////////
$(document).on('click', '#add-notification', addnotification);
$(document).on('click', '.notification-position-up-buttons', function(){changeNotificationPosition($(this),'up');});
$(document).on('click', '.notification-position-down-buttons', function(){changeNotificationPosition($(this),'down');});
$(document).on('click', '.update-notification-btn', updateNotification);
$(document).on('click', '.delete-notification-btn', deleteNotification);
$(document).on('click', '.restore-notification-btn', restoreNotification);
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
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
        window.history.replaceState({}, document.title, "/" + "admin/licenses");
    }
    //Wait until tu queries are done
    $.when(
        getClients(),
        getEmployees(),
    ).done(function(){
        changeTab();
    });
});
function getClients(){
    PostMethodFunction('/admin/clients/get-all',{},null, showClients, null);
}
function showClients(response){
    let appendContent = '<option value="">Selecciona un cliente</option>';
    $.each(response.data, function(index, value){
        appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
    });
    $('#create-license-client').html(appendContent);
    $('#update-license-client').html(appendContent);
}
function getEmployees(){
    PostMethodFunction('/admin/employees/get-all',{},null, showEmployees, null);
}
function showEmployees(response){
    let appendContent = '<option value="">Selecciona un empleado</option>';
    $.each(response.data, function(index, value){
        appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
    });
    $('#create-license-employee').html(appendContent);
    $('#update-license-employee').html(appendContent);
}
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        if(url_license_id == null){
            getLicensesPage();    
        }else{
            getLicenseById(url_license_id);
        }
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
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
    PostMethodFunction('/admin/licenses/get-page',DataSend,null, showLicensesPage,null);
}
function goToUpdateTab(){
    let license_id = $(this).parent().parent().attr('license-id');
    current_license = licenses.find(license => license.id == license_id);
    if(current_license != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
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
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><p><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</p></td>';
            appendContent += '<td class="columns-client text-left"><p>'+value.client.name+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+'</p></td>';
            appendContent += '<td class="columns-service text-left"><p>'+value.service.name+'</p></td>';
            appendContent += '<td class="columns-type text-left"><p>'+value.type_string+(value.type==1?(" ("+value.recurrence_months+")"):'')+'</p></td>';
            appendContent += '<td class="columns-value text-end" title="'+value.value+'"><p>$'+value.value_string+'</p></td>';
            appendContent += '<td class="columns-last-billing-date text-center"><p>'+(value.last_billing_date==null?'':value.last_billing_date)+'</p></td>';
            appendContent += '<td class="columns-last-payed_date text-center"><p>'+(value.last_payed_date==null?'':value.last_payed_date)+'</p></td>';
            appendContent += '<td class="columns-remaining-days text-center"><p>'+(value.remaining_days==null?'':value.remaining_days)+'</p></td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'"></p></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    //appendContent += '<i class="fa-solid fa-scale-balanced list-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    //appendContent += '<i class="fa-solid fa-scale-balanced list-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#license-list-table #license-list-table-body').empty().append(appendContent);
    DBshowPagination();
    //for test go to first license
    /*current_license = licenses[0];
    $('#nav-update-tab').tab('show');
    $('#nav-update-tab').trigger('click');
    showCurrentLicense();*/
}
function getLicenseById(license_id){
    let DataSend = {
        license_id: license_id
    };
    PostMethodFunction('/admin/licenses/get-by-id',DataSend,null, function(response){
        current_license = response.license;
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
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
    let description = $('#create-license-description').val();
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
    /*if(employee_id == null || employee_id == ''){
        $('#create-license-employee').addClass('is-invalid');
        alertWarning('Debe seleccionar un empleado');
        flag = false;
    }else{
        $('#create-license-employee').removeClass('is-invalid');
    }*/
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
            description: description,
        };
        PostMethodFunction('/admin/licenses/add',dataSend,null, function(response){
            $('#add-license-button').attr('disabled', false);
            //empty inputs
            $('#create-license-client').val('');
            $('#create-license-name').val('');
            $('#create-license-service').attr('item-id', '');
            $('#create-license-service input').val('');
            $('#create-license-employee').val('');
            $('#create-license-value').val('');
            $('#create-license-description').val('');
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
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
            showCurrentLicense();
        }, function(){$('#add-license-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentLicense(){
    $('#update-license-unique-id').text(current_license.unique_id);
    $('#update-license-state').attr('value', current_license.active);
    $('#update-license-state .toggle-value[value="'+current_license.active+'"]').click();
    $('#update-license-client').val(current_license.client_id);
    $('#update-license-name').val(current_license.name);
    $('#update-license-service').attr('item-id', current_license.service_id);
    $('#update-license-service input').val(current_license.service.name);
    $('#update-license-employee').val(current_license.employee_id);
    $('#update-license-value').val(current_license.value);
    $('#update-license-description').val(current_license.description);
    $('#update-license-type').val(current_license.type);
    $('#update-license-recurrence-months').val(current_license.recurrence_months);
    $('#update-license-billing-day').val(current_license.billing_day);
    $('#update-license-days-to-expire').val(current_license.days_to_expire);
    $('#update-license-last-payed-date').val(current_license.last_payed_date);
    $('#update-license-next-billing-date').val(current_license.next_billing_date);
    $('#update-license-user-key').text(current_license.user_key);
    $('#copy-update-license-user-key').attr('data-clipboard-text', current_license.user_key);
    $('#update-license-password-key').text(current_license.password_key);
    $('#copy-update-license-password-key').attr('data-clipboard-text', current_license.password_key);
    $('#update-license-last-billing-date').text(current_license.last_billing_date);
    $('#update-license-last-payed-date').text(current_license.last_payed_date);
    $('#update-license-remaining-days').text(current_license.remaining_days);
    if(current_license.deleted_at == null){
        //buttons
        $('#update-license-delete').addClass('d-block').removeClass('d-none');
        $('#update-license-restore').addClass('d-none').removeClass('d-block');
        $('#update-license-button').addClass('d-block').removeClass('d-none');
        $('#update-license-details-button').addClass('d-block').removeClass('d-none');
        //disabled
        $('#update-license-state').prop('disabled', false);
        $('#update-license-client').prop('disabled', false);
        $('#update-license-name').prop('disabled', false);
        $('#update-license-service').prop('disabled', false);
        $('#update-license-employee').prop('disabled', false);
        $('#update-license-value').prop('disabled', false);
        $('#update-license-type').prop('disabled', false);
        $('#update-license-recurrence-months').prop('disabled', false);
        $('#update-license-billing-day').prop('disabled', false);
        $('#update-license-days-to-expire').prop('disabled', false);
    }else{
        //buttons
        $('#update-license-delete').addClass('d-none').removeClass('d-block');
        $('#update-license-restore').addClass('d-block').removeClass('d-none');
        $('#update-license-button').addClass('d-none').removeClass('d-block');
        $('#update-license-details-button').addClass('d-none').removeClass('d-block');
        //disabled
        $('#update-license-state').prop('disabled', true);
        $('#update-license-client').prop('disabled', true);
        $('#update-license-name').prop('disabled', true);
        $('#update-license-service').prop('disabled', true);
        $('#update-license-employee').prop('disabled', true);
        $('#update-license-value').prop('disabled', true);
        $('#update-license-type').prop('disabled', true);
        $('#update-license-recurrence-months').prop('disabled', true);
        $('#update-license-billing-day').prop('disabled', true);
        $('#update-license-days-to-expire').prop('disabled', true);
    }
    getLicenseDocuments();
    getServiceNotifications();
}
function updateLicense(){
    let container = $(this).parent();
    let flag = true;
    let state = $('#update-license-state').attr('value');
    let client_id = $('#update-license-client').val();
    let name = $('#update-license-name').val();
    let service_id = $('#update-license-service').attr('item-id');
    let employee_id = $('#update-license-employee').val();
    let value = $('#update-license-value').val();
    let description = $('#update-license-description').val();
    if(state == null || state == ''){
        $('#update-license-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }else{
        $('#update-license-state').removeClass('is-invalid');
    }
    if(client_id == null || client_id == ''){
        $('#update-license-client').addClass('is-invalid');
        alertWarning('Debe seleccionar un cliente');
        flag = false;
    }else{
        $('#update-license-client').removeClass('is-invalid');
    }
    if(name == null || name == ''){
        $('#update-license-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del proveedor');
        flag = false;
    }else{
        $('#update-license-name').removeClass('is-invalid');
    }
    if(service_id == null || service_id == ''){
        $('#update-license-service').addClass('is-invalid');
        alertWarning('Debe seleccionar un servicio');
        flag = false;
    }else{
        $('#update-license-service').removeClass('is-invalid');
    }
    /*if(employee_id == null || employee_id == ''){
        $('#update-license-employee').addClass('is-invalid');
        alertWarning('Debe seleccionar un empleado');
        flag = false;
    }else{
        $('#update-license-employee').removeClass('is-invalid');
    }*/
    if(value == null || value == ''){
        $('#update-license-value').addClass('is-invalid');
        alertWarning('Debe ingresar el valor del servicio');
        flag = false;
    }else{
        $('#update-license-value').removeClass('is-invalid');
    }
    if(flag){
        $('#update-license-button').prop('disabled', true);
        let dataSend = {
            id: current_license.id,
            state: state,
            client_id: client_id,
            name: name,
            service_id: service_id,
            employee_id: employee_id,
            value: value,
            description: description,
        };
        PostMethodFunction('/admin/licenses/update',dataSend,null, function(response){
            $('#update-license-button').attr('disabled', false);
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
        }, function(){$('#update-license-button').attr('disabled', false);});
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
            PostMethodFunction('/admin/licenses/delete',DataSend,null, function(response){
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
            PostMethodFunction('/admin/licenses/restore',DataSend,null, function(response){
                alertSuccess('Licencia restaurada');
                tabs_view['nav-list-tab'] = false;
                current_license.deleted_at = null;
                showCurrentLicense();
            },null);
        }
        , null
    );
}
function updateLicenseDetails(){
    let container = $(this).parent();
    let flag = true;
    let type = $('#update-license-type').val();
    let recurrence_months = $('#update-license-recurrence-months').val();
    let billing_day = $('#update-license-billing-day').val();
    let days_to_expire = $('#update-license-days-to-expire').val();
    let next_billing_date = $('#update-license-next-billing-date').val();
    let last_payed_date = $('#update-license-last-payed-date').val();
    if(type == null || type == ''){
        $('#update-license-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de licencia');
        flag = false;
    }else{
        $('#update-license-type').removeClass('is-invalid');
        if(type == '1'){
            if(recurrence_months == null || recurrence_months == ''){
                $('#update-license-monthly-frequency').addClass('is-invalid');
                alertWarning('Debe ingresar la frecuencia mensual');
                flag = false;
            }else{
                $('#update-license-monthly-frequency').removeClass('is-invalid');
            }
            if(billing_day == null || billing_day == ''){
                $('#update-license-billing-day').addClass('is-invalid');
                alertWarning('Debe ingresar el dia de facturación');
                flag = false;
            }else{
                $('#update-license-billing-day').removeClass('is-invalid');
            }
            if(days_to_expire == null || days_to_expire == ''){
                $('#update-license-days-to-expire').addClass('is-invalid');
                alertWarning('Debe ingresar los días de expiración');
                flag = false;
            }else{
                $('#update-license-days-to-expire').removeClass('is-invalid');
            }
        }
    }
    if(flag){
        $('#update-license-details-button').prop('disabled', true);
        let dataSend = {
            id: current_license.id,
            type: type,
            recurrence_months: recurrence_months,
            billing_day: billing_day,
            days_to_expire: days_to_expire,
            next_billing_date: next_billing_date,
            last_payed_date: last_payed_date,
        };
        PostMethodFunction('/admin/licenses/update-details',dataSend,null, function(response){
            $('#update-license-details-button').attr('disabled', false);
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
            showCurrentLicense();
        }, function(){$('#update-license-details-button').attr('disabled', false);});
    }
    
}
function licenseTypeChange(){
    var value = $(this).val();
    if(value == '1'){
        $('#update-license-recurrence-months').attr('disabled', false);
        $('#update-license-billing-day').attr('disabled', false);
        $('#update-license-days-to-expire').attr('disabled', false);
    }else{
        $('#update-license-recurrence-months').attr('disabled', true);
        $('#update-license-billing-day').attr('disabled', true);
        $('#update-license-days-to-expire').attr('disabled', true);
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
        PostMethodMultimediaFunction('/admin/licenses/documents/add', dinamicForm, null, function(response){
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
    PostMethodFunction('/admin/licenses/documents/get',DataSend,null, showLicenseDocuments,null);
}
function showLicenseDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="license-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="license-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-license-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-license-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#license-documents-table #license-documents-table-body').empty().append(appendContent);
}
function updateLicenseDocument(){
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
        PostMethodFunction('/admin/licenses/documents/update',DataSend,null, function(response){
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
            PostMethodFunction('/admin/licenses/documents/delete',DataSend,null, function(response){
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
        PostMethodFunction('/admin/licenses/notifications/add',DataSend,null,function(response){
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
    PostMethodFunction('/admin/licenses/notifications/get',DataSend,null, showServiceNotifications,null);
}
function showServiceNotifications(response){
    let notifications = response.data;
    let appendContent = '';
    $.each(notifications,function(index,value){
        appendContent += '<tr notification-id='+value.id+' class="update-notification-row'+(value.deleted_at==null?'':' deleted')+'">';
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
                    appendContent += '<i class="fa-solid fa-pen-to-square update-notification-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-notification-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-trash-arrow-up restore-notification-btn"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#notifications-table tbody .update-notification-row').remove();
    $('#notifications-table tbody').append(appendContent);
    //click on state
    $('#notifications-table tbody .update-notification-row .notification-active').each(function(){
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
    //clone #update-license-city-from .crud-list
    let city_from_list = $('#update-license-city-from').parent().find('.crud-list').clone();
    $('#notifications-table tbody .update-notification-row .crud-input-container').append(city_from_list);
}
function changeNotificationPosition(container, direction){
    let notification_id = container.parent().parent().attr('notification-id');
    data = {
        notification_id: notification_id,
        direction: direction
    };
    PostMethodFunction('/admin/licenses/notifications/change-position',data,null,function(response){
        getServiceNotifications();
    },null);
}
function updateNotification(){
    let update_btn = $(this);
    let container = update_btn.parent().parent();
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
        PostMethodFunction('/admin/licenses/notifications/update',DataSend,null,function(response){
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
            PostMethodFunction('/admin/licenses/notifications/delete',DataSend,null, function(response){
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
            PostMethodFunction('/admin/licenses/notifications/restore',DataSend,null,function(response){
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