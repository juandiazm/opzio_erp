////VAR TABS
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false,
    'nav-traceability-tab': false,
    'nav-update-tab': false,
};
var current_tab = null;
var allUsers = [];
var users = [];
var current_user = null;
import { userState } from './state.js';
import { getAllUserPermissions } from './permissions.js';
import * as list from './list.js';
import { getUserNextId, loadCreateImageBorder, createUser } from './create.js';
import * as update from './update.js';

function showCurrentUser(){
    update.showCurrentUser();
}

function changeTab(){
    userState.currentTab = $('#nav-tab .active').attr('id');
    if(userState.currentTab!='nav-update-tab') $('#nav-update-tab').addClass('d-none');
    if(userState.tabsView[userState.currentTab]==false && userState.currentTab == 'nav-list-tab'){
        $('#search-list-input').focus();
        list.getUsersPage();
    }else if(userState.tabsView[userState.currentTab]==false && userState.currentTab == 'nav-create-tab'){
        getUserNextId();
    }else if(userState.currentTab == 'nav-traceability-tab'){
        if(userState.troughtUser && userState.currentUser != null){
            userState.troughtUser = false;
            $('#nav-traceability').attr('user-id',userState.currentUser.id);
        }
    }else if(userState.currentTab == 'nav-update-tab'){
        $('#nav-update-tab').removeClass('d-none');
    }
    userState.tabsView[userState.currentTab] = true;
}

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('change', '#db-pagination-per-page', list.DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', list.DBchangePage);
$(document).on('click', '#db-page-item-back', list.DBselectBackPage);
$(document).on('click', '#db-page-item-next', list.DBselectNextPage);
$(document).on('change', '#search-list-input', list.getUsersPage);
$(document).on('click', '.list-update-btn', function(){list.goToUpdateTab(this, showCurrentUser);});
$(document).on('click', '.list-update-traceability', function(){
    list.goToTraceabilityTab('id%'+$(this).parent().parent().attr('user-id'));
});
$(document).on('click', '#nav-create .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('change', '#nav-create .input-color',loadCreateImageBorder);
$(document).on('click', '#add-button', createUser);
$(document).on('click', '#nav-update .image-plus-icon',function(){
    $(this).parent().find('.input-color').click();
});
$(document).on('change', '#nav-update .input-color',update.loadUpdateImageBorder);
$(document).on('click', '#update-button', update.updateUser);
$(document).on('click', '#update-user-delete',function(){update.deleteUser(userState.currentUser.id);});
$(document).on('click', '#update-user-restore',function(){update.restoreUser(userState.currentUser.id);});
$(document).on('click', '#update-user-go-traceability',function(){
    list.goToTraceabilityTab('id%'+userState.currentUser.id);
});

$(document).ready(function(){
    getAllUserPermissions(showCurrentUser);
    changeTab();
});