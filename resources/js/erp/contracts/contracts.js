import { contractState } from './state.js';
import { getContractsPage, openContract, changePage, changePageSize, selectBackPage, selectNextPage } from './list.js';
import { addContract, deleteContract, generateContract, restoreContract, sendContract, showCurrentContract, updateContract } from './form.js';
import { loadCatalogs, setContractableOptions, setTemplateOptions } from './shared.js';
import * as types from './types.js';
import * as templates from './templates.js';
import * as schedules from './schedules.js';

function changeTab() {
    contractState.currentTab = $('#nav-tab .active').attr('id');
    if(contractState.currentTab !== 'nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(contractState.tabsView[contractState.currentTab] === false){
        if(contractState.currentTab === 'nav-list-tab') getContractsPage();
        if(contractState.currentTab === 'nav-types-tab') types.getTypes();
        if(contractState.currentTab === 'nav-templates-tab') templates.getTemplates();
        if(contractState.currentTab === 'nav-schedules-tab') schedules.getSchedules();
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

$(document).on('change', '#create-contract-type', function(){ setTemplateOptions('#create-contract-type', '#create-contract-template'); });
$(document).on('change', '#update-contract-type', function(){ setTemplateOptions('#update-contract-type', '#update-contract-template'); });
$(document).on('change', '#contract-schedule-type', function(){ setTemplateOptions('#contract-schedule-type', '#contract-schedule-template'); });
$(document).on('change', '#create-contractable-type', function(){ setContractableOptions('#create-contractable-type', '#create-contractable-id'); });
$(document).on('change', '#update-contractable-type', function(){ setContractableOptions('#update-contractable-type', '#update-contractable-id'); });
$(document).on('change', '#contract-schedule-target-type', function(){ setContractableOptions('#contract-schedule-target-type', '#contract-schedule-target-id', null, true); });

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
$(document).on('click', '#contract-schedule-save', schedules.saveSchedule);
$(document).on('click', '#contract-schedule-cancel', schedules.cancelSchedule);
$(document).on('click', '.contract-schedule-edit', schedules.editSchedule);
$(document).on('click', '.contract-schedule-delete', schedules.deleteSchedule);
$(document).on('click', '.contract-schedule-restore', schedules.restoreSchedule);

$(document).ready(function(){
    const urlParams = new URLSearchParams(window.location.search);
    contractState.urlContractId = urlParams.get('contract_id');
    if(contractState.urlContractId != null){
        window.history.replaceState({}, document.title, '/admin/contracts');
    }
    loadCatalogs(function(){
        if(contractState.urlContractId != null){
            openContract(contractState.urlContractId, showCurrentContract);
        }else{
            changeTab();
        }
    });
});