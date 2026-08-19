export const outcomeState = {
    pagination: {
        page: 1,
        size: 5,
        total: 0,
    },
    tabsView: {
        'nav-list-tab': false,
        'nav-create-tab': false,
        'nav-update-tab': false,
    },
    currentTab: null,
    currentContainer: null,
    currentOutcome: null,
    catalogsReady: false,
    totalAmount: 0,
    outcomeTypes: [],
    catalogs: {
        types: [],
        providers: [],
        employees: [],
        departments: [],
        users: [],
        clients: [],
        current_user_id: null,
    },
    outcomes: [],
};