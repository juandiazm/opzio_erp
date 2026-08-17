import { employeeState } from './state.js';

export function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
    paginationContainer.empty();
    let appendedContent = '';
    appendedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
    let closePage = null;
    let showPageSize = 3;
    let dots = {left: false, right: false};
    for (let index = 1; index <= employeeState.dbPagination.totalPages; index++) {
        closePage = Math.abs(employeeState.dbPagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.left = true;
            }
        }else if(index >= employeeState.dbPagination.totalPages - 2){
            if(!dots.right){
                appendedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
                dots.right = true;
            }
            if(String(index).length<3){
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
                appendedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==employeeState.dbPagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
    }
    appendedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
    if(employeeState.dbPagination.total>5){
        appendedContent += '<li class="page-item">';
        appendedContent += "<p class='page-link'>";
        appendedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
        appendedContent += '<option value="5"'+((employeeState.dbPagination.per_page==5)?' selected':'')+'>5</option>';
        appendedContent += '<option value="10"'+((employeeState.dbPagination.per_page==10)?' selected':'')+'>10</option>';
        appendedContent += '<option value="50"'+((employeeState.dbPagination.per_page==50)?' selected':'')+'>50</option>';
        appendedContent += '</select>';
        appendedContent += "</p>";
        appendedContent += '</li>';
    }
    paginationContainer.append(appendedContent);
}

export function DBchangePageSize(){
    employeeState.dbPagination.per_page = $('#db-pagination-per-page').val();
    employeeState.dbPagination.page = 1;
    getEmployeesPage();
}

export function DBchangePage(){
    let selectedPage = $(this).attr('title');
    if(selectedPage != employeeState.dbPagination.page){
        employeeState.dbPagination.page = selectedPage;
        getEmployeesPage();
    }
}

export function DBselectBackPage(){
    if(employeeState.dbPagination.page>1){
        employeeState.dbPagination.page = parseInt(employeeState.dbPagination.page)-1;
        getEmployeesPage();
    }
}

export function DBselectNextPage(){
    if(employeeState.dbPagination.page<employeeState.dbPagination.totalPages){
        employeeState.dbPagination.page = parseInt(employeeState.dbPagination.page)+1;
        getEmployeesPage();
    }
}

export function getEmployeesPage(){
    let dataSend = {
        pagination: employeeState.dbPagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/employees/get-page',dataSend,null, showEmployeesPage,null);
}

export function goToUpdateTab(row, onLoaded){
    let employeeId = $(row).parent().parent().attr('employee-id');
    employeeState.currentEmployee = employeeState.employees.find(employee => employee.id == employeeId);
    if(employeeState.currentEmployee != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        onLoaded();
    }
}

export function setCurrentEmployeeFromRow(row){
    let employeeId = $(row).closest('.employee-row-info').attr('employee-id');
    employeeState.currentEmployee = employeeState.employees.find(employee => employee.id == employeeId);
    return employeeState.currentEmployee;
}

function showEmployeesPage(response){
    employeeState.dbPagination = response.pagination;
    employeeState.employees = response.data;
    let appendContent = '';
    $.each(employeeState.employees,function(index,value){
        value.charge = value.charge?value.charge:'';
        const fullName = value.name+(value.last_name ? (' '+value.last_name) : '');
        appendContent += '<tr employee-id='+value.id+' class="employee-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-identity text-start erp-identity-cell">';
                appendContent += '<div class="erp-identity">';
                    appendContent += '<div class="image-column-container erp-avatar" style="background-image:url(\'/images/erp/employees/'+value.photo+'\');"></div>';
                    appendContent += '<div class="erp-identity-copy">';
                        appendContent += '<p class="erp-identity-name" title="'+fullName+'">'+fullName+'</p>';
                        appendContent += '<span class="erp-identity-meta" title="'+value.uid+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+value.uid+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+value.uid.substr(value.uid.length - 5)+'</span></span>';
                    appendContent += '</div>';
                appendContent += '</div>';
            appendContent += '</td>';
            appendContent += '<td class="columns-identification text-start" title="'+value.identification+'"><p>'+value.identification+'</p></td>';
            if(value.department==null){
                appendContent += '<td class="columns-department text-start" title=""><p></p></td>';
            }else{
                appendContent += '<td class="columns-department text-start" title="'+value.department.name+'"><p>'+value.department.name+'</p></td>';
            }
            appendContent += '<td class="columns-position text-start" title="'+value.charge+'"><p>'+value.charge+'</p></td>';
            appendContent += '<td class="columns-email text-start" title="'+value.work_email+'"><p>'+value.work_email+'</p></td>';
            appendContent += '<td class="columns-state text-center"><span class="erp-status '+(value.state?'is-active':'is-inactive')+'"><span class="erp-status-label">'+(value.state?'Activo':'Inactivo')+'</span></span></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-ban list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#employee-list-table #employee-list-table-body').empty().append(appendContent);
    DBshowPagination();
}