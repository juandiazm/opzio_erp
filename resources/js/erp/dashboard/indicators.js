import { dashboardState } from './state.js';

function formatAverageComparison(percentage){
    return (percentage >= 0 ? '<i class="last-month-comparison-icon fa-solid fa-arrow-up"></i>' : '<i class="last-month-comparison-icon fa-solid fa-arrow-down"></i>')+' '+percentage+'% vs promedio 12 meses';
}

export function getIncomeOutcomeValuesByMonth(){
    $('.income-outcome-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-income-outcome-values-by-month', {date: $('#income-outcome-month-input').val()}, null, showIncomeOutcomeValuesByMonth, null);
}
function showIncomeOutcomeValuesByMonth(response){
    $('.income-outcome-segment .income-number-container .income-number span').text('$ '+response.data.incomes.current_month);
    $('.income-outcome-segment .income-number-container .last-month-comparison-message').html(formatAverageComparison(response.data.incomes.difference_porcentage)).attr('title', response.data.incomes.difference_porcentage+'% en comparación al promedio de los últimos 12 meses');
    $('.income-outcome-segment .outcome-number-container .outcome-number span').text('$ '+response.data.outcomes.current_month);
    $('.income-outcome-segment .outcome-number-container .last-month-comparison-message').html(formatAverageComparison(response.data.outcomes.difference_porcentage)).attr('title', response.data.outcomes.difference_porcentage+'% en comparación al promedio de los últimos 12 meses');
    $('.income-outcome-segment .segment-title .loading-icon').remove();
}

export function getIncomesByStatus(status, successFunction, loaders = []){
    loaders.forEach(element => { $(element+' .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>'); });
    PostMethodFunction('/admin/dashboard/get-incomes-by-status', {status: status}, null, successFunction, null);
}

export function getActiveClientsAndLicenses(){
    $('.active-clients-container .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-active-clients-and-licenses', {date: $('#income-outcome-month-input').val()}, null, showActiveClientsAndLicenses, null);
}
function showActiveClientsAndLicenses(response){
    $('.active-clients-container .active-clients-value').text(response.data.clients);
    $('.active-clients-container .active-clients-value-licenses').text(response.data.licenses+' Licencias activas');
    $('.active-clients-container .segment-title .loading-icon').remove();
}