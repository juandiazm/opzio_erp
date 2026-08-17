import { incomeState } from './state.js';

export function showPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();
    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for(let index = 1; index <= incomeState.pagination.totalPages; index++) {
        closePage = Math.abs(incomeState.pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
        }else if(index <= showPageSize){
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
            if(!dots.left && index == showPageSize){ appendedContent += '<li class="page-item" title="'+index+'"><p class="page-link">...</p></li>'; dots.left = true; }
        }else if(index >= incomeState.pagination.totalPages - 2){
            if(!dots.right){ appendedContent += '<li class="page-item" title="'+index+'"><p class="page-link">...</p></li>'; dots.right = true; }
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == incomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
        }
    }
    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(incomeState.pagination.total > 5) appendedContent += '<li class="page-item"><p class="page-link"><select id="db-pagination-per-page" aria-label="Default select example"><option value="5"'+(incomeState.pagination.per_page == 5 ? ' selected' : '')+'>5</option><option value="10"'+(incomeState.pagination.per_page == 10 ? ' selected' : '')+'>10</option><option value="50"'+(incomeState.pagination.per_page == 50 ? ' selected' : '')+'>50</option></select></p></li>';
    paginationContainer.append(appendedContent);
}

export function changePageSize(){ incomeState.pagination.per_page = $('#db-pagination-per-page').val(); incomeState.pagination.page = 1; getIncomesPage(); }
export function changePage(){ let selectedPage = $(this).attr('title'); if(selectedPage != incomeState.pagination.page){ incomeState.pagination.page = selectedPage; getIncomesPage(); } }
export function selectBackPage(){ if(incomeState.pagination.page > 1){ incomeState.pagination.page = parseInt(incomeState.pagination.page) - 1; getIncomesPage(); } }
export function selectNextPage(){ if(incomeState.pagination.page < incomeState.pagination.totalPages){ incomeState.pagination.page = parseInt(incomeState.pagination.page) + 1; getIncomesPage(); } }

export function getIncomesPage(){
    if(incomeState.incomeId != null && incomeState.incomeId != '' && incomeState.incomeId != 0) $('#search-list-input').val(incomeState.incomeId);
    PostMethodFunction('/admin/incomes/get-page', {pagination: incomeState.pagination, search: $('#search-list-input').val(), state: $('#state-list-input').val()}, null, showIncomesPage, null);
}

export function setCurrentIncomeFromRow(row){
    let incomeId = $(row).closest('.income-row-info').attr('income-id');
    incomeState.currentIncome = incomeState.incomes.find(income => income.id == incomeId);
    return incomeState.currentIncome;
}

export function goToUpdateTab(row){
    let incomeId = $(row).parent().parent().attr('income-id');
    incomeState.currentIncome = incomeState.incomes.find(income => income.id == incomeId);
    if(incomeState.currentIncome != null){ incomeState.tabsView['nav-update-tab'] = false; $('#nav-update-tab').tab('show'); $('#nav-update-tab').trigger('click'); }
}

