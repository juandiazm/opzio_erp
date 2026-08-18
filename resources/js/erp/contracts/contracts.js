import { contractState } from './state.js';
import { getContractsPage, openContract, changePage, changePageSize, selectBackPage, selectNextPage } from './list.js';
import { addContract, deleteContract, generateContract, initializeRecurrenceForms, restoreContract, sendContract, showCurrentContract, updateContract } from './form.js';
import { addContractSource, applyLicenseToSources, loadCatalogs, refreshContractSourceRow, removeContractSource, renderContractSources, renderContractVariables, setContractableOptions, setTemplateOptions } from './shared.js';
import * as types from './types.js';
import * as templates from './templates.js';

function changeTab() {
    contractState.currentTab = $('#nav-tab .active').attr('id');
    if(contractState.currentTab !== 'nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(contractState.tabsView[contractState.currentTab] === false){
        if(contractState.currentTab === 'nav-list-tab') getContractsPage();
        if(contractState.currentTab === 'nav-types-tab') types.getTypes();
        if(contractState.currentTab === 'nav-templates-tab') templates.getTemplates();
    }
    if(contractState.currentTab === 'nav-update-tab') showCurrentContract();
    contractState.tabsView[contractState.currentTab] = true;
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '#add-contract-button', addContract);
$(document).on('click', '#update-contract-button', updateContract);
$(document).on('click', '#update-contract-generate-button', generateContract);
$(document).on('click', '#update-contract-send-button', function(){ sendContract(); });
$(document).on('click', '#update-contract-delete', function(){ deleteContract(); });
$(document).on('click', '#update-contract-restore', restoreContract);
$(document).on('click', '.contract-open-btn', function(){ openContract($(this).attr('data-contract-id'), showCurrentContract); });
$(document).on('click', '.contract-generate-btn', function(){ openContract($(this).attr('data-contract-id'), function(){ generateContract(); }); });
$(document).on('click', '.contract-send-btn', function(){ sendContract($(this).attr('data-contract-id')); });
$(document).on('click', '.contract-delete-btn', function(){ deleteContract($(this).attr('data-contract-id'), getContractsPage); });
$(document).on('click', '.contract-restore-btn', function(){ openContract($(this).attr('data-contract-id'), function(){ restoreContract(); }); });
$(document).on('change', '#contract-search, #contract-type-filter, #contract-status-filter', function(){ contractState.dbPagination.page = 1; getContractsPage(); });
$(document).on('change', '#contract-pagination-per-page', changePageSize);
$(document).on('click', '.contract-page-number', changePage);
$(document).on('click', '#contract-page-back', selectBackPage);
$(document).on('click', '#contract-page-next', selectNextPage);
$(document).on('change', '.contract-signature-status-select', function(){
    const select = $(this);
    const previousStatus = select.attr('data-previous-status');
    PostMethodFunction('/admin/contracts/change-signature-status', {id: select.attr('data-contract-id'), signature_status: select.val()}, null, function(){
        select.attr('data-previous-status', select.val());
        alertSuccess('Estado del PDF firmado actualizado');
        getContractsPage();
    }, function(){
        select.val(previousStatus);
    });
});

$(document).on('change', '#create-contract-type', function(){ setTemplateOptions('#create-contract-type', '#create-contract-template'); renderContractVariables('create'); renderContractSources('create'); });
$(document).on('change', '#update-contract-type', function(){ setTemplateOptions('#update-contract-type', '#update-contract-template'); renderContractVariables('update'); renderContractSources('update'); });
$(document).on('change', '#create-contract-template', function(){ renderContractVariables('create'); renderContractSources('create'); });
$(document).on('change', '#update-contract-template', function(){ renderContractVariables('update'); renderContractSources('update'); });
$(document).on('click', '[data-contract-add-source]', function(){
    addContractSource($(this).attr('data-contract-add-source'));
});
$(document).on('click', '.contracts-remove-source', function(){
    removeContractSource($(this).closest('[data-contract-source-row]'));
});
$(document).on('change', '[data-contract-source-role="type"]', function(){
    refreshContractSourceRow($(this).closest('[data-contract-source-row]'));
    applyLicenseToSources($(this).attr('data-contract-source-prefix'));
});
$(document).on('change', '[data-contract-source-role="id"]', function(){
    applyLicenseToSources($(this).attr('data-contract-source-prefix') || $(this).closest('[data-contract-source-row]').find('[data-contract-source-role="type"]').attr('data-contract-source-prefix'));
});
$(document).on('click', '#contract-type-save', types.saveType);
$(document).on('click', '#contract-type-cancel', types.cancelType);
$(document).on('click', '.contract-type-update', types.editType);
$(document).on('click', '.contract-type-delete', types.deleteType);
$(document).on('click', '.contract-type-restore', types.restoreType);
$(document).on('click', '#contract-template-save', templates.saveTemplate);
$(document).on('click', '#contract-template-cancel', templates.cancelTemplate);
$(document).on('click', '.contract-template-edit', templates.editTemplate);
$(document).on('click', '.contract-template-delete', templates.deleteTemplate);
$(document).on('click', '.contract-template-restore', templates.restoreTemplate);

$(document).ready(function(){
    templates.initializeTemplateEditor();
    types.initializeTypeForm();
    initializeRecurrenceForms();
    const urlParams = new URLSearchParams(window.location.search);
    contractState.urlContractId = urlParams.get('contract_id');
    if(contractState.urlContractId != null){
        const url = new URL(window.location.href);
        url.searchParams.delete('contract_id');
        window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
    }
    loadCatalogs(function(){
        templates.refreshVariablePalette();
        if(contractState.urlContractId != null){
            openContract(contractState.urlContractId, showCurrentContract);
        }else{
            changeTab();
        }
    });
});