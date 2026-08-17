import { licenseState } from './state.js';
import { renderEntityAvatar } from '../shared/list.js';

export function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();

    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';

    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for (let index = 1; index <= licenseState.dbPagination.totalPages; index++) {
        closePage = Math.abs(licenseState.dbPagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.left = true;
            }
        }else if(index >= licenseState.dbPagination.totalPages - 2){
            if(!dots.right){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.right = true;
            }
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==licenseState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
    }

    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(licenseState.dbPagination.total>5){
        appendedContent += '<li class="page-item">';
        appendedContent += "<p class='page-link'>";
        appendedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
        appendedContent += '<option value="5"'+((licenseState.dbPagination.per_page==5)?' selected':'')+'>5</option>';
        appendedContent += '<option value="10"'+((licenseState.dbPagination.per_page==10)?' selected':'')+'>10</option>';
        appendedContent += '<option value="50"'+((licenseState.dbPagination.per_page==50)?' selected':'')+'>50</option>';
        appendedContent += '</select>';
        appendedContent += "</p>";
        appendedContent += '</li>';
    }
    paginationContainer.append(appendedContent);
}

export function DBchangePageSize(){
    licenseState.dbPagination.per_page = $('#db-pagination-per-page').val();
    licenseState.dbPagination.page = 1;
    getLicensesPage();
}

export function DBchangePage(){
    let selectedPage = $(this).attr('title');
    if(selectedPage != licenseState.dbPagination.page){
        licenseState.dbPagination.page = selectedPage;
        getLicensesPage();
    }
}

export function DBselectBackPage(){
    if(licenseState.dbPagination.page>1){
        licenseState.dbPagination.page = parseInt(licenseState.dbPagination.page)-1;
        getLicensesPage();
    }
}

export function DBselectNextPage(){
    if(licenseState.dbPagination.page<licenseState.dbPagination.totalPages){
        licenseState.dbPagination.page = parseInt(licenseState.dbPagination.page)+1;
        getLicensesPage();
    }
}

export function getLicensesPage(){
    let dataSend = {
        pagination: licenseState.dbPagination,
        search: $('#search-list-input').val(),
        state: $('#state-list-input').val(),
    };
    PostMethodFunction('/admin/licenses/get-page',dataSend,null, showLicensesPage,null);
}

export function goToUpdateTab(row, onLoaded){
    let licenseId = $(row).parent().parent().attr('license-id');
    licenseState.currentLicense = licenseState.licenses.find(license => license.id == licenseId);
    if(licenseState.currentLicense != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    }
}

export function setCurrentLicenseFromRow(row){
    let licenseId = $(row).closest('.license-row-info').attr('license-id');
    licenseState.currentLicense = licenseState.licenses.find(license => license.id == licenseId);
    return licenseState.currentLicense;
}

function showLicensesPage(response){
    licenseState.dbPagination = response.pagination;
    licenseState.licenses = response.licenses;
    let appendContent = '';
    $.each(licenseState.licenses,function(index,value){
        value.value_string = value.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        const licenseName = value.name || 'Licencia sin nombre';
        const associatedClient = value.client || null;
        const clientName = associatedClient ? associatedClient.name : 'Sin cliente';
        const licenseType = value.type_string+(value.type==1?(' ('+value.recurrence_months+')'):'');
        appendContent += '<tr license-id='+value.id+' class="license-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-identity text-start erp-identity-cell"><div class="erp-identity">'+renderEntityAvatar(associatedClient, 'clients')+'<div class="erp-identity-copy"><p class="erp-identity-name" title="'+licenseName+'">'+licenseName+'</p><span class="erp-identity-meta" title="'+value.unique_id+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+value.unique_id+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+value.unique_id.substr(value.unique_id.length - 5)+'</span></span><small class="erp-meta-label">'+clientName+'</small></div></div></td>';
            appendContent += '<td class="columns-service text-start"><div class="erp-meta-stack"><span>'+value.service.name+'</span><small>'+licenseType+'</small></div></td>';
            appendContent += '<td class="columns-value text-end" title="'+value.value+'"><p>$'+value.value_string+'</p></td>';
            appendContent += '<td class="columns-validity text-center"><div class="erp-meta-stack"><span>Factura: '+(value.last_billing_date==null?'-':value.last_billing_date)+'</span><small>Pago: '+(value.last_payed_date==null?'-':value.last_payed_date)+' · '+(value.remaining_days==null?'-':value.remaining_days)+' días</small></div></td>';
            appendContent += '<td class="columns-state text-center"><span class="erp-status '+(value.active?'is-active':'is-inactive')+'"><span class="erp-status-label">'+(value.active?'Activa':'Inactiva')+'</span></span></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#license-list-table #license-list-table-body').empty().append(appendContent);
    DBshowPagination();
}

export function getLicenseById(licenseId, onLoaded){
    let dataSend = {
        license_id: licenseId
    };
    PostMethodFunction('/admin/licenses/get-by-id',dataSend,null, function(response){
        licenseState.currentLicense = response.license;
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    },null);
}