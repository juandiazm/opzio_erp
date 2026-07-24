import { loadPdfViewer, pdfPrevPage, pdfNextPage, pdfZoomIn, pdfZoomOut, printPdf, downloadPdf, sharePdf, fullscreenPdf, initPdfViewer, destroyPdfViewer } from '../../pdf-viewer.js';
$(document).on('click', '#nav-tab .nav-link', changeTab);
//list incomes
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getIncomesPage();
});
$(document).on('change', '#state-list-input', function(){
    db_pagination.page = 1;
    getIncomesPage();
});
$(document).on('click', '.list-view-order', function(){
    let income_id = $(this).closest('tr').attr('income-id');
    current_income = incomes.find(income => income.id == income_id);
    showIncomeOrder();
});
$(document).on('click', '.list-update-traceability', function(){
    current_income = incomes.find(income => income.id == $(this).closest('.income-row-info').attr('income-id'));
    goToIncomesTraceability('id%'+current_income.id);
});
$(document).on('click', '.list-go-to-pay', function(){
    goToPay($(this).attr('unique_id'));
});
//Purchase Order Viewer
$(document).on('click', '#close-order-viewer', closeOrderViewer);
$(document).on('click', '#pdf-prev-page', pdfPrevPage);
$(document).on('click', '#pdf-next-page', pdfNextPage);
$(document).on('click', '#pdf-zoom-in', pdfZoomIn);
$(document).on('click', '#pdf-zoom-out', pdfZoomOut);
$(document).on('click', '#pdf-print', printPdf);
$(document).on('click', '#pdf-download', downloadPdf);
$(document).on('click', '#pdf-share', sharePdf);
$(document).on('click', '#pdf-fullscreen', fullscreenPdf);
$(document).ready(function() { initPdfViewer(); });
$(document).on('click', '#send-order-button', sendOrder);
$(document).on('click', '#cancel-send-order-button', cancelSendOrder);
$(document).on('click', '#confirm-send-order-button', confirmSendOrder);
$(document).on('click', '.receiver-item-delete', deleteReceiver);
$(document).on('click', '.receiver-item-create', createReceiver);
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
$(document).ready(function(){
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
    let DataSend = {
        pagination: db_pagination,
        search: $('#search-list-input').val(),
        state: $('#state-list-input').val(),
    };
    PostMethodFunction('/client/payments/get-page',DataSend,null, showIncomesPage,null);
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
            appendContent += '<td class="columns-bill text-center" title="">'+(value.bill==null?'':+'<a href="'+value.bill+'" target="_blank">Ver factura</a>')+'</td>';
            appendContent += '<td class="columns-created-at text-center"><p>'+value.created_at_string+'</p></td>';
            appendContent += '<td class="columns-state text-center active-col"><label class="selected active-state state-'+value.state+'">'+(value.state_text)+'</label></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.payment_state!=1 && value.state!=1){
                    appendContent += '<i class="fa-solid fa-credit-card-front list-go-to-pay" unique_id="'+value.unique_id+'"></i>';
                }
                appendContent += '<i class="fa-solid fa-eye list-view-order"></i>';
                appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#income-list-table #income-list-table-body').empty().append(appendContent);
    DBshowPagination();
}
function goToPay(unique_id){
    url = '/client/payments/pay/'+unique_id+'?close_tab=true';
    //open in new tab
    window.open(url, '_blank');
    swallMessage(
        'Proceso de pago'
        , 'Refresca la página para ver los cambios'
        , 'success'
        , 'Entendido'
        , null
        , null
        , function(){
            getIncomesPage();
        }
        , null
    );
}

//Purchase Order Viewer
let receiversList = [];
function showIncomeOrder(openWindow = true){
    if(current_income != null){
        let pdfUrl = '/storage/incomes/pdfs/'+current_income.unique_id+'.pdf?'+Date.now();
        loadPdfViewer(pdfUrl);
        if(openWindow){
            $('#order-viewer-container').css('display','flex');
            $('#erp-app-sidebar').css('visibility','hidden');
            $('#menu-nav').css('visibility','hidden');
        }
    }
}
function closeOrderViewer(){
    destroyPdfViewer();
    $('#order-viewer-container').fadeOut(100);
    $('#erp-app-sidebar').css('visibility','visible');
    $('#menu-nav').css('visibility','visible');
    cancelSendOrder();
}
function printOrderPdf(){
    printPdf();
}
function sendOrder(){
    $('#send-order-button').attr('disabled',true);
    let DataSend = {
        income_id: current_income.id
    };
    PostMethodFunction('/client/payments/get-licenses',DataSend,null, function(response){
        //get ids
        let license_ids = [];
        $.each(response.data, function(index, item){
            license_ids.push(item.license_id);
        });
        DataSend = {
            license_ids: license_ids
        };
        PostMethodFunction('/client/licenses/notifications/get-by-licenses-ids',DataSend,null, function(response){
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
    PostMethodFunction('/client/payments/send',dataSend,null, function(response){
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