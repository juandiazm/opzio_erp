export const notificationState = {
    clients: [],
    clientsLoaded: false,
    clientsLoading: false,
    emailPagination: {page: 1, size: 10, totalPages: 0},
    smsPagination: {page: 1, size: 10, totalPages: 0},
    activeChannel: 'email',
    resendEmailId: null,
    resendSmsId: null,
    emailDetail: null,
    emailEditor: null,
    savedEditorRange: null,
};