import { providerState } from './state.js';

export function showPagination(){
    let totalPages = Math.ceil(providerState.pagination.total/providerState.pagination.per_page);
    let appendedContent = '';
    appendedContent += '<li class="page-item align-self-center my-0 me-2">';
    appendedContent += '<select id="pagination-per-page" class="form-select w-auto py-1" aria-label="Default select example">';
    if(providerState.pagination.total>5) appendedContent += '<option value="5"'+((providerState.pagination.per_page==5)?' selected':'')+'>5</option>';
    if(providerState.pagination.total>10) appendedContent += '<option value="10"'+((providerState.pagination.per_page==10)?' selected':'')+'>10</option>';
    if(providerState.pagination.total>50) appendedContent += '<option value="50"'+((providerState.pagination.per_page==50)?' selected':'')+'>50</option>';
    appendedContent += '<option value="'+providerState.pagination.total+'"'+((providerState.pagination.per_page==providerState.pagination.total)?' selected':'')+'>'+providerState.pagination.total+'</option>';
    appendedContent += '</select></li>';
    if(providerState.pagination.page>1){
        appendedContent += '<li id="page-item-back" class="page-item align-self-center my-0"><p class="page-link my-0" tabindex="-1"><<</p></li>';
    }
    for (let index = 1; index <= totalPages; index++) {
        if(providerState.pagination.page==index){
            appendedContent += '<li class="page-item-number page-item align-self-center my-0 active" text="'+index+'"><p class="page-link my-0">'+index+' <span class="sr-only">(current)</span></p></li>';
        }else{
            appendedContent += '<li class="page-item-number page-item align-self-center my-0" text="'+index+'"><p class="page-link my-0">'+index+'</p></li>';
        }
    }
    if(providerState.pagination.page<totalPages) appendedContent += '<li id="page-item-next" class="page-item align-self-center my-0"><p class="page-link my-0">>></p></li>';
    $('#pagination').empty().append(appendedContent);
}

export function changePageSize(){
    providerState.pagination.per_page = $('#pagination-per-page').val();
    providerState.pagination.page = 1;
    getProvidersPage();
}
export function changePage(){ providerState.pagination.page = $(this).attr('text'); getProvidersPage(); }
export function selectBackPage(){ providerState.pagination.page = parseInt(providerState.pagination.page)-1; getProvidersPage(); }
export function selectNextPage(){ providerState.pagination.page = parseInt(providerState.pagination.page)+1; getProvidersPage(); }

export function getProvidersPage(){
    let dataSend = {pagination: providerState.pagination, search: $('#search-list-input').val()};
    PostMethodFunction('/admin/providers/get-page',dataSend,null, showProvidersPage,null);
}

export function goToUpdateTab(row, onLoaded){
    let providerId = $(row).parent().parent().attr('provider-id');
    providerState.currentProvider = providerState.providers.find(provider => provider.id == providerId);
    if(providerState.currentProvider != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    }
}

export function setCurrentProviderFromRow(row){
    let providerId = $(row).closest('.provider-row-info').attr('provider-id');
    providerState.currentProvider = providerState.providers.find(provider => provider.id == providerId);
    return providerState.currentProvider;
}

function showProvidersPage(response){
    providerState.pagination = response.pagination;
    providerState.providers = response.data;
    let appendContent = '';
    $.each(providerState.providers,function(index,value){
        appendContent += '<tr provider-id='+value.id+' class="provider-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-photo text-center image-column"><div class="image-column-container d-blox mx-auto" style="background-image:url(\'/images/erp/providers/'+value.photo+'\');"></td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'">'+(value.active?'Activo':'Inactivo')+'</p></td>';
            appendContent += '<td class="columns-identification text-left"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+(value.lastname==null?'':(' '+value.lastname))+'</p></td>';
            appendContent += '<td class="columns-phone text-center"><p>'+value.phone+'</p></td>';
            appendContent += '<td class="columns-email text-left email-col" title="'+value.email+'"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i><i class="fa-solid fa-trash-can list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td></tr>';
    });
    $('#provider-list-table #provider-list-table-body').empty().append(appendContent);
    showPagination();
}