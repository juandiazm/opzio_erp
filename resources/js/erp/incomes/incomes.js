$(document).on('click', '#nav-tab .nav-link', changeTab);
//list incomes
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getIncomesPage();
});
$(document).on('change', '#state-list-input', function(){
    db_pagination.page = 1;
    getIncomesPage();
});
$(document).on('click', '.list-delete-btn', function(){deleteIncome($(this).parent().parent().attr('employee-id'));});
$(document).on('click', '.list-view-order', function(){
    let income_id = $(this).closest('tr').attr('income-id');
    current_income = incomes.find(income => income.id == income_id);
    showIncomeOrder();
});
$(document).on('click', '.list-update-traceability', function(){
    current_income = incomes.find(income => income.id == $(this).closest('.income-row-info').attr('income-id'));
    goToIncomesTraceability('id%'+current_income.id);
});
//Create and update purchase order
$(document).on('change', '.input-client', loadClientData);
$(document).on('click', '.state-input', changeCreateOrderState);
$(document).on('change', '.input-item-license', loadLicenseData);
$(document).on('change', '.input-item-comission', getComissionValue);
$(document).on('click', '.add-license-button', addLicenseItem);
$(document).on('click', '.delete-license-button', deleteLicenseItem);
$(document).on('click', '.update-license-button', updateLicenseItem);
$(document).on('change', '.input-timely-payment', changeTimelyPayment);
//Create purchase order
$(document).on('click', '#create-income-button', createIncome);
//Update purchase order
$(document).on('click', '#update-income-button', updateIncome);
$(document).on('click', '#view-income-document', showIncomeOrder);
$(document).on('click', '#print-income-button', printOrderPdf);
$(document).on('click', '#pay-state-btn', changePayState);
$(document).on('click', '.update-state', changeInputState);
//Purchase Order Viewer
$(document).on('click', '#close-order-viewer', closeOrderViewer);
$(document).on('click', '#send-order-button', sendOrder);
$(document).on('click', '#cancel-send-order-button', cancelSendOrder);
$(document).on('click', '#confirm-send-order-button', confirmSendOrder);
$(document).on('click', '.receiver-item-delete', deleteReceiver);
$(document).on('click', '.receiver-item-create', createReceiver);

