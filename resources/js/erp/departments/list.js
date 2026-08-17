import { departmentState } from './state.js';

export function showPagination(){
    let totalPages = Math.ceil(departmentState.pagination.total/departmentState.pagination.per_page);
    let appendedContent = '<li class="page-item align-self-center my-0 me-2"><select id="pagination-per-page" class="form-select w-auto py-1" aria-label="Default select example">';
    if(departmentState.pagination.total>5) appendedContent += '<option value="5"'+((departmentState.pagination.per_page==5)?' selected':'')+'>5</option>';
    if(departmentState.pagination.total>10) appendedContent += '<option value="10"'+((departmentState.pagination.per_page==10)?' selected':'')+'>10</option>';
    if(departmentState.pagination.total>50) appendedContent += '<option value="50"'+((departmentState.pagination.per_page==50)?' selected':'')+'>50</option>';
    appendedContent += '<option value="'+departmentState.pagination.total+'"'+((departmentState.pagination.per_page==departmentState.pagination.total)?' selected':'')+'>'+departmentState.pagination.total+'</option></select></li>';
    if(departmentState.pagination.page>1) appendedContent += '<li id="page-item-back" class="page-item align-self-center my-0"><p class="page-link my-0" tabindex="-1"><<</p></li>';
    for(let index=1; index<=totalPages; index++){
        appendedContent += '<li class="page-item-number page-item align-self-center my-0'+(departmentState.pagination.page==index?' active':'')+'" text="'+index+'"><p class="page-link my-0">'+index+(departmentState.pagination.page==index?' <span class="sr-only">(current)</span>':'')+'</p></li>';
    }
    if(departmentState.pagination.page<totalPages) appendedContent += '<li id="page-item-next" class="page-item align-self-center my-0"><p class="page-link my-0">>></p></li>';
    $('#pagination').empty().append(appendedContent);
}
export function changePageSize(){ departmentState.pagination.per_page = $('#pagination-per-page').val(); departmentState.pagination.page = 1; getDepartmentsPage(); }
export function changePage(){ departmentState.pagination.page = $(this).attr('text'); getDepartmentsPage(); }
export function selectBackPage(){ departmentState.pagination.page = parseInt(departmentState.pagination.page)-1; getDepartmentsPage(); }
export function selectNextPage(){ departmentState.pagination.page = parseInt(departmentState.pagination.page)+1; getDepartmentsPage(); }
export function getDepartmentsPage(){ PostMethodFunction('/admin/departments/get-page',{pagination: departmentState.pagination,search: $('#search-list-input').val()},null,showDepartmentsPage,null); }
export function goToUpdateTab(row,onLoaded){
    let departmentId = $(row).parent().parent().attr('department-id');
    departmentState.currentDepartment = departmentState.departments.find(department => department.id == departmentId);
    if(departmentState.currentDepartment != null){ $('#nav-update-tab').tab('show'); $('#nav-update-tab').trigger('click'); onLoaded(); }
}
export function setCurrentDepartmentFromRow(row){
    let departmentId = $(row).closest('.department-row-info').attr('department-id');
    departmentState.currentDepartment = departmentState.departments.find(department => department.id == departmentId);
    return departmentState.currentDepartment;
}
function showDepartmentsPage(response){
    departmentState.pagination = response.pagination;
    departmentState.departments = response.departments;
    let appendContent = '';
    $.each(departmentState.departments,function(index,value){
        value.budget_string = value.budget.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        appendContent += '<tr department-id='+value.id+' class="department-row-info'+(value.deleted_at==null?'':' deleted')+'">';
        appendContent += '<td class="columns-identity text-start erp-identity-cell"><div class="erp-identity erp-identity-plain"><div class="erp-identity-copy"><p class="erp-identity-name" title="'+value.name+'">'+value.name+'</p><span class="erp-identity-meta" title="'+value.unique_id+'"><button type="button" class="erp-copy-id copy-action" data-clipboard-text="'+value.unique_id+'" title="Copiar ID" aria-label="Copiar ID"><i class="fa-regular fa-copy"></i></button><span>'+value.unique_id.substr(value.unique_id.length - 5)+'</span></span></div></div></td><td class="columns-budget text-end" title="$'+value.budget_string+'"><p>$'+value.budget_string+'</p></td><td class="columns-employees-number text-center" title="'+value.employees.length+'"><p>'+value.employees.length+'</p></td>';
        if(value.director != null){ value.director.complete_name = value.director.name+' '+(value.director.last_name?value.director.last_name:''); appendContent += '<td class="columns-director text-center" title="'+value.director.complete_name+'"><p>'+value.director.complete_name+'</p></td>'; }else appendContent += '<td class="columns-director text-center" title=""><p></p></td>';
        appendContent += '<td class="columns-actions text-end action-cell">';
        if(value.deleted_at == null) appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i><i class="fa-solid fa-trash-can list-delete-btn"></i>'; else appendContent += '<i class="fa-solid fa-eye list-update-btn"></i><i class="fa-solid fa-bars-progress list-update-traceability"></i>';
        appendContent += '</td></tr>';
    });
    $('#department-list-table #department-list-table-body').empty().append(appendContent);
    showPagination();
}