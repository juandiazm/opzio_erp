import { notificationState } from './state.js';
import * as email from './email.js';
import * as sms from './sms.js';
import { closeComposeModal, loadClients, renderClientList } from './shared.js';

function changeTab() {
    const activeTab = $('#notifications-tabs .active');
    if (activeTab.length === 0) return;
    notificationState.activeChannel = activeTab.attr('id').includes('sms') ? 'sms' : 'email';
    if (notificationState.activeChannel === 'sms') sms.loadSms();
    else email.loadEmails();
}

$(document).on('shown.bs.tab', '#notifications-sms-tab, #notifications-email-tab', changeTab);
$(document).on('click', '#notifications-new-email', email.openNewEmailModal);
$(document).on('click', '#notifications-new-sms', sms.openNewSmsModal);
$(document).on('click', '#notifications-close-modal, #notifications-cancel-modal', closeComposeModal);
$(document).on('click', '#notifications-compose-modal', function(event) { if (event.target === this) closeComposeModal(); });
$(document).on('click', '#notifications-save-modal', function() {
    notificationState.activeChannel === 'email' ? email.saveEmail() : sms.saveSms();
});
$(document).on('click', '.notifications-view-email', email.viewEmail);
$(document).on('click', '.notifications-resend-email', email.resendEmail);
$(document).on('click', '.notifications-resend-sms', sms.resendSms);
$(document).on('click', '#notifications-close-email-view', email.closeEmailView);
$(document).on('click', '#notifications-edit-email-view', email.editEmailFromView);
$(document).on('click', '#notifications-email-refresh', email.loadEmails);
$(document).on('click', '#notifications-sms-refresh', sms.loadSms);
$(document).on('change', '#notifications-email-status, #notifications-sms-status', function() {
    const channel = $(this).attr('id').includes('email') ? 'email' : 'sms';
    notificationState[channel+'Pagination'].page = 1;
    channel === 'email' ? email.loadEmails() : sms.loadSms();
});
$(document).on('change', '#notifications-email-search, #notifications-sms-search', function() {
    const channel = $(this).attr('id').includes('email') ? 'email' : 'sms';
    notificationState[channel+'Pagination'].page = 1;
    channel === 'email' ? email.loadEmails() : sms.loadSms();
});
$(document).on('change', '#notifications-email-client-list input[data-client-id], #notifications-sms-client-list input[data-client-id], #notifications-email-all-clients, #notifications-sms-all-clients', function() {
    const elementId = String($(this).attr('id') || '');
    const channel = elementId.includes('email') || $(this).closest('#notifications-email-client-list').length ? 'email' : 'sms';
    if ($(this).is('[id$="all-clients"]')) renderClientList(channel);
    if (channel === 'sms') sms.updateSmsCounter();
});
$(document).on('input', '#notifications-email-client-search, #notifications-sms-client-search', function() {
    renderClientList($(this).attr('id').includes('email') ? 'email' : 'sms');
});
$(document).on('input', '#notifications-sms-body, #notifications-sms-manual', sms.updateSmsCounter);
$(document).on('keydown', function(event) {
    if (event.key !== 'Escape') return;
    if (!$('#notifications-compose-modal').hasClass('d-none')) closeComposeModal();
    else if (!$('#notifications-email-view-modal').hasClass('d-none')) email.closeEmailView();
});

$(document).ready(function() {
    email.initializeEditor();
    loadClients();
    email.loadEmails();
});
