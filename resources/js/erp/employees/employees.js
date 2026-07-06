$(document).on('click', '#nav-tab .nav-link', changeTab);
/////////
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-employee-button', addEmployee);
//////////
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.list-update-btn', goToUpdateTab);
$(document).on('change', '#search-list-input', getEmployeesPage);
$(document).on('click', '.list-delete-btn', function(){deleteEmployee($(this).parent().parent().attr('employee-id'));});
$(document).on('click', '.list-update-traceability', function(){
    current_employee = employees.find(employee => employee.id == $(this).closest('.employee-row-info').attr('employee-id'));
    goToEmployeesTraceability('id%'+current_employee.id);
});
//////////
$(document).on('click', '#update-employee-button', updateEmployee);
$(document).on('click', '#update-employee-hiring-button', updateHiringData);
$(document).on('click', '#update-employee-delete', function(){deleteEmployee(current_employee.id);});
$(document).on('click', '#update-employee-restore', function(){restoreEmployee(current_employee.id);});
$(document).on('click','#add-employee-documens-button', addEmployeeDocument);
$(document).on('click', '.update-employee-file-btn', updateEmployeeDocument);
$(document).on('click', '.delete-employee-file-btn', deleteEmployeeDocument);
$(document).on('click', '#update-employee-go-traceability', function(){
    goToEmployeesTraceability('id%'+current_employee.id);
});
//////////
$(document).on('click', '.update-employee-license-btn', updateEmployeeLicense);

/////////
////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
}
var employees = [];
var current_employee = null;
var employee_id = null;
var current_tab = null;
var departments_list = [];
var trought_user = false;
$(document).ready(function(){
    changeTab();
    getAllDepartments();
});
function changeTab(){
    current_tab = $('#nav-tab .active').attr('id');
    if(current_tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getEmployeesPage();    
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-traceability-tab'){
        if(trought_user == false){
            user_id = null;
        }
        trought_user = false;
    }else if(current_tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
        getEmployeeDocuments();
        getEmployeeLicenses();
    }
    tabs_view[current_tab] = true;    
}
//List Employee Functions
var db_pagination = {
    page:1,
    per_page:10,
    total:0,
};
function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
	paginationContainer.empty();
	
		let AppenedContent = '';
        AppenedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
		
        let closePage = null;
        let showPageSize = 3;
        let dots = {left: false, right: false};
        for (let index = 1; index <= db_pagination.totalPages; index++) {
        closePage = Math.abs(db_pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.left = true;
            }
        }else if(index >= db_pagination.totalPages - 2){
            if(!dots.right){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.right = true;
            }
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
        }
		
		AppenedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
        //page size
        if(db_pagination.total>5){
            AppenedContent += '<li class="page-item">';
                AppenedContent += "<p class='page-link'>";
                    AppenedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
                        AppenedContent += '<option value="5"'+((db_pagination.per_page==5)?' selected':'')+'>5</option>';
                        AppenedContent += '<option value="10"'+((db_pagination.per_page==10)?' selected':'')+'>10</option>';
                        AppenedContent += '<option value="50"'+((db_pagination.per_page==50)?' selected':'')+'>50</option>';
                    AppenedContent += '</select>';
                    AppenedContent += "</p>";
            AppenedContent += '</li>';
        }
		paginationContainer.append(AppenedContent);
	
}
function DBchangePageSize(){
    db_pagination.per_page = $('#db-pagination-per-page').val();
    db_pagination.page = 1;
    getEmployeesPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getEmployeesPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getEmployeesPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getEmployeesPage();
    }
}
function getEmployeesPage(){
    let DataSend = {
        pagination: db_pagination,
        search: $('#search-list-input').val()
    };
    PostMethodFunction('/admin/employees/get-page',DataSend,null, showEmployeesPage,null);
}
function goToUpdateTab(){
    let employee_id = $(this).parent().parent().attr('employee-id');
    current_employee = employees.find(employee => employee.id == employee_id);
    if(current_employee != null){
        $('#nav-update-tab').tab('show');
        $('#nav-update-tab').trigger('click');
        showCurrentEmployee();
    }
}
function showEmployeesPage(response){
    db_pagination = response.pagination;
    employees = response.data;
    let appendContent = '';
    $.each(employees,function(index,value){
        value.charge = value.charge?value.charge:'';
        appendContent += '<tr employee-id='+value.id+' class="employee-row-info'+(value.deleted_at==null?'':' deleted')+'">';
        appendContent += '<td class="columns-id text-left" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.uid+'"></i>'+value.uid.substr(value.uid.length - 5)+'</td>';
            appendContent += '<td class="columns-photo text-center image-column">';
                appendContent += '<div class="image-column-container d-blox mx-auto" style="background-image:url(\'/images/erp/employees/'+value.photo+'\');">';
            appendContent += ' </td>';
            appendContent += '<td class="columns-identification text-start" title="'+value.identification+'"><p>'+value.identification+'</p></td>';
            appendContent += '<td class="columns-name text-start" title="'+value.name+'"><p>'+value.name+(value.last_name?'':(' '+value.last_name))+'</p></td>';
            if(value.department==null){
                appendContent += '<td class="columns-department text-start" title=""><p></p></td>';
            }else{
                appendContent += '<td class="columns-department text-start" title="'+value.department.name+'"><p>'+value.department.name+'</p></td>';
            }
            appendContent += '<td class="columns-position text-start" title="'+value.charge+'"><p>'+value.charge+'</p></td>';
            appendContent += '<td class="columns-email text-start" title="'+value.work_email+'"><p>'+value.work_email+'</p></td>';
            appendContent += '<td class="columns-statte text-center active-col"><p class="active-state active-state-'+value.state+'">'+(value.state?'Activo':'Inactivo')+'</p></td>';
            appendContent += '<td class="columns-actions text-end action-cell">';
                if(value.deleted_at==null){
                    appendContent += '<i class="fa-solid fa-pen-to-square list-update-btn"></i>';
                    //appendContent += '<i class="fa-solid fa-scale-balanced list-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-ban list-delete-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-eye list-update-btn"></i>';
                    //appendContent += '<i class="fa-solid fa-scale-balanced list-traceability"></i>';
                    appendContent += '<i class="fa-solid fa-bars-progress list-update-traceability"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#employee-list-table #employee-list-table-body').empty().append(appendContent);
    DBshowPagination();
    
}
function deleteEmployee(employee_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este empleado?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: employee_id,
            };
            PostMethodFunction('/admin/employees/delete',DataSend,null, function(response){
                alertSuccess('Empleado eliminado');
                if(current_tab == 'nav-update-tab'){
                    current_employee.deleted_at = response.data.deleted_at;
                    showCurrentEmployee();
                }else{
                    getEmployeesPage();
                }
                tabs_view['nav-list-tab'] = false;
                
            },null);
        }
        , null
    );
}
function restoreEmployee(employee_id){
    swallMessage(
        'Advertencia'
        , '¿Está seguro de restaurar este empleado?'
        , 'warning'
        , 'Si, restaurar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: employee_id,
            };
            PostMethodFunction('/admin/employees/restore',DataSend,null, function(response){
                alertSuccess('Empleado restaurado');
                if(current_tab == 'nav-update-tab'){
                    current_employee.deleted_at = null;
                    showCurrentEmployee();
                }else{
                    getEmployeesPage();
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
function addEmployee(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#create-employee-name').val();
    let last_name = $('#create-employee-last-name').val();
    let id_type = $('#create-employee-id-type').val();
    let identification = $('#create-employee-identification').val();
    let country = $('#create-employee-country').attr('item-id');
    let phone = $('#create-employee-phone').val();
    let personal_email = $('#create-employee-personal-email').val();
    let work_email = $('#create-employee-work-email').val();
    let state = $('#create-employee-state').attr('value');
    let image = $('#create-employee-img').val();
    if(image == null || image == ''){
        $('#create-employee-img').addClass('is-invalid');
        alertWarning('Debe ingresar una imagen');
        flag = false;
    }
    if(name == null || name == ''){
        $('#create-employee-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del empleado');
        flag = false;
    }else{
        $('#create-employee-name').removeClass('is-invalid');
    }
    if(last_name == null || last_name == ''){
        $('#create-employee-last-name').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del empleado');
        flag = false;
    }else{
        $('#create-employee-last-name').removeClass('is-invalid');
    }
    if(id_type == null || id_type == ''){
        $('#create-employee-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }else{
        $('#create-employee-id-type').removeClass('is-invalid');
    }
    if(identification == null || identification == ''){
        $('#create-employee-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del empleado');
        flag = false;
    }else{
        $('#create-employee-identification').removeClass('is-invalid');
    }
    if(state == null || state == ''){
        $('#create-employee-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }      
    if(country == null || country == ''){
        $('#create-employee-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-employee-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del empleado');
        flag = false;
    }
    if(personal_email == null || personal_email == '' || !validateEmail(personal_email)){
        $('#create-employee-personal-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{
        $('#create-employee-personal-email').removeClass('is-invalid');
    }
    if(work_email == null || work_email == '' || !validateEmail(work_email)){
        $('#create-employee-work-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{
        $('#create-employee-work-email').removeClass('is-invalid');
    }
    if(flag){
        $('#add-employee-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="text" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="last_name" value="'+last_name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="id_type" value="'+id_type+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="identification" value="'+identification+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="phone" value="'+phone+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="personal_email" value="'+personal_email+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="work_email" value="'+work_email+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('#create-employee-img').clone(true)[0]);
        
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#create-employee-img')[0].files;
        $('#temporal-form').remove();
        /////////////////////////////
        PostMethodMultimediaFunction('/admin/employees/add',dinamicForm,null, function(response){
            $('#add-employee-button').prop('disabled', false);
            $(container).find('.image_preview').css('display', 'inline-block');
            $(container).find('.image-container').css('background-image', 'none');
	        $(container).find('.image-icon').css('display', 'inline-block');
            $('#create-employee-img').val('');
            $('#create-employee-name').val('');
            $('#create-employee-last-name').val('');
            $('#create-employee-identification').val('');
            $('#create-employee-phone').val('');
            $('#create-employee-personal-email').val('');
            $('#create-employee-work-email').val('');
            swallMessage(
                'Exito'
                , 'Empleado creado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
            current_employee = response.employee;
            showCurrentEmployee();
            $('#nav-update-tab').tab('show');
            $('#nav-update-tab').trigger('click');
        }, function(){$('#add-employee-button').attr('disabled', false);});
    }
}
//Update User functions
function showCurrentEmployee(){
    $('#update-employee-img-container').css('background-image','url("/images/erp/employees/'+current_employee.photo+'")');
    $('#update-employee-img-container .image-icon').css('display','none');
    $('#update-employee-uid').text(current_employee.uid);
    $('#update-employee-name').val(current_employee.name);
    $('#update-employee-last-name').val(current_employee.last_name);
    $('#update-employee-id-type').val(current_employee.id_type);
    $('#update-employee-identification').val(current_employee.identification);
    $('#update-employee-state').attr('value', current_employee.state);
    $('#update-employee-state .toggle-value[value="'+current_employee.state+'"]').click();
    if(current_employee.country == null){
        $('#update-employee-country').attr('item-id', '');
        $('#update-employee-country input').val('');
    }else{
        $('#update-employee-country').attr('item-id', current_employee.country.id);
        $('#update-employee-country input').val(current_employee.country.name);
    
    }
    $('#update-employee-phone').val(current_employee.phone);
    $('#update-employee-personal-email').val(current_employee.personal_email);
    $('#update-employee-work-email').val(current_employee.work_email);
    //Hiring data
    $('#hiring-employee-entry-date').val(current_employee.entry_date);
    $('#hiring-employee-payment-type').val(current_employee.payment_type);
    $('#hiring-employee-bank').val(current_employee.bank);
    $('#hiring-employee-account-number').val(current_employee.account_number);
    $('#hiring-employee-account-type').val(current_employee.account_type);
    $('#hiring-employee-salary').val(current_employee.salary);
    $('#hiring-employee-contract').val(current_employee.contract);
    $('#hiring-employee-department').val(current_employee.department_id);
    $('#hiring-employee-charge').val(current_employee.charge);
    $('#hiring-employee-eps').attr('item-id', current_employee.eps_id);
    $('#hiring-employee-eps input').val(current_employee.eps==null?'':current_employee.eps.name);
    $('#hiring-employee-afp').attr('item-id', current_employee.afp_id);
    $('#hiring-employee-afp input').val(current_employee.afp==null?'':current_employee.afp.name);
    $('#hiring-employee-arl').attr('item-id', current_employee.arl_id);
    $('#hiring-employee-arl input').val(current_employee.arl==null?'':current_employee.arl.name);
    $('#hiring-employee-retirement-date').val(current_employee.retirement_date);
    if(current_employee.deleted_at!=null){
        $('#update-employee-button').addClass('d-none').removeClass('d-block');
        $('#update-employee-hiring-button').addClass('d-none').removeClass('d-block');
        $('#add-employee-documens-button').addClass('d-none').removeClass('d-block');
        $('#update-employee-documents').addClass('d-none').removeClass('d-block');
        $('#update-employee-delete').addClass('d-none').removeClass('d-block');
        $('#update-employee-restore').addClass('d-block').removeClass('d-none');
        //enable inputs
        $('#update-employee-name').prop('disabled', true);
        $('#update-employee-last-name').prop('disabled', true);
        $('#update-employee-id-type').prop('disabled', true);
        $('#update-employee-identification').prop('disabled', true);
        $('#update-employee-country').prop('disabled', true);
        $('#update-employee-phone').prop('disabled', true);
        $('#update-employee-personal-email').prop('disabled', true);
        $('#update-employee-work-email').prop('disabled', true);
        $('#update-employee-state').prop('disabled', true);
        //Hiring data
        $('#hiring-employee-entry-date').prop('disabled', true);
        $('#hiring-employee-payment-type').prop('disabled', true);
        $('#hiring-employee-bank').prop('disabled', true);
        $('#hiring-employee-account-number').prop('disabled', true);
        $('#hiring-employee-account-type').prop('disabled', true);
        $('#hiring-employee-salary').prop('disabled', true);
        $('#hiring-employee-contract').prop('disabled', true);
        $('#hiring-employee-department').prop('disabled', true);
        $('#hiring-employee-charge').prop('disabled', true);
        $('#hiring-employee-eps').prop('disabled', true);
        $('#hiring-employee-afp').prop('disabled', true);
        $('#hiring-employee-arl').prop('disabled', true);
        $('#hiring-employee-retirement-date').prop('disabled', true);
    }else{
        $('#update-employee-button').removeClass('d-none').addClass('d-block');
        $('#update-employee-hiring-button').removeClass('d-none').addClass('d-block');
        $('#add-employee-documens-button').removeClass('d-none').addClass('d-block');
        $('#update-employee-documents').removeClass('d-none').addClass('d-block');
        $('#update-employee-delete').removeClass('d-none').addClass('d-block');
        $('#update-employee-restore').removeClass('d-block').addClass('d-none');
        //enable inputs
        $('#update-employee-name').prop('disabled', false);
        $('#update-employee-last-name').prop('disabled', false);
        $('#update-employee-id-type').prop('disabled', false);
        $('#update-employee-identification').prop('disabled', false);
        $('#update-employee-country').prop('disabled', false);
        $('#update-employee-phone').prop('disabled', false);
        $('#update-employee-personal-email').prop('disabled', false);
        $('#update-employee-work-email').prop('disabled', false);
        $('#update-employee-state').prop('disabled', false);
        //Hiring data
        $('#hiring-employee-entry-date').prop('disabled', false);
        $('#hiring-employee-payment-type').prop('disabled', false);
        $('#hiring-employee-bank').prop('disabled', false);
        $('#hiring-employee-account-number').prop('disabled', false);
        $('#hiring-employee-account-type').prop('disabled', false);
        $('#hiring-employee-salary').prop('disabled', false);
        $('#hiring-employee-contract').prop('disabled', false);
        $('#hiring-employee-department').prop('disabled', false);
        $('#hiring-employee-charge').prop('disabled', false);
        $('#hiring-employee-eps').prop('disabled', false);
        $('#hiring-employee-afp').prop('disabled', false);
        $('#hiring-employee-arl').prop('disabled', false);
        $('#hiring-employee-retirement-date').prop('disabled', false);
    }
}
function updateEmployee(){
    let container = $(this).parent();
    let flag = true;
    let name = $('#update-employee-name').val();
    let last_name = $('#update-employee-last-name').val();
    let id_type = $('#update-employee-id-type').val();
    let identification = $('#update-employee-identification').val();
    let country = $('#update-employee-country').attr('item-id');
    let phone = $('#update-employee-phone').val();
    let personal_email = $('#update-employee-personal-email').val();
    let work_email = $('#update-employee-work-email').val();
    let state = $('#update-employee-state').attr('value');
    if(name == null || name == ''){
        $('#update-employee-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del empleado');
        flag = false;
    }else{
        $('#update-employee-name').removeClass('is-invalid');
    }
    if(last_name == null || last_name == ''){
        $('#update-employee-last-name').addClass('is-invalid');
        alertWarning('Debe ingresar el apellido del empleado');
        flag = false;
    }else{
        $('#update-employee-last-name').removeClass('is-invalid');
    }
    if(id_type == null || id_type == ''){
        $('#update-employee-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }else{
        $('#update-employee-id-type').removeClass('is-invalid');
    }
    if(identification == null || identification == ''){
        $('#update-employee-identification').addClass('is-invalid');
        alertWarning('Debe ingresar la identificación del empleado');
        flag = false;
    }else{
        $('#update-employee-identification').removeClass('is-invalid');
    }
    if(state == null || state == ''){
        $('#update-employee-state').addClass('is-invalid');
        alertWarning('Debe seleccionar un estado');
        flag = false;
    }      
    if(country == null || country == ''){
        $('#update-employee-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#update-employee-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del empleado');
        flag = false;
    }
    if(personal_email == null || personal_email == '' || !validateEmail(personal_email)){
        $('#update-employee-personal-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{
        $('#update-employee-personal-email').removeClass('is-invalid');
    }
    if(work_email == null || work_email == '' || !validateEmail(work_email)){
        $('#update-employee-work-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del empleado');
        flag = false;
    }else{
        $('#update-employee-work-email').removeClass('is-invalid');
    }
    if(flag){
        $('#update-employee-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="id" value="'+current_employee.id+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="last_name" value="'+last_name+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="id_type" value="'+id_type+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="identification" value="'+identification+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="country" value="'+country+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="phone" value="'+phone+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="personal_email" value="'+personal_email+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="work_email" value="'+work_email+'">')[0]);
        dinamicForm.appendChild($('<input type="text" name="state" value="'+state+'">')[0]);
        dinamicForm.appendChild($('#update-employee-img').clone(true)[0]);
        
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.input_image')[0].files = $('#update-employee-img')[0].files;
        $('#temporal-form').remove();

        PostMethodMultimediaFunction('/admin/employees/update',dinamicForm,null, function(response){
            $('#update-employee-button').prop('disabled', false);
            swallMessage(
                'Exito'
                , 'Empleado actualizado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            tabs_view['nav-list-tab'] = false;
        }, function(){$('#update-employee-button').attr('disabled', false);});
    }
        
}
function addEmployeeDocument(){
    let container = $(this).parent();
    let name = container.find('.employee-document-input-name').val();
    let file = container.find('.employee-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.employee-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.employee-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-employee-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="employee_id" value="'+current_employee.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.employee-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.employee-document-input-file')[0].files =  container.find('.employee-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/employees/documents/add', dinamicForm, null, function(response){
            $('#add-employee-documens-button').attr('disabled', false);
            container.find('.employee-document-input-name').val('');
            container.find('.employee-document-input-file').val('');
            swallMessage(
                'Exito'
                , 'Documento agregado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getEmployeeDocuments();
        }, function(){$('#add-employee-documens-button').attr('disabled', false);});
    }
}
function getEmployeeDocuments(){
    let DataSend = {
        employee_id: current_employee.id
    };
    PostMethodFunction('/admin/employees/documents/get',DataSend,null, showEmployeeDocuments,null);
}
function showEmployeeDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="employee-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="employee-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-employee-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-employee-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#employee-documents-table #employee-documents-table-body').empty().append(appendContent);
}
function updateEmployeeDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.employee-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.employee-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let DataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/admin/employees/documents/update',DataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}
function deleteEmployeeDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este documento?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: id,
            };
            PostMethodFunction('/admin/employees/documents/delete',DataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
    
}
//Hiring data
function updateHiringData(){
    $('#update-employee-hiring-button').attr('disabled', true);
    let entry_date = $('#hiring-employee-entry-date').val();
    let payment_type = $('#hiring-employee-payment-type').val();
    let bank = $('#hiring-employee-bank').val();
    let account_number = $('#hiring-employee-account-number').val();
    let account_type = $('#hiring-employee-account-type').val();
    let salary = $('#hiring-employee-salary').val();
    let contract = $('#hiring-employee-contract').val();
    let department_id = $('#hiring-employee-department').val();
    let charge = $('#hiring-employee-charge').val();
    let eps_id = $('#hiring-employee-eps').attr('item-id');
    let afp_id = $('#hiring-employee-afp').attr('item-id');
    let arl_id = $('#hiring-employee-arl').attr('item-id');
    let retirement_date = $('#hiring-employee-retirement-date').val();
    let dataSend = {
        id: current_employee.id,
        entry_date: entry_date,
        payment_type: payment_type,
        bank: bank,
        account_number: account_number,
        account_type: account_type,
        salary: salary,
        contract: contract,
        department_id: department_id,
        charge: charge,
        eps_id: eps_id,
        afp_id: afp_id,
        arl_id: arl_id,
        retirement_date: retirement_date,
    };
    PostMethodFunction('/admin/employees/hiring/update',dataSend,null, function(response){
        $('#update-employee-hiring-button').attr('disabled', false);
        swallMessage(
            'Exito'
            , 'Datos de contratación actualizados'
            , 'success'
            , null
            , null
            , 3000
            , null
            , null
        );
        tabs_view['nav-list-tab'] = false;
    }, function(){$('#update-employee-hiring-button').attr('disabled', false);});
}
function getAllDepartments(){
    PostMethodFunction('/admin/departments/get-all',{},null, function(response){
        departments_list = response.departments;
        let appendContent = '';
        appendContent += '<option value="" selected disabled>Selecciona un departamento</option>';
        $.each(departments_list,function(index,value){
            appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
        });
        $('#hiring-employee-department').empty().append(appendContent);
    },null);
}
//Licenses
function getEmployeeLicenses(){
    let DataSend = {
        employee_id: current_employee.id
    };
    PostMethodFunction('/admin/employees/licenses/get-by-employee-id',DataSend,null, showEmployeeLicenses,null);
}
function showEmployeeLicenses(response){
    let appendContent = '';
    $.each(response.licenses,function(index,value){
        appendContent += '<tr class="employee-license-row" license-id="'+value.id+'">';
            appendContent += '<td class="text-left"><a href="/admin/licenses?license_id='+value.id+'" class="employee-license-input-serivice align-self-end input-value">'+value.service.name+'</a></td>';
            appendContent += '<td class="text-left"><p class="employee-license-input-name align-self-end input-value">'+value.name+'</p></td>';
            appendContent += '<td class="text-left"><p class="employee-license-input-client-name align-self-end input-value">'+value.client.name+(value.client.lastname==null?'':' '+value.client.lastname)+'</p></td>';
            appendContent += '<td class="text-left"><input type="number" name="" class="employee-license-input-comission form-control align-self-center input-value text-center" placeholder="Comisión..." value="'+value.comission+'"></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-employee-license-btn"></i>';
                appendContent += '<i class="fa-solid fa-bars-progress traceability-employee-license-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#employee-licenses-table #employee-licenses-table-body').empty().append(appendContent);
}
function updateEmployeeLicense(){
    let container = $(this).parent().parent();
    let license_id = container.attr('license-id');
    let comission = container.find('.employee-license-input-comission').val();
    let flag = true;
    if(comission == null || comission == ''){
        container.find('.employee-license-input-comission').addClass('is-invalid');
        alertWarning('Debe ingresar la comisión');
        flag = false;
    }else{
        container.find('.employee-license-input-comission').removeClass('is-invalid');
    }
    if(flag){
        let DataSend = {
            id: license_id,
            comission: comission
        };
        PostMethodFunction('/admin/employees/licenses/update-comission',DataSend,null, function(response){
            alertSuccess('Licencia actualizada');
        },null);
    }
}
function goToEmployeesTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}