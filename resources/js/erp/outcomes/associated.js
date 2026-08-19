const state = {
    association: null,
    id: null,
    page: 1,
    size: 10,
    total: 0,
    totalPages: 0,
};

function escapeHtml(value){ return $('<div>').text(value == null ? '' : value).html(); }

function associationLabel(value){
    if(!value) return '-';
    return value.label || [value.name, value.last_name || value.lastname].filter(Boolean).join(' ') || value.complete_name || '-';
}

function formatCurrency(value){
    return '$'+(Number(value) || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
}

function getPane(){ return $('#sub-nav-outcomes'); }

function getContext(){
    const pane = getPane();
    return {
        association: String(pane.attr('data-outcome-association') || ''),
        id: String(pane.attr('data-outcome-association-id') || ''),
    };
}

function showOutcomeTotals(totals, totalRecords){
    const amount = Number(totals?.amount) || 0;
    const records = Number(totalRecords) || 0;
    $('#associated-outcomes-total-amount').text(formatCurrency(amount));
    $('#associated-outcomes-total-records').text(records === 1 ? '1 registro' : records+' registros');
}

function showPagination(){
    const paginationContainer = $('#associated-outcomes-pagination');
    paginationContainer.empty();
    let content = '<li class="page-item page-item-back" id="associated-outcomes-page-back"><p class="page-link">&lt;</p></li>';
    let dots = {left: false, right: false};
    for(let index = 1; index <= state.totalPages; index++){
        const distance = Math.abs(state.page - index);
        const page = '<li class="page-item page-item-number" title="'+index+'"><p class="page-link'+(index == state.page ? ' active' : '')+'">'+(String(index).length < 3 ? index : '<small>'+index+'</small>')+'</p></li>';
        if(distance <= 3 || index <= 3 || index >= state.totalPages - 2){
            if(index >= state.totalPages - 2 && !dots.right && index > state.page + 3){
                content += '<li class="page-item"><p class="page-link">...</p></li>';
                dots.right = true;
            }
            content += page;
        }else if(index <= 3 && !dots.left){
            content += '<li class="page-item"><p class="page-link">...</p></li>';
            dots.left = true;
        }
    }
    content += '<li class="page-item page-item-next" id="associated-outcomes-page-next"><p class="page-link">&gt;</p></li>';
    if(state.total > 5){
        content += '<li class="page-item"><p class="page-link"><select id="associated-outcomes-pagination-per-page" aria-label="Registros por página"><option value="5"'+(state.size == 5 ? ' selected' : '')+'>5</option><option value="10"'+(state.size == 10 ? ' selected' : '')+'>10</option><option value="50"'+(state.size == 50 ? ' selected' : '')+'>50</option></select></p></li>';
    }
    paginationContainer.append(content);
}

function renderAssociationCell(value, relation){
    return '<div class="outcome-association-readonly">'+escapeHtml(associationLabel(value[relation]))+'</div>';
}

function renderOutcomes(response){
    state.total = Number(response.pagination?.total) || 0;
    state.totalPages = Number(response.pagination?.totalPages) || 0;
    state.page = Number(response.pagination?.page) || state.page;
    state.size = Number(response.pagination?.size) || state.size;

    const outcomes = Array.isArray(response.data) ? response.data : [];
    let content = '';
    outcomes.forEach(function(value){
        const deleted = value.deleted_at != null;
        const outcomeDate = value.date ? value.date.substring(0, 10) : '-';
        const uniqueId = String(value.unique_id || '');
        const outcomeType = escapeHtml(value.outcome_type?.name || 'Sin tipo');
        content += '<tr outcome-id="'+escapeHtml(value.id)+'" class="'+(deleted ? 'deleted' : '')+'">';
        content += '<td class="columns-identity text-start erp-identity-cell"><div class="erp-identity erp-identity-plain"><div class="erp-identity-copy"><p class="erp-identity-name" title="'+escapeHtml(value.name)+'">'+escapeHtml(value.name)+'</p><span class="erp-identity-meta" title="'+escapeHtml(uniqueId)+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+escapeHtml(uniqueId)+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+escapeHtml(uniqueId.slice(-5))+'</span><span class="outcome-type-badge" title="Tipo de egreso">'+outcomeType+'</span><span> - '+escapeHtml(outcomeDate)+'</span></span></div></div></td>';
        content += '<td class="columns-provider text-start">'+renderAssociationCell(value, 'provider')+'</td>';
        content += '<td class="columns-employee text-start">'+renderAssociationCell(value, 'employee')+'</td>';
        content += '<td class="columns-department text-start">'+renderAssociationCell(value, 'department')+'</td>';
        content += '<td class="columns-user text-start">'+renderAssociationCell(value, 'user')+'</td>';
        content += '<td class="columns-client text-start">'+renderAssociationCell(value, 'client')+'</td>';
        content += '<td class="columns-total text-end" title="'+escapeHtml(value.amount)+'"><p class="erp-amount">'+formatCurrency(value.amount)+'</p></td>';
        content += '<td class="columns-actions text-center action-cell">';
        if(deleted) content += '<i class="fa fa-ban associated-recover-outcome" title="Recuperar"></i>';
        else content += '<i class="fa fa-trash-alt associated-delete-outcome" title="Eliminar"></i>';
        content += '</td></tr>';
    });
    if(content === '') content = '<tr><td colspan="8" class="text-center associated-outcomes-empty">No hay egresos asociados</td></tr>';
    $('#associated-outcomes-table-body').empty().append(content);
    showOutcomeTotals(response.totals, state.total);
    showPagination();
}

export function loadAssociatedOutcomes(){
    const context = getContext();
    if(!context.association || !context.id) return;
    state.association = context.association;
    state.id = context.id;
    const data = {
        page: state.page,
        size: state.size,
        search: String($('#associated-outcomes-search').val() || '').trim(),
    };
    data[context.association] = context.id;
    PostMethodFunction('/admin/outcomes/get', data, null, function(response){
        const currentContext = getContext();
        if(currentContext.association === context.association && currentContext.id === context.id) renderOutcomes(response);
    }, null);
}

export function setContext(association, id){
    const nextAssociation = String(association || '');
    const nextId = String(id || '');
    const changed = state.association !== nextAssociation || state.id !== nextId;
    getPane().attr({'data-outcome-association': nextAssociation, 'data-outcome-association-id': nextId});
    if(!changed) return;
    state.association = nextAssociation;
    state.id = nextId;
    state.page = 1;
    state.total = 0;
    state.totalPages = 0;
    $('#associated-outcomes-search').val('');
    $('#associated-outcomes-table-body').empty();
    showOutcomeTotals(null, 0);
    if(getPane().hasClass('show')) loadAssociatedOutcomes();
}

function changePageSize(){
    state.size = Number($('#associated-outcomes-pagination-per-page').val()) || 10;
    state.page = 1;
    loadAssociatedOutcomes();
}

function changePage(){
    const selectedPage = Number($(this).attr('title'));
    if(selectedPage && selectedPage !== state.page){
        state.page = selectedPage;
        loadAssociatedOutcomes();
    }
}

function selectBackPage(){
    if(state.page > 1){
        state.page -= 1;
        loadAssociatedOutcomes();
    }
}

function selectNextPage(){
    if(state.page < state.totalPages){
        state.page += 1;
        loadAssociatedOutcomes();
    }
}

function deleteOutcome(){
    const id = $(this).closest('tr').attr('outcome-id');
    if(!id) return;
    swallMessage('Advertencia', '¿Está seguro de eliminar este egreso?', 'error', 'Si, eliminar', 'No', null, function(){
        PostMethodFunction('/admin/outcomes/delete', {id: id}, null, function(){ alertSuccess('Egreso eliminado'); loadAssociatedOutcomes(); }, null);
    }, null);
}

function recoverOutcome(){
    const id = $(this).closest('tr').attr('outcome-id');
    if(!id) return;
    swallMessage('Advertencia', '¿Está seguro de recuperar este egreso?', 'error', 'Si, recuperar', 'No', null, function(){
        PostMethodFunction('/admin/outcomes/recover', {id: id}, null, function(){ alertSuccess('Egreso recuperado'); loadAssociatedOutcomes(); }, null);
    }, null);
}

$(document).on('shown.bs.tab', '#sub-nav-outcomes-tab', loadAssociatedOutcomes);
$(document).on('change', '#associated-outcomes-search', function(){ state.page = 1; loadAssociatedOutcomes(); });
$(document).on('change', '#associated-outcomes-pagination-per-page', changePageSize);
$(document).on('click', '#associated-outcomes-pagination .page-item-number', changePage);
$(document).on('click', '#associated-outcomes-page-back', selectBackPage);
$(document).on('click', '#associated-outcomes-page-next', selectNextPage);
$(document).on('click', '.associated-delete-outcome', deleteOutcome);
$(document).on('click', '.associated-recover-outcome', recoverOutcome);

window.AssociatedOutcomes = {
    load: loadAssociatedOutcomes,
    setContext,
};