export function showIncomesPage(response){
    incomeState.incomeStatesTotals = {
        '-1': {total: 0, text: 'Eliminado', class: 'state-1'},
        '0': {total: 0, text: 'Pendiente', class: 'state-0'},
        '1': {total: 0, text: 'Rechazada', class: 'state-1'},
        '2': {total: 0, text: 'Aprobada', class: 'state-2'},
        '3': {total: 0, text: 'Pagada', class: 'state-3'},
        '4': {total: 0, text: 'Facturada', class: 'state-4'},
    };
    incomeState.pagination = response.pagination;
    incomeState.incomes = response.data;
    let appendedContent = '';
    $.each(incomeState.incomes, function(index, value){
        value.total = Math.round(value.total);
        appendedContent += '<tr income-id='+value.id+' class="income-row-info'+(value.state != 1 ? '' : ' deleted')+'">';
        appendedContent += '<td class="columns-id text-left" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
        appendedContent += '<td class="columns-client text-start" title="'+value.client_name+'"><p>'+value.client_name+'</p></td>';
        appendedContent += '<td class="columns-timely-payment text-center"><p>'+value.timely_payment+'</p></td>';
        appendedContent += '<td class="columns-cutoff-date text-center"><p>'+value.cutoff_date+'</p></td>';
        appendedContent += '<td class="columns-total text-end" title="'+value.total+'"><p>$'+value.total.toLocaleString('es-CO')+'</p></td>';
        appendedContent += '<td class="columns-bill text-center" title="">'+(value.bill_name == null ? '' : value.bill_name)+'</td>';
        appendedContent += '<td class="columns-created-at text-center"><p>'+value.created_at_string+'</p></td>';
        appendedContent += '<td class="columns-bill text-center">'+(value.siigo_invoice_url ? '<a href="'+value.siigo_invoice_url+'" target="_blank" title="Ver factura electrónica"><i class="fa-solid fa-file-invoice text-primary"></i></a>' : '<a href="javascript:void(0)" onclick="window.createSiigoInvoice('+value.id+')" title="Crear factura electrónica"><i class="fa-solid fa-file-circle-plus text-success"></i></a>')+'</td>';
        appendedContent += '<td class="columns-state text-center active-col"><label class="selected active-state state-'+value.state+'">'+value.state_text+'</label></td><td class="columns-actions text-end action-cell">';
        if(value.payment_state != 1 && value.state != 1) appendedContent += '<i class="fa-regular fa-link copy-action me-1 list-pay-link" data-clipboard-text="'+window.location.origin+'/client/payments/pay/'+value.unique_id+'"></i>';
        appendedContent += '<i class="fa-solid fa-receipt list-view-order"></i>';
        if(value.state != 1){ if(value.state == 2) appendedContent += '<i class="fa-solid fa-hand-holding-dollar list-manage-advances" title="Gestionar abonos"></i>'; appendedContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>'; }
        else appendedContent += '<i class="fa-solid fa-eye list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>';
        appendedContent += '</td></tr>';
        if(value.state in incomeState.incomeStatesTotals) incomeState.incomeStatesTotals[value.state].total += value.total;
        incomeState.incomeStatesTotals['-1'].total += value.total;
    });
    $('#income-list-table #income-list-table-body').empty().append(appendedContent);
    showPagination();
    let selectedState = $('#state-list-input').val();
    $('#state-list-input').empty();
    $('#state-list-input').append('<option value="-1" '+(selectedState == '-1' ? 'selected' : '')+'>Todos ($'+incomeState.incomeStatesTotals['-1'].total.toLocaleString('es-CO')+')</option>');
    $('#state-list-input').append('<option value="0" '+(selectedState == 0 ? 'selected' : '')+'>Cotizaciones ($'+incomeState.incomeStatesTotals[0].total.toLocaleString('es-CO')+')</option>');
    $('#state-list-input').append('<option value="1" '+(selectedState == 1 ? 'selected' : '')+'>Rechazadas ($'+incomeState.incomeStatesTotals[1].total.toLocaleString('es-CO')+')</option>');
    $('#state-list-input').append('<option value="2" '+(selectedState == 2 ? 'selected' : '')+'>Aprobadas ($'+incomeState.incomeStatesTotals[2].total.toLocaleString('es-CO')+')</option>');
    $('#state-list-input').append('<option value="3" '+(selectedState == 3 ? 'selected' : '')+'>Pagadas ($'+incomeState.incomeStatesTotals[3].total.toLocaleString('es-CO')+')</option>');
    $('#state-list-input').append('<option value="4" '+(selectedState == 4 ? 'selected' : '')+'>Facturadas ($'+incomeState.incomeStatesTotals[4].total.toLocaleString('es-CO')+')</option>');
    if(incomeState.incomeId != null && incomeState.incomeId != '' && incomeState.incomeId != 0){
        if(incomeState.incomes.length > 0){ incomeState.currentIncome = incomeState.incomes[0]; if(incomeState.currentIncome != null){ incomeState.tabsView['nav-update-tab'] = false; $('#nav-update-tab').tab('show'); $('#nav-update-tab').trigger('click'); } }
        incomeState.incomeId = null;
    }
}

export function deleteIncome(incomeId){
    swallMessage('Advertencia', '¿Está seguro de eliminar este empleado?', 'error', 'Si, eliminar', 'No', null, function(){
        PostMethodFunction('/admin/incomes/delete', {id: incomeId}, null, function(response){
            alertSuccess('Empleado eliminado');
            if(incomeState.currentTab == 'nav-update-tab') incomeState.currentIncome.deleted_at = response.data.deleted_at; else getIncomesPage();
            incomeState.tabsView['nav-list-tab'] = false;
        }, null);
    }, null);
}

export function restoreIncome(incomeId){
    swallMessage('Advertencia', '¿Está seguro de restaurar este empleado?', 'warning', 'Si, restaurar', 'No', null, function(){
        PostMethodFunction('/admin/incomes/restore', {id: incomeId}, null, function(response){
            alertSuccess('Empleado restaurado');
            if(incomeState.currentTab == 'nav-update-tab') incomeState.currentIncome.deleted_at = null; else getIncomesPage();
            incomeState.tabsView['nav-list-tab'] = false;
        }, null);
    }, null);
}

export function createSiigoInvoice(incomeId){
    swallMessage('¿Crear factura electrónica?', '¿Está seguro que desea crear una factura electrónica para este ingreso?', 'warning', 'Sí, crear', 'Cancelar', null, function(){
        $.ajax({url: 'incomes/create-siigo-invoice', type: 'POST', data: {income_id: incomeId, _token: $('meta[name="csrf-token"]').attr('content')}, success: function(){ swallMessage('Exitoso', 'Factura electrónica creada exitosamente', 'success', null, null, 3000, null, null); getIncomesPage(); }, error: function(xhr){ console.error('Error:', xhr); swallMessage('Error', 'Error al crear la factura electrónica', 'error', null, null, 3000, null, null); }});
    }, null);
}