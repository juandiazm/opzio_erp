import { contractState } from './state.js';
import { escapeHtml, formatDate } from './shared.js';
import { renderEntityAvatar } from '../shared/list.js';

function getContractableImageFolder(contractableType) {
    const normalizedType = String(contractableType || '').toLowerCase();
    if(normalizedType.indexOf('employee') !== -1) return 'employees';
    if(normalizedType.indexOf('provider') !== -1) return 'providers';
    return 'clients';
}

export function showPagination() {
    const container = $('#contract-pagination');
    container.empty();
    let html = '';
    if(contractState.dbPagination.totalPages > 1){
        html += '<li class="page-item '+(contractState.dbPagination.page <= 1 ? 'disabled' : '')+'" id="contract-page-back"><p class="page-link">&lt;</p></li>';
        for(let page = 1; page <= contractState.dbPagination.totalPages; page++){
            if(page <= 3 || page >= contractState.dbPagination.totalPages - 2 || Math.abs(page - contractState.dbPagination.page) <= 1){
                html += '<li class="page-item contract-page-number" title="'+page+'"><p class="page-link'+(page == contractState.dbPagination.page ? ' active' : '')+'">'+page+'</p></li>';
            }else if(page === 4 || page === contractState.dbPagination.totalPages - 3){
                html += '<li class="page-item"><p class="page-link">...</p></li>';
            }
        }
        html += '<li class="page-item '+(contractState.dbPagination.page >= contractState.dbPagination.totalPages ? 'disabled' : '')+'" id="contract-page-next"><p class="page-link">&gt;</p></li>';
    }
    if(contractState.dbPagination.total > 5){
        html += '<li class="page-item"><p class="page-link"><select id="contract-pagination-per-page" aria-label="Contratos por página"><option value="5"'+(contractState.dbPagination.per_page == 5 ? ' selected' : '')+'>5</option><option value="10"'+(contractState.dbPagination.per_page == 10 ? ' selected' : '')+'>10</option><option value="50"'+(contractState.dbPagination.per_page == 50 ? ' selected' : '')+'>50</option></select></p></li>';
    }
    if(html === '') return;
    container.append(html);
}

export function getContractsPage() {
    PostMethodFunction('/admin/contracts/get-page', {
        pagination: contractState.dbPagination,
        search: $('#contract-search').val(),
        contract_type_id: $('#contract-type-filter').val(),
        status: $('#contract-status-filter').val(),
    }, null, showContractsPage, null);
}

function showContractsPage(response) {
    contractState.dbPagination = response.pagination;
    contractState.contracts = response.contracts || [];
    let html = '';
    contractState.contracts.forEach(function(contract) {
        const deleted = contract.deleted_at != null;
        const uniqueId = String(contract.unique_id || '');
        const contractMeta = [contract.type ? contract.type.name : '', contract.subject || ''].filter(Boolean).join(' - ');
        const associatedEntity = contract.contractable || null;
        const associatedEntityIsLogo = getContractableImageFolder(contract.contractable_type) !== 'employees';
        html += '<tr class="contract-row'+(deleted ? ' deleted' : '')+'" contract-id="'+contract.id+'">';
        html += '<td class="erp-identity-cell"><div class="erp-identity">'+renderEntityAvatar(associatedEntity, getContractableImageFolder(contract.contractable_type), associatedEntityIsLogo)+'<div class="erp-identity-copy"><p class="erp-identity-name" title="'+escapeHtml(contract.name)+'">'+escapeHtml(contract.name)+'</p><span class="erp-identity-meta" title="'+escapeHtml(uniqueId)+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+escapeHtml(uniqueId)+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+escapeHtml(uniqueId.slice(-8))+'</span></span><small class="erp-meta-label">'+escapeHtml(contractMeta)+'</small></div></div></td>';
        html += '<td>'+escapeHtml(contract.contractable_name || '')+'</td>';
        html += '<td><div class="erp-meta-stack"><span>Inicio: '+escapeHtml(formatDate(contract.start_date))+'</span><small>Vence: '+escapeHtml(formatDate(contract.end_date))+'</small></div></td>';
        html += '<td><span class="erp-status status-'+escapeHtml(contract.status)+'"><span class="erp-status-label">'+escapeHtml(contract.status_string)+'</span></span></td>';
        html += '<td class="text-end action-cell">';
        html += '<i class="fa-solid '+(deleted ? 'fa-eye' : 'fa-pen-to-square')+' contract-open-btn" data-contract-id="'+contract.id+'" title="Abrir contrato"></i>';
        if(!deleted){
            html += '<i class="fa-solid fa-wand-magic-sparkles contract-generate-btn" data-contract-id="'+contract.id+'" title="Generar contrato"></i>';
            html += '<i class="fa-solid fa-paper-plane contract-send-btn" data-contract-id="'+contract.id+'" title="Enviar contrato"></i>';
            html += '<i class="fa-solid fa-trash-can contract-delete-btn" data-contract-id="'+contract.id+'" title="Eliminar contrato"></i>';
        }else{
            html += '<i class="fa-solid fa-rotate-left contract-restore-btn" data-contract-id="'+contract.id+'" title="Restaurar contrato"></i>';
        }
        html += '</td></tr>';
    });
    $('#contract-list-table-body').empty().append(html);
    showPagination();
}

export function openContract(id, onLoaded) {
    PostMethodFunction('/admin/contracts/get-by-id', {id: id}, null, function(response) {
        contractState.currentContract = response.contract;
        $('#nav-update-tab').removeClass('d-none').tab('show').trigger('click');
        if(onLoaded) onLoaded();
    }, null);
}

export function changePageSize() {
    contractState.dbPagination.per_page = $('#contract-pagination-per-page').val();
    contractState.dbPagination.page = 1;
    getContractsPage();
}

export function changePage() {
    const page = $(this).attr('title');
    if(page != contractState.dbPagination.page){
        contractState.dbPagination.page = page;
        getContractsPage();
    }
}

export function selectBackPage() {
    if(contractState.dbPagination.page > 1){
        contractState.dbPagination.page--;
        getContractsPage();
    }
}

export function selectNextPage() {
    if(contractState.dbPagination.page < contractState.dbPagination.totalPages){
        contractState.dbPagination.page++;
        getContractsPage();
    }
}