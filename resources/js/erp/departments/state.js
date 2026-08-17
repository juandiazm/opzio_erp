export const departmentState = {
    tabsView: {
        'nav-list-tab': false,
        'nav-create-tab': false,
        'nav-traceability-tab': false,
        'nav-update-tab': false,
    },
    departments: [],
    currentDepartment: null,
    currentTab: null,
    notAssignedEmployees: [],
    pagination: {
        page: 1,
        per_page: 10,
        total: 0,
    },
};