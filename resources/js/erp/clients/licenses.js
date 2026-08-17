import { clientState } from './state.js';

export function getClientLicenses(){
    let dataSend = {
        client_id: clientState.currentClient.id
    };
    PostMethodFunction('/admin/clients/licenses/get-by-client-id',dataSend,null, showClientLicenses,null);
}

function showClientLicenses(response){
    let appendContent = '';
    $.each(response.licenses,function(index,value){
        appendContent += '<tr class="client-license-row" license-id="'+value.id+'">';
            appendContent += '<td class="text-left"><p class="client-license-input-serivice=name align-self-end input-value">'+value.service.name+'</p></td>';
            appendContent += '<td class="text-left"><p class="client-license-input-name align-self-end input-value">'+value.name+'</p></td>';
            appendContent += '<td class="text-end action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square go-to-license-btn"></i>';
                appendContent += '<i class="fa-solid fa-bars-progress traceability-employee-license-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-licenses-table #client-licenses-table-body').empty().append(appendContent);
}

export function goToLicense(){
    let licenseId = $(this).parent().parent().attr('license-id');
    window.location.href = '/admin/licenses?license_id='+licenseId;
}