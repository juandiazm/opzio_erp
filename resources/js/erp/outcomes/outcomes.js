import { outcomeState } from './state.js';
import * as list from './list.js';
import * as massImport from './import.js';
import * as shared from './shared.js';
import { createOutcome } from './create.js';
import { updateOutcome, showCurrentOutcome } from './update.js';
import { exportOutcomes } from './export.js';
import { getUpdateRecordId, setUpdateRecordId } from '../layouts/record-url.js';

function setOutcomeActionsExpanded(expanded){
    $('#outcome-actions-container')
        .toggleClass('is-expanded', expanded)
        .toggleClass('is-collapsed', !expanded);
    $('#outcome-actions-toggle').attr('aria-expanded', expanded ? 'true' : 'false');
    $('#outcome-actions-menu').attr('aria-hidden', expanded ? 'false' : 'true');
}

function loadOutcomeFromUrl(){
    const recordId = outcomeState.urlRecordId;
    if(recordId == null) return false;
    outcomeState.urlRecordId = null;
    outcomeState.pagination.page = 1;
    $('#search-list-input').val(recordId);
    $('#date-from, #date-to').val('');
    list.getOutcomesPage(showCurrentOutcome, recordId);
    return true;
}

function changeTab(){
    const activeTab = $('#nav-tab .active');
    if(activeTab.length === 0) return;
    outcomeState.currentTab = activeTab.attr('id');
    outcomeState.currentContainer = $(activeTab.attr('data-bs-target'));
    if(outcomeState.currentTab === 'nav-list-tab' && !outcomeState.catalogsReady) return;
    $('#nav-update-tab').toggleClass('d-none', outcomeState.currentTab !== 'nav-update-tab');
    if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        if(!loadOutcomeFromUrl()) list.getOutcomesPage();
        outcomeState.tabsView['nav-create-tab'] = false;
        outcomeState.tabsView['nav-update-tab'] = false;
    }else if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-create-tab'){
        outcomeState.tabsView['nav-list-tab'] = false;
        outcomeState.tabsView['nav-update-tab'] = false;
    }else if(outcomeState.currentTab == 'nav-update-tab'){
        if(outcomeState.currentOutcome){
            setUpdateRecordId(outcomeState.currentOutcome.unique_id || outcomeState.currentOutcome.id);
            showCurrentOutcome();
        }else{
            loadOutcomeFromUrl();
        }
    }
    outcomeState.tabsView[outcomeState.currentTab] = true;
}

$(document).ready(function(){
    const recordId = getUpdateRecordId(['outcome_uid']);
    if(recordId != null){
        outcomeState.urlRecordId = recordId;
        setUpdateRecordId(recordId, ['outcome_uid']);
    }
    const finishCatalogLoading = function(){
        outcomeState.catalogsReady = true;
        list.initializeDateRange();
        list.refreshCatalogControls();
        changeTab();
    };
    shared.getOutcomeFormData(finishCatalogLoading, finishCatalogLoading);
});
$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('mouseenter', '#outcome-actions-container', function(){ setOutcomeActionsExpanded(true); });
$(document).on('focusin', '#outcome-actions-container', function(event){
    if(event.target.id !== 'outcome-actions-toggle' || !this.classList.contains('is-collapsed')){
        setOutcomeActionsExpanded(true);
    }
});
$(document).on('mouseleave', '#outcome-actions-container', function(){
    if(!this.contains(document.activeElement)) setOutcomeActionsExpanded(false);
});
$(document).on('focusout', '#outcome-actions-container', function(){
    const container = this;
    window.setTimeout(function(){
        if(!container.contains(document.activeElement) && !container.matches(':hover')) setOutcomeActionsExpanded(false);
    }, 0);
});
$(document).on('click', '#outcome-actions-toggle', function(){ setOutcomeActionsExpanded(true); });
$(document).on('click', function(event){
    if(!$(event.target).closest('#outcome-actions-container').length) setOutcomeActionsExpanded(false);
});
$(document).on('keydown', '#outcome-actions-container', function(event){
    if(event.key === 'Escape'){
        setOutcomeActionsExpanded(false);
        $('#outcome-actions-toggle').trigger('focus');
    }
});
$(document).on('click', '#toggle-outcome-filters', list.toggleFilters);
$(document).on('click', '#create-outcome-button', createOutcome);
$(document).on('click', '#update-outcome-button', updateOutcome);
$(document).on('click', '#import-btn-container', massImport.openMassImportModal);
$(document).on('click', '#export-outcomes-btn', exportOutcomes);
$(document).on('click', '#import-cancel-btn, #import-cancel-action', massImport.closeMassImportModal);
$(document).on('change', '#import-format', massImport.updateImportFormat);
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