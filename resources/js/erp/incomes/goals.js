import { incomeState } from './state.js';

function escapeHtml(value){
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(character){
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
    });
}

function setFormExpanded(expanded){
    const body = $('#income-goal-form-body');
    const toggle = $('#income-goal-toggle');
    body.prop('hidden', !expanded);
    toggle.attr('aria-expanded', expanded ? 'true' : 'false');
    toggle.find('i').toggleClass('fa-plus', !expanded).toggleClass('fa-minus', expanded);
}

function resetForm(){
    incomeState.editingGoalId = null;
    $('#income-goal-target-amount, #income-goal-frequency-months, #income-goal-start-date, #income-goal-end-date').val('').removeClass('is-invalid');
    $('#income-goal-save').html('<i class="fa-solid fa-plus"></i> Agregar');
    $('#income-goal-cancel').addClass('d-none');
    setFormExpanded(false);
}

function formatCreatedAt(goal){
    if(goal.created_at_string) return goal.created_at_string;
    if(!goal.created_at) return '-';
    return String(goal.created_at).replace('T', ' ').substring(0, 16);
}

function formatAmount(value){
    const amount = Number(value);
    return Number.isFinite(amount) ? '$'+amount.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2}) : '-';
}

function formatFrequency(frequencyMonths){
    const months = Number(frequencyMonths);
    if(months === 1) return 'Mensual';
    if(months === 12) return 'Anual';
    return 'Cada '+months+' meses';
}

function formatDate(value){
    if(!value) return '';
    const parts = String(value).split('T')[0].split('-');
    return parts.length === 3 ? parts[2]+'/'+parts[1]+'/'+parts[0] : String(value);
}

function formatDateRange(goal){
    if(!goal.start_date || !goal.end_date) return 'Sin rango definido';
    return formatDate(goal.start_date)+' - '+formatDate(goal.end_date);
}

function getInclusiveMonthCount(startDate, endDate){
    const startParts = startDate.split('-').map(Number);
    const endParts = endDate.split('-').map(Number);
    return ((endParts[0] - startParts[0]) * 12) + (endParts[1] - startParts[1]) + 1;
}

export function initializeGoalForm(){ setFormExpanded(false); }

export function toggleForm(){ setFormExpanded($('#income-goal-toggle').attr('aria-expanded') !== 'true'); }

export function getGoals(){ PostMethodFunction('/admin/incomes/goals/get', {}, null, renderGoals, null); }

function renderGoals(response){
    incomeState.goals = response.data || response.goals || [];
    let html = '';
    $.each(incomeState.goals, function(index, goal){
        const deleted = goal.deleted_at != null;
        const targetAmount = escapeHtml(formatAmount(goal.target_amount));
        html += '<tr class="income-goal-row'+(deleted ? ' deleted' : '')+'" data-goal-id="'+escapeHtml(goal.id)+'">';
        html += '<td class="income-goal-amount-cell text-start"><strong class="income-goal-amount">'+targetAmount+'</strong></td>';
        html += '<td class="income-goal-frequency-cell text-center"><span class="income-goal-frequency-label">'+escapeHtml(formatFrequency(goal.frequency_months))+'</span></td>';
        html += '<td class="income-goal-range-cell text-center">'+escapeHtml(formatDateRange(goal))+'</td>';
        html += '<td class="income-goal-created-cell text-center">'+escapeHtml(formatCreatedAt(goal))+'</td>';
        html += '<td class="text-end income-goal-actions">';
        if(deleted) html += '<button type="button" class="income-goal-action income-goal-restore" title="Restaurar meta" aria-label="Restaurar meta"><i class="fa-solid fa-rotate-left"></i></button>';
        else html += '<button type="button" class="income-goal-action income-goal-update" title="Editar meta" aria-label="Editar meta"><i class="fa-solid fa-pen-to-square"></i></button><button type="button" class="income-goal-action income-goal-delete" title="Eliminar meta" aria-label="Eliminar meta"><i class="fa-solid fa-trash-can"></i></button>';
        html += '</td></tr>';
    });
    $('#income-goals-table-body').html(html || '<tr><td colspan="5" class="text-center">No hay metas registradas</td></tr>');
}

export function saveGoal(){
    const targetAmount = $('#income-goal-target-amount').val();
    const frequencyMonths = $('#income-goal-frequency-months').val();
    const startDate = $('#income-goal-start-date').val();
    const endDate = $('#income-goal-end-date').val();
    let valid = true;
    if(!targetAmount || Number(targetAmount) <= 0){ $('#income-goal-target-amount').addClass('is-invalid'); alertWarning('Debe ingresar un monto mayor a 0'); valid = false; }
    else $('#income-goal-target-amount').removeClass('is-invalid');
    if(!frequencyMonths || !Number.isInteger(Number(frequencyMonths)) || Number(frequencyMonths) < 1){ $('#income-goal-frequency-months').addClass('is-invalid'); alertWarning('Debe ingresar una frecuencia válida en meses'); valid = false; }
    else $('#income-goal-frequency-months').removeClass('is-invalid');
    if(!startDate){ $('#income-goal-start-date').addClass('is-invalid'); alertWarning('Debe seleccionar el inicio del rango'); valid = false; }
    else $('#income-goal-start-date').removeClass('is-invalid');
    if(!endDate){ $('#income-goal-end-date').addClass('is-invalid'); alertWarning('Debe seleccionar el fin del rango'); valid = false; }
    else $('#income-goal-end-date').removeClass('is-invalid');
    if(startDate && endDate && frequencyMonths){
        const start = new Date(startDate+'T00:00:00');
        const end = new Date(endDate+'T00:00:00');
        const lastDayOfEndMonth = new Date(end.getFullYear(), end.getMonth()+1, 0).getDate();
        if(start > end){ alertWarning('El inicio del rango no puede ser posterior al fin'); valid = false; }
        else if(start.getDate() !== 1 || end.getDate() !== lastDayOfEndMonth){ alertWarning('El rango debe iniciar el primer día y terminar el último día de un mes'); valid = false; }
        else if(getInclusiveMonthCount(startDate, endDate) % Number(frequencyMonths) !== 0){ alertWarning('La cantidad de meses del rango debe ser múltiplo de la frecuencia'); valid = false; }
    }
    if(!valid) return;
    const data = {target_amount: targetAmount, frequency_months: frequencyMonths, start_date: startDate, end_date: endDate};
    const endpoint = incomeState.editingGoalId ? '/admin/incomes/goals/update' : '/admin/incomes/goals/add';
    if(incomeState.editingGoalId) data.id = incomeState.editingGoalId;
    $('#income-goal-save').attr('disabled', true);
    PostMethodFunction(endpoint, data, null, function(){
        $('#income-goal-save').attr('disabled', false);
        alertSuccess(incomeState.editingGoalId ? 'Meta actualizada correctamente' : 'Meta agregada correctamente');
        resetForm();
        getGoals();
    }, function(){ $('#income-goal-save').attr('disabled', false); });
}

export function editGoal(){
    const row = $(this).closest('.income-goal-row');
    const goalId = row.attr('data-goal-id');
    const goal = incomeState.goals.find(function(item){ return String(item.id) === String(goalId); });
    if(!goal) return;
    incomeState.editingGoalId = goalId;
    $('#income-goal-target-amount').val(goal.target_amount);
    $('#income-goal-frequency-months').val(goal.frequency_months);
    $('#income-goal-start-date').val(goal.start_date ? String(goal.start_date).split('T')[0] : '');
    $('#income-goal-end-date').val(goal.end_date ? String(goal.end_date).split('T')[0] : '');
    $('#income-goal-save').html('<i class="fa-solid fa-floppy-disk"></i> Actualizar');
    $('#income-goal-cancel').removeClass('d-none');
    setFormExpanded(true);
    $('#income-goal-target-amount').trigger('focus');
}

export function cancelGoal(){ resetForm(); }

export function deleteGoal(){
    const goalId = $(this).closest('.income-goal-row').attr('data-goal-id');
    swallMessage('Eliminar meta', '¿Está seguro de eliminar esta meta?', 'warning', 'Sí, eliminar', 'Cancelar', null, function(){
        PostMethodFunction('/admin/incomes/goals/delete', {id: goalId}, null, function(){ alertSuccess('Meta eliminada correctamente'); getGoals(); }, null);
    }, null);
}

export function restoreGoal(){
    const goalId = $(this).closest('.income-goal-row').attr('data-goal-id');
    PostMethodFunction('/admin/incomes/goals/restore', {id: goalId}, null, function(){ alertSuccess('Meta restaurada correctamente'); getGoals(); }, null);
}
