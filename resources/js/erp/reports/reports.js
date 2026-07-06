import { getRandomColors } from "../../app-colors";
$('.report-item-date-input').on('apply.daterangepicker', function (ev, picker) {
    setDataOnReportItem($(this).attr('id'), picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
    refreshCheckedGraphs(picker.startDate, picker.endDate, $(this).attr('id'));
});
$('#date-range-input-zoom-in').on('apply.daterangepicker', function (ev, picker) {
    setDataOnReportItem(zoomGraphId, picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
});
$('.report-item-canvas').on('click', openZoomInModal);
$('#zoom-in-close-icon').on('click', closeZoomInModal);
$('#export-report-excel-container').on('click', function(){
    let sheets = new Array();
    $('.report-item-checkbox:checked').each(function(){
        sheets.push($(this).closest('.report-item').attr('value'));
    });
    exportReport(sheets);
});
$('#zoom-in-export-report-excel-icon').on('click', function(){
    let sheets = new Array();
    sheets.push($('#'+zoomGraphId).closest('.report-item').attr('value'));
    exportReport(sheets);
});
var usersReportGraph = null;
var clientsReportGraph = null;
var employeesReportGraph = null;
var licensesReportGraph = null;
var incomesReportGraph = null;
var outcomesReportGraph = null;
var zoomReportGraph = null;
var zoomGraphId = null;
var usersData;
var clientsData;
var employeesData;
var licensesData;
var allIncomesData;
var payedIncomesData;
var outcomesData;
$(document).ready(function () {
    let startDate = moment().subtract(3, 'month');
    let endDate = moment();
    $('.report-item-date-input').daterangepicker({
        showDropdowns: true,
        startDate: startDate,
        endDate: endDate,
        maxDate: endDate,
    });
    $('#date-range-input-zoom-in').daterangepicker({
        showDropdowns: true,
        startDate: startDate,
        endDate: endDate,
        maxDate: endDate,
    });
    startDate = startDate.format('YYYY-MM-DD');
    endDate = endDate.format('YYYY-MM-DD');
    setDataOnReportItem('date-range-input-users', startDate, endDate);
    setDataOnReportItem('date-range-input-clients', startDate, endDate);
    setDataOnReportItem('date-range-input-employees', startDate, endDate);
    setDataOnReportItem('date-range-input-licenses', startDate, endDate);
    setDataOnReportItem('date-range-input-incomes', startDate, endDate);
    setDataOnReportItem('date-range-input-outcomes', startDate, endDate);
});

function setDataOnReportItem(reportItem, fromDate, toDate) {
    switch (reportItem) {
        case 'date-range-input-users':
            getUsersReportData(fromDate, toDate);
            break;
        case 'date-range-input-clients':
            getClientsReportData(fromDate, toDate);
            break;
        case 'date-range-input-employees':
            getEmployeesReportData(fromDate, toDate);
            break;
        case 'date-range-input-licenses':
            getLicensesReportData(fromDate, toDate);
            break;
        case 'date-range-input-incomes':
            getIncomesReportData(fromDate, toDate);
            break;
        case 'date-range-input-outcomes':
            getOutcomesReportData(fromDate, toDate);
            break;
    }
}
function refreshCheckedGraphs(startDate, endDate, currentReportId) {
    $('.report-item-checkbox:checked').each(function () {
        let container = $(this).closest('.report-item');
        let refreshReportId = container.find('.report-item-date-input').attr('id');
        let dateInput = container.find('.report-item-date-input');
        dateInput.data('daterangepicker').setStartDate(startDate);
        dateInput.data('daterangepicker').setEndDate(endDate);
        if(refreshReportId != currentReportId){
            setDataOnReportItem(refreshReportId, startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));
        }
    });
}
function getUsersReportData(fromDate, toDate) {
    let DataSend = {
        fromDate: fromDate,
        toDate: toDate
    };
    PostMethodFunction('/admin/reports/users/get-by-date-range', DataSend, null, showUsersReportData, null);
}
function showUsersReportData(response) {
    usersData = response;
    if (usersReportGraph != null) usersReportGraph.destroy();
    let ctx = document.getElementById('users-report-graph');
    usersReportGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.values(response.data.report).map(entry => entry.label),
            datasets: [{
                label: 'Usuarios',
                data: Object.values(response.data.report).map(entry => entry.total),
                borderWidth: 1,
                borderRadius: 10,
                backgroundColor: ['#F6AA1C'],
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function getClientsReportData(fromDate, toDate) {
    let DataSend = {
        fromDate: fromDate,
        toDate: toDate
    };
    PostMethodFunction('/admin/reports/clients/get-by-date-range', DataSend, null, showClientsReportData, null);
}
function showClientsReportData(response) {
    clientsData = response;
    if (clientsReportGraph != null) clientsReportGraph.destroy();
    let ctx = document.getElementById('clients-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    let colors = getRandomColors(labels.length);
    clientsReportGraph = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: 'Clientes',
                data: Object.values(response.data.report).map(entry => entry.total),
                backgroundColor: colors,
            }]
        },
        options: {
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            plugins: {
                legend: {
                    display: true, // Show legend
                    position: 'top', // Align legend at the top
                    align: 'start', // Align items at the start (left)
                    labels: {
                        boxWidth: 20, // Width of the box to display color
                        padding: 15 // Space between legend items
                    }
                },
                datalabels: {
                    color: '#fff', // Label color
                    anchor: 'center', // Position in the middle of the slice
                    align: 'end', // Position in the middle of the slice
                    formatter: (value, context) => {
                        // Return percentage or value
                        if(value == 0) return '';
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = (value / total * 100).toFixed(0);
                        if(percentage < 5) return '';
                        return `${percentage}%`;
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function getEmployeesReportData(fromDate, toDate) {
    let DataSend = {
        fromDate: fromDate,
        toDate: toDate
    };
    PostMethodFunction('/admin/reports/employees/get-by-date-range', DataSend, null, showEmployeesReportData, null);
}
function showEmployeesReportData(response) {
    employeesData = response;
    if (employeesReportGraph != null) employeesReportGraph.destroy();
    let ctx = document.getElementById('employees-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    employeesReportGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Empleados',
                data: Object.values(response.data.report).map(entry => entry.total),
                backgroundColor: ['#00057B'],
            }]
        },
        options: {
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            plugins: {
                legend: {
                    display: true, // Show legend
                    position: 'top', // Align legend at the top
                    align: 'start', // Align items at the start (left)
                    labels: {
                        boxWidth: 20, // Width of the box to display color
                        padding: 15 // Space between legend items
                    }
                }
            }
        }
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function getLicensesReportData(fromDate, toDate) {
    let DataSend = {
        fromDate: fromDate,
        toDate: toDate
    };
    PostMethodFunction('/admin/reports/licenses/get-by-date-range', DataSend, null, showLicensesReportData, null);
}
function showLicensesReportData(response){
    licensesData = response;
    if (licensesReportGraph != null) licensesReportGraph.destroy();
    let ctx = document.getElementById('licenses-report-graph');
    let labels = Object.values(response.data.labels);
    let colors = getRandomColors(labels.length);
    let data = new Array();
    let counter = 0;
    $.each(response.data.report, function(index, value){
        data.push({
            label: index+' - '+Object.values(value).reduce((acc, entry) => acc + entry.total, 0),
            data: Object.values(value).map(entry => entry.total),
            backgroundColor: [colors[counter]],
            borderColor: [colors[counter]],
            borderWidth: 1,
        });
        counter++;
    });
    licensesReportGraph = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: data
        },
        options: {
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            plugins: {
                legend: {
                    display: true, // Show legend
                    position: 'top', // Align legend at the top
                    align: 'start', // Align items at the start (left)
                    labels: {
                        boxWidth: 20, // Width of the box to display color
                        padding: 15 // Space between legend items
                    }
                }
            }
        }
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function getIncomesReportData(fromDate, toDate) {
    Promise.all([
        new Promise((resolve, reject) => {PostMethodFunction('/admin/reports/incomes/get-by-state-date-range', {
            fromDate: fromDate,
            toDate: toDate,
            states: [
                '0'
                ,'1'
                ,'2'
                ,'3'
                ,'4'
            ]
        }, null, function(response){resolve(response)} , function(response){resolve(null)})})
        , new Promise((resolve, reject) => {PostMethodFunction('/admin/reports/incomes/get-payed-by-date-range', {
            fromDate: fromDate,
            toDate: toDate
        }, null, function(response){resolve(response)} , function(response){resolve(null)})})
    ]).then(function(data){
        showIncomesReportData(data[0], data[1]);
    });
}
function showIncomesReportData(allIncomes, payedIncomes) {
    allIncomesData = allIncomes;
    payedIncomesData = payedIncomes;
    if (incomesReportGraph != null) incomesReportGraph.destroy();
    let ctx = document.getElementById('incomes-report-graph');
    let labels = Object.values(allIncomes.data.report).map(entry => entry.label);
    incomesReportGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pagados',
                    data: Object.values(payedIncomes.data.report).map(entry => entry.total),
                    backgroundColor: '#00057B',
                }
                ,{
                    label: 'Totales',
                    data: Object.values(allIncomes.data.report).map(entry => entry.total),
                    backgroundColor: '#F6AA1C',
                }
            ]
        },
        options: {
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            scales: {
                y: {
                    beginAtZero: true // Comenzar el eje Y desde cero
                }
            }
        }
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function getOutcomesReportData(fromDate, toDate) {
    let DataSend = {
        fromDate: fromDate,
        toDate: toDate
    };
    PostMethodFunction('/admin/reports/outcomes/get-by-date-range', DataSend, null, showOutcomesReportData, null);
}
function showOutcomesReportData(response) {
    outcomesData = response;
    if (outcomesReportGraph != null) outcomesReportGraph.destroy();
    let ctx = document.getElementById('outcomes-report-graph');
    let labels = Object.values(response.data.report).map(entry => entry.label);
    outcomesReportGraph = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Gastos',
                data: Object.values(response.data.report).map(entry => entry.total),
                borderWidth: 1,
                borderRadius: 10,
                backgroundColor:['#0153ff']
            }]
        },
        options: {
            responsive: true, // Make the chart responsive
            maintainAspectRatio: false, // Allow custom height based on CSS
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
    //refresh zoom modal data
    if(!$('#zoom-in-super-container').hasClass('d-none')){
        $('#'+zoomGraphId).closest('.report-item').find('.report-item-canvas').click();
    }
}
function openZoomInModal(){
    $('#zoom-in-super-container').removeClass('d-none');
    if(zoomReportGraph != null) zoomReportGraph.destroy();
    zoomGraphId = $(this).closest('.report-item').find('.report-item-date-input').attr('id');
    let graphConfig = null;
    let total = '';
    let partition = '';
    let average = '';
    let tableHtml = '';
    let zoomTitle = '';
    let dateRangeInput = null;
    switch (zoomGraphId) {
        case 'date-range-input-users':
            zoomTitle = 'Reporte usuarios';
            dateRangeInput = $('#date-range-input-users').data('daterangepicker');
            graphConfig = usersReportGraph;
            total = Object.values(usersData.data.report).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(usersData.data.report).length;
            average = Math.round(total / partition);
            total = total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setUserZoomInTable();
            break;
        case 'date-range-input-clients':
            zoomTitle = 'Reporte clientes';
            dateRangeInput = $('#date-range-input-clients').data('daterangepicker');
            graphConfig = clientsReportGraph;
            total = Object.values(clientsData.data.report).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(clientsData.data.report).length;
            average = Math.round(total / partition);
            total = total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setClientZoomInTable();
            break;
        case 'date-range-input-employees':
            zoomTitle = 'Reporte empleados';
            dateRangeInput = $('#date-range-input-employees').data('daterangepicker');
            graphConfig = employeesReportGraph;
            total = Object.values(employeesData.data.report).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(employeesData.data.report).length;
            average = Math.round(total / partition);
            total = total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setEmployeeZoomInTable();
            break;
        case 'date-range-input-licenses':
            zoomTitle = 'Reporte licencias';
            dateRangeInput = $('#date-range-input-licenses').data('daterangepicker');
            graphConfig = licensesReportGraph;
            total = Object.values(licensesData.data.labels).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(licensesData.data.labels).length;
            average = Math.round(total / partition);
            total = total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setLicenseZoomInTable();
            break;
        case 'date-range-input-incomes':
            zoomTitle = 'Reporte ingresos';
            dateRangeInput = $('#date-range-input-incomes').data('daterangepicker');
            graphConfig = incomesReportGraph;
            total = Object.values(payedIncomesData.data.report).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(allIncomesData.data.report).length;
            average = '$'+Math.round(total / partition).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            total = '$'+total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setIncomeZoomInTable();
            break;
        case 'date-range-input-outcomes':
            zoomTitle = 'Reporte egresos';
            dateRangeInput  = $('#date-range-input-outcomes').data('daterangepicker');
            graphConfig = outcomesReportGraph;
            total = Object.values(outcomesData.data.report).reduce((acc, entry) => acc + entry.total, 0);
            partition = Object.values(outcomesData.data.report).length;
            average = Math.round(total / partition);
            total = total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            partition = partition.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            tableHtml = setOutcomeZoomInTable();
            break;
        
    }
    $('#date-range-input-zoom-in').data('daterangepicker').setStartDate(dateRangeInput.startDate);
    $('#date-range-input-zoom-in').data('daterangepicker').setEndDate(dateRangeInput.endDate);
    let newCanvas = document.getElementById('zoom-in-report-graph');
    zoomReportGraph = new Chart(newCanvas, graphConfig.config);
    setZoomInLabelData(
        total
        , partition
        , average
    );
    $('#zoom-in-table').html(tableHtml);
    $('#zoom-in-title').text(zoomTitle);
}
function closeZoomInModal(){
    $('#zoom-in-super-container').addClass('d-none');
}
function setZoomInLabelData(total, partition, average){
    $('#zoom-in-total-value').text(total);
    $('#zoom-in-partition-value').text(partition);
    $('#zoom-in-average-value').text(average);
}
function setIncomeZoomInTable(){
    console.log(allIncomesData.data.incomes[0]);
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="client-name text-start">Cliente</th>';
        tableHtml += '<th scope="col" class="text-end">Valor Cobrado</th>';
        tableHtml += '<th scope="col" class="text-end">Valor Pagado</th>';
        tableHtml += '<th scope="col" class="bill-name text-start">Factura</th>';
        tableHtml += '<th scope="col" class="text-center">Estado</th>';
        tableHtml += '<th scope="col" class="text-center">Fecha de Pago</th>';
        tableHtml += '<th scope="col" class="text-center">Fecha de creación</th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    //body
    tableHtml += '<tbody>';
    $.each(allIncomesData.data.incomes, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            tableHtml += '<td class="client-name text-start" title="'+value.client.complete_name+'">'+value.client.complete_name+'</td>';
            tableHtml += '<td class="text-end">$'+value.total_string+'</td>';
            tableHtml += '<td class="text-end">$'+value.bill_final_value_string+'</td>';
            tableHtml += '<td class="bill-name text-start" title="'+value.bill_name+'">'+(value.bill_name==null?'':value.bill_name)+'</td>';
            tableHtml += '<td class="text-center">'+value.state_text+'</td>';
            tableHtml += '<td class="text-center">'+(value.payment_date==null?'':value.payment_date)+'</td>';
            tableHtml += '<td class="text-center">'+value.created_at_string+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function setUserZoomInTable(){
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="text-start">Identificación</th>';
        tableHtml += '<th scope="col" class="text-start">Nombre Completo</th>';
        tableHtml += '<th scope="col" class="text-start">Usuario</th>';
        tableHtml += '<th scope="col" class="text-start">Correo Electrónico</th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    //body
    tableHtml += '<tbody>';
    $.each(usersData.data.users, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            tableHtml += '<td class="text-start" title="'+value.identification+'">'+value.identification+'</td>';
            tableHtml += '<td class="text-start" title="'+value.complete_name+'">'+value.complete_name+'</td>';
            tableHtml += '<td class="text-start" title="'+value.username+'">'+value.username+'</td>';
            tableHtml += '<td class="text-start" title="'+value.email+'">'+value.email+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function setClientZoomInTable(){
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="text-start">Identificación</th>';
        tableHtml += '<th scope="col" class="text-center identification-type">Tipo de Identificación</th>';
        tableHtml += '<th scope="col" class="text-start name-col">Nombre Completo</th>';
        tableHtml += '<th scope="col" class="text-start name-col">Correo Electrónico</th>';
        tableHtml += '<th scope="col" class="text-start">Teléfono</th>';
        tableHtml += '<th scope="col" class="text-center">Activo</th>';
    tableHtml += '</tr>';
    //body
    tableHtml += '</thead>';
    tableHtml += '<tbody>';
    $.each(clientsData.data.clients, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            tableHtml += '<td class="text-start" title="'+value.identification+'">'+value.identification+'</td>';
            tableHtml += '<td class="text-center identification-type" title="'+value.identification_type_string+'">'+value.identification_type_string+'</td>';
            tableHtml += '<td class="text-start name-col" title="'+value.complete_name+'">'+value.complete_name+'</td>';
            tableHtml += '<td class="text-start name-col" title="'+value.email+'">'+value.email+'</td>';
            tableHtml += '<td class="text-start" title="'+value.phone+'">'+value.phone+'</td>';
            tableHtml += '<td class="text-center">'+(value.active==1?'Sí':'No')+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function setEmployeeZoomInTable(){
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="text-start">Identificación</th>';
        tableHtml += '<th scope="col" class="text-start">Nombre Completo</th>';
        tableHtml += '<th scope="col" class="text-start">Cargo</th>';
        tableHtml += '<th scope="col" class="text-start">Correo personal</th>';
        tableHtml += '<th scope="col" class="text-start">Correo empresarial</th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    //body
    tableHtml += '<tbody>';
    $.each(employeesData.data.employees, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.uid+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.uid+'"></i>'+value.uid.substr(value.uid.length - 5)+'</td>';
            tableHtml += '<td class="text-start" title="'+value.identification+'">'+value.identification+'</td>';
            tableHtml += '<td class="text-start" title="'+value.complete_name+'">'+value.complete_name+'</td>';
            tableHtml += '<td class="text-start" title="'+value.charge+'">'+value.charge+'</td>';
            tableHtml += '<td class="text-start" title="'+value.email+'">'+value.personal_email+'</td>';
            tableHtml += '<td class="text-start" title="'+value.email+'">'+value.work_email+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function setLicenseZoomInTable(){
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="text-start name-col">Nombre</th>';
        tableHtml += '<th scope="col" class="text-start client-name">Cliente</th>';
        tableHtml += '<th scope="col" class="text-start name-col">Servicio</th>';
        tableHtml += '<th scope="col" class="text-start">Tipo</th>';
        tableHtml += '<th scope="col" class="text-end">Valor</th>';
        tableHtml += '<th scope="col" class="text-center">Estado</th>';
        tableHtml += '<th scope="col" class="text-center">Último Pago</th>';
        tableHtml += '<th scope="col" class="text-center">Días Restantes</th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    //body
    tableHtml += '<tbody>';
    $.each(licensesData.data.licenses, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            tableHtml += '<td class="text-start name-col" title="'+value.name+'">'+value.name+'</td>';
            tableHtml += '<td class="text-start client-name" title="'+value.client+'">'+value.client.complete_name+'</td>';
            tableHtml += '<td class="text-start name-col" title="'+value.service.name+'">'+value.service.name+'</td>';
            tableHtml += '<td class="text-start" title="'+value.type_string+'">'+value.type_string+'</td>';
            tableHtml += '<td class="text-end" title="'+value.value_string+'">$'+value.value_string+'</td>';
            tableHtml += '<td class="text-center">'+value.active_string+'</td>';
            tableHtml += '<td class="text-center" title="'+value.last_payed_date+'">'+value.last_payed_date+'</td>';
            tableHtml += '<td class="text-center">'+value.remaining_days+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function setOutcomeZoomInTable(){
    //headers
    let tableHtml = '<thead>';
    tableHtml += '<tr>';
        tableHtml += '<th scope="col" class="text-start">ID</th>';
        tableHtml += '<th scope="col" class="text-start">Concepto</th>';
        tableHtml += '<th scope="col" class="text-end">Valor</th>';
        tableHtml += '<th scope="col" class="text-center">Fecha</th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    //body
    tableHtml += '<tbody>';
    $.each(outcomesData.data.outcomes, function(index, value){
        tableHtml += '<tr>';
            tableHtml += '<td class="text-start" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            tableHtml += '<td class="text-start" title="'+value.name+'">'+value.name+'</td>';
            tableHtml += '<td class="text-end" title="'+value.amount+'">$'+value.amount+'</td>';
            tableHtml += '<td class="text-center" title="'+value.date_string+'">'+value.date_string+'</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    return tableHtml;
}
function exportReport(sheets = null){
    let button = $(this);
    if(button.prop('disabled')) return;
    if(sheets == null || sheets.length == 0){
        swallMessage(
            'Error'
            , 'No hay datos para exportar<br>Por favor seleccione al menos un reporte'
            , 'error'
            , 'Ok'
            , null
            , 3000
            , null
            , null
        );
        return;
    }
    let DataSend = {
        sheets: sheets
    };
    button.prop('disabled', true);
    PostMethodFunction('/admin/reports/export', DataSend, null, function(response){
        button.prop('disabled', false);
        location.href = '/admin/reports/download/'+response.data;
    }, function(){
        button.prop('disabled', false);
    });
}