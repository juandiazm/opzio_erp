import { providerState } from './state.js';
import { verificationInputChange } from './shared.js';
import * as list from './list.js';
import { addProvider } from './create.js';
import * as update from './update.js';
import { addProviderDocument, updateProviderDocument, deleteProviderDocument } from './documents.js';
import { addContact, updateContact, deleteContact, restoreContact, forceDeleteContact } from './contacts.js';

function showCurrentProvider(){ update.showCurrentProvider(); }
function goToProvidersTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}
function changeTab(){
    providerState.currentTab = $('#nav-tab .active').attr('id');
    if(providerState.currentTab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(providerState.tabsView[providerState.currentTab]==false && providerState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus(); list.getProvidersPage();
    }else if(providerState.currentTab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    providerState.tabsView[providerState.currentTab] = true;
}
$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-provider-button', function(){addProvider(this,showCurrentProvider);});
$(document).on('change', '#pagination-per-page', list.changePageSize);
$(document).on('click', '#pagination .page-item-number', list.changePage);
$(document).on('click', '#page-item-back', list.selectBackPage);
$(document).on('click', '#page-item-next', list.selectNextPage);
$(document).on('click', '.list-update-btn', function(){list.goToUpdateTab(this,showCurrentProvider);});
$(document).on('change', '#search-list-input', list.getProvidersPage);
$(document).on('click', '.list-delete-btn', function(){
    let provider = list.setCurrentProviderFromRow(this);
    if(provider != null) update.deleteProvider();
});
$(document).on('click', '.list-update-traceability', function(){
    let provider = list.setCurrentProviderFromRow(this);
    if(provider != null) goToProvidersTraceability('id%'+provider.id);
});
$(document).on('click', '#update-provider-button', update.updateProvider);
$(document).on('click', '#update-provider-delete', update.deleteProvider);
$(document).on('click', '#update-provider-restore', update.restoreProvider);
$(document).on('click', '#update-provider-go-traceability', function(){goToProvidersTraceability('id%'+providerState.currentProvider.id);});
$(document).on('click','#add-provider-documens-button', addProviderDocument);
$(document).on('click', '.update-provider-file-btn', updateProviderDocument);
$(document).on('click', '.delete-provider-file-btn', deleteProviderDocument);
$(document).on('click', '#add-contact', addContact);
$(document).on('click', '.update-contact-btn', updateContact);
$(document).on('click', '.delete-contact-btn', deleteContact);
$(document).on('click', '.restore-contact-btn', restoreContact);
$(document).on('click', '.force-delete-contact-btn', forceDeleteContact);
$(document).ready(changeTab);