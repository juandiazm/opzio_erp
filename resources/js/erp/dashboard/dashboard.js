$(document).on('change', '#income-outcome-month-input', function(){
    if(timeout != null) clearTimeout(timeout);
    timeout = setTimeout(function(){
        getIncomeOutcomeValuesByMonth();
    }, 500);
});
$(document).on('change', '.income-outcome-by-month-input', function(){
    if(timeout != null) clearTimeout(timeout);
    timeout = setTimeout(function(){
        getIncomesOutcomesByMonthRange();
    }, 500);
});
$(document).on('change', '.sales-by-month-input', function(){
    if(timeout != null) clearTimeout(timeout);
    timeout = setTimeout(function(){
        getIncomesOutcomesByMonthRange();
    }, 500);
});
$(document).on('click', '.due-clients-row', goToLicense);
$(document).on('change', '.sales-by-month-input', function(){
    if(timeout != null) clearTimeout(timeout);
    timeout = setTimeout(function(){
        getSalesByMonthRange();
    }, 500);
});
$(document).on('change', '.incomes-by-client-input', function(){
    if(timeout != null) clearTimeout(timeout);
    timeout = setTimeout(function(){
        getIncomesByClientDateRange();
    }, 500);
});
////////////////////////////////////////////////
var timeout = null;
var collectIncomesList = [];
var quotationIncomesList = [];
var incomeOutcomeGraph = null;
var dueIncomesList = [];
var newClientGraph = null;
var newSalesGraph = null;
var incomesByClientGraph = null;
////////////////////////////////////////////////
$(document).ready(function(){
    getIncomeOutcomeValuesByMonth();
    getIncomesByStatus(2, showCollectIncomes, ['.collect-container', '.approve-incomes-segment']);
    getIncomesByStatus(0, showQuotationIncomes, ['.quotation-segment']);
    getActiveClientsAndLicenses();
    getIncomesOutcomesByMonthRange();
    getClientLicencesDues();
    getNewClientsByDateRange();
    getSalesByMonthRange();
    getIncomesByClientDateRange();
});
function getIncomeOutcomeValuesByMonth(){
    $('.income-outcome-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date: $('#income-outcome-month-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-income-outcome-values-by-month', dataSend, null, showIncomeOutcomeValuesByMonth, null);
}
function showIncomeOutcomeValuesByMonth(response){
    $('.income-outcome-segment .income-number-container .income-number span').text('$ '+response.data.incomes.current_month);
    $('.income-outcome-segment .income-number-container .last-month-comparison-message').html((response.data.incomes.difference_porcentage>=0?'<i class="last-month-comparison-icon fa-solid fa-arrow-up"></i>':'<i class="last-month-comparison-icon fa-solid fa-arrow-down"></i>')+' '+response.data.incomes.difference_porcentage+'% en comparación al promedio de los últimos 12 meses');
    $('.income-outcome-segment .outcome-number-container .outcome-number span').text('$ '+response.data.outcomes.current_month);
    $('.income-outcome-segment .outcome-number-container .last-month-comparison-message').html((response.data.outcomes.difference_porcentage>=0?'<i class="last-month-comparison-icon fa-solid fa-arrow-up"></i>':'<i class="last-month-comparison-icon fa-solid fa-arrow-down"></i>')+' '+response.data.outcomes.difference_porcentage+'% en comparación al promedio de los últimos 12 meses');
    /////////////////////////
    $('.income-outcome-segment .segment-title .loading-icon').remove();
}
function getIncomesByStatus(status, successFunction, loaders = []){
    loaders.forEach(element => {
        $(element+' .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    });
    let dataSend = {
        status: status
    };
    PostMethodFunction('/admin/dashboard/get-incomes-by-status', dataSend, null, successFunction, null);
}
function showCollectIncomes(response){
    collectIncomesList = response.data.incomes;
    //////////////////////////////////
    $('.collect-container .receivable-value').text('$ '+response.data.total_value);
    $('.approve-incomes-segment .approve-incomes-value').text('$ '+response.data.total_value);
    $('.approve-incomes-quantity').text(response.data.total_items);
    ////List of collect incomes
    $('.approve-incomes-table tbody').empty();
    let html = '';
    $.each(collectIncomesList, function(i, income){
        html += '<tr>';
            html += '<td class="approve-incomes-link"><p class="approve-incomes-a copy-action" data-clipboard-text="'+income.payment_link+'"><i class="approve-incomes-link-icon fa-solid fa-link"></i></p></td>';
            html += '<td class="approve-incomes-client"><p class="approve-incomes-value">'+income.client_name+'</p></td>';
            html += '<td class="approve-incomes-amount"><p class="approve-incomes-value">$ '+income.total_string+'</p></td>';
            html += '<td class="approve-incomes-cutoff"><p class="approve-incomes-value">'+income.cutoff_date_string+'</p></td>';
            html += '<td class="approve-incomes-overdue"><p class="approve-incomes-value'+(income.days_overdue > 0 ? ' overdue-text' : '')+'">'+income.days_overdue+'</p></td>';
            html += '<td class="approve-incomes-action"><a href="/admin/incomes?income_uid='+income.unique_id+'" class="approve-incomes-action-link"><i class="approve-incomes-action-icon fa-solid fa-pen-to-square"></i></a></td>';
        html += '</tr>';
    });
    $('.approve-incomes-table tbody').append(html);

    /////////////////////////
    $('.collect-container .segment-title .loading-icon').remove();
    $('.approve-incomes-segment .segment-title .loading-icon').remove();

}
function getActiveClientsAndLicenses(){
    $('.active-clients-container .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date: $('#income-outcome-month-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-active-clients-and-licenses', dataSend, null, showActiveClientsAndLicenses, null);

}
function showActiveClientsAndLicenses(response){
    $('.active-clients-container .active-clients-value').text(response.data.clients);
    $('.active-clients-container .active-clients-value-licenses').text(response.data.licenses+" Licencias activas");
    /////////////////////////
    $('.active-clients-container .segment-title .loading-icon').remove();
}
function showQuotationIncomes(response){
    quotationIncomesList = response.data.incomes;
    ///////////////////////////
    $('.quotation-segment .quotation-value').text('$ '+response.data.total_value);
    $('.quotation-segment .quotation-quantity').text(response.data.total_items);
    ////List of collect incomes
    $('.quotation-table tbody').empty();
    let html = '';
    $.each(quotationIncomesList, function(i, income){
        html += '<tr>';
            html += '<td class="quotation-client"><p class="quotation-value">'+income.client_name+'</p></td>';
            html += '<td class="quotation-amount"><p class="quotation-value">$ '+income.total_string+'</p></td>';
            html += '<td class="quotation-action"><a href="/admin/incomes?income_uid='+income.unique_id+'" class="quotation-action-link"><i class="quotation-action-icon fa-solid fa-pen-to-square"></i></a></td>';
        html += '</tr>';
    });
    $('.quotation-table tbody').append(html);
    ///////////////////////////
    $('.quotation-segment .segment-title .loading-icon').remove();
}
function getIncomesOutcomesByMonthRange(){
    $('.income-outcome-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date_from: $('#income-outcome-graph-month-form-input').val(),
        date_to: $('#income-outcome-graph-month-to-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-incomes-outcomes-by-month-range', dataSend, null, showIncomesOutcomesByMonthRange, null);
}
//Graphics
function showIncomesOutcomesByMonthRange(response){
    $('.income-outcome-graph-segment .income-total').text('$ '+response.data.incomes.incomes_total_string).attr('title', '$'+response.data.incomes.incomes_average_string);
    $('.income-outcome-graph-segment .outcome-total').text('$ '+response.data.outcomes.outcomes_total_string).attr('title', '$'+response.data.outcomes.outcomes_average_string);
    $('.income-outcome-graph-segment .balance-total').text('$ '+response.data.balance.total_string);
    
    // Actualizar promedios en el footer
    $('.income-outcome-graph-segment .average-income-value').text('$ '+response.data.incomes.incomes_average_string);
    $('.income-outcome-graph-segment .average-outcome-value').text('$ '+response.data.outcomes.outcomes_average_string);
    
    if(incomeOutcomeGraph != null) incomeOutcomeGraph.destroy();
    let ctx = document.getElementById('income-outcome-graph');
    
    incomeOutcomeGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: response.data.incomes.month_labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: response.data.incomes.incomes_by_month,
                    backgroundColor: '#0153FF',
                    borderColor: '#0153FF',
                    borderWidth: 1
                },
                {
                    label: 'Egresos',
                    data: response.data.outcomes.outcomes_by_month,
                    backgroundColor: '#E99E9E',
                    borderColor: '#E99E9E',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true
                    }
                }
            }
        }
    });
    /////////////////////////
    $('.income-outcome-graph-segment .segment-title .loading-icon').remove();
}
////////////////////////
function getClientLicencesDues(){
    $('.due-clients-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-client-licences-dues', {}, null, showClientLicencesDues, null);
}
function showClientLicencesDues(response){
    dueIncomesList = response.data;
    ///////////////////////////
    ////List of collect incomes
    $('.due-clients-table tbody').empty();
    let html = '';
    $.each(dueIncomesList, function(i, income){
        html += '<tr class="due-clients-row" license_id="'+income.id+'">';
            html += '<td class="">';
                html += '<img src="/'+income.client.photo_path+'" title="'+income.client.name+'" alt="'+income.client.name+'" class="client-image">';
            html += '</td>';
            html += '<td class="client-name"><p class="client-value">'+income.name+'</p></td>';
            html += '<td class="client-amount"><p class="client-value">$ '+income.value_string+'</p></td>';
            html += '<td class="client-days"><p class="client-value">'+income.remaining_days+'</p></td>';
        html += '</tr>';
    });
    $('.due-clients-table tbody').append(html);
    /////////////////////////
    $('.due-clients-segment .segment-title .loading-icon').remove();
}
function goToLicense(){
    let license_id = $(this).attr('license_id');
    window.location.href = '/admin/licenses?license_id='+license_id;
}
////////////////////////
//Graphics
function getNewClientsByDateRange(){
    $('.new-clients-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date_from: $('#new-clients-graph-month-form-input').val(),
        date_to: $('#new-clients-graph-month-to-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-new-clients-by-date-range', dataSend, null, showNewClientsByDateRange, null);

}
function showNewClientsByDateRange(response){
    if(newClientGraph != null) newClientGraph.destroy();
    let ctx = document.getElementById('new-clients-graph');
    var gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 100);
    gradient.addColorStop(0, '#00057B');
    gradient.addColorStop(1, '#0153FF');
    newClientGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: response.data.month_labels,
            datasets: [{
                label: 'Clientes Nuevos',
                data: response.data.clients_by_month,
                backgroundColor: gradient,
                borderColor: gradient,
                borderWidth: 1,
                borderRadius: 10
            }]	
        },
        options: {
            indexAxis: 'y',
            // Elements options apply to all of the options unless overridden in a dataset
            // In this case, we are setting the border of each horizontal bar to be 2px wide
            elements: {
              bar: {
                borderWidth: 1,
              }
            },
            plugins: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                  display: false
                }
            },
            scales: {
                x: {
                grid: {
                    display: true
                }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    }
                }
            }
          },
    });
}
//Graphics
function getSalesByMonthRange(){
    $('.sales-by-month-graph-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date_from: $('#sales-by-month-graph-month-form-input').val(),
        date_to: $('#sales-by-month-graph-month-to-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-sales-by-month-range', dataSend, null, showSalesByMonthRange, null);
}
function showSalesByMonthRange(response){
    if(newSalesGraph != null) newSalesGraph.destroy();
    let ctx = document.getElementById('sales-by-month-graph');
    newSalesGraph = new Chart(ctx, {
        type: 'line',
        data: {
            labels: response.data.month_labels,
            datasets: [{
                label: 'Ventas',
                data: response.data.incomes_by_month,
                backgroundColor: '#0153FF',
                borderColor: '#0153FF',
                borderWidth: 1
            }]	
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                grid: {
                    display: false
                }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true
                    }
                }
            }
        }
    });
}
//Graphics - Incomes by Client
function getIncomesByClientDateRange(){
    $('.incomes-by-client-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    let dataSend = {
        date_from: $('#incomes-by-client-month-from-input').val(),
        date_to: $('#incomes-by-client-month-to-input').val()
    };
    PostMethodFunction('/admin/dashboard/get-incomes-by-client-date-range', dataSend, null, showIncomesByClientDateRange, null);
}
function showIncomesByClientDateRange(response){
    $('.incomes-by-client-segment .incomes-by-client-total').text('$ '+response.data.incomes_total_string);
    
    if(incomesByClientGraph != null) incomesByClientGraph.destroy();
    let ctx = document.getElementById('incomes-by-client-graph');
    
    // Colores para el gráfico de torta
    const colors = [
        '#0153FF',
        '#4A90E2',
        '#7CB5EC',
        '#00B4D8',
        '#90E0EF',
        '#CAF0F8',
        '#023E8A',
        '#0077B6',
        '#48CAE4',
        '#ADE8F4'
    ];
    
    incomesByClientGraph = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: response.data.client_labels,
            datasets: [{
                label: 'Ingresos',
                data: response.data.incomes_by_client,
                backgroundColor: colors,
                borderColor: '#FFFFFF',
                borderWidth: 2
            }]	
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        padding: 10,
                        font: {
                            size: 11
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                const dataset = data.datasets[0];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                
                                return data.labels.map((label, i) => {
                                    const meta = chart.getDatasetMeta(0);
                                    const style = meta.controller.getStyle(i);
                                    const value = dataset.data[i];
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    
                                    return {
                                        text: `${label}: ${percentage}%`,
                                        fillStyle: style.backgroundColor,
                                        strokeStyle: style.borderColor,
                                        lineWidth: style.borderWidth,
                                        hidden: !chart.getDataVisibility(i),
                                        index: i
                                    };
                                });
                            }
                            return [];
                        },
                        // Aplicar estilos visuales cuando está oculto
                        filter: function(item, chart) {
                            return true; // Mostrar todos los items
                        }
                    },
                    onClick: function(e, legendItem, legend) {
                        const index = legendItem.index;
                        const chart = legend.chart;
                        
                        // Toggle visibility
                        chart.toggleDataVisibility(index);
                        chart.update();
                    },
                    onHover: function(e, legendItem, legend) {
                        e.native.target.style.cursor = 'pointer';
                    },
                    onLeave: function(e, legendItem, legend) {
                        e.native.target.style.cursor = 'default';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '$ ' + context.parsed.toLocaleString('es-CO');
                            
                            // Calcular porcentaje
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                            
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    /////////////////////////
    $('.incomes-by-client-segment .segment-title .loading-icon').remove();
}
