import { outcomeState } from './state.js';
import { collectOutcomeForm, validateOutcomeForm } from './create.js';
import { getOutcomesPage } from './list.js';

export function showCurrentOutcome(){
    const currentOutcome = outcomeState.currentOutcome;
    if(!currentOutcome) return;
    $('#update-outcome-date').val(currentOutcome.date ? currentOutcome.date.substring(0, 10) : '');
    $('#update-outcome-name').val(currentOutcome.name || '');
    $('#update-outcome-description').val(currentOutcome.description || '');
    $('#update-outcome-amount').val(currentOutcome.amount || '');
    $('#update-outcome-type').attr('item-id', currentOutcome.outcome_type_id || '');
    $('#update-outcome-type .crud-current-selected-input').val(currentOutcome.outcome_type?.name || '');
    $('#update-outcome-provider').val(currentOutcome.provider_id || '').trigger('change');
    $('#update-outcome-employee').val(currentOutcome.employee_id || '').trigger('change');
    $('#update-outcome-department').val(currentOutcome.department_id || '').trigger('change');
    $('#update-outcome-user').val(currentOutcome.user_id || '').trigger('change');
    $('#update-outcome-client').val(currentOutcome.client_id || '').trigger('change');
    const disabled = currentOutcome.deleted_at != null;
    $('#update-outcome-form input,#update-outcome-form select,#update-outcome-form textarea').prop('disabled', disabled);
    $('#update-outcome-button').toggleClass('d-none', disabled).toggleClass('d-block', !disabled);
}

export function updateOutcome(){
    if(!outcomeState.currentOutcome) return;
    const data = collectOutcomeForm('update');
    data.id = outcomeState.currentOutcome.id;
    if(!validateOutcomeForm(data, 'update')) return;
    $('#update-outcome-button').prop('disabled', true);
    PostMethodFunction('/admin/outcomes/update', data, null, function(response){
        $('#update-outcome-button').prop('disabled', false);
        alertSuccess('Egreso actualizado correctamente');
        outcomeState.currentOutcome = response.data;
        showCurrentOutcome();
        outcomeState.tabsView['nav-list-tab'] = false;
        getOutcomesPage();
    }, function(){ $('#update-outcome-button').prop('disabled', false); });
}