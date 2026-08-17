export const userState = {
    tabsView: {
        'nav-list-tab': false,
        'nav-create-tab': false,
        'nav-traceability-tab': false,
        'nav-update-tab': false,
    },
    currentTab: null,
    allUsers: [],
    users: [],
    currentUser: null,
    userId: null,
    troughtUser: false,
    dbPagination: {
        page: 1,
        per_page: 10,
        total: 0,
    },
};