$(document).on('click', '#import-report-excel-container', getImportAssistantsExcel);
$(document).on('change', '#import-report-excel-input', importAssistantsExcel);
$(document).on('click', '#import-report-excel-input', function (event) {
    event.stopPropagation();
});
//Advances (Abonos)
$(document).on('click', '.list-manage-advances', openAdvancesModal);
$(document).on('click', '#close-advances-modal', closeAdvancesModal);
$(document).on('click', '#create-advance-button', showCreateAdvanceForm);
$(document).on('click', '#cancel-advance-button', hideAdvanceForm);
$(document).on('click', '#save-advance-button', saveAdvance);
$(document).on('click', '.advance-item-edit', editAdvance);
$(document).on('click', '.advance-item-delete', deleteAdvance);
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var current_tab = null;
var current_container = null;
var incomes = [];
var current_income = null;
var income_id = null;
var incomeStatesTotals = {
    '-1': {total:0, text:'Todas', class:'state-1'},
    '0': {total:0, text:'Pendiente', class:'state-0'},
    '1': {total:0, text:'Rechazada', class:'state-1'},
    '2': {total:0, text:'Aprobada', class:'state-2'},
    '3': {total:0, text:'Pagada', class:'state-3'},
    '4': {total:0, text:'Facturada', class:'state-4'},
}
$(document).ready(function(){
    //get url params
    let urlParams = new URLSearchParams(window.location.search);
    income_id = urlParams.get('income_uid');
    if(income_id!=null && income_id!='' && income_id!=0){
        window.history.replaceState({}, document.title, "/" + "admin/incomes");
    }
    changeTab();
});
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    current_container = $($('#nav-tab .active').attr('data-bs-target'));
    currentLicencesList = [];
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getIncomesPage();
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        getAllClients();
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
       
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
        getAllClients();
    }
    tabs_view[current_tab] = true;    
}
//List incomes
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
    getIncomesPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getIncomesPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getIncomesPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getIncomesPage();
    }
}
function getIncomesPage(){
    if(income_id!=null && income_id!='' && income_id!=0){
        $('#search-list-input').val(income_id);
    }
    let DataSend = {
        pagination: db_pagination,
        search: $('#search-list-input').val(),
        state: $('#state-list-input').val(),
    };
    PostMethodFunction('/admin/incomes/get-page',DataSend,null, showIncomesPage,null);
}
function goToUpdateTab(){
    let income_id = $(this).parent().parent().attr('income-id');
    current_income = incomes.find(income => income.id == income_id);
    if(current_income != null){
        tabs_view['nav-update-tab'] = false;
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
    }
}
function showIncomesPage(response){
    //states
    incomeStatesTotals = {
        '-1': {total:0, text:'Eliminado', class:'state-1'},
        '0': {total:0, text:'Pendiente', class:'state-0'},
        '1': {total:0, text:'Rechazada', class:'state-1'},
        '2': {total:0, text:'Aprobada', class:'state-2'},
        '3': {total:0, text:'Pagada', class:'state-3'},
        '4': {total:0, text:'Facturada', class:'state-4'},
    };
    ////////
    db_pagination = response.pagination;
    incomes = response.data;
    let appendContent = '';
    $.each(incomes,function(index,value){
        //round total
        value.total = Math.round(value.total);
        appendContent += '<tr income-id='+value.id+' class="income-row-info'+(value.state!=1?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-left" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-client text-start" title="'+value.client_name+'"><p>'+value.client_name+'</p></td>';
            appendContent += '<td class="columns-timely-payment text-center"><p>'+value.timely_payment+'</p></td>';
            appendContent += '<td class="columns-cutoff-date text-center"><p>'+value.cutoff_date+'</p></td>';
            appendContent += '<td class="columns-total text-end" title="'+value.total+'"><p>$'+value.total.toLocaleString('es-CO')+'</p></td>';
            appendContent += '<td class="columns-bill text-center" title="">'+(value.bill_name==null?'':value.bill_name)+'</td>';
            appendContent += '<td class="columns-created-at text-center"><p>'+value.created_at_string+'</p></td>';
            appendContent += '<td class="columns-bill text-center">' + 
                (value.siigo_invoice_url ? 
                    '<a href="' + value.siigo_invoice_url + '" target="_blank" title="Ver factura electrónica">' +
                        '<i class="fa-solid fa-file-invoice text-primary"></i>' +
                    '</a>' : 
                    '<a href="javascript:void(0)" onclick="window.createSiigoInvoice(' + value.id + ')" title="Crear factura electrónica">' +
                        '<i class="fa-solid fa-file-circle-plus text-success"></i>' +
                    '</a>') + 
            '</td>';
            appendContent += '<td class="columns-state text-center active-col"><label class="selected active-state state-'+value.state+'">'+(value.state_text)+'</label></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.payment_state!=1 && value.state!=1){
                    //get base url
                    appendContent += '<i class="fa-regular fa-link copy-action me-1 list-pay-link" data-clipboard-text="'+window.location.origin+'/client/payments/pay/'+value.unique_id+'"></i>';
                }
                appendContent += '<i class="fa-solid fa-receipt list-view-order"></i>';
                if(value.state!=1){
                    // Solo mostrar abonos para ingresos aprobados (estado 2)
                    if(value.state == 2){
                        appendContent += '<i class="fa-solid fa-hand-holding-dollar list-manage-advances" title="Gestionar abonos"></i>';
                    }
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
        //calculate states totals
        if(value.state in incomeStatesTotals){
            incomeStatesTotals[value.state].total += value.total;
        }
        incomeStatesTotals['-1'].total += value.total;
    });
    $('#income-list-table #income-list-table-body').empty().append(appendContent);
    DBshowPagination();
    //show total on states
    let selectedState = $('#state-list-input').val();
    $('#state-list-input').empty();
    $('#state-list-input').append(`<option value="-1" ${selectedState=='-1'?'selected':''}>Todos ($${incomeStatesTotals['-1'].total.toLocaleString('es-CO')})</option>`);
    $('#state-list-input').append(`<option value="0" ${selectedState==0?'selected':''}>Cotizaciones ($${incomeStatesTotals[0].total.toLocaleString('es-CO')})</option>`);
    $('#state-list-input').append(`<option value="1" ${selectedState==1?'selected':''}>Rechazadas ($${incomeStatesTotals[1].total.toLocaleString('es-CO')})</option>`);
    $('#state-list-input').append(`<option value="2" ${selectedState==2?'selected':''}>Aprobadas ($${incomeStatesTotals[2].total.toLocaleString('es-CO')})</option>`);
    $('#state-list-input').append(`<option value="3" ${selectedState==3?'selected':''}>Pagadas ($${incomeStatesTotals[3].total.toLocaleString('es-CO')})</option>`);
    $('#state-list-input').append(`<option value="4" ${selectedState==4?'selected':''}>Facturadas ($${incomeStatesTotals[4].total.toLocaleString('es-CO')})</option>`);
    //////////////////////
    if(income_id != null && income_id != '' && income_id != 0){
        if(incomes.length>0){
            current_income = incomes[0];
            if(current_income != null){
                tabs_view['nav-update-tab'] = false;
                $('#nav-update-tab').tab('show');
                $('#nav-update-tab').trigger('click');
            }
        }
        income_id = null;
    }
}
function deleteIncome(income_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este empleado?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: income_id,
            };
            PostMethodFunction('/admin/incomes/delete',DataSend,null, function(response){
                alertSuccess('Empleado eliminado');
                if(current_tab == 'nav-update-tab'){
                    current_income.deleted_at = response.data.deleted_at;
                    showCurrentIncome();
                }else{
                    getIncomesPage();
                }
                tabs_view['nav-list-tab'] = false;
                
            },null);
        }
        , null
    );
}
function restoreIncome(income_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este empleado?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: income_id,
            };
            PostMethodFunction('/admin/incomes/restore',DataSend,null, function(response){
                alertSuccess('Empleado restaurado');
                if(current_tab == 'nav-update-tab'){
                    current_income.deleted_at = null;
                    showCurrentIncome();
                }else{
                    getIncomesPage();
                }
                tabs_view['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}
//Create and update purchase order
let clientsList =[];
let currentClient = null;
let selected_license = [];
let currentLicencesList = [];
function changeCreateOrderState(){
    current_container.find('.state-input').removeClass('selected');
    $(this).addClass('selected');
}
function getAllClients(){
    if(clientsList.length==0){
        let DataSend = {};
        PostMethodFunction('/admin/clients/get-all',DataSend,null, showAllClients,null);
    }else if(current_tab == 'nav-update-tab'){
        showCurrentIncome();
    }
}
function showAllClients(response){
    clientsList = response.data;
    currentClient = null;
    create_current_licenses = [];
    selected_license = null;
    currentLicencesList = [];
    let html = '';
    $.each(clientsList, function(i, client){
        html += '<option value="'+client.id+'">'+client.name+'</option>';
    });
    $('.input-client').append(html);
    if(current_tab == 'nav-update-tab'){
        showCurrentIncome();
    }
}
function loadClientData(){
    resetLicenseInputs();
    if(currentLicencesList.length>0){
        swallMessage(
            'Cambio de cliente'
            , '¿Estás seguro de cambiar de cliente?<br>Si cambias de cliente se perderán los datos de las licencias'
            , 'warning'
            , 'Si, cambiar'
            , 'No, Cancelar'
            , null
            , function(){
                getClientData();
                currentLicencesList = [];
                showLicensesItems();
            }
            , null
        );
    }else{
        getClientData();
    }            
    
}
function getClientData(){
    let client_id = current_container.find('.input-client').val();
    currentClient = clientsList.find(client => client.id == client_id);
    if(currentClient==undefined){
        alertWarning('El cliente no existe');
        return;
    }
    current_container.find('.input-identification').text(currentClient.identification);
    getClientLicenses();
}
function getClientLicenses(){
    let DataSend = {
        client_id: currentClient.id
    };
    PostMethodFunction('/admin/clients/licenses/get-by-client-id',DataSend,null, showClientLicenses,null);
}
function showClientLicenses(response){
    create_current_licenses = response.licenses;
    let html = '<option value="0" selected disabled>Seleccione una licencia</option>';
    $.each(create_current_licenses, function(i, license){
        html += '<option value="'+license.id+'">'+license.name+'</option>';
    });
    current_container.find('.input-item-license').html(html);
}
function loadLicenseData(){
    let container = current_container.find('.order-licenses-list-item');
    let license_id = $(this).val();
    selected_license = create_current_licenses.find(license => license.id == license_id);
    if(selected_license==null){
        alertWarning('La licencia no existe');
    }
    container.find('.input-item-service').text(selected_license.service.name);
    container.find('.input-item-recurrence').text((selected_license.type == 2 || selected_license.recurrence_months==null)?'':(selected_license.recurrence_months+' meses'));
    container.find('.input-item-value').val(selected_license.value);
    container.find('.input-item-employee').text(selected_license.employee==null?'':selected_license.employee.name+(selected_license.employee.last_name==null?'':' '+selected_license.employee.last_name));
    container.find('.input-item-comission').val(selected_license.comission==null?'0':selected_license.comission).change();
    container.find('.input-item-tax').text((selected_license.service.tax_id==null?'0':selected_license.service.tax.value*100)+'%');
}
function getComissionValue(){
    try{
        let container = $(this).parent().parent();
        let comission = container.find('.input-item-comission').val();
        let value = container.find('.input-item-value').val();
        container.find('.input-item-total-comission').text('$'+((comission/100)*value).toLocaleString('es-CO'));
    }catch(e){
        current_container.find('.input-item-comission').val('0').change();
    }

}
function addLicenseItem(){
    let flag = true;
    let value = current_container.find('.input-item-value').val();
    let comission = current_container.find('.input-item-comission').val();
    let description = current_container.find('.input-item-description').val();
    let hours = current_container.find('.input-item-hours').val();
    if(selected_license==null){
        alertWarning('Debes seleccionar una licencia');
        flag = false;
    }
    if(value==''){
        alertWarning('Debes ingresar un valor');
        flag = false;
    }
    if(hours == '' || hours == null || hours < 0){
        alertWarning('Debes ingresar las horas invertidas');
    }
    if(flag){
        let tax_value = 0;
        let tax_name = '';
        if(selected_license.service.tax_id!=null){
            tax_value = selected_license.service.tax.value;
            tax_name = selected_license.service.tax.name;
        }
        let license_item = {
            license_id: selected_license.id,
            license_name: selected_license.name,
            service_id: selected_license.service_id,
            service_name: selected_license.service.name,
            recurrence_months: (selected_license.type == 2 || selected_license.recurrence_months==null)?null:selected_license.recurrence_months,
            value: value,
            employee_id: selected_license.employee_id,
            employee_name: selected_license.employee==null?'':selected_license.employee.name+(selected_license.employee.last_name==null?'':' '+selected_license.employee.last_name),
            tax_id: selected_license.service.tax_id,
            tax_value: tax_value,
            tax_name: tax_name,
            comission:comission,
            total: (value*(1+parseFloat(tax_value))),
            hours:hours,
            description:description
        }
        currentLicencesList.push(license_item);
        //delete inputs and texts
        resetLicenseInputs();   
        alertSuccess('Licencia agregada correctamente');
        showLicensesItems();
    }
}
function resetLicenseInputs(){
    current_container.find('.add-row .input-item-value').val('0').select().focus();
    current_container.find('.add-row .input-item-description').val('');
    current_container.find('.add-row .input-item-employee').text('');
    current_container.find('.add-row .input-item-comission').val('0');
    current_container.find('.add-row .input-item-total-comission').text('0');
    current_container.find('.add-row .input-item-hours').val('0');
    //current_container.find('.add-row .input-item-license').val('');
    //current_container.find('.add-row .input-item-service').text('');
    //current_container.find('.add-row .input-item-recurrence').text('');
    //current_container.find('.add-row .input-item-tax').text('');
}
function showLicensesItems(){
    let html = '';
    let total = 0;
    let current_tax = 0;
    $.each(currentLicencesList, function(index, item){
        item.total = parseFloat(item.total);
        item.tax_value = parseFloat(item.tax_value);
        item.comission = parseFloat(item.comission);
        item.value = parseFloat(item.value);
        html += '<li class="update-income-licenses-list-item order-licenses-list-item row" index="'+index+'">';
            html += '<div class="col-12 col-md-6 d-flex flex-column justify-content-center">';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-license">Licencia</span>';
                    html += '<p class="form-control input-value input-item-license">'+item.license_name+'</p>';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-service">Servicio</span>';
                    html += '<p class="form-control input-value input-item-service">'+item.service_name+'</p>';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-recurrence">Recurrencia</span>';
                    html += '<p class="form-control input-value input-item-recurrence">'+(item.recurrence_months==null?'':item.recurrence_months)+'</p>';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-value">Valor</span>';
                    if(current_tab != 'nav-update-tab' || current_income.state==0){
                        html += '<input type="number" class="form-control input-value input-item-value" name="input-item-value" value="'+item.value+'">';
                    }else{
                        html += '<p class="form-control input-value input-item-value" name="input-item-value">'+item.value+'</p>';
                    }
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-hours">Horas</span>';
                    html += '<input type="number" class="form-control input-value input-item-hours" name="input-item-hours" value="'+item.hours+'">';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-employee">Empleado</span>';
                    html += '<p class="form-control input-value input-item-employee">'+(item.employee_name==null?'':item.employee_name)+'</p>';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-comission">Comisión</span>';
                    if(current_tab != 'nav-update-tab' || current_income.state==0){
                        html += '<input type="number" class="form-control input-value input-item-comission" name="input-item-comission" value="'+item.comission+'">';
                    }else{
                        html += '<p class="form-control input-value input-item-comission" name="input-item-comission">'+item.comission+'</p>';
                    }
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-total-comission">Total Comisión</span>';
                    html += '<p class="input-value input-item-total-comission">$'+((item.comission/100)*item.value).toLocaleString('es-CO')+'</p>';
                html += '</div>';
                html += '<div class="input-container d-flex justify-content-start">';
                    html += '<span class="input-title align-self-center" for="input-item-tax">Impuesto</span>';
                    html += '<p class="input-value align-self-center input-item-tax" name="item-tax">'+item.tax_value*100+'%</p>';
                html += '</div>';
            html += '</div>';
            html += '<div class="col-12 col-md-6 d-flex flex-column justify-content-center">';
                html += '<div class="input-container d-flex flex-column justify-content-center description-container">';
                    html += '<span class="input-title align-self-start" for="input-license-description">Descripción</span>';
                    if(current_tab != 'nav-update-tab' || current_income.state==0){
                        html += '<textarea class="form-control input-value input-item-description" name="description">'+(item.description==null?'':item.description)+'</textarea>';
                    }else{
                        html += '<p class="form-control input-value input-item-description" name="description">'+(item.description==null?'':item.description)+'</p>';
                    }
                html += '</div>';
            html += '</div>';
            if(current_tab != 'nav-update-tab' || current_income.state==0){
                html += '<div class="d-flex justify-content-end align-items-center">';
                    html += '<i class="fas fa-pen-to-square update-license-button"></i>';
                    html += '<i class="fas fa-trash-can delete-license-button"></i>';
                html += '</div>';
            }
        html += '</li>';
        total += item.total;
    });
    current_container.find('.update-income-licenses-list-item').remove();
    current_container.find('.income-licenses-list').append(html);
    current_container.find('.input-total-value').html('<strong>$'+total.toLocaleString('es-CO')+'</strong>');
}
function deleteLicenseItem(){
    let index = $(this).parent().parent().attr('index');
    swallMessage(
        'Eliminar licencia'
        , '¿Estás seguro de eliminar esta licencia?'
        , 'error'
        , 'Si, eliminar'
        , 'No, Cancelar'
        , null
        , function(){
            currentLicencesList.splice(index, 1);
            alertWarning('Licencia eliminada correctamente');
            showLicensesItems();
        }
        , null
    );
}
function updateLicenseItem(){
    let flag = true;
    let container = $(this).closest('.update-income-licenses-list-item');
    let index = container.attr('index');
    let value = container.find('.input-item-value').val();
    let description = container.find('.input-item-description').val();
    let comission = container.find('.input-item-comission').val();
    let hours = container.find('.input-item-hours').val();
    if(value==''){
        alertWarning('Debes ingresar un valor');
        flag = false;
    }
    if(hours == '' || hours == null || hours < 0){
        alertWarning('Debes ingresar las horas invertidas');
        flag = false;
    }
    if(flag){
        currentLicencesList[index].value = value;
        currentLicencesList[index].comission = comission;
        currentLicencesList[index].description = description;
        currentLicencesList[index].total = (value*(1+currentLicencesList[index].tax_value));
        currentLicencesList[index].hours = hours;
        alertSuccess('Licencia actualizada correctamente');
        showLicensesItems();
    }
    
}
function changeTimelyPayment(){
    let timely_payment = $(this).val();
    let cutoff_date = new Date(timely_payment);
    cutoff_date.setDate(cutoff_date.getDate()+15);
    current_container.find('.input-cutoff-date').val(cutoff_date.toISOString().split('T')[0]);
}
//Create purchase order
function createIncome(){
    let flag = true;
    let client_id = current_container.find('.input-client').val();
    let timely_payment = current_container.find('.input-timely-payment').val();
    let cutoff_date = current_container.find('.input-cutoff-date').val();
    let description = current_container.find('.input-description').val();
    let state = current_container.find('.state-input.selected').attr('value');
    if(client_id==null || client_id==''){
        current_container.find('.input-client').addClass('is-invalid');
        alertWarning('Debes seleccionar un cliente');
        flag = false;
    }else{
        current_container.find('.input-client').removeClass('is-invalid');
    }
    if(timely_payment==null || timely_payment==''){
        current_container.find('.input-timely-payment').addClass('is-invalid');
        alertWarning('Debes ingresar una fecha de pago');
        flag = false;
    }else{
        current_container.find('.input-timely-payment').removeClass('is-invalid');
    }
    if(cutoff_date==null || cutoff_date==''){
        current_container.find('.input-cutoff-date').addClass('is-invalid');
        alertWarning('Debes ingresar una fecha de corte');
        flag = false;
    }else{
        current_container.find('.input-cutoff-date').removeClass('is-invalid');
    }
    if(currentLicencesList.length==0){
        alertWarning('Debes ingresar al menos una licencia');
        flag = false;
    }
    if(flag){
        $('#create-income-button').attr('disabled',true);
        let DataSend = {
            state: state,
            client_id: client_id,
            client_identification: currentClient.identification,
            client_name: currentClient.name+(currentClient.last_name==null?'':' '+currentClient.last_name),
            timely_payment: timely_payment,
            cutoff_date: cutoff_date,
            description: description,
            licenses: currentLicencesList
        };
        PostMethodFunction('/admin/incomes/create',DataSend,null, successCreateIncome,function(){
            $('#create-income-button').attr('disabled',false);
        });
    }

}
function successCreateIncome(response){
    $('#create-income-button').attr('disabled',false);
    alertSuccess('Ingreso creado correctamente');
    currentLicencesList = [];
    showLicensesItems();
    current_container.find('.input-client').val('');
    current_container.find('.input-identification').text('');
    current_container.find('.input-timely-payment').val('');
    current_container.find('.input-cutoff-date').val('');
    current_container.find('.input-description').val('');
    current_container.find('.state-input').removeClass('selected');
    current_container.find('.state-input[value="0"]').addClass('selected');
    //go to list tab
    current_income = response.data.income;
    tabs_view['nav-list-tab'] = false;
    tabs_view['nav-update-tab'] = false;
    $('#nav-update-tab').tab('show');
    $('#nav-update-tab').trigger('click');
}
//Update purchase order
function showCurrentIncome(){
    currentLicencesList = [];
    current_container.find('.state-input').removeClass('selected');
    current_container.find('.state-input[value="'+current_income.state+'"]').addClass('selected');
    current_container.find('.input-client').val(current_income.client_id).change();
    current_container.find('.input-identification').text(current_income.client_identification);
    current_container.find('.input-timely-payment').val(current_income.timely_payment);
    current_container.find('.input-cutoff-date').val(current_income.cutoff_date);
    current_container.find('.input-description').val(current_income.description);
    current_container.find('.input-bill-name').val(current_income.bill_name);
    current_container.find('.input-bill-final-value').val(current_income.bill_final_value);
    if(current_income.state==0 || current_income.state==1 || current_income.state==2){
        current_container.find('#update-income-button').css('display','block');
        current_container.find('.input-client').attr('disabled',false);
        current_container.find('.input-timely-payment').attr('disabled',false);
        current_container.find('.input-cutoff-date').attr('disabled',false);
        current_container.find('.input-description').attr('disabled',false);
        current_container.find('.input-bill-name').attr('disabled',false);
        current_container.find('.input-bill-final-value').attr('disabled',false);
        current_container.find('.order-licenses-list-item-update').css('display','flex');
    }else{
        current_container.find('#update-income-button').css('display','none');
        current_container.find('.input-client').attr('disabled',true);
        current_container.find('.input-timely-payment').attr('disabled',true);
        current_container.find('.input-cutoff-date').attr('disabled',true);
        current_container.find('.input-description').attr('disabled',true);
        current_container.find('.input-bill-name').attr('disabled',true);
        current_container.find('.input-bill-final-value').attr('disabled',true);
        current_container.find('.order-licenses-list-item-update').css('display','none');
    }
    getIncomeLicenses();
}
function getIncomeLicenses(){
    let DataSend = {
        income_id: current_income.id
    };
    PostMethodFunction('/admin/incomes/get-licenses',DataSend,null, showIncomeLicenses,null);
}
function showIncomeLicenses(response){
    currentLicencesList = response.data;
    showLicensesItems();
}
function updateIncome(){
    let flag = true;
    let client_id = current_container.find('.input-client').val();
    let timely_payment = current_container.find('.input-timely-payment').val();
    let cutoff_date = current_container.find('.input-cutoff-date').val();
    let description = current_container.find('.input-description').val();
    let state = current_container.find('.state-input.selected').attr('value');
    let bill_name = current_container.find('.input-bill-name').val();
    let bill_final_value = current_container.find('.input-bill-final-value').val();
    if(client_id==null || client_id==''){
        current_container.find('.input-client').addClass('is-invalid');
        alertWarning('Debes seleccionar un cliente');
        flag = false;
    }else{
        current_container.find('.input-client').removeClass('is-invalid');
    }
    if(timely_payment==null || timely_payment==''){
        current_container.find('.input-timely-payment').addClass('is-invalid');
        alertWarning('Debes ingresar una fecha de pago');
        flag = false;
    }else{
        current_container.find('.input-timely-payment').removeClass('is-invalid');
    }
    if(cutoff_date==null || cutoff_date==''){
        current_container.find('.input-cutoff-date').addClass('is-invalid');
        alertWarning('Debes ingresar una fecha de corte');
        flag = false;
    }else{
        current_container.find('.input-cutoff-date').removeClass('is-invalid');
    }
    if(currentLicencesList.length==0){
        alertWarning('Debes ingresar al menos una licencia');
        flag = false;
    }
    if(state == 3){
        if(bill_name=='' || bill_name==null){
            alertWarning('Debes ingresar el nombre de la factura');
            flag = false;
        }
        if(bill_final_value=='' || bill_final_value==null){
            alertWarning('Debes ingresar el valor de la factura');
            flag = false;
        }
    }
    if(flag){
        $('#update-income-button').attr('disabled',true);
        $('.state-input-container').addClass('d-none');
        let DataSend = {
            id: current_income.id,
            state: state,
            client_id: client_id,
            client_identification: currentClient.identification,
            client_name: currentClient.name+(currentClient.last_name==null?'':' '+currentClient.last_name),
            timely_payment: timely_payment,
            cutoff_date: cutoff_date,
            description: description,
            bill_name: bill_name,
            bill_final_value: bill_final_value,
            licenses: currentLicencesList
        };
        PostMethodFunction('/admin/incomes/update',DataSend,null, successUpdateIncome,function(){
            $('#update-income-button').attr('disabled',false);
            $('.state-input-container').removeClass('d-none');
        });
    }
}
function successUpdateIncome(response){
    $('#update-income-button').attr('disabled',false);
    $('.state-input-container').removeClass('d-none');
    alertSuccess('Ingreso actualizado correctamente');
    current_income = response.data;
    current_container.find('.state-input').removeClass('selected');
    current_container.find('.state-input[value="'+current_income.state+'"]').addClass('selected');
    //open income document
    showIncomeOrder();
    //update list tab flag
    tabs_view['nav-list-tab'] = false;
    // Refresh the incomes list
    getIncomesPage();
}
function changePayState(){
    let flag = true;
    let bill_name = current_container.find('.input-bill-name').val();
    let bill_final_value = current_container.find('.input-bill-final-value').val();
    if(current_income.state != 3){ 
        if(bill_name=='' || bill_name==null){
            alertWarning('Debes ingresar el nombre de la factura');
            current_container.find('.input-bill-name').addClass('is-invalid');
            flag = false;
        }else{
            current_container.find('.input-bill-name').removeClass('is-invalid');
        }
        if(bill_final_value=='' || bill_final_value==null){
            alertWarning('Debes ingresar el valor de la factura');
            current_container.find('.input-bill-final-value').addClass('is-invalid');
            flag = false;
        }else{
            current_container.find('.input-bill-final-value').removeClass('is-invalid');
        }
    }else{
        flag = false;
    }
    if(flag){
        Swal.fire({
            title: '<span style="color:#484848 !important;">Pago</span>',
            html: '¿Está a punto de cambiar el estado de pago de este ingreso a pagado?<br><br><div class="form-check d-flex justify-content-center align-items-center gap-2"><input class="form-check-input mt-0" type="checkbox" id="swal-notify-client"><label class="form-check-label" for="swal-notify-client">Enviar correo de agradecimiento</label></div>',
            icon: 'success',
            iconColor: '#220245',
            showConfirmButton: true,
            confirmButtonText: 'Si, cambiar',
            confirmButtonColor: '#220245',
            showCancelButton: true,
            cancelButtonColor: '#C4C4C4',
            cancelButtonText: 'No, Cancelar',
            reverseButtons: true,
            width: ((window.innerWidth > 768) ? '768px' : '90%'),
            preConfirm: () => {
                return { notify: document.getElementById('swal-notify-client').checked };
            }
        }).then((result) => {
            if(result.isConfirmed){
                let notify_client = result.value.notify;
                let DataSend = {
                    income_id: current_income.id,
                    bill_name: bill_name,
                    bill_final_value: bill_final_value,
                    notify_client: notify_client
                };
                PostMethodFunction('/admin/incomes/change-state-to-pay',DataSend,null, function(response){
                    alertSuccess('Estado de pago cambiado correctamente');
                    current_income.state = 3;
                    current_income.bill_name = bill_name;
                    current_income.bill_final_value = bill_final_value;
                    showCurrentIncome();
                    //list tab update
                    tabs_view['nav-list-tab'] = false;
                },null);
            }
        });
    }else{
        /*revert state*/
        console.log('revert state');
        setTimeout(() => {
            $('#update-income-container').find('.state-input').removeClass('selected');
            $('#update-income-container').find('.state-input[value="'+current_income.state+'"]').click();
        }, 500);
    }
}
function changeInputState(){
    let state = $(this).attr('value');
    swallMessage(
        'Advertencia'
        , '¿Estás seguro de cambiar el estado de este ingreso?'
        , 'warning'
        , 'Si, cambiar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                income_id: current_income.id,
                state: state
            };
            PostMethodFunction('/admin/incomes/change-state',DataSend,null, function(response){
                alertSuccess('Estado cambiado correctamente');
                current_income.state = state;
                showCurrentIncome();
                //list tab update
                tabs_view['nav-list-tab'] = false;
                // Refresh the incomes list
                getIncomesPage();
            },null);
        }
        , null
    );
}
//Purchase Order Viewer
let receiversList = [];
function showIncomeOrder(openWindow = true){
    if(current_income != null){
        $('#order-viewer').attr('src','/storage/incomes/pdfs/'+current_income.unique_id+'.pdf?'+Date.now());
        if(openWindow){
            $('#order-viewer-container').css('display','flex');
            $('#erp-app-sidebar').css('visibility','hidden');
        }
    }
}
function closeOrderViewer(){
    $('#order-viewer-container').fadeOut(100);
    $('#erp-app-sidebar').css('visibility','visible');
    cancelSendOrder();
}
function printOrderPdf(){
    showIncomeOrder(false);
    var pdfViewer = document.getElementById("order-viewer");
    pdfViewer.onload = function() {
        pdfViewer.contentWindow.print();
        //remove pdf viewer after print
        pdfViewer.onload = null;
    };
}
function sendOrder(){
    $('#send-order-button').attr('disabled',true);
    let DataSend = {
        income_id: current_income.id
    };
    PostMethodFunction('/admin/incomes/get-licenses',DataSend,null, function(response){
        //get ids
        let license_ids = [];
        $.each(response.data, function(index, item){
            license_ids.push(item.license_id);
        });
        DataSend = {
            license_ids: license_ids
        };
        PostMethodFunction('/admin/licenses/notifications/get-by-licenses-ids',DataSend,null, function(response){
            receiversList = response.data;
            $('#send-order-button').attr('disabled',false);
            $('#send-order-container').fadeIn(100);
            showReceiversList();
        }, function(){$('#send-order-button').attr('disabled',false);});
    },function(){$('#send-order-button').attr('disabled',false);});
}
function showReceiversList(){
    let html = '';
    $.each(receiversList, function(index, item){
        html += '<tr class="receiver-item" index="'+index+'">';
            html += '<td class="columns-send-email text-left"><p>'+item.email+'</p></td>';
            html += '<td class="columns-send-phone text-left">'+item.phone+'</td>';
            html += '<td class="columns-send-actions text-center">';
                html += '<i class="receiver-item-delete fa-solid fa-trash-can align-self-center"></i>';
            html += '</td>';
        html += '</tr>';
    });
    html += '<tr class="receiver-item">';
        html += '<td class="columns-send-email text-left"><input type="text" class="form-control" name="email" placeholder="Correo"></td>';
        html += '<td class="columns-send-phone text-left"><input type="number" class="form-control" name="phone" placeholder="Teléfono"></td>';
        html += '<td class="columns-send-actions text-center">';
            html += '<i class="receiver-item-create fa-solid fa-plus align-self-center"></i>';
        html += '</td>';
    html += '</tr>';
    $('#receivers-list-body').html(html);
}
function cancelSendOrder(){
    $('#send-order-container').fadeOut(100);
}
function confirmSendOrder(){
    if(receiversList.length==0){
        alertWarning('Debes ingresar al menos un destinatario');
        return;
    }
    $('#confirm-send-order-button').attr('disabled',true);
    let dataSend = {
        income_id: current_income.id,
        receivers: receiversList
    };
    PostMethodFunction('/admin/incomes/send',dataSend,null, function(response){
        $('#confirm-send-order-button').attr('disabled',false);
        swallMessage(
            'Exito'
            , 'Orden de compra enviada correctamente'
            , 'success'
            , null
            , null
            , 30000
            , null
            , null
        );
        $('#send-order-container').fadeOut(100);
    },function(){$('#confirm-send-order-button').attr('disabled',false);});
}
function deleteReceiver(){
    let index = $(this).closest('.receiver-item').attr('index');
    receiversList.splice(index, 1);
    showReceiversList();
}
function createReceiver(){
    let flag = true;
    let email = $('#receivers-list-body input[name="email"]').val();
    let phone = $('#receivers-list-body input[name="phone"]').val();
    if(email=='' || email==null || !validateEmail(email)){
        alertWarning('Debes ingresar un correo válido');
        flag = false;
    }
    if(flag){
        let receiver = {
            email: email,
            phone: phone
        };
        receiversList.push(receiver);
        showReceiversList();
    }
}
function goToIncomesTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}

