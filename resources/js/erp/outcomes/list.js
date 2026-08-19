import { outcomeState } from './state.js';

function escapeHtml(value){ return $('<div>').text(value == null ? '' : value).html(); }
function associationLabel(value){
    if(!value) return '-';
    return value.label || [value.name, value.last_name || value.lastname].filter(Boolean).join(' ') || value.complete_name || '-';
}

const filterDefinitions = [
    {field: 'outcome_type_id', catalog: 'types', selector: '#outcome-type-filter', allLabel: 'Todos los tipos', noneLabel: 'Sin tipo'},
    {field: 'provider_id', catalog: 'providers', selector: '#outcome-provider-filter', allLabel: 'Todos los proveedores', noneLabel: 'Sin proveedor'},
    {field: 'employee_id', catalog: 'employees', selector: '#outcome-employee-filter', allLabel: 'Todos los empleados', noneLabel: 'Sin empleado'},
    {field: 'department_id', catalog: 'departments', selector: '#outcome-department-filter', allLabel: 'Todos los departamentos', noneLabel: 'Sin departamento'},
    {field: 'user_id', catalog: 'users', selector: '#outcome-user-filter', allLabel: 'Todos los usuarios', noneLabel: 'Sin usuario'},
    {field: 'client_id', catalog: 'clients', selector: '#outcome-client-filter', allLabel: 'Todos los clientes', noneLabel: 'Sin cliente'},
];

const associationDefinitions = [
    {field: 'provider_id', catalog: 'providers', relation: 'provider', emptyLabel: 'Sin proveedor', columnClass: 'columns-provider', allowEmpty: true},
    {field: 'employee_id', catalog: 'employees', relation: 'employee', emptyLabel: 'Sin empleado', columnClass: 'columns-employee', allowEmpty: true},
    {field: 'department_id', catalog: 'departments', relation: 'department', emptyLabel: 'Sin departamento', columnClass: 'columns-department', allowEmpty: true},
    {field: 'user_id', catalog: 'users', relation: 'user', emptyLabel: 'Sin usuario', columnClass: 'columns-user', allowEmpty: false, editable: false},
    {field: 'client_id', catalog: 'clients', relation: 'client', emptyLabel: 'Sin cliente', columnClass: 'columns-client', allowEmpty: true},
];

