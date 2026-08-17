import { employeeState } from './state.js';
import { verificationInputChange, getAllDepartments } from './shared.js';
import * as list from './list.js';
import { addEmployee } from './create.js';
import * as update from './update.js';
import { addEmployeeDocument, getEmployeeDocuments, updateEmployeeDocument, deleteEmployeeDocument } from './documents.js';
import { getEmployeeLicenses, updateEmployeeLicense } from './licenses.js';

function showCurrentEmployee(){
    update.showCurrentEmployee();
}

function changeTab(){
    employeeState.currentTab = $('#nav-tab .active').attr('id');
    if(employeeState.currentTab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(employeeState.tabsView[employeeState.currentTab]==false && employeeState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        list.getEmployeesPage();
    }else if(employeeState.tabsView[employeeState.currentTab]==false && employeeState.currentTab == 'nav-create-tab'){
    }else if(employeeState.tabsView[employeeState.currentTab]==false && employeeState.currentTab == 'nav-traceability-tab'){
        if(employeeState.troughtUser == false){ window.user_id = null; }
        employeeState.troughtUser = false;
    }else if(employeeState.currentTab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
        getEmployeeDocuments();
        getEmployeeLicenses();
    }
    employeeState.tabsView[employeeState.currentTab] = true;
}

function goToEmployeesTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-employee-button', function(){addEmployee(this, showCurrentEmployee);});
$(document).on('change', '#db-pagination-per-page', list.DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.DBchangePage);
$(document).on('click', '#db-page-item-back', list.DBselectBackPage);
$(document).on('click', '#db-page-item-next', list.DBselectNextPage);
$(document).on('click', '.list-update-btn', function(){list.goToUpdateTab(this, showCurrentEmployee);});
$(document).on('change', '#search-list-input', list.getEmployeesPage);
$(document).on('click', '.list-delete-btn', function(){update.deleteEmployee($(this).parent().parent().attr('employee-id'), list.getEmployeesPage);});
$(document).on('click', '.list-update-traceability', function(){
    let currentEmployee = list.setCurrentEmployeeFromRow(this);
    if(currentEmployee != null) goToEmployeesTraceability('id%'+currentEmployee.id);
});
$(document).on('click', '#update-employee-button', update.updateEmployee);
$(document).on('click', '#update-employee-hiring-button', update.updateHiringData);
$(document).on('click', '#update-employee-delete', function(){update.deleteEmployee(employeeState.currentEmployee.id, list.getEmployeesPage);});
$(document).on('click', '#update-employee-restore', function(){update.restoreEmployee(employeeState.currentEmployee.id, list.getEmployeesPage);});
$(document).on('click','#add-employee-documens-button', addEmployeeDocument);
$(document).on('click', '.update-employee-file-btn', updateEmployeeDocument);
$(document).on('click', '.delete-employee-file-btn', deleteEmployeeDocument);
$(document).on('click', '#update-employee-go-traceability', function(){goToEmployeesTraceability('id%'+employeeState.currentEmployee.id);});
$(document).on('click', '.update-employee-license-btn', updateEmployeeLicense);

$(document).ready(function(){
    changeTab();
    getAllDepartments();
});