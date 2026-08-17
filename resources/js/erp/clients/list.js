import { clientState } from './state.js';

export function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();

    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for (let index = 1; index <= clientState.dbPagination.totalPages; index++) {
        closePage = Math.abs(clientState.dbPagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.left = true;
            }
        }else if(index >= clientState.dbPagination.totalPages - 2){
            if(!dots.right){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.right = true;
            }
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==clientState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
    }

    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(clientState.dbPagination.total>5){
        appendedContent += '<li class="page-item">';
        appendedContent += "<p class='page-link'>";
        appendedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
        appendedContent += '<option value="5"'+((clientState.dbPagination.per_page==5)?' selected':'')+'>5</option>';
        appendedContent += '<option value="10"'+((clientState.dbPagination.per_page==10)?' selected':'')+'>10</option>';
        appendedContent += '<option value="50"'+((clientState.dbPagination.per_page==50)?' selected':'')+'>50</option>';
        appendedContent += '</select>';
        appendedContent += "</p>";
        appendedContent += '</li>';
    }
    paginationContainer.append(appendedContent);
}

export function DBchangePageSize(){
    clientState.dbPagination.per_page = $('#db-pagination-per-page').val();
    clientState.dbPagination.page = 1;
    getClientsPage();
}

export function DBchangePage(){
    let selectedPage = $(this).attr('title');
    if(selectedPage != clientState.dbPagination.page){
        clientState.dbPagination.page = selectedPage;
        getClientsPage();
    }
}

export function DBselectBackPage(){
    if(clientState.dbPagination.page>1){
        clientState.dbPagination.page = parseInt(clientState.dbPagination.page)-1;
        getClientsPage();
    }
}

export function DBselectNextPage(){
    if(clientState.dbPagination.page<clientState.dbPagination.totalPages){
        clientState.dbPagination.page = parseInt(clientState.dbPagination.page)+1;
        getClientsPage();
    }
}

export function getClientsPage(){
    let dataSend = {
        pagination: clientState.dbPagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/clients/get-page',dataSend,null, showClientsPage,null);
}

export function goToUpdateTab(row, onLoaded){
    let clientId = $(row).parent().parent().attr('client-id');
    clientState.currentClient = clientState.clients.find(client => client.id == clientId);
    if(clientState.currentClient != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    }
}

function showClientsPage(response){
    clientState.dbPagination = response.pagination;
    clientState.clients = response.data;
    let appendContent = '';
    $.each(clientState.clients,function(index,value){
        appendContent += '<tr client-id='+value.id+'>';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-logo text-center image-column">';
                appendContent += '<div class="image-column-container d-blox mx-auto" style="background-image:url(\'/'+(value.photo_path)+'\');">';
            appendContent += ' </td>';
            appendContent += '<td class="columns-identification text-left"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-name text-left"><p>'+value.name+(value.lastname==null?'':(' '+value.lastname))+'</p></td>';
            appendContent += '<td class="columns-state text-center active-col"><p class="active-state active-state-'+value.active+'">'+(value.active?'Activo':'Inactivo')+'</p></td>';
            appendContent += '<td class="columns-phone text-center"><p>'+(value.phone==null?'':value.phone)+'</p></td>';
            appendContent += '<td class="columns-email text-left email-col" title="'+value.email+'"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-license text-center"><p>'+value.licenses_count+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-list-table #client-list-table-body').empty().append(appendContent);
    DBshowPagination();
}