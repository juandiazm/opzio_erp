import { departmentState } from './state.js';

export function addDepartment(onCreated){
    let name = $('#create-department-name').val();
    let budget = $('#create-department-budget').val();
    let directorId = $('#add-department-director').val();
    directorId = directorId == null || directorId == 0 ? null : directorId;
    let flag = true;
    if(name == null || name == ''){ $('#create-department-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del departamento'); flag = false; }else $('#create-department-name').removeClass('is-invalid');
    if(budget == null || budget == ''){ $('#create-department-budget').addClass('is-invalid'); alertWarning('Debe ingresar el presupuesto del departamento'); flag = false; }else $('#create-department-budget').removeClass('is-invalid');
    if(flag){
        $('#add-department-button').prop('disabled', true);
        PostMethodFunction('/admin/departments/add',{name,budget,director_id:directorId},null,function(response){
            $('#add-department-button').prop('disabled', false); $('#create-department-name').val(''); $('#create-department-budget').val(''); $('#add-department-director').val(0);
            swallMessage('Exito','Departamento creado','success',null,null,3000,null,null); departmentState.tabsView['nav-list-tab'] = false; departmentState.currentDepartment = response.department; onCreated(); $('#nav-update-tab').tab('show'); $('#nav-update-tab').trigger('click');
        },function(){$('#add-department-button').attr('disabled', false);});
    }
}