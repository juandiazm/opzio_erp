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

function formatCount(value, singular, plural){
    const count = Number(value) || 0;
    return count+' '+(count === 1 ? singular : plural);
}

function showRecurrenceState(icon, message){
    $('.incomes-by-recurrence-grid').html('<div class="incomes-by-recurrence-state"><i class="'+icon+'" aria-hidden="true"></i><span>'+escapeHtml(message)+'</span></div>');
}

export function getIncomesByRecurrenceRange(){
    const dateFrom = $('#incomes-by-recurrence-month-from-input').val();
    const dateTo = $('#incomes-by-recurrence-month-to-input').val();
    if(!dateFrom || !dateTo) return;
    if(dateFrom > dateTo){
        alertWarning('El inicio del rango no puede ser posterior al fin');
        return;
    }

    $('.incomes-by-recurrence-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-incomes-by-recurrence-range', {date_from: dateFrom, date_to: dateTo}, null, showIncomesByRecurrenceRange, showIncomesByRecurrenceError);
}

function showIncomesByRecurrenceRange(response){
    const data = response.data || {};
    dashboardState.incomesByRecurrence = Array.isArray(data.recurrence_details) ? data.recurrence_details : [];
    $('.incomes-by-recurrence-projected-total').text('$ '+(data.projected_total_string || '0'));
    $('.incomes-by-recurrence-paid-total').text('$ '+(data.paid_total_string || '0'));
    $('.incomes-by-recurrence-segment .loading-icon').remove();

    if(dashboardState.incomesByRecurrence.length === 0){
        showRecurrenceState('fa-regular fa-repeat', 'No hay licencias recurrentes para mostrar');
        return;
    }

    let html = '';
    $.each(dashboardState.incomesByRecurrence, function(index, group){
        const percentage = Number(group.paid_percentage);
        const progress = Number.isFinite(percentage) ? Math.min(Math.max(percentage, 0), 100) : 0;
        const activeLicenses = Number(group.active_license_count) || 0;
        const paidIncomeCount = Number(group.paid_income_count) || 0;
        const paidPercentage = group.paid_percentage_string || '0%';
        const cycleLabel = formatCount(group.cycle_count, 'ciclo', 'ciclos');
        const licenseLabel = activeLicenses > 0
            ? formatCount(activeLicenses, 'licencia activa', 'licencias activas')
            : 'Sin licencias activas';

        html += '<article class="recurrence-group-card">';
        html += '<div class="recurrence-group-header"><div><span class="recurrence-group-index">Grupo '+(index + 1)+'</span><h2>'+escapeHtml(group.label || 'Cada '+group.recurrence_months+' meses')+'</h2></div><span class="recurrence-cycle-count">'+escapeHtml(cycleLabel)+'</span></div>';
        html += '<div class="recurrence-group-values">';
        html += '<div class="recurrence-group-value"><span>Proyección</span><strong>'+escapeHtml(formatAmount(group.projected_amount))+'</strong><small>'+escapeHtml(formatAmount(group.projected_cycle_amount))+' por ciclo</small></div>';
        html += '<div class="recurrence-group-value"><span>Pagado</span><strong>'+escapeHtml(formatAmount(group.paid_amount))+'</strong><small>'+escapeHtml(formatCount(paidIncomeCount, 'ingreso pagado', 'ingresos pagados'))+'</small></div>';
        html += '</div>';
        html += '<div class="recurrence-progress-header"><span>Cobrado vs. proyección</span><strong>'+escapeHtml(paidPercentage)+'</strong></div>';
        html += '<div class="recurrence-progress" role="progressbar" aria-label="Cumplimiento del grupo '+(index + 1)+'" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'+progress+'"><span style="width: '+progress+'%"></span></div>';
        html += '<div class="recurrence-group-footer"><span>'+escapeHtml(licenseLabel)+'</span><span>'+escapeHtml(formatCount(Number(group.paid_license_count) || 0, 'licencia cobrada', 'licencias cobradas'))+'</span></div>';
        html += '</article>';
    });
    $('.incomes-by-recurrence-grid').html(html);
}

function showIncomesByRecurrenceError(){
    dashboardState.incomesByRecurrence = [];
    $('.incomes-by-recurrence-segment .loading-icon').remove();
    $('.incomes-by-recurrence-projected-total, .incomes-by-recurrence-paid-total').text('$0');
    showRecurrenceState('fa-regular fa-triangle-exclamation', 'No fue posible cargar las recurrencias');
}