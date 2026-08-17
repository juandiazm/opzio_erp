export function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}

export function getClients(){
    PostMethodFunction('/admin/clients/get-all',{},null, showClients, null);
}

function showClients(response){
    const options = [
        {value: '', label: 'Selecciona un cliente', disabled: true},
        ...response.data.map(function(value){ return {value: value.id, label: value.name}; }),
    ];
    ['#create-license-client', '#update-license-client'].forEach(function(selector){
        const select = $(selector)[0];
        if(!select) return;
        if(window.SearchableDropdown){
            window.SearchableDropdown.setOptions(select, options);
            select.selectedIndex = 0;
            window.SearchableDropdown.init(select);
        }else{
            $(select).html(options.map(function(option){ return '<option value="'+option.value+'"'+(option.disabled ? ' selected disabled' : '')+'>'+option.label+'</option>'; }).join(''));
        }
    });
}

export function getEmployees(){
    PostMethodFunction('/admin/employees/get-all',{},null, showEmployees, null);
}

function showEmployees(response){
    const options = [
        {value: '', label: 'Selecciona un empleado', disabled: true},
        ...response.data.map(function(value){ return {value: value.id, label: value.name}; }),
    ];
    ['#create-license-employee', '#update-license-employee'].forEach(function(selector){
        const select = $(selector)[0];
        if(!select) return;
        if(window.SearchableDropdown){
            window.SearchableDropdown.setOptions(select, options);
            select.selectedIndex = 0;
            window.SearchableDropdown.init(select);
        }else{
            $(select).html(options.map(function(option){ return '<option value="'+option.value+'"'+(option.disabled ? ' selected disabled' : '')+'>'+option.label+'</option>'; }).join(''));
        }
    });
}