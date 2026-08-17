import { getRandomColors } from '../../app-colors';
import { reportState } from './state.js';

export function setDataOnReportItem(reportItem, fromDate, toDate){
    switch(reportItem){
        case 'date-range-input-users': getUsersReportData(fromDate, toDate); break;
        case 'date-range-input-clients': getClientsReportData(fromDate, toDate); break;
        case 'date-range-input-employees': getEmployeesReportData(fromDate, toDate); break;
        case 'date-range-input-licenses': getLicensesReportData(fromDate, toDate); break;
        case 'date-range-input-incomes': getIncomesReportData(fromDate, toDate); break;
        case 'date-range-input-outcomes': getOutcomesReportData(fromDate, toDate); break;
    }
}

export function refreshCheckedGraphs(startDate, endDate, currentReportId){
    $('.report-item-checkbox:checked').each(function(){
        let container = $(this).closest('.report-item');
        let refreshReportId = container.find('.report-item-date-input').attr('id');
        let dateInput = container.find('.report-item-date-input');
        dateInput.data('daterangepicker').setStartDate(startDate);
        dateInput.data('daterangepicker').setEndDate(endDate);
        if(refreshReportId != currentReportId) setDataOnReportItem(refreshReportId, startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));
    });
}

function refreshZoomModal(){
    if(!$('#zoom-in-super-container').hasClass('d-none') && reportState.zoomGraphId) $('#'+reportState.zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
}

function getUsersReportData(fromDate, toDate){ PostMethodFunction('/admin/reports/users/get-by-date-range', {fromDate: fromDate, toDate: toDate}, null, showUsersReportData, null); }
function showUsersReportData(response){
    reportState.data.users = response;
    if(reportState.graphs.users != null) reportState.graphs.users.destroy();
    let ctx = document.getElementById('users-report-graph');
    reportState.graphs.users = new Chart(ctx, {type: 'bar', data: {labels: Object.values(response.data.report).map(entry => entry.label), datasets: [{label: 'Usuarios', data: Object.values(response.data.report).map(entry => entry.total), borderWidth: 1, borderRadius: 10, backgroundColor: ['#F6AA1C']}]}, options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: false}}}});
    refreshZoomModal();
}

function getClientsReportData(fromDate, toDate){ PostMethodFunction('/admin/reports/clients/get-by-date-range', {fromDate: fromDate, toDate: toDate}, null, showClientsReportData, null); }
function showClientsReportData(response){
    reportState.data.clients = response;
    if(reportState.graphs.clients != null) reportState.graphs.clients.destroy();
    let ctx = document.getElementById('clients-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    reportState.graphs.clients = new Chart(ctx, {type: 'pie', data: {labels: labels, datasets: [{label: 'Clientes', data: Object.values(response.data.report).map(entry => entry.total), backgroundColor: getRandomColors(labels.length)}]}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'top', align: 'start', labels: {boxWidth: 20, padding: 15}}, datalabels: {color: '#fff', anchor: 'center', align: 'end', formatter: (value, context) => { if(value == 0) return ''; const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); const percentage = (value / total * 100).toFixed(0); if(percentage < 5) return ''; return `${percentage}%`; }}}}, plugins: [ChartDataLabels]});
    refreshZoomModal();
}

function getEmployeesReportData(fromDate, toDate){ PostMethodFunction('/admin/reports/employees/get-by-date-range', {fromDate: fromDate, toDate: toDate}, null, showEmployeesReportData, null); }
function showEmployeesReportData(response){
    reportState.data.employees = response;
    if(reportState.graphs.employees != null) reportState.graphs.employees.destroy();
    let ctx = document.getElementById('employees-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    reportState.graphs.employees = new Chart(ctx, {type: 'bar', data: {labels: labels, datasets: [{label: 'Empleados', data: Object.values(response.data.report).map(entry => entry.total), backgroundColor: ['#00057B']}]}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'top', align: 'start', labels: {boxWidth: 20, padding: 15}}}}});
    refreshZoomModal();
}

function getLicensesReportData(fromDate, toDate){ PostMethodFunction('/admin/reports/licenses/get-by-date-range', {fromDate: fromDate, toDate: toDate}, null, showLicensesReportData, null); }
function showLicensesReportData(response){
    reportState.data.licenses = response;
    if(reportState.graphs.licenses != null) reportState.graphs.licenses.destroy();
    let ctx = document.getElementById('licenses-report-graph');
    let labels = Object.values(response.data.labels);
    let colors = getRandomColors(labels.length);
    let data = [];
    let counter = 0;
    $.each(response.data.report, function(index, value){ data.push({label: index+' - '+Object.values(value).reduce((acc, entry) => acc + entry.total, 0), data: Object.values(value).map(entry => entry.total), backgroundColor: [colors[counter]], borderColor: [colors[counter]], borderWidth: 1}); counter++; });
    reportState.graphs.licenses = new Chart(ctx, {type: 'line', data: {labels: labels, datasets: data}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'top', align: 'start', labels: {boxWidth: 20, padding: 15}}}}});
    refreshZoomModal();
}

function getIncomesReportData(fromDate, toDate){
    Promise.all([
        new Promise((resolve) => { PostMethodFunction('/admin/reports/incomes/get-by-state-date-range', {fromDate: fromDate, toDate: toDate, states: ['0', '1', '2', '3', '4']}, null, function(response){ resolve(response); }, function(){ resolve(null); }); }),
        new Promise((resolve) => { PostMethodFunction('/admin/reports/incomes/get-payed-by-date-range', {fromDate: fromDate, toDate: toDate}, null, function(response){ resolve(response); }, function(){ resolve(null); }); })
    ]).then(function(data){ showIncomesReportData(data[0], data[1]); });
}
function showIncomesReportData(allIncomes, payedIncomes){
    reportState.data.allIncomes = allIncomes;
    reportState.data.payedIncomes = payedIncomes;
    if(reportState.graphs.incomes != null) reportState.graphs.incomes.destroy();
    let ctx = document.getElementById('incomes-report-graph');
    let labels = Object.values(allIncomes.data.report).map(entry => entry.label);
    reportState.graphs.incomes = new Chart(ctx, {type: 'bar', data: {labels: labels, datasets: [{label: 'Pagados', data: Object.values(payedIncomes.data.report).map(entry => entry.total), backgroundColor: '#00057B'}, {label: 'Totales', data: Object.values(allIncomes.data.report).map(entry => entry.total), backgroundColor: '#F6AA1C'}]}, options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true}}}});
    refreshZoomModal();
}

function getOutcomesReportData(fromDate, toDate){ PostMethodFunction('/admin/reports/outcomes/get-by-date-range', {fromDate: fromDate, toDate: toDate}, null, showOutcomesReportData, null); }
function showOutcomesReportData(response){
    reportState.data.outcomes = response;
    if(reportState.graphs.outcomes != null) reportState.graphs.outcomes.destroy();
    let ctx = document.getElementById('outcomes-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    reportState.graphs.outcomes = new Chart(ctx, {type: 'line', data: {labels: labels, datasets: [{label: 'Gastos', data: Object.values(response.data.report).map(entry => entry.total), borderWidth: 1, borderRadius: 10, backgroundColor: ['#220245']}]}, options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: false}}}});
    refreshZoomModal();
}