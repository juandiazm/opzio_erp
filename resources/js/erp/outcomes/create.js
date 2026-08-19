import { outcomeState } from './state.js';

export function collectOutcomeForm(prefix){
    return {
        date: $('#'+prefix+'-outcome-date').val(),
        name: $('#'+prefix+'-outcome-name').val().trim(),
        description: $('#'+prefix+'-outcome-description').val().trim(),
        amount: $('#'+prefix+'-outcome-amount').val(),
        outcome_type_id: $('#'+prefix+'-outcome-type').attr('item-id') || null,
        provider_id: $('#'+prefix+'-outcome-provider').val() || null,
        employee_id: $('#'+prefix+'-outcome-employee').val() || null,
        department_id: $('#'+prefix+'-outcome-department').val() || null,
        user_id: $('#'+prefix+'-outcome-user').val() || null,
        client_id: $('#'+prefix+'-outcome-client').val() || null,
    };
}

export function validateOutcomeForm(data, prefix){
    let valid = true;
    const fields = [
        {selector: '#'+prefix+'-outcome-date', value: data.date, message: 'Debes ingresar la fecha del egreso'},
        {selector: '#'+prefix+'-outcome-name', value: data.name, message: 'Debes ingresar el nombre del egreso'},
        {selector: '#'+prefix+'-outcome-amount', value: data.amount && Number(data.amount) > 0, message: 'Debes ingresar un monto válido'},
    ];
    fields.forEach(function(field){
        $(field.selector).toggleClass('is-invalid', !field.value);
        if(!field.value){ alertWarning(field.message); valid = false; }
    });
    $('#'+prefix+'-outcome-type').closest('.crud-input-container').toggleClass('is-invalid', !data.outcome_type_id);
    if(!data.outcome_type_id){
        alertWarning('Debes seleccionar el tipo de egreso');
        valid = false;
    }
    return valid;
}

function resetCreateForm(){
    $('#create-outcome-date').val(new Date().toISOString().slice(0, 10));
    $('#create-outcome-name,#create-outcome-description,#create-outcome-amount').val('');
    $('#create-outcome-type').removeAttr('item-id').find('.crud-current-selected-input').val('');
    $('#create-outcome-provider,#create-outcome-employee,#create-outcome-department,#create-outcome-client').val('').trigger('change');
    $('#create-outcome-user').val(outcomeState.catalogs.current_user_id || '').trigger('change');
    $('#create-outcome-form .is-invalid').removeClass('is-invalid');
}

export function createOutcome(){
    const data = collectOutcomeForm('create');
    if(!validateOutcomeForm(data, 'create')) return;
    $('#create-outcome-button').prop('disabled', true);
    PostMethodFunction('/admin/outcomes/create', data, null, function(response){
        $('#create-outcome-button').prop('disabled', false);
        alertSuccess('Egreso creado correctamente');
        resetCreateForm();
        outcomeState.currentOutcome = response.data;
        outcomeState.tabsView['nav-list-tab'] = false;
        outcomeState.tabsView['nav-update-tab'] = false;
        $('#nav-update-tab').removeClass('d-none').tab('show');
        $('#nav-update-tab').trigger('click');
    }, function(){ $('#create-outcome-button').prop('disabled', false); });
}