import { incomeState } from './state.js';
import { renderEntityAvatar } from '../shared/list.js';
import { setUpdateRecordId } from '../layouts/record-url.js';

const incomeStateIcons = {
    0: 'fa-file-pen',
    1: 'fa-circle-xmark',
    2: 'fa-circle-check',
    3: 'fa-money-bill-wave',
    4: 'fa-file-invoice-dollar',
};

function renderIncomeState(income){
    const state = Number(income.state);
    const icon = incomeStateIcons[state] || 'fa-circle-question';
    return '<span class="erp-status income-state-status status-state-'+state+'" title="'+income.state_text+'"><i class="fa-solid '+icon+' erp-status-icon" aria-hidden="true"></i><span class="erp-status-label">'+income.state_text+'</span></span>';
}

function renderSiigoAction(income, client){
    if(income.siigo_invoice_url) return '<a href="'+income.siigo_invoice_url+'" target="_blank" title="Ver factura electrónica"><i class="fa-solid fa-file-invoice text-primary"></i></a>';
    if(Number(income.state) < 2) return '<span class="text-muted" title="La factura estará disponible cuando el ingreso sea aprobado"><i class="fa-solid fa-clock"></i></span>';
    if(!client || !client.siigo_ready){
        const missing = client && client.siigo_missing_fields ? client.siigo_missing_fields.join(', ') : 'datos del cliente';
        return '<span class="text-warning" title="Completa: '+missing+'"><i class="fa-solid fa-user-clock"></i></span>';
    }
    return '<a href="javascript:void(0)" onclick="window.createSiigoInvoice('+income.id+')" title="Crear factura electrónica"><i class="fa-solid fa-file-circle-plus text-success"></i></a>';
}

function renderIncomeAging(income){
    const state = Number(income.state);
    if(state === 3 || Number(income.payment_state) === 1){
        return '<span class="income-aging income-aging-paid">Pagado</span>';
    }
    if(income.deleted_at || ![2, 4].includes(state)){
        return '<span class="text-muted">—</span>';
    }

    const daysOverdue = Number(income.days_overdue) || 0;
    if(daysOverdue > 0){
        const dayLabel = daysOverdue === 1 ? 'día' : 'días';
        return '<span class="income-aging income-aging-overdue" title="En mora '+daysOverdue+' '+dayLabel+'">-'+daysOverdue+'</span>';
    }

    const daysUntilCutoff = Number(income.days_until_cutoff) || 0;
    const title = daysUntilCutoff === 0
        ? 'Vence hoy'
        : 'Faltan '+daysUntilCutoff+' '+(daysUntilCutoff === 1 ? 'día' : 'días');
    return '<span class="income-aging income-aging-upcoming" title="'+title+'">'+daysUntilCutoff+'</span>';
}

export function updateSortHeaders(){
    $('#income-list-table-header th[data-sort-key]').each(function(){
        const header = $(this);
        const isActive = header.attr('data-sort-key') === incomeState.sortBy;
        const direction = incomeState.sortDirection === 'asc' ? 'asc' : 'desc';
        const label = header.attr('data-sort-label');
        const icon = header.find('.income-sort-indicator');

        header.attr('aria-sort', isActive ? (direction === 'asc' ? 'ascending' : 'descending') : 'none');
        icon.removeClass('fa-sort fa-sort-up fa-sort-down').addClass(
            isActive ? (direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'
        );
        header.find('.income-sort-button').attr(
            'aria-label',
            'Ordenar por '+label+(isActive ? ' ('+(direction === 'asc' ? 'ascendente' : 'descendente')+')' : '')
        );
    });
}

export function updateStateFilterSummary(){
    const options = $('.income-state-filter');
    const selected = options.filter(':checked');
    let summary = 'Todos los estados';

    if(selected.length === 1){
        summary = selected.first().closest('.income-state-filter-option').find('.erp-status-label').text().trim();
    }else if(selected.length > 1 && selected.length < options.length){
        summary = selected.length+' estados seleccionados';
    }

    $('#income-state-filter-summary').text(summary);
    $('#income-state-filter-toggle').attr('aria-label', 'Filtrar por estado: '+summary);
}

function updateIncomeStateFilterTotals(){
    $('#income-state-filter-total-all').text('$'+incomeState.incomeStatesTotals['-1'].total.toLocaleString('es-CO'));
    ['0', '1', '2', '3', '4'].forEach(function(state){
        $('#income-state-filter-total-'+state).text(
            '$'+incomeState.incomeStatesTotals[state].total.toLocaleString('es-CO')
        );
    });
}

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

export function toggleIncomeSort(sortBy){
    if(incomeState.sortBy === sortBy){
        incomeState.sortDirection = incomeState.sortDirection === 'asc' ? 'desc' : 'asc';
    }else{
        incomeState.sortBy = sortBy;
        incomeState.sortDirection = 'asc';
    }
    incomeState.pagination.page = 1;
    updateSortHeaders();
    getIncomesPage();
}

export function getIncomesPage(){
    if(incomeState.incomeId != null && incomeState.incomeId != '' && incomeState.incomeId != 0) $('#search-list-input').val(incomeState.incomeId);
    PostMethodFunction('/admin/incomes/get-page', {
        pagination: incomeState.pagination,
        search: $('#search-list-input').val(),
        states: $('.income-state-filter:checked').map(function(){ return this.value; }).get(),
        due_status: $('#due-status-list-input').val(),
        sort_by: incomeState.sortBy,
        sort_direction: incomeState.sortDirection,
    }, null, showIncomesPage, null);
}

export function setCurrentIncomeFromRow(row){
    let incomeId = $(row).closest('.income-row-info').attr('income-id');
    incomeState.currentIncome = incomeState.incomes.find(income => income.id == incomeId);
    return incomeState.currentIncome;
}

export function goToUpdateTab(row){
    let incomeId = $(row).parent().parent().attr('income-id');
    incomeState.currentIncome = incomeState.incomes.find(income => income.id == incomeId);
    if(incomeState.currentIncome != null){ setUpdateRecordId(incomeState.currentIncome.unique_id || incomeState.currentIncome.id); incomeState.tabsView['nav-update-tab'] = false; $('#nav-update-tab').removeClass('d-none').tab('show'); $('#nav-update-tab').trigger('click'); }
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
    updateSortHeaders();
    let appendedContent = '';
    $.each(incomeState.incomes, function(index, value){
        value.total = Math.round(value.total);
        const associatedClient = value.client || null;
        appendedContent += '<tr income-id='+value.id+' class="income-row-info'+(value.state != 1 ? '' : ' deleted')+'">';
        appendedContent += '<td class="columns-identity text-start erp-identity-cell" title="'+value.unique_id+'"><div class="erp-identity">'+renderEntityAvatar(associatedClient, 'clients')+'<div class="erp-identity-copy"><p class="erp-identity-name" title="'+value.client_name+'">'+value.client_name+'</p><span class="erp-identity-meta"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+value.unique_id+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+value.unique_id.substr(value.unique_id.length - 5)+'</span></span></div></div></td>';
        appendedContent += '<td class="columns-cycle text-center"><div class="erp-meta-stack"><span>Pago oportuno: '+value.timely_payment+'</span><small>Corte: '+value.cutoff_date+'</small></div></td>';
        appendedContent += '<td class="columns-total text-end" title="'+value.total+'"><p>$'+value.total.toLocaleString('es-CO')+'</p></td>';
        appendedContent += '<td class="columns-bill text-center"><div class="erp-meta-stack erp-bill-meta"><span>'+(value.bill_name == null ? '-' : value.bill_name)+'</span><span>'+renderSiigoAction(value, associatedClient)+'</span></div></td>';
        appendedContent += '<td class="columns-payment-date text-center"><p>'+(value.payment_date || '—')+'</p></td>';
        appendedContent += '<td class="columns-aging text-center">'+renderIncomeAging(value)+'</td>';
        appendedContent += '<td class="columns-created-at text-center"><p>'+value.created_at_string+'</p></td>';
        appendedContent += '<td class="columns-state text-center">'+renderIncomeState(value)+'</td><td class="columns-actions text-end action-cell">';
        const paymentLinkAvailable = value.payment_state != 1 && value.state != 1;
        if(paymentLinkAvailable){
            appendedContent += '<i class="fa-regular fa-link copy-action me-1 list-pay-link" data-clipboard-text="'+window.location.origin+'/client/payments/pay/'+value.unique_id+'" title="Copiar link de pago" aria-label="Copiar link de pago"></i>';
            appendedContent += '<i class="fa-solid fa-bell list-payment-reminder" title="Enviar recordatorio de pago" aria-label="Enviar recordatorio de pago"></i>';
        }
        appendedContent += '<i class="fa-solid fa-receipt list-view-order"></i>';
        if(value.state != 1){ if(value.state == 2) appendedContent += '<i class="fa-solid fa-hand-holding-dollar list-manage-advances" title="Gestionar abonos"></i>'; appendedContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>'; }
        else appendedContent += '<i class="fa-solid fa-eye list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>';
        appendedContent += '</td></tr>';
        if(value.state in incomeState.incomeStatesTotals) incomeState.incomeStatesTotals[value.state].total += value.total;
        incomeState.incomeStatesTotals['-1'].total += value.total;
    });
    $('#income-list-table #income-list-table-body').empty().append(appendedContent);
    showPagination();
    updateIncomeStateFilterTotals();
    updateStateFilterSummary();
    if(incomeState.incomeId != null && incomeState.incomeId != '' && incomeState.incomeId != 0){
        if(incomeState.incomes.length > 0){ incomeState.currentIncome = incomeState.incomes[0]; if(incomeState.currentIncome != null){ setUpdateRecordId(incomeState.currentIncome.unique_id || incomeState.currentIncome.id); incomeState.tabsView['nav-update-tab'] = false; $('#nav-update-tab').removeClass('d-none').tab('show'); $('#nav-update-tab').trigger('click'); } }
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
        $.ajax({url: 'incomes/create-siigo-invoice', type: 'POST', data: {income_id: incomeId, _token: $('meta[name="csrf-token"]').attr('content')}, success: function(){ swallMessage('Exitoso', 'Factura electrónica creada exitosamente', 'success', null, null, 3000, null, null); getIncomesPage(); }, error: function(xhr){ const response = xhr.responseJSON || {}; const missing = response.data && response.data.missing_fields ? '<br><br>Falta: '+response.data.missing_fields.join(', ') : ''; swallMessage('No se puede facturar', (response.message || 'Error al crear la factura electrónica')+missing, 'warning', null, null, 5000, null, null); }});
    }, null);
}