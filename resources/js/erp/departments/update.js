import { departmentState } from './state.js';
import { getDepartmentsPage } from './list.js';

export function showCurrentDepartment(){
    let currentDepartment = departmentState.currentDepartment;
    $('#sub-nav-outcomes').attr('data-outcome-association-id', currentDepartment.id);
    if(window.AssociatedOutcomes) window.AssociatedOutcomes.setContext('department_id', currentDepartment.id);
    $('#update-department-uid').text(currentDepartment.unique_id); $('#update-department-name').val(currentDepartment.name); $('#update-department-budget').val(currentDepartment.budget); $('#update-department-employees').text(currentDepartment.employees_count); $('#update-department-director').val(currentDepartment.director != null ? currentDepartment.director.id : 0).trigger('change');
    const deleted = currentDepartment.deleted_at != null;
    $('#update-department-button').toggleClass('d-none',deleted).toggleClass('d-block',!deleted); $('#update-department-delete').toggleClass('d-none',deleted).toggleClass('d-block',!deleted); $('#update-department-restore').toggleClass('d-block',deleted).toggleClass('d-none',!deleted);
    let appendContent = '';
    $.each(currentDepartment.employees,function(index,value){
        value.charge = value.charge?value.charge:''; value.identification = value.identification?value.identification:''; value.email = value.email?value.email:''; value.phone = value.phone?value.phone:''; value.salary = value.salary!=null?value.salary.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'):''; value.entry_date = value.entry_date?value.entry_date:'';
        appendContent += '<tr employee-id='+value.id+'><td class="columns-employees-name text-center" title="'+value.name+' '+(value.last_name?value.last_name:'')+'"><p>'+value.name+' '+(value.last_name?value.last_name:'')+'</p></td><td class="columns-employees-position text-center" title="'+value.charge+'"><p>'+value.charge+'</p></td><td class="columns-employees-identification text-center" title="'+value.identification+'"><p>'+value.identification+'</p></td><td class="columns-employees-email text-center" title="'+value.email+'"><p>'+value.email+'</p></td><td class="columns-employees-phone text-center" title="'+value.phone+'"><p>'+value.phone+'</p></td><td class="columns-employees-salary text-end" title="$'+value.salary+'"><p>$'+value.salary+'</p></td><td class="columns-employees-entry-date text-center" title="'+value.entry_date+'"><p>'+value.entry_date+'</p></td></tr>';
    });
    $('#department-employee-table tbody').empty().append(appendContent);
}
export function updateDepartment(){
    let name = $('#update-department-name').val(); let budget = $('#update-department-budget').val(); let directorId = $('#update-department-director').val(); let flag = true;
    if(name == null || name == ''){ $('#update-department-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del departamento'); flag = false; }else $('#update-department-name').removeClass('is-invalid');
    if(budget == null || budget == ''){ $('#update-department-budget').addClass('is-invalid'); alertWarning('Debe ingresar el presupuesto del departamento'); flag = false; }else $('#update-department-budget').removeClass('is-invalid');
    if(directorId == null || directorId == 0){ $('#update-department-director').addClass('is-invalid'); alertWarning('Debe seleccionar un director'); flag = false; }else $('#update-department-director').removeClass('is-invalid');
    if(flag){ $('#update-department-button').prop('disabled', true); PostMethodFunction('/admin/departments/update',{id:departmentState.currentDepartment.id,name,budget,director_id:directorId},null,function(){ $('#update-department-button').prop('disabled', false); swallMessage('Exito','Departamento actualizado','success',null,null,3000,null,null); departmentState.tabsView['nav-list-tab'] = false; },function(){$('#update-department-button').attr('disabled', false);}); }
}
export function deleteDepartment(departmentId){
    swallMessage('Advertencia','¿Está seguro de eliminar este departamento?','error','Si, eliminar','No',null,function(){ PostMethodFunction('/admin/departments/delete',{id:departmentId},null,function(response){ alertSuccess('Departamento eliminado'); if(departmentState.currentTab == 'nav-update-tab'){ departmentState.currentDepartment.deleted_at = response.data.deleted_at; showCurrentDepartment(); }else getDepartmentsPage(); departmentState.tabsView['nav-list-tab'] = false; },null); },null);
}
export function restoreDepartment(departmentId){
    swallMessage('Advertencia','¿Está seguro de restaurar este departamento?','warning','Si, restaurar','No',null,function(){ PostMethodFunction('/admin/departments/restore',{id:departmentId},null,function(){ alertSuccess('Departamento restaurado'); if(departmentState.currentTab == 'nav-update-tab'){ departmentState.currentDepartment.deleted_at = null; showCurrentDepartment(); }else getDepartmentsPage(); departmentState.tabsView['nav-list-tab'] = false; },null); },null);
}