function getImportAssistantsExcel() {
    if (!$('#import-report-excel-icon').hasClass('fa-bounce')) {
        swallMessage(
            '<i class="fas fa-file-excel" style="font-size:100px; color:#220245; margin-bottom:1vh;"></i><br>Importar ingresos', 'Asegúrate de usar la plantilla oficial para que el proceso se realice correctamente.<br>Si aún no tienes la plantilla, puedes descargarla haciendo clic en el botón.<a href="/admin/incomes/download-template" id="download-assistant-template" target="_blank">Descargar plantilla <i class="fas fa-download"></i></a></a>¿deseas continuar?', null, 'Importar', 'Cancelar', null,
            function () {
                $('#import-report-excel-input').click();
            },
            null,
            '#10BE16'
        );
    }
}

function importAssistantsExcel() {
    let button = $(this);
    let icon = $('#import-report-excel-icon');
    let file = button.prop('files')[0];

    if (file != null) {
        icon.addClass('fa-bounce');
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('#import-report-excel-input').clone(true)[0]);

        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('#import-report-excel-input')[0].files = $('#import-report-excel-input')[0].files;

        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/incomes/import-massive-quotations', dinamicForm, null, function (response) {
            if(response.status == 0){
                let message = 'Algunos ingresos no pudieron ser importados, por favor verifica que los datos sean correctos y vuelve a intentarlo.';
                message += '<div class="assistant-import-error-container">';
                $.each(response.data, function(index, value){
                    message += '<div class="assistant-import-error-item">';
                        message += '<strong>Fila: '+value.row+': </strong> - '+value.message;
                    message += '</div>';
                });
                message += '</div>';
                swallMessage(
                    'Algunos inconvenientes', message, 'warning', 'Entendido', null, null, null, null
                );
            }else{
                swallMessage(
                    'Exitoso', 'Ingresos importados correctamente', 'success', null, null, 3000, null, null
                );
                getIncomesPage()
            }

           $('#import-report-excel-input').val('');
            icon.removeClass('fa-bounce');
        }, function () {
            button.val('');
            icon.removeClass('fa-bounce');
        });
    }
}

