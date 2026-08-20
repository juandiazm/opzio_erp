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
        const options = [
            {value: '0', label: 'Sin director'},
            ...departmentState.notAssignedEmployees.map(function(value){ return {value: value.id, label: value.name+' '+(value.last_name ? value.last_name : '')}; }),
        ];
        ['#add-department-director', '#update-department-director'].forEach(function(selector){
            const select = $(selector)[0];
            if(!select) return;
            if(window.SearchableDropdown){
                window.SearchableDropdown.setOptions(select, options);
                select.selectedIndex = 0;
                window.SearchableDropdown.init(select);
            }else{
                $(select).empty().append(options.map(function(option, index){ return '<option value="'+option.value+'"'+(index === 0 ? ' selected' : '')+'>'+option.label+'</option>'; }).join(''));
            }
        });
    },null);
}