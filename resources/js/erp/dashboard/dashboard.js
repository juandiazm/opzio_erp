import { dashboardState } from './state.js';
import * as indicators from './indicators.js';
import * as tables from './tables.js';
import * as charts from './charts.js';

function schedule(callback){
    if(dashboardState.timeout != null) clearTimeout(dashboardState.timeout);
    dashboardState.timeout = setTimeout(callback, 500);
}

$(document).on('change', '#income-outcome-month-input', function(){ schedule(indicators.getIncomeOutcomeValuesByMonth); });
$(document).on('change', '.income-outcome-by-month-input', function(){ schedule(charts.getIncomesOutcomesByMonthRange); });
$(document).on('change', '.sales-by-month-input', function(){ schedule(charts.getIncomesOutcomesByMonthRange); });
$(document).on('click', '.due-clients-row', tables.goToLicense);
$(document).on('change', '.sales-by-month-input', function(){ schedule(charts.getSalesByMonthRange); });
$(document).on('change', '.incomes-by-client-input', function(){ schedule(charts.getIncomesByClientDateRange); });

$(document).ready(function(){
    indicators.getIncomeOutcomeValuesByMonth();
    indicators.getIncomesByStatus(2, tables.showCollectIncomes, ['.collect-container', '.approve-incomes-segment']);
    indicators.getIncomesByStatus(0, tables.showQuotationIncomes, ['.quotation-segment']);
    indicators.getActiveClientsAndLicenses();
    charts.getIncomesOutcomesByMonthRange();
    tables.getClientLicencesDues();
    charts.getNewClientsByDateRange();
    charts.getSalesByMonthRange();
    charts.getIncomesByClientDateRange();
});