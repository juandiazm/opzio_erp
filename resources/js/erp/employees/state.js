export const employeeState = {
    tabsView: {
        'nav-list-tab': false,
        'nav-create-tab': false,
        'nav-traceability-tab': false,
        'nav-update-tab': false,
    },
    employees: [],
    currentEmployee: null,
    currentTab: null,
    departments: [],
    troughtUser: false,
    dbPagination: {
        page: 1,
        per_page: 10,
        total: 0,
    },
};