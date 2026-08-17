import { dashboardState } from './state.js';

export function showCollectIncomes(response){
    dashboardState.collectIncomesList = response.data.incomes;
    $('.collect-container .receivable-value').text('$ '+response.data.total_value);
    $('.approve-incomes-segment .approve-incomes-value').text('$ '+response.data.total_value);
    $('.approve-incomes-quantity').text(response.data.total_items);
    $('.approve-incomes-table tbody').empty();
    let html = '';
    $.each(dashboardState.collectIncomesList, function(i, income){
        html += '<tr><td class="approve-incomes-link"><p class="approve-incomes-a copy-action" data-clipboard-text="'+income.payment_link+'"><i class="approve-incomes-link-icon fa-solid fa-link"></i></p></td><td class="approve-incomes-client"><p class="approve-incomes-value">'+income.client_name+'</p></td><td class="approve-incomes-amount"><p class="approve-incomes-value">$ '+income.total_string+'</p></td><td class="approve-incomes-cutoff"><p class="approve-incomes-value">'+income.cutoff_date_string+'</p></td><td class="approve-incomes-overdue"><p class="approve-incomes-value'+(income.days_overdue > 0 ? ' overdue-text' : '')+'">'+income.days_overdue+'</p></td><td class="approve-incomes-action"><a href="/admin/incomes?income_uid='+income.unique_id+'" class="approve-incomes-action-link"><i class="approve-incomes-action-icon fa-solid fa-pen-to-square"></i></a></td></tr>';
    });
    $('.approve-incomes-table tbody').append(html);
    $('.collect-container .segment-title .loading-icon').remove();
    $('.approve-incomes-segment .segment-title .loading-icon').remove();
}

export function showQuotationIncomes(response){
    dashboardState.quotationIncomesList = response.data.incomes;
    $('.quotation-segment .quotation-value').text('$ '+response.data.total_value);
    $('.quotation-segment .quotation-quantity').text(response.data.total_items);
    $('.quotation-table tbody').empty();
    let html = '';
    $.each(dashboardState.quotationIncomesList, function(i, income){ html += '<tr><td class="quotation-client"><p class="quotation-value">'+income.client_name+'</p></td><td class="quotation-amount"><p class="quotation-value">$ '+income.total_string+'</p></td><td class="quotation-action"><a href="/admin/incomes?income_uid='+income.unique_id+'" class="quotation-action-link"><i class="quotation-action-icon fa-solid fa-pen-to-square"></i></a></td></tr>'; });
    $('.quotation-table tbody').append(html);
    $('.quotation-segment .segment-title .loading-icon').remove();
}

export function getClientLicencesDues(){
    $('.due-clients-segment .segment-title').append('<i class="loading-icon fa-duotone fa-spinner-third fa-spin"></i>');
    PostMethodFunction('/admin/dashboard/get-client-licences-dues', {}, null, showClientLicencesDues, null);
}
function showClientLicencesDues(response){
    dashboardState.dueIncomesList = response.data;
    $('.due-clients-table tbody').empty();
    let html = '';
    $.each(dashboardState.dueIncomesList, function(i, income){ html += '<tr class="due-clients-row" license_id="'+income.id+'"><td><img src="/'+income.client.photo_path+'" title="'+income.client.name+'" alt="'+income.client.name+'" class="client-image"></td><td class="client-name"><p class="client-value">'+income.name+'</p></td><td class="client-amount"><p class="client-value">$ '+income.value_string+'</p></td><td class="client-days"><p class="client-value">'+income.remaining_days+'</p></td></tr>'; });
    $('.due-clients-table tbody').append(html);
    $('.due-clients-segment .segment-title .loading-icon').remove();
}

export function goToLicense(){ window.location.href = '/admin/licenses?license_id='+$(this).attr('license_id'); }