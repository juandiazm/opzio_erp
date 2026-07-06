$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-department-button', addDepartment);
//////////
$(document).on('change', '#pagination-per-page', changePageSize);
$(document).on('click', '#pagination .page-item-number', changePage);
$(document).on('click', '#page-item-back', selectBackPage);
$(document).on('click', '#page-item-next', selectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('change', '#search-list-input', getDepartmentsPage);
$(document).on('click', '.list-delete-btn', function(){deleteDepartment($(this).parent().parent().attr('department-id'));});
$(document).on('click', '.list-update-traceability', function(){
    current_department = departments.find(department => department.id == $(this).closest('.department-row-info').attr('department-id'));
    goToDepartmentsTraceability('id%'+current_department.id);
});
//////////
$(document).on('click', '#update-department-button', updateDepartment);
$(document).on('click', '#update-department-delete', function(){deleteDepartment(current_department.id);});
$(document).on('click', '#update-department-restore', function(){restoreDepartment(current_department.id);});
$(document).on('click', '#update-department-go-traceability', function(){
    goToDepartmentsTraceability('id%'+current_department.id);
});
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var departments = [];
var current_department = null;
var department_id = null;
var current_tab = null;
var notDepartmentAssignedEmployees = [];
$(document).ready(function(){
    getAllEmployees();
    changeTab();
});
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getDepartmentsPage();    
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    tabs_view[current_tab] = true;    
}
//List Department Functions
var pagination = {
    page:1,
    per_page:10,
    total:0,
};
function showPagination(){
    var total_pages = Math.ceil(pagination.total/pagination.per_page);
    var AppenedContent = '';
    AppenedContent += '<li class="page-item align-self-center my-0 me-2">';
        AppenedContent += '<select id="pagination-per-page" class="form-select w-auto py-1" aria-label="Default select example">';
            if(pagination.total>5){
                AppenedContent += '<option value="5"'+((pagination.per_page==5)?' selected':'')+'>5</option>';
            }
            if(pagination.total>10){
                AppenedContent += '<option value="10"'+((pagination.per_page==10)?' selected':'')+'>10</option>';
            }
            if(pagination.total>50){
                AppenedContent += '<option value="50"'+((pagination.per_page==50)?' selected':'')+'>50</option>';
            }
            AppenedContent += '<option value="'+pagination.total+'"'+((pagination.per_page==pagination.total)?' selected':'')+'>'+pagination.total+'</option>';
        AppenedContent += '</select>';
    AppenedContent += '</li>';
    if(pagination.page>1){
        AppenedContent += '<li id="page-item-back" class="page-item align-self-center my-0" '+((pagination.page==1)?' disabled':'')+'>';
        AppenedContent += '<p class="page-link my-0" tabindex="-1"><<</p>';
    }
    AppenedContent += '</li>';
    for (let index = 1; index <= total_pages; index++) {
        if(pagination.page==index){
            AppenedContent += '<li class="page-item-number page-item align-self-center my-0 active" text="'+index+'">';
                AppenedContent += '<p class="page-link my-0">'+index+' <span class="sr-only">(current)</span></p>';
            AppenedContent += '</li>';
        }else{
            AppenedContent += '<li class="page-item-number page-item align-self-center my-0" text="'+index+'"><p class="page-link my-0">'+index+'</p></li>';
        }
    }
    if(pagination.page<total_pages){
        AppenedContent += '<li id="page-item-next" class="page-item align-self-center my-0" '+((pagination.page==total_pages)?' disabled':'')+'>';
            AppenedContent += '<p class="page-link my-0">>></p>';
        AppenedContent += '</li>';
    }
    $('#pagination').empty().append(AppenedContent);
}
function changePageSize(){
    pagination.per_page = $('#pagination-per-page').val();
    pagination.page = 1;
    getDepartmentsPage();
}
function changePage(){
    pagination.page = $(this).attr('text');
    getDepartmentsPage();
}
function selectBackPage(){
    pagination.page = parseInt(pagination.page)-1;
    getDepartmentsPage();
}
function selectNextPage(){
    pagination.page = parseInt(pagination.page)+1;
    getDepartmentsPage();
}
function getDepartmentsPage(){
    let DataSend = {
        pagination: pagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/departments/get-page',DataSend,null, showDepartmentsPage,null);
}
function goToUpdateTab(){
    let department_id = $(this).parent().parent().attr('department-id');
    current_department = departments.find(department => department.id == department_id);
    if(current_department != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentDepartment();
    }
}
function showDepartmentsPage(response){
    pagination = response.pagination;
    departments = response.departments;
    let appendContent = '';
    $.each(departments,function(index,value){
        //Money format with not cents
        value.budget_string = value.budget.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        appendContent += '<tr department-id='+value.id+' class="department-row-info'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-left" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-name text-left" title="'+value.name+'"><p>'+value.name+'</p></td>';
            appendContent += '<td class="columns-budget text-end" title="$'+value.budget_string+'"><p>$'+value.budget_string+'</p></td>';
            appendContent += '<td class="columns-employees-number text-center" title="'+value.employees.length+'"><p>'+value.employees.length+'</p></td>';
            if(value.director != null){
                value.director.complete_name = value.director.name+' '+(value.director.last_name?value.director.last_name:'');
                appendContent += '<td class="columns-director text-center" title="'+value.director.complete_name+'"><p>'+value.director.complete_name+'</p></td>';
            }else{
                appendContent += '<td class="columns-director text-center" title=""><p></p></td>';
            }
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    //appendContent += '<i class="fa-solid fa-scale-balanced list-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#department-list-table #department-list-table-body').empty().append(appendContent);
    showPagination();
    
}
function deleteDepartment(department_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este departamento?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: department_id,
            };
            PostMethodFunction('/admin/departments/delete',DataSend,null, function(response){
                alertSuccess('Departamento eliminado');
                if(current_tab == 'nav-update-tab'){
                    current_department.deleted_at = response.data.deleted_at;
                    showCurrentDepartment();
                }else{
                    getDepartmentsPage();
                }
                tabs_view['nav-list-tab'] = false;
                
            },null);
        }
        , null
    );
}
function restoreDepartment(department_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este departamento?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: department_id,
            };
            PostMethodFunction('/admin/departments/restore',DataSend,null, function(response){
                alertSuccess('Departamento restaurado');
                if(current_tab == 'nav-update-tab'){
                    current_department.deleted_at = null;
                    showCurrentDepartment();
                }else{
                    getDepartmentsPage();
                }
                tabs_view['nav-list-tab'] = false;
            },null);
        }
        , null
    );
}
//////////////////////////////////////////////////////
function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}
function addDepartment(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#create-department-name').val();
    let budget = $('#create-department-budget').val();
    let director_id = $('#add-department-director').val();
    if(name == null || name == ''){
        $('#create-department-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del departamento');
        flag = false;
    }else{
        $('#create-department-name').removeClass('is-invalid');
    }
    if(budget == null || budget == ''){
        $('#create-department-budget').addClass('is-invalid');
        alertWarning('Debe ingresar el presupuesto del departamento');
        flag = false;
    }else{
        $('#create-department-budget').removeClass('is-invalid');
    }
    if(director_id == null || director_id == 0){
        $('#add-department-director').addClass('is-invalid');
        alertWarning('Debe seleccionar un director');
        flag = false;
    }else{
        $('#add-department-director').removeClass('is-invalid');
    }
    if(flag){
        $('#add-department-button').prop('disabled', true);
        let dataSend = {
            name: name,
            budget: budget,
            director_id: director_id,
        };
        PostMethodFunction('/admin/departments/add',dataSend,null, function(response){
            $('#add-department-button').prop('disabled', false);
            $('#create-department-name').val('');
            $('#create-department-budget').val('');
            $('#create-department-director').val(0);
            swallMessage(
                'Exito'
                , 'Departamento creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_department = response.department;
            showCurrentDepartment();
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
        }, function(){$('#add-department-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentDepartment(){
    $('#update-department-uid').text(current_department.unique_id);
    $('#update-department-name').val(current_department.name);
    $('#update-department-budget').val(current_department.budget);
    $('#update-department-employees').text(current_department.employees_count);
    if(current_department.director != null){
        $('#update-department-director').val(current_department.director.id);
    }else{
        $('#update-department-director').val(0);
    }
    if(current_department.deleted_at == null){
        $('#update-department-button').removeClass('d-none').addClass('d-block');
        $('#update-department-delete').removeClass('d-none').addClass('d-block');
        $('#update-department-restore').removeClass('d-block').addClass('d-none');
    }else{
        $('#update-department-button').removeClass('d-block').addClass('d-none');
        $('#update-department-delete').removeClass('d-block').addClass('d-none');
        $('#update-department-restore').removeClass('d-none').addClass('d-block');
    }
    let appendContent = '';
    $.each(current_department.employees,function(index,value){
        value.charge = value.charge?value.charge:'';
        value.identification = value.identification?value.identification:'';
        value.email = value.email?value.email:'';
        value.phone = value.phone?value.phone:'';
        value.salary = value.salary!=null?value.salary.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'):'';
        value.entry_date = value.entry_date?value.entry_date:'';
        appendContent += '<tr employee-id='+value.id+'>';
            appendContent += '<td class="columns-employees-name text-center" title="'+value.name+' '+(value.last_name?value.last_name:'')+'"><p>'+value.name+' '+(value.last_name?value.last_name:'')+'</p></td>';
            appendContent += '<td class="columns-employees-position text-center" title="'+value.charge+'"><p>'+value.charge+'</p></td>';
            appendContent += '<td class="columns-employees-identification text-center" title="'+value.identification+'"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-employees-email text-center" title="'+value.email+'"><p>'+value.email+'</p></td>';
            appendContent += '<td class="columns-employees-phone text-center" title="'+value.phone+'"><p>'+value.phone+'</p></td>';
            appendContent += '<td class="columns-employees-salary text-end" title="$'+value.salary+'"><p>$'+value.salary+'</p></td>';
            appendContent += '<td class="columns-employees-entry-date text-center" title="'+value.entry_date+'"><p>'+value.entry_date+'</p></td>';
        appendContent += '</tr>';
    });
    $('#department-employee-table tbody').empty().append(appendContent);
}
function updateDepartment(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#update-department-name').val();
    let budget = $('#update-department-budget').val();
    let director_id = $('#update-department-director').val();
    if(name == null || name == ''){
        $('#update-department-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del departamento');
        flag = false;
    }else{
        $('#update-department-name').removeClass('is-invalid');
    }
    if(budget == null || budget == ''){
        $('#update-department-budget').addClass('is-invalid');
        alertWarning('Debe ingresar el presupuesto del departamento');
        flag = false;
    }else{
        $('#update-department-budget').removeClass('is-invalid');
    }
    if(director_id == null || director_id == 0){
        $('#update-department-director').addClass('is-invalid');
        alertWarning('Debe seleccionar un director');
        flag = false;
    }else{
        $('#update-department-director').removeClass('is-invalid');
    }
    if(flag){
        $('#update-department-button').prop('disabled', true);
        let dataSend = {
            id: current_department.id,
            name: name,
            budget: budget,
            director_id: director_id,
        };
        PostMethodFunction('/admin/departments/update',dataSend,null, function(response){
            $('#update-department-button').prop('disabled', false);
            swallMessage(
                'Exito'
                , 'Departamento actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#update-department-button').attr('disabled', false);});
    }
        
}
function getAllEmployees(){
    let DataSend = {
        department_id: department_id,
    };
    PostMethodFunction('/admin/employees/get-all',DataSend,null, function(response){
        notDepartmentAssignedEmployees = response.data;
        let appendContent = '';
        appendContent += '<option value="0" selected disabled>Seleccione un director</option>';
        $.each(notDepartmentAssignedEmployees,function(index,value){
            appendContent += '<option value="'+value.id+'">'+value.name+' '+(value.last_name?value.last_name:'')+'</option>';
        });
        $('#add-department-director').empty().append(appendContent);
        $('#update-department-director').empty().append(appendContent);
    },null);
}
function goToDepartmentsTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}