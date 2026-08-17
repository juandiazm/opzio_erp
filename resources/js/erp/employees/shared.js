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
        const select = $('#hiring-employee-department')[0];
        const options = [
            {value: '', label: 'Selecciona un departamento', disabled: true},
            ...employeeState.departments.map(function(value){ return {value: value.id, label: value.name}; }),
        ];
        if(select && window.SearchableDropdown){
            window.SearchableDropdown.setOptions(select, options);
            select.selectedIndex = 0;
            window.SearchableDropdown.init(select);
        }else if(select){
            $(select).empty().append(options.map(function(option){ return '<option value="'+option.value+'"'+(option.disabled ? ' selected disabled' : '')+'>'+option.label+'</option>'; }).join(''));
        }
    },null);
}