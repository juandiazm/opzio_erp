import { clientState } from './state.js';
import { verificationInputChange } from './shared.js';
import * as list from './list.js';
import { addClient } from './create.js';
import * as update from './update.js';
import { addClientDocument, updateClientDocument, deleteClientDocument } from './documents.js';
import { goToLicense } from './licenses.js';
import { addClientUser, restoreClientUserPassword, deleteClientUser, restoreClientUser, goToUserTraceability } from './users.js';
import { initSync } from './sync.js';

function showCurrentClient(){
    update.showCurrentClient();
}

function changeTab(){
    let tab = $('#nav-tab .active').attr('id');
    if(tab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(clientState.tabsView[tab]==false && tab == 'nav-list-tab'){
        list.getClientsPage();
    }else if(clientState.tabsView[tab]==false && tab == 'nav-create-tab'){
    }else if(clientState.tabsView[tab]==false && tab == 'nav-traceability-tab'){
    }else if(tab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    clientState.tabsView[tab] = true;
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-client-button', function(){addClient(this, showCurrentClient);});
$(document).on('change', '#db-pagination-per-page', list.DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.DBchangePage);
$(document).on('click', '#db-page-item-back', list.DBselectBackPage);
$(document).on('click', '#db-page-item-next', list.DBselectNextPage);
$(document).on('click', '.list-update-btn', function(){list.goToUpdateTab(this, showCurrentClient);});
$(document).on('change', '#search-list-input', function(){
    clientState.dbPagination.page = 1;
    list.getClientsPage();
});
$(document).on('click', '#add-client-user-button', addClientUser);
$(document).on('click', '.restore-client-user-password-btn', restoreClientUserPassword);
$(document).on('click', '.delete-client-user-btn', deleteClientUser);
$(document).on('click', '.restore-client-user-btn', restoreClientUser);
$(document).on('click', '.list-client-user-traceability', goToUserTraceability);
$(document).on('click', '#update-client-button', update.updateClient);
$(document).on('click','#add-client-documens-button', addClientDocument);
$(document).on('click', '.update-client-file-btn', updateClientDocument);
$(document).on('click', '.delete-client-file-btn', deleteClientDocument);
$(document).on('click', '.go-to-license-btn', goToLicense);

$(document).ready(function(){
    initSync(list.getClientsPage);
    changeTab();
});