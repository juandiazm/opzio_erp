import { dashboardState } from './state.js';

export function getIncomesOutcomesByMonthRange(){
    $('.income-outcome-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {date_from: $('#income-outcome-graph-month-form-input').val(), date_to: $('#income-outcome-graph-month-to-input').val()};
    PostMethodFunction('/admin/dashboard/get-incomes-outcomes-by-month-range', dataSend, null, showIncomesOutcomesByMonthRange, null);
}
function showIncomesOutcomesByMonthRange(response){
    $('.income-outcome-graph-segment .income-total').text('$ '+response.data.incomes.incomes_total_string).attr('title', '$'+response.data.incomes.incomes_average_string);
    $('.income-outcome-graph-segment .outcome-total').text('$ '+response.data.outcomes.outcomes_total_string).attr('title', '$'+response.data.outcomes.outcomes_average_string);
    $('.income-outcome-graph-segment .balance-total').text('$ '+response.data.balance.total_string);
    $('.income-outcome-graph-segment .average-income-value').text('$ '+response.data.incomes.incomes_average_string);
    $('.income-outcome-graph-segment .average-outcome-value').text('$ '+response.data.outcomes.outcomes_average_string);
    if(dashboardState.incomeOutcomeGraph != null) dashboardState.incomeOutcomeGraph.destroy();
    dashboardState.incomeOutcomeGraph = new Chart(document.getElementById('income-outcome-graph'), {type: 'bar', data: {labels: response.data.incomes.month_labels, datasets: [{label: 'Ingresos', data: response.data.incomes.incomes_by_month, backgroundColor: '#220245', borderColor: '#220245', borderWidth: 1}, {label: 'Egresos', data: response.data.outcomes.outcomes_by_month, backgroundColor: '#E99E9E', borderColor: '#E99E9E', borderWidth: 1}]}, options: {responsive: true, maintainAspectRatio: false, scales: {x: {grid: {display: false}}, y: {beginAtZero: true, grid: {display: true}}}}});
    $('.income-outcome-graph-segment .segment-title .loading-icon').remove();
}

export function getNewClientsByDateRange(){
    $('.new-clients-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-new-clients-by-date-range', {date_from: $('#new-clients-graph-month-form-input').val(), date_to: $('#new-clients-graph-month-to-input').val()}, null, showNewClientsByDateRange, null);
}
function showNewClientsByDateRange(response){
    if(dashboardState.newClientGraph != null) dashboardState.newClientGraph.destroy();
    let ctx = document.getElementById('new-clients-graph');
    if(!ctx) return;
    let gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 100);
    gradient.addColorStop(0, '#00057B');
    gradient.addColorStop(1, '#220245');
    dashboardState.newClientGraph = new Chart(ctx, {type: 'bar', data: {labels: response.data.month_labels, datasets: [{label: 'Clientes Nuevos', data: response.data.clients_by_month, backgroundColor: gradient, borderColor: gradient, borderWidth: 1, borderRadius: 10}]}, options: {indexAxis: 'y', elements: {bar: {borderWidth: 1}}, plugins: {responsive: true, maintainAspectRatio: false, legend: {display: false}}, scales: {x: {grid: {display: true}}, y: {beginAtZero: true, grid: {display: false}}}}});
}

export function getSalesByMonthRange(){
    $('.sales-by-month-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-sales-by-month-range', {date_from: $('#sales-by-month-graph-month-form-input').val(), date_to: $('#sales-by-month-graph-month-to-input').val()}, null, showSalesByMonthRange, null);
}
function showSalesByMonthRange(response){
    if(dashboardState.newSalesGraph != null) dashboardState.newSalesGraph.destroy();
    let ctx = document.getElementById('sales-by-month-graph');
    if(!ctx) return;
    dashboardState.newSalesGraph = new Chart(ctx, {type: 'line', data: {labels: response.data.month_labels, datasets: [{label: 'Ventas', data: response.data.incomes_by_month, backgroundColor: '#220245', borderColor: '#220245', borderWidth: 1}]}, options: {responsive: true, maintainAspectRatio: false, scales: {x: {grid: {display: false}}, y: {beginAtZero: true, grid: {display: true}}}}});
}

export function getIncomesByClientDateRange(){
    $('.incomes-by-client-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-incomes-by-client-date-range', {date_from: $('#incomes-by-client-month-from-input').val(), date_to: $('#incomes-by-client-month-to-input').val()}, null, showIncomesByClientDateRange, null);
}
function showIncomesByClientDateRange(response){
    $('.incomes-by-client-segment .incomes-by-client-total').text('$ '+response.data.incomes_total_string);
    if(dashboardState.incomesByClientGraph != null) dashboardState.incomesByClientGraph.destroy();
    let colors = ['#220245', '#4A90E2', '#7CB5EC', '#00B4D8', '#90E0EF', '#CAF0F8', '#023E8A', '#0077B6', '#48CAE4', '#ADE8F4'];
    dashboardState.incomesByClientGraph = new Chart(document.getElementById('incomes-by-client-graph'), {type: 'pie', data: {labels: response.data.client_labels, datasets: [{label: 'Ingresos', data: response.data.incomes_by_client, backgroundColor: colors, borderColor: '#FFFFFF', borderWidth: 2}]}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'right', labels: {boxWidth: 15, padding: 10, font: {size: 11}, generateLabels: function(chart){ const data = chart.data; if(data.labels.length && data.datasets.length){ const dataset = data.datasets[0]; const total = dataset.data.reduce((a, b) => a + b, 0); return data.labels.map((label, i) => { const meta = chart.getDatasetMeta(0); const style = meta.controller.getStyle(i); const value = dataset.data[i]; const percentage = ((value / total) * 100).toFixed(1); return {text: `${label}: ${percentage}%`, fillStyle: style.backgroundColor, strokeStyle: style.borderColor, lineWidth: style.borderWidth, hidden: !chart.getDataVisibility(i), index: i}; }); } return []; }, filter: function(){ return true; }}, onClick: function(e, legendItem, legend){ legend.chart.toggleDataVisibility(legendItem.index); legend.chart.update(); }, onHover: function(e){ e.native.target.style.cursor = 'pointer'; }, onLeave: function(e){ e.native.target.style.cursor = 'default'; }}, tooltip: {callbacks: {label: function(context){ let label = context.label || ''; if(label) label += ': '; label += '$ '+context.parsed.toLocaleString('es-CO'); const total = context.dataset.data.reduce((a, b) => a + b, 0); label += ' ('+((context.parsed / total) * 100).toFixed(1)+'%)'; return label; }}}}}});
    $('.incomes-by-client-segment .segment-title .loading-icon').remove();
}