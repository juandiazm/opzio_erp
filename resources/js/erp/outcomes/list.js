import { outcomeState } from './state.js';

function escapeHtml(value){ return $('<div>').text(value == null ? '' : value).html(); }
function associationLabel(value){
    if(!value) return '-';
    return value.label || [value.name, value.last_name || value.lastname].filter(Boolean).join(' ') || value.complete_name || '-';
}

export function showPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();
    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for(let index = 1; index <= outcomeState.pagination.totalPages; index++){
        closePage = Math.abs(outcomeState.pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
        }else if(index <= showPageSize){
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
            if(!dots.left && index == showPageSize){ appendedContent += '<li class="page-item" title="'+index+'"><p class="page-link">...</p></li>'; dots.left = true; }
        }else if(index >= outcomeState.pagination.totalPages - 2){
            if(!dots.right){ appendedContent += '<li class="page-item" title="'+index+'"><p class="page-link">...</p></li>'; dots.right = true; }
            if(String(index).length < 3) appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'">'+index+'</p></li>';
            else appendedContent += '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == outcomeState.pagination.page ? ' active' : '')+'"><small>'+index+'</small></p></li>';
        }
    }
    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(outcomeState.pagination.total > 5) appendedContent += '<li class="page-item"><p class="page-link"><select id="db-pagination-per-page" aria-label="Default select example"><option value="5"'+(outcomeState.pagination.size == 5 ? ' selected' : '')+'>5</option><option value="10"'+(outcomeState.pagination.size == 10 ? ' selected' : '')+'>10</option><option value="50"'+(outcomeState.pagination.size == 50 ? ' selected' : '')+'>50</option></select></p></li>';
    paginationContainer.append(appendedContent);
}

export function getOutcomesPage(){
    let dataSend = {page: outcomeState.pagination.page, size: outcomeState.pagination.size, search: $('#search-list-input').val().trim(), from: $('#date-from').val() || null, to: $('#date-to').val() || null};
    PostMethodFunction('/admin/outcomes/get', dataSend, null, showOutcomesPage, null);
}

export function goToUpdateTab(){
    const outcomeId = $(this).closest('tr').attr('outcome-id');
    outcomeState.currentOutcome = outcomeState.outcomes.find(outcome => outcome.id == outcomeId);
    if(outcomeState.currentOutcome == null || outcomeState.currentOutcome.deleted_at != null) return;
    outcomeState.tabsView['nav-update-tab'] = false;
    $('#nav-update-tab').removeClass('d-none').tab('show');
    $('#nav-update-tab').trigger('click');
}

export function showOutcomesPage(response){
    outcomeState.pagination = response.pagination;
    outcomeState.outcomes = response.data;
    let appendedContent = '';
    $.each(outcomeState.outcomes, function(index, value){
        value.total = Math.round(value.total);
        appendedContent += '<tr outcome-id='+value.id+' class="'+(value.deleted_at == null ? '' : ' deleted')+'">';
        const outcomeDate = value.date ? value.date.substring(0, 10) : '-';
        const associationItems = [
            ['Proveedor', associationLabel(value.provider)],
            ['Empleado', associationLabel(value.employee)],
            ['Departamento', associationLabel(value.department)],
            ['Usuario', associationLabel(value.user)],
            ['Cliente', associationLabel(value.client)],
        ].map(function(item){ return '<small><span class="erp-meta-label">'+item[0]+':</span> '+escapeHtml(item[1])+'</small>'; }).join('');
        let displayType = (value.type === -1) ? 'Otro' : 'Otro';
        appendedContent += '<td class="columns-identity text-start erp-identity-cell"><div class="erp-identity erp-identity-plain"><div class="erp-identity-copy"><p class="erp-identity-name" title="'+escapeHtml(value.name)+'">'+escapeHtml(value.name)+'</p><span class="erp-identity-meta" title="'+escapeHtml(value.unique_id)+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+escapeHtml(value.unique_id)+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+escapeHtml(value.unique_id.substr(value.unique_id.length - 5))+'</span><span> - '+outcomeDate+'</span></span></div></div></td>';
        appendedContent += '<td class="columns-detail text-start"><div class="erp-meta-stack"><span>'+displayType+'</span><small title="'+escapeHtml(value.description)+'">'+escapeHtml(value.description == null ? '-' : value.description)+'</small></div></td>';
        appendedContent += '<td class="columns-associations text-start"><div class="erp-meta-stack erp-association-stack">'+associationItems+'</div></td>';
        appendedContent += '<td class="columns-total text-end" title="'+value.amount+'"><p class="erp-amount">$'+parseInt(value.amount).toLocaleString('es-CO')+'</p></td>';
        appendedContent += '<td class="columns-actions text-center action-cell">';
        if(value.deleted_at == null) appendedContent += '<i class="fa fa-pen-to-square edit-outcome" title="Editar"></i><i class="fa fa-trash-alt delete-outcome" title="Eliminar"></i>';
        else appendedContent += '<i class="fa fa-ban recover-outcome" title="Recuperar" style="color: red;"></i>';
        appendedContent += '</td></tr>';
    });
    $('#outcome-list-table #outcome-list-table-body').empty().append(appendedContent);
    showPagination();
}

export function changePageSize(){ outcomeState.pagination.size = $('#db-pagination-per-page').val(); outcomeState.pagination.page = 1; getOutcomesPage(); }
export function changePage(){ let selectedPage = $(this).attr('title'); if(selectedPage != outcomeState.pagination.page){ outcomeState.pagination.page = selectedPage; getOutcomesPage(); } }
export function selectBackPage(){ if(outcomeState.pagination.page > 1){ outcomeState.pagination.page = parseInt(outcomeState.pagination.page) - 1; getOutcomesPage(); } }
export function selectNextPage(){ if(outcomeState.pagination.page < outcomeState.pagination.totalPages){ outcomeState.pagination.page = parseInt(outcomeState.pagination.page) + 1; getOutcomesPage(); } }

export function deleteOutcome(){
    const row = $(this).closest('tr');
    const id = row.attr('outcome-id');
    if(!id) return;
    swallMessage('Advertencia', '¿Está seguro de eliminar este egreso?', 'error', 'Si, eliminar', 'No', null, function(){
        PostMethodFunction('/admin/outcomes/delete', {id: id}, null, function(){ alertSuccess('Egreso eliminado'); getOutcomesPage(); }, null);
    }, null);
}

export function recoverOutcome(){
    const row = $(this).closest('tr');
    const id = row.attr('outcome-id');
    if(!id) return;
    swallMessage('Advertencia', '¿Está seguro de recuperar este egreso?', 'error', 'Si, recuperar', 'No', null, function(){
        PostMethodFunction('/admin/outcomes/recover', {id: id}, null, function(){ alertSuccess('Egreso recuperado'); getOutcomesPage(); }, null);
    }, null);
}