import { departmentState } from './state.js';

export function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}

export function getAllEmployees(){
    PostMethodFunction('/admin/employees/get-all',{department_id: null},null,function(response){
        departmentState.notAssignedEmployees = response.data;
        let appendContent = '<option value="0" selected disabled>Seleccione un director</option>';
        $.each(departmentState.notAssignedEmployees,function(index,value){
            appendContent += '<option value="'+value.id+'">'+value.name+' '+(value.last_name?value.last_name:'')+'</option>';
        });
        $('#add-department-director').empty().append(appendContent);
        $('#update-department-director').empty().append(appendContent);
    },null);
}