function formatCurrency(value){
    return '$'+(Number(value) || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
}
function showOutcomeTotals(totals, totalRecords){
    const amount = Number(totals?.amount) || 0;
    const records = Number(totalRecords) || 0;
    $('#outcome-total-amount').text(formatCurrency(amount));
    $('#outcome-total-records').text(records === 1 ? '1 registro' : records+' registros');
}

function setFilterOptions(definition){
    const select = $(definition.selector);
    if(select.length === 0) return;
    const selectedValue = select.val() || '';
    const options = [
        {value: '', label: definition.allLabel},
        {value: 'none', label: definition.noneLabel},
    ].concat((outcomeState.catalogs[definition.catalog] || []).map(function(item){
        return {value: item.id, label: associationLabel(item)};
    }));
    const hasSelectedValue = options.some(function(option){ return String(option.value) === String(selectedValue); });
    if(window.SearchableDropdown){
        window.SearchableDropdown.setOptions(select[0], options);
        window.SearchableDropdown.setValue(select[0], hasSelectedValue ? selectedValue : '', false);
        return;
    }
    select.empty();
    options.forEach(function(option){ select.append($('<option>', {value: option.value, text: option.label})); });
    select.val(hasSelectedValue ? selectedValue : '');
}

export function refreshCatalogControls(){
    filterDefinitions.forEach(setFilterOptions);
}

export function initializeDateRange(){
    const now = new Date();
    const formatDate = function(date){
        return date.getFullYear()+'-'+String(date.getMonth() + 1).padStart(2, '0')+'-'+String(date.getDate()).padStart(2, '0');
    };
    if(!$('#date-from').val()) $('#date-from').val(formatDate(new Date(now.getFullYear(), now.getMonth(), 1)));
    if(!$('#date-to').val()) $('#date-to').val(formatDate(now));
}

export function toggleFilters(){
    const toggle = $('#toggle-outcome-filters');
    const panel = $('#outcome-association-filters');
    const expanded = toggle.attr('aria-expanded') === 'true';
    toggle.attr('aria-expanded', expanded ? 'false' : 'true');
    toggle.attr('title', expanded ? 'Mostrar filtros adicionales' : 'Ocultar filtros adicionales');
    toggle.find('.outcome-filter-toggle-label').text(expanded ? 'Más filtros' : 'Ocultar filtros');
    toggle.find('.outcome-filter-toggle-icon').toggleClass('fa-chevron-down', expanded).toggleClass('fa-chevron-up', !expanded);
    panel.prop('hidden', expanded);
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
    let dataSend = {page: outcomeState.pagination.page, size: outcomeState.pagination.size, search: String($('#search-list-input').val() || '').trim(), from: $('#date-from').val() || null, to: $('#date-to').val() || null};
    filterDefinitions.forEach(function(definition){
        dataSend[definition.field] = $(definition.selector).val() || null;
    });
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

function renderAssociationSelect(outcome, definition){
    const selectedId = outcome[definition.field] == null ? '' : String(outcome[definition.field]);
    const catalogItems = (outcomeState.catalogs[definition.catalog] || []).slice();
    if(selectedId && !catalogItems.some(function(item){ return String(item.id) === selectedId; })){
        catalogItems.unshift({id: selectedId, label: associationLabel(outcome[definition.relation])});
    }
    let options = '';
    if(definition.allowEmpty){
        options += '<option value="">'+escapeHtml(definition.emptyLabel)+'</option>';
    }else{
        options += '<option value="" disabled'+(selectedId ? '' : ' selected')+'>'+escapeHtml(definition.emptyLabel)+'</option>';
    }
    catalogItems.forEach(function(item){
        const itemId = String(item.id);
        options += '<option value="'+escapeHtml(itemId)+'"'+(itemId === selectedId ? ' selected' : '')+'>'+escapeHtml(associationLabel(item))+'</option>';
    });
    return '<select class="form-select form-select-sm outcome-association-inline js-searchable-dropdown" data-association="'+definition.field+'" data-current-value="'+escapeHtml(selectedId)+'" aria-label="Cambiar '+definition.field.replace('_id', '')+'"'+(outcome.deleted_at != null ? ' disabled' : '')+'>'+options+'</select>';
}

function renderAssociationCell(outcome, definition){
    if(definition.editable === false){
        return '<div class="outcome-association-readonly">'+escapeHtml(associationLabel(outcome[definition.relation]))+'</div>';
    }
    return renderAssociationSelect(outcome, definition);
}

export function showOutcomesPage(response){
    outcomeState.pagination = response.pagination || outcomeState.pagination;
    outcomeState.totalAmount = Number(response.totals?.amount) || 0;
    outcomeState.outcomes = Array.isArray(response.data) ? response.data : [];
    let appendedContent = '';
    $.each(outcomeState.outcomes, function(index, value){
        appendedContent += '<tr outcome-id="'+value.id+'" class="'+(value.deleted_at == null ? '' : ' deleted')+'">';
        const outcomeDate = value.date ? value.date.substring(0, 10) : '-';
        const uniqueId = String(value.unique_id || '');
        const outcomeType = escapeHtml(value.outcome_type?.name || 'Sin tipo');
        appendedContent += '<td class="columns-identity text-start erp-identity-cell"><div class="erp-identity erp-identity-plain"><div class="erp-identity-copy"><p class="erp-identity-name" title="'+escapeHtml(value.name)+'">'+escapeHtml(value.name)+'</p><span class="erp-identity-meta" title="'+escapeHtml(uniqueId)+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+escapeHtml(uniqueId)+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+escapeHtml(uniqueId.slice(-5))+'</span><span class="outcome-type-badge" title="Tipo de egreso">'+outcomeType+'</span><span> - '+outcomeDate+'</span></span></div></div></td>';
        associationDefinitions.forEach(function(definition){
            appendedContent += '<td class="'+definition.columnClass+' text-start">'+renderAssociationCell(value, definition)+'</td>';
        });
        appendedContent += '<td class="columns-total text-end" title="'+escapeHtml(value.amount)+'"><p class="erp-amount">'+formatCurrency(value.amount)+'</p></td>';
        appendedContent += '<td class="columns-actions text-center action-cell">';
        if(value.deleted_at == null) appendedContent += '<i class="fa fa-pen-to-square edit-outcome" title="Editar"></i><i class="fa fa-trash-alt delete-outcome" title="Eliminar"></i>';
        else appendedContent += '<i class="fa fa-ban recover-outcome" title="Recuperar" style="color: red;"></i>';
        appendedContent += '</td></tr>';
    });
    $('#outcome-list-table #outcome-list-table-body').empty().append(appendedContent);
    showOutcomeTotals(response.totals, outcomeState.pagination.total);
    showPagination();
}

export function changePageSize(){ outcomeState.pagination.size = $('#db-pagination-per-page').val(); outcomeState.pagination.page = 1; getOutcomesPage(); }
export function changePage(){ let selectedPage = $(this).attr('title'); if(selectedPage != outcomeState.pagination.page){ outcomeState.pagination.page = selectedPage; getOutcomesPage(); } }
export function selectBackPage(){ if(outcomeState.pagination.page > 1){ outcomeState.pagination.page = parseInt(outcomeState.pagination.page) - 1; getOutcomesPage(); } }
export function selectNextPage(){ if(outcomeState.pagination.page < outcomeState.pagination.totalPages){ outcomeState.pagination.page = parseInt(outcomeState.pagination.page) + 1; getOutcomesPage(); } }

export function changeAssociation(){
    const select = $(this);
    const id = select.closest('tr').attr('outcome-id');
    const association = select.attr('data-association');
    const previousValue = select.attr('data-current-value') || '';
    const nextValue = select.val() || null;
    if(!id || !association || String(nextValue || '') === String(previousValue)) return;
    select.prop('disabled', true);
    PostMethodFunction('/admin/outcomes/update-association', {id: id, association: association, association_id: nextValue}, null, function(){
        alertSuccess('Asociación actualizada');
        getOutcomesPage();
    }, function(){
        select.prop('disabled', false).val(previousValue);
    });
}

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