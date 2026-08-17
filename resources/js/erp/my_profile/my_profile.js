import { profileState, setCurrentUser, setPermissions } from './state.js';
import { loadUpdateImageBorder, showCurrentUser, updateHeaderProfile } from './profile.js';
import { initializePermissions } from './permissions.js';
import { updateUser } from './update.js';

$(document).on('click', '#nav-tab .nav-link', changeTab);
$(document).on('change', '#nav-update .input-color', loadUpdateImageBorder);
$(document).on('click', '#update-button', updateUser);

$(document).ready(function(){
    changeTab();
    initializePermissions();
});

function changeTab(){
    const tab = $('#nav-tab .active').attr('id');
    if(tab === 'nav-update-tab') showCurrentUser();
    profileState.tabsView[tab] = true;
}

export { profileState, setCurrentUser, setPermissions, showCurrentUser, updateHeaderProfile };