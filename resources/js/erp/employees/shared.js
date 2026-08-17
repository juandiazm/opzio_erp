import { employeeState } from './state.js';

export function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}

export function getAllDepartments(){
    PostMethodFunction('/admin/departments/get-all',{},null, function(response){
        employeeState.departments = response.departments;
        let appendContent = '<option value="" selected disabled>Selecciona un departamento</option>';
        $.each(employeeState.departments,function(index,value){
            appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
        });
        $('#hiring-employee-department').empty().append(appendContent);
    },null);
}