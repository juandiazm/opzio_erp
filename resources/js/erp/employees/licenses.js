import { employeeState } from './state.js';

export function getEmployeeLicenses(){
    let dataSend = {employee_id: employeeState.currentEmployee.id};
    PostMethodFunction('/admin/employees/licenses/get-by-employee-id',dataSend,null, showEmployeeLicenses,null);
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

export function updateEmployeeLicense(){
    let container = $(this).parent().parent();
    let licenseId = container.attr('license-id');
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
        let dataSend = {id: licenseId, comission: comission};
        PostMethodFunction('/admin/employees/licenses/update-comission',dataSend,null, function(response){
            alertSuccess('Licencia actualizada');
        },null);
    }
}