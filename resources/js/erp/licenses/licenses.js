import { licenseState } from './state.js';
import { getClients, getEmployees, verificationInputChange } from './shared.js';
import * as list from './list.js';
import { addLicense } from './create.js';
import * as update from './update.js';
import { updateLicenseDetails, licenseTypeChange } from './details.js';
import { addLicenseDocument, updateLicenseDocument, deleteLicenseDocument } from './documents.js';
import { addnotification, changeNotificationPosition, updateNotification, deleteNotification, restoreNotification } from './notifications.js';

function showCurrentLicense(){
    update.showCurrentLicense();
}

function openCurrentLicense(){
    showCurrentLicense();
}

function changeTab(){
    licenseState.currentTab = $('#nav-tab .active').attr('id');
    if(licenseState.currentTab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(licenseState.tabsView[licenseState.currentTab]==false && licenseState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        if(licenseState.urlLicenseId == null){
            list.getLicensesPage();
        }else{
            list.getLicenseById(licenseState.urlLicenseId, openCurrentLicense);
        }
    }else if(licenseState.tabsView[licenseState.currentTab]==false && licenseState.currentTab == 'nav-create-tab'){
    }else if(licenseState.tabsView[licenseState.currentTab]==false && licenseState.currentTab == 'nav-traceability-tab'){
    }else if(licenseState.currentTab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    licenseState.tabsView[licenseState.currentTab] = true;
}

function goToLicensesTraceability(search){
    $('#nav-traceability').attr('search',search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('click', '.verification-input-icon', verificationInputChange);
$(document).on('click', '#add-license-button', function(){addLicense(showCurrentLicense);});
$(document).on('change', '#db-pagination-per-page', list.DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.DBchangePage);
$(document).on('click', '#db-page-item-back', list.DBselectBackPage);
$(document).on('click', '#db-page-item-next', list.DBselectNextPage);
$(document).on('click', '.list-update-btn', function(){list.goToUpdateTab(this, openCurrentLicense);});
$(document).on('click', '.list-delete-btn', function(){update.deleteLicense($(this).parent().parent().attr('license-id'), list.getLicensesPage);});
$(document).on('change', '#search-list-input', function(){
    licenseState.dbPagination.page = 1;
    list.getLicensesPage();
});
$(document).on('change', '#state-list-input', function(){
    licenseState.dbPagination.page = 1;
    list.getLicensesPage();
});
$(document).on('click', '.list-update-traceability', function(){
    let currentLicense = list.setCurrentLicenseFromRow(this);
    if(currentLicense != null){
        goToLicensesTraceability('id%'+currentLicense.id);
    }
});
$(document).on('click', '#update-license-button', update.updateLicense);
$(document).on('click', '#update-license-delete', function(){update.deleteLicense(licenseState.currentLicense.id, list.getLicensesPage);});
$(document).on('click', '#update-license-restore', update.restoreLicense);
$(document).on('click', '#update-license-details-button', function(){updateLicenseDetails(showCurrentLicense);});
$(document).on('change', '#update-license-type', licenseTypeChange);
$(document).on('click', '#update-license-go-traceability', function(){
    goToLicensesTraceability('id%'+licenseState.currentLicense.id);
});
$(document).on('click','#add-license-documens-button', addLicenseDocument);
$(document).on('click', '.update-license-file-btn', updateLicenseDocument);
$(document).on('click', '.delete-license-file-btn', deleteLicenseDocument);
$(document).on('click', '#add-notification', addnotification);
$(document).on('click', '.notification-position-up-buttons', function(){changeNotificationPosition($(this),'up');});
$(document).on('click', '.notification-position-down-buttons', function(){changeNotificationPosition($(this),'down');});
$(document).on('click', '.update-notification-btn', updateNotification);
$(document).on('click', '.delete-notification-btn', deleteNotification);
$(document).on('click', '.restore-notification-btn', restoreNotification);

$(document).ready(function(){
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    licenseState.urlLicenseId = urlParams.get('license_id');
    if(licenseState.urlLicenseId != null){
        window.history.replaceState({}, document.title, "/" + "admin/licenses");
    }
    $.when(
        getClients(),
        getEmployees(),
    ).done(function(){
        changeTab();
    });
});