window.createSiigoInvoice = function(incomeId) {
    swallMessage(
        '¿Crear factura electrónica?'
        , '¿Está seguro que desea crear una factura electrónica para este ingreso?'
        , 'warning'
        , 'Sí, crear'
        , 'Cancelar'
        , null
        , function(){
            $.ajax({
                url: 'incomes/create-siigo-invoice',
                type: 'POST',
                data: {
                    income_id: incomeId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    swallMessage(
                        'Exitoso', 'Factura electrónica creada exitosamente', 'success', null, null, 3000, null, null
                    );
                    getIncomesPage()
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    swallMessage(
                        'Error', 'Error al crear la factura electrónica', 'error', null, null, 3000, null, null
                    );
                }
            });
        }
        , null
    );
};

//Advances (Abonos) Management
var current_advance_income = null;
var advances_list = [];
var current_advance = null;

function openAdvancesModal(){
    let income_id = $(this).closest('tr').attr('income-id');
    current_advance_income = incomes.find(income => income.id == income_id);
    if(current_advance_income != null){
        $('#advances-modal').fadeIn(300);
        $('#advances-modal-income-id').text(current_advance_income.unique_id.substr(current_advance_income.unique_id.length - 5));
        $('#advances-modal-income-client').text(current_advance_income.client_name);
        let income_total = parseFloat(current_advance_income.total) || 0;
        $('#advances-modal-income-total').text('$'+Math.round(income_total).toLocaleString('es-CO'));
        loadAdvancesList();
    }
}

function closeAdvancesModal(){
    $('#advances-modal').fadeOut(300);
    current_advance_income = null;
    advances_list = [];
    current_advance = null;
    hideAdvanceForm();
}

function loadAdvancesList(){
    GetMethodFunction('/admin/incomes/advances/get-by-income/'+current_advance_income.id, null, showAdvancesList, null);
}

function showAdvancesList(response){
    if(response.status == 1){
        advances_list = response.data.advances;
        let total_advances = parseFloat(response.data.total_advances) || 0;
        let balance_pending = parseFloat(response.data.balance_pending) || 0;
        let income_total = parseFloat(response.data.income_total) || 0;
        
        $('#advances-modal-total-advances').text('$'+Math.round(total_advances).toLocaleString('es-CO'));
        $('#advances-modal-balance-pending').text('$'+Math.round(balance_pending).toLocaleString('es-CO'));
        
        let appendContent = '';
        if(advances_list.length > 0){
            $.each(advances_list, function(index, value){
                let amount = parseFloat(value.amount) || 0;
                appendContent += '<tr class="advance-item" advance-id="'+value.id+'">';
                    appendContent += '<td class="text-center">'+value.payment_date+'</td>';
                    appendContent += '<td class="text-end">$'+Math.round(amount).toLocaleString('es-CO')+'</td>';
                    appendContent += '<td class="text-center">'+(value.payment_method || '-')+'</td>';
                    appendContent += '<td class="text-center">'+(value.reference || '-')+'</td>';
                    appendContent += '<td class="text-center">'+(value.user ? value.user.name : '-')+'</td>';
                    appendContent += '<td class="text-end">';
                        appendContent += '<i class="fa-solid fa-pen-to-square advance-item-edit" title="Editar"></i>';
                        appendContent += '<i class="fa-solid fa-trash advance-item-delete" title="Eliminar"></i>';
                    appendContent += '</td>';
                appendContent += '</tr>';
            });
        }else{
            appendContent = '<tr><td colspan="6" class="text-center">No hay abonos registrados</td></tr>';
        }
        $('#advances-list-body').empty().append(appendContent);
    }else{
        alertWarning(response.message);
    }
}

function showCreateAdvanceForm(){
    current_advance = null;
    $('#advance-form-title').text('Agregar Abono');
    $('#advance-form-amount').val('');
    $('#advance-form-date').val(new Date().toISOString().split('T')[0]);
    $('#advance-form-method').val('');
    $('#advance-form-reference').val('');
    $('#advance-form-notes').val('');
    $('#advance-form-container').slideDown(300);
    $('#create-advance-button').hide();
}

function hideAdvanceForm(){
    $('#advance-form-container').slideUp(300);
    $('#create-advance-button').show();
    current_advance = null;
}

function saveAdvance(){
    let amount = $('#advance-form-amount').val();
    let payment_date = $('#advance-form-date').val();
    let payment_method = $('#advance-form-method').val();
    let reference = $('#advance-form-reference').val();
    let notes = $('#advance-form-notes').val();
    
    if(!amount || amount <= 0){
        alertWarning('Debe ingresar un monto válido');
        return;
    }
    
    if(!payment_date){
        alertWarning('Debe seleccionar una fecha de pago');
        return;
    }
    
    let DataSend = {
        income_id: current_advance_income.id,
        amount: amount,
        payment_date: payment_date,
        payment_method: payment_method,
        reference: reference,
        notes: notes
    };
    
    if(current_advance){
        // Update
        PostMethodFunction('/admin/incomes/advances/update/'+current_advance.id, DataSend, null, function(response){
            if(response.status == 1){
                alertSuccess('Abono actualizado correctamente');
                loadAdvancesList();
                hideAdvanceForm();
                // Refresh the incomes list
                tabs_view['nav-list-tab'] = false;
                getIncomesPage();
            }else{
                alertWarning(response.message);
            }
        }, null);
    }else{
        // Create
        PostMethodFunction('/admin/incomes/advances/create', DataSend, null, function(response){
            if(response.status == 1){
                alertSuccess('Abono creado correctamente');
                loadAdvancesList();
                hideAdvanceForm();
                // Refresh the incomes list
                tabs_view['nav-list-tab'] = false;
                getIncomesPage();
            }else{
                alertWarning(response.message);
            }
        }, null);
    }
}

function editAdvance(){
    let advance_id = $(this).closest('tr').attr('advance-id');
    current_advance = advances_list.find(advance => advance.id == advance_id);
    
    if(current_advance){
        $('#advance-form-title').text('Editar Abono');
        $('#advance-form-amount').val(current_advance.amount);
        $('#advance-form-date').val(current_advance.payment_date);
        $('#advance-form-method').val(current_advance.payment_method || '');
        $('#advance-form-reference').val(current_advance.reference || '');
        $('#advance-form-notes').val(current_advance.notes || '');
        $('#advance-form-container').slideDown(300);
        $('#create-advance-button').hide();
    }
}

function deleteAdvance(){
    let advance_id = $(this).closest('tr').attr('advance-id');
    
    // Usar los mismos colores que swallMessage
    Swal.fire({
        title: '<span style="color:#484848 !important;">Eliminar Abono</span>',
        html: '¿Está seguro de eliminar este abono?',
        icon: 'warning',
        iconColor: '#220245',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#220245',
        cancelButtonColor: '#C4C4C4',
        reverseButtons: true,
        width: ((window.innerWidth > 768) ? '768px' : '90%'),
        customClass: {
            container: 'swal-high-zindex'
        },
        didOpen: () => {
            // Asegurar que el swal esté por encima del modal
            $('.swal2-container').css('z-index', '99999');
        }
    }).then((result) => {
        if (result.isConfirmed) {
            PostMethodFunction('/admin/incomes/advances/delete/'+advance_id, {}, null, function(response){
                if(response.status == 1){
                    alertSuccess('Abono eliminado correctamente');
                    loadAdvancesList();
                    // Refresh the incomes list
                    tabs_view['nav-list-tab'] = false;
                    getIncomesPage();
                }else{
                    alertWarning(response.message);
                }
            }, null);
        }
    });
}