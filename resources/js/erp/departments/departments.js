import { departmentState } from './state.js';
import { verificationInputChange, getAllEmployees } from './shared.js';
import * as list from './list.js';
import { addDepartment } from './create.js';
import * as update from './update.js';

function showCurrentDepartment(){ update.showCurrentDepartment(); }
function goToDepartmentsTraceability(search){ $('#nav-traceability').attr('search',search); $('#nav-traceability-tab').tab('show'); $('#nav-traceability-tab').trigger('click'); }
function changeTab(){
    departmentState.currentTab = $('#nav-tab .active').attr('id');
    if(departmentState.currentTab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(departmentState.tabsView[departmentState.currentTab]==false && departmentState.currentTab == 'nav-list-tab'){ $('#search-list-input').focus(); list.getDepartmentsPage(); }
    else if(departmentState.currentTab == 'nav-update-tab') $('#nav-update-tab').removeClass('d-none');
    departmentState.tabsView[departmentState.currentTab] = true;
}
$(document).on('click','#nav-tab .nav-link',changeTab);
$(document).on('click','.verification-input-icon',verificationInputChange);
$(document).on('click','#add-department-button',function(){addDepartment(showCurrentDepartment);});
$(document).on('change','#pagination-per-page',list.changePageSize);
$(document).on('click','#pagination .page-item-number',list.changePage);
$(document).on('click','#page-item-back',list.selectBackPage);
$(document).on('click','#page-item-next',list.selectNextPage);
$(document).on('click','.list-update-btn',function(){list.goToUpdateTab(this,showCurrentDepartment);});
$(document).on('change','#search-list-input',list.getDepartmentsPage);
$(document).on('click','.list-delete-btn',function(){update.deleteDepartment($(this).parent().parent().attr('department-id'));});
$(document).on('click','.list-update-traceability',function(){let department=list.setCurrentDepartmentFromRow(this);if(department!=null)goToDepartmentsTraceability('id%'+department.id);});
$(document).on('click','#update-department-button',update.updateDepartment);
$(document).on('click','#update-department-delete',function(){update.deleteDepartment(departmentState.currentDepartment.id);});
$(document).on('click','#update-department-restore',function(){update.restoreDepartment(departmentState.currentDepartment.id);});
$(document).on('click','#update-department-go-traceability',function(){goToDepartmentsTraceability('id%'+departmentState.currentDepartment.id);});
$(document).ready(function(){getAllEmployees();changeTab();});