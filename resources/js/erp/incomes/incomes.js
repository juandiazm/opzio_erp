import { incomeState } from './state.js';
import * as list from './list.js';
import * as create from './create.js';
import * as update from './update.js';
import * as order from './order.js';
import * as incomeImport from './import.js';
import * as advances from './advances.js';
import { goToIncomesTraceability } from './shared.js';

function changeTab(){
    incomeState.currentTab = $('#nav-tab .active').attr('id');
    incomeState.currentContainer = $($('#nav-tab .active').attr('data-bs-target'));
    incomeState.currentLicencesList = [];
    if(incomeState.currentTab != 'nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(incomeState.tabsView[incomeState.currentTab] == false && incomeState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        list.getIncomesPage();
    }else if(incomeState.tabsView[incomeState.currentTab] == false && incomeState.currentTab == 'nav-create-tab'){
        create.getAllClients();
    }else if(incomeState.currentTab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
        create.getAllClients(update.showCurrentIncome);
    }
    incomeState.tabsView[incomeState.currentTab] = true;
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('change', '#db-pagination-per-page', list.changePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.changePage);
$(document).on('click', '#db-page-item-back', list.selectBackPage);
$(document).on('click', '#db-page-item-next', list.selectNextPage);
$(document).on('click', '.list-update-btn', function(){ list.goToUpdateTab(this); });
$(document).on('change', '#search-list-input', function(){ incomeState.pagination.page = 1; list.getIncomesPage(); });
$(document).on('change', '#state-list-input', function(){ incomeState.pagination.page = 1; list.getIncomesPage(); });
$(document).on('click', '.list-delete-btn', function(){ list.deleteIncome($(this).parent().parent().attr('income-id')); });
$(document).on('click', '.list-view-order', function(){ if(list.setCurrentIncomeFromRow(this)) order.showIncomeOrder(); });
$(document).on('click', '.list-update-traceability', function(){ if(list.setCurrentIncomeFromRow(this)) goToIncomesTraceability('id%'+incomeState.currentIncome.id); });

$(document).on('change', '.input-client', create.loadClientData);
$(document).on('click', '.state-input', create.changeCreateOrderState);
$(document).on('change', '.input-item-license', create.loadLicenseData);
$(document).on('change', '.input-item-comission', create.getComissionValue);
$(document).on('click', '.add-license-button', create.addLicenseItem);
$(document).on('click', '.delete-license-button', create.deleteLicenseItem);
$(document).on('click', '.update-license-button', create.updateLicenseItem);
$(document).on('change', '.input-timely-payment', create.changeTimelyPayment);
$(document).on('click', '#create-income-button', create.createIncome);
$(document).on('click', '#update-income-button', update.updateIncome);
$(document).on('click', '#view-income-document', order.showIncomeOrder);
$(document).on('click', '#print-income-button', order.printPdf);
$(document).on('click', '#pay-state-btn', update.changePayState);
$(document).on('click', '.update-state', update.changeInputState);

$(document).on('click', '#close-order-viewer', order.closeOrderViewer);
$(document).on('click', '#pdf-prev-page', order.pdfPrevPage);
$(document).on('click', '#pdf-next-page', order.pdfNextPage);
$(document).on('click', '#pdf-zoom-in', order.pdfZoomIn);
$(document).on('click', '#pdf-zoom-out', order.pdfZoomOut);
$(document).on('click', '#pdf-print', order.printPdf);
$(document).on('click', '#pdf-download', order.downloadPdf);
$(document).on('click', '#pdf-share', order.shareIncomePdf);
$(document).on('click', '#pdf-fullscreen', order.fullscreenPdf);
$(document).on('click', '#send-order-button', order.sendOrder);
$(document).on('click', '#cancel-send-order-button', order.cancelSendOrder);
$(document).on('click', '#confirm-send-order-button', order.confirmSendOrder);
$(document).on('click', '.receiver-item-delete', order.deleteReceiver);
$(document).on('click', '.receiver-item-create', order.createReceiver);

$(document).on('click', '#import-report-excel-container', incomeImport.getImportAssistantsExcel);
$(document).on('change', '#import-report-excel-input', incomeImport.importAssistantsExcel);
$(document).on('click', '#import-report-excel-input', function(event){ event.stopPropagation(); });

$(document).on('click', '.list-manage-advances', advances.openAdvancesModal);
$(document).on('click', '#close-advances-modal', advances.closeAdvancesModal);
$(document).on('click', '#create-advance-button', advances.showCreateAdvanceForm);
$(document).on('click', '#cancel-advance-button', advances.hideAdvanceForm);
$(document).on('click', '#save-advance-button', advances.saveAdvance);
$(document).on('click', '.advance-item-edit', advances.editAdvance);
$(document).on('click', '.advance-item-delete', advances.deleteAdvance);

window.createSiigoInvoice = list.createSiigoInvoice;

$(document).ready(function(){
    let urlParams = new URLSearchParams(window.location.search);
    incomeState.incomeId = urlParams.get('income_uid');
    if(incomeState.incomeId != null && incomeState.incomeId != '' && incomeState.incomeId != 0){
        const url = new URL(window.location.href);
        url.searchParams.delete('income_uid');
        window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
    }
    order.init();
    changeTab();
});