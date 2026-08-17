export const profileState = {
    currentUser: window.current_user || {},
    permissions: window.permissions || [],
    tabsView: {
        'nav-update-tab': false,
    },
};

export function setCurrentUser(user){
    profileState.currentUser = user;
    window.current_user = user;
}

export function setPermissions(permissions){
    profileState.permissions = permissions;
    window.permissions = permissions;
}