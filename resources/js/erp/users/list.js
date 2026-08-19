import { userState } from './state.js';

export function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();
    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for (let index = 1; index <= userState.dbPagination.totalPages; index++) {
        closePage = Math.abs(userState.dbPagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.left = true;
            }
        }else if(index >= userState.dbPagination.totalPages - 2){
            if(!dots.right){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.right = true;
            }
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==userState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
    }
    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(userState.dbPagination.total>5){
        appendedContent += '<li class="page-item">';
        appendedContent += "<p class='page-link'>";
        appendedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
        appendedContent += '<option value="5"'+((userState.dbPagination.per_page==5)?' selected':'')+'>5</option>';
        appendedContent += '<option value="10"'+((userState.dbPagination.per_page==10)?' selected':'')+'>10</option>';
        appendedContent += '<option value="50"'+((userState.dbPagination.per_page==50)?' selected':'')+'>50</option>';
        appendedContent += '</select>';
        appendedContent += "</p>";
        appendedContent += '</li>';
    }
    paginationContainer.append(appendedContent);
}

export function DBchangePageSize(){
    userState.dbPagination.per_page = $('#db-pagination-per-page').val();
    userState.dbPagination.page = 1;
    getUsersPage();
}

export function DBchangePage(){
    let selectedPage = $(this).attr('title');
    if(selectedPage != userState.dbPagination.page){
        userState.dbPagination.page = selectedPage;
        getUsersPage();
    }
}

export function DBselectBackPage(){
    if(userState.dbPagination.page>1){
        userState.dbPagination.page = parseInt(userState.dbPagination.page)-1;
        getUsersPage();
    }
}

export function DBselectNextPage(){
    if(userState.dbPagination.page<userState.dbPagination.totalPages){
        userState.dbPagination.page = parseInt(userState.dbPagination.page)+1;
        getUsersPage();
    }
}

export function getUsersPage(){
    let dataSend = {
        pagination: userState.dbPagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/users/get-page',dataSend,null, showUsersPage,null);
}

export function goToUpdateTab(row, onLoaded){
    userState.userId = $(row).parent().parent().attr('user-id');
    userState.currentUser = userState.users.find(user => user.id == userState.userId);
    if(userState.currentUser != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    }
}

export function goToTraceabilityTab(userId){
    userState.currentUser = userState.users.find(user => user.id == userId);
    if(userState.currentUser != null){
        userState.troughtUser = true;
        $('#nav-traceability-tab').tab('show');
        $('#nav-traceability-tab').trigger('click');
    }
}

function showUsersPage(response){
    userState.dbPagination = response.pagination;
    userState.users = response.data;
    let appendContent = '';
    $.each(userState.users,function(index,value){
        const fullName = value.name+(value.lastname ? (' '+value.lastname) : '');
        appendContent += '<tr user-id='+value.id+' class="'+(value.deleted_at==null?'':'deleted')+'">';
            appendContent += '<td class="columns-identity text-start erp-identity-cell">';
                appendContent += '<div class="erp-identity">';
                    appendContent += '<div class="image-column-container erp-avatar" style="background-image:url(\'/storage/images/erp/users/'+value.photo+'\');border-color:'+value.color+';"></div>';
                    appendContent += '<div class="erp-identity-copy">';
                        appendContent += '<p class="erp-identity-name" title="'+fullName+'">'+fullName+'</p>';
                        appendContent += '<span class="erp-identity-meta" title="'+value.unique_id+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+value.unique_id+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+value.unique_id.substr(value.unique_id.length - 5)+'</span></span>';
                    appendContent += '</div>';
                appendContent += '</div>';
            appendContent += '</td>';
            appendContent += '<td class="columns-username text-center"><p>'+value.username+'</p></td>';
            appendContent += '<td class="columns-identification text-center"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-email text-left"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                }
                appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#user-list-table #user-list-table-body').empty().append(appendContent);
    DBshowPagination();
}