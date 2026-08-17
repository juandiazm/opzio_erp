import { escapeHtml, formatDate } from './shared.js';

function renderAssociatedContracts(response) {
    let html = '';
    (response.contracts || []).forEach(function(contract) {
        html += '<tr class="'+(contract.deleted_at != null ? 'deleted' : '')+'">';
        html += '<td>'+escapeHtml(String(contract.unique_id || '').slice(-8))+'</td>';
        html += '<td>'+escapeHtml(contract.name)+'</td>';
        html += '<td>'+escapeHtml(contract.type ? contract.type.name : '')+'</td>';
        html += '<td>'+escapeHtml(formatDate(contract.start_date))+' - '+escapeHtml(formatDate(contract.end_date))+'</td>';
        html += '<td>'+escapeHtml(contract.status_string || contract.status)+'</td>';
        html += '<td><a href="/admin/contracts?contract_id='+encodeURIComponent(contract.id)+'" title="Abrir contrato"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>';
        html += '</tr>';
    });
    $('#associated-contracts-table-body').empty().append(html || '<tr><td colspan="6" class="text-center">No hay contratos asociados</td></tr>');
}

export function loadAssociatedContracts(type, id) {
    if(!type || !id) return;
    PostMethodFunction('/admin/contracts/get-associated', {contractable_type: type, contractable_id: id}, null, renderAssociatedContracts, null);
}

$(document).on('shown.bs.tab', '#sub-nav-contracts-tab', function() {
    loadAssociatedContracts($('#sub-nav-contracts').attr('data-contractable-type'), $('#sub-nav-contracts').attr('data-contractable-id'));
});

window.ContractAssociations = {load: loadAssociatedContracts};