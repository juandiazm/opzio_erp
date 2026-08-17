import { outcomeState } from './state.js';
import * as list from './list.js';
import * as massImport from './import.js';

function changeTab(){
    if($('#nav-tab .active').length === 0) return;
    outcomeState.currentTab = $('#nav-tab .active').attr('id');
    outcomeState.currentContainer = $($('#nav-tab .active').attr('data-bs-target'));
    outcomeState.currentLicencesList = [];
    if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        list.getOutcomesPage();
        outcomeState.tabsView['nav-create-tab'] = false;
    }else if(outcomeState.tabsView[outcomeState.currentTab] == false && outcomeState.currentTab == 'nav-create-tab'){
        outcomeState.tabsView['nav-list-tab'] = false;
    }
    outcomeState.tabsView[outcomeState.currentTab] = true;
}

$(document).ready(function(){ changeTab(); });
$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '#nav-create #import-btn-container', massImport.openMassImportModal);
$(document).on('click', '#nav-create #import-cancel-btn', massImport.closeMassImportModal);
$(document).on('click', '#nav-create #import-confirm-btn', massImport.confirmMassImport);
$(document).on('change', '#db-pagination-per-page', list.changePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.changePage);
$(document).on('click', '#db-page-item-back', list.selectBackPage);
$(document).on('click', '#db-page-item-next', list.selectNextPage);
$(document).on('click', '.delete-outcome', list.deleteOutcome);
$(document).on('click', '.recover-outcome', list.recoverOutcome);
$(document).on('change', '#search-list-input', function(){ outcomeState.pagination.page = 1; list.getOutcomesPage(); });
$(document).on('change', '#date-from, #date-to', function(){ outcomeState.pagination.page = 1; list.getOutcomesPage(); });