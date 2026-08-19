import { outcomeState } from './state.js';
import * as list from './list.js';
import * as massImport from './import.js';
import * as shared from './shared.js';
import { createOutcome } from './create.js';
import { updateOutcome, showCurrentOutcome } from './update.js';

function changeTab(){
    const activeTab = $('#nav-tab .active');
    if(activeTab.length === 0) return;
    outcomeState.currentTab = activeTab.attr('id');
    outcomeState.currentContainer = $(activeTab.attr('data-bs-target'));
    if(outcomeState.currentTab === 'nav-list-tab' && !outcomeState.catalogsReady) return;
    $('#nav-update-tab').toggleClass('d-none', outcomeState.currentTab !== 'nav-update-tab');
    if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        list.getOutcomesPage();
        outcomeState.tabsView['nav-create-tab'] = false;
        outcomeState.tabsView['nav-update-tab'] = false;
    }else if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-create-tab'){
        outcomeState.tabsView['nav-list-tab'] = false;
        outcomeState.tabsView['nav-update-tab'] = false;
    }else if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-update-tab' && outcomeState.currentOutcome){
        showCurrentOutcome();
    }
    outcomeState.tabsView[outcomeState.currentTab] = true;
}

$(document).ready(function(){
    const finishCatalogLoading = function(){
        outcomeState.catalogsReady = true;
        list.initializeDateRange();
        list.refreshCatalogControls();
        changeTab();
    };
    shared.getOutcomeFormData(finishCatalogLoading, finishCatalogLoading);
});
$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '#toggle-outcome-filters', list.toggleFilters);
$(document).on('click', '#create-outcome-button', createOutcome);
$(document).on('click', '#update-outcome-button', updateOutcome);
$(document).on('click', '#import-btn-container', massImport.openMassImportModal);
$(document).on('click', '#import-cancel-btn, #import-cancel-action', massImport.closeMassImportModal);
$(document).on('click', '#import-confirm-btn', massImport.confirmMassImport);
$(document).on('click', '#import-form-container', function(event){
    if(event.target === this) massImport.closeMassImportModal();
});
$(document).on('change', '#db-pagination-per-page', list.changePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.changePage);
$(document).on('click', '#db-page-item-back', list.selectBackPage);
$(document).on('click', '#db-page-item-next', list.selectNextPage);
$(document).on('click', '.delete-outcome', list.deleteOutcome);
$(document).on('click', '.recover-outcome', list.recoverOutcome);
$(document).on('click', '.edit-outcome', list.goToUpdateTab);
$(document).on('change', '.outcome-association-inline', list.changeAssociation);
$(document).on('change', '#search-list-input', function(){ outcomeState.pagination.page = 1; list.getOutcomesPage(); });
$(document).on('change', '#date-from, #date-to', function(){ outcomeState.pagination.page = 1; list.getOutcomesPage(); });
$(document).on('change', '.outcome-filter', function(){ outcomeState.pagination.page = 1; list.getOutcomesPage(); });