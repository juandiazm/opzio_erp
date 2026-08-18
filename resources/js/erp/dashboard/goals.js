import { dashboardState } from './state.js';

function escapeHtml(value){
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(character){
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
    });
}

function formatAmount(value){
    const amount = Number(value);
    if(!Number.isFinite(amount)) return '$0';
    return '$ '+amount.toLocaleString('es-CO', {maximumFractionDigits: 0});
}

function formatDate(value){
    if(!value) return '';
    const parts = String(value).split('T')[0].split('-');
    return parts.length === 3 ? parts[2]+'/'+parts[1]+'/'+parts[0] : String(value);
}

function formatPercentage(value){
    const percentage = Number(value);
    if(!Number.isFinite(percentage)) return '0%';
    return percentage.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2})+'%';
}

function getStatusClass(status){
    if(status === 'upcoming') return 'is-upcoming';
    if(status === 'finished') return 'is-finished';
    if(status === 'missing_range') return 'is-missing-range';
    return 'is-in-progress';
}

function getRemainingLabel(goal){
    const remaining = Number(goal.remaining_amount);
    if(Number.isFinite(remaining) && remaining > 0) return 'Faltan '+formatAmount(remaining);
    return 'Objetivo superado';
}

export function getIncomeGoalsProgress(){
    $('.income-goals-segment .income-goals-header').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-income-goal-progress', {}, null, showIncomeGoalsProgress, showIncomeGoalsError);
}

function showIncomeGoalsProgress(response){
    dashboardState.incomeGoals = response.data && Array.isArray(response.data.goals) ? response.data.goals : [];
    const goals = dashboardState.incomeGoals;
    $('.income-goals-count').text(goals.length+' '+(goals.length === 1 ? 'meta activa' : 'metas activas'));
    $('.income-goals-segment .loading-icon').remove();

    if(goals.length === 0){
        $('.income-goals-grid').html('<div class="income-goals-state"><i class="fa-regular fa-bullseye-arrow" aria-hidden="true"></i><span>No hay metas activas</span></div>');
        return;
    }

    let html = '';
    $.each(goals, function(index, goal){
        const completion = Number(goal.completion_percentage);
        const progress = Number(goal.progress_percentage);
        const safeCompletion = Number.isFinite(completion) ? completion : 0;
        const safeProgress = Number.isFinite(progress) ? Math.min(Math.max(progress, 0), 100) : 0;
        const comparisonRange = goal.comparison_start_date && goal.comparison_end_date
            ? formatDate(goal.comparison_start_date)+' - '+formatDate(goal.comparison_end_date)
            : 'Rango no configurado';
        const statusClass = getStatusClass(goal.status);
        const statusLabel = goal.status_label || 'En curso';
        const actualAmount = formatAmount(goal.actual_amount);
        const targetAmount = formatAmount(goal.target_amount);
        const completionLabel = goal.completion_percentage_string || formatPercentage(safeCompletion);

        html += '<article class="income-goal-card '+statusClass+'">';
        html += '<div class="income-goal-card-header">';
        html += '<div><span class="income-goal-card-number">Meta '+(index + 1)+'</span><h2>'+escapeHtml(goal.frequency_label || 'Meta de ingreso')+'</h2></div>';
        html += '<span class="income-goal-status">'+escapeHtml(statusLabel)+'</span>';
        html += '</div>';
        html += '<div class="income-goal-card-values">';
        html += '<div class="income-goal-value"><span>Ingresos reales</span><strong>'+escapeHtml(actualAmount)+'</strong></div>';
        html += '<div class="income-goal-value"><span>Objetivo del periodo</span><strong>'+escapeHtml(targetAmount)+'</strong></div>';
        html += '</div>';
        html += '<div class="income-goal-progress-header"><span>Cumplimiento</span><strong>'+escapeHtml(completionLabel)+'</strong></div>';
        html += '<div class="income-goal-progress" role="progressbar" aria-label="Cumplimiento de la meta '+(index + 1)+'" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'+safeProgress+'"><span style="width: '+safeProgress+'%"></span></div>';
        html += '<div class="income-goal-card-footer"><span><i class="fa-regular fa-calendar-range" aria-hidden="true"></i>'+escapeHtml(comparisonRange)+'</span><span>'+escapeHtml(getRemainingLabel(goal))+'</span></div>';
        html += '</article>';
    });
    $('.income-goals-grid').html(html);
}

function showIncomeGoalsError(){
    dashboardState.incomeGoals = [];
    $('.income-goals-segment .loading-icon').remove();
    $('.income-goals-count').text('Sin datos');
    $('.income-goals-grid').html('<div class="income-goals-state"><i class="fa-regular fa-triangle-exclamation" aria-hidden="true"></i><span>No fue posible cargar las metas</span></div>');
}