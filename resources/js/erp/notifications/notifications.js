import { notificationState } from './state.js';
import * as email from './email.js';
import * as sms from './sms.js';
import * as whatsapp from './whatsapp.js';
import { closeComposeModal, loadClients, loadNotificationTags, renderClientList } from './shared.js';

function changeTab(event) {
    const activeTab = event && event.target ? $(event.target) : $('#nav-tab .active');
    if (activeTab.length === 0) return;
    notificationState.activeChannel = activeTab.attr('id').includes('sms') ? 'sms' : (activeTab.attr('id').includes('whatsapp') ? 'whatsapp' : 'email');
    $('#erp-app-content').toggleClass('notifications-whatsapp-active', notificationState.activeChannel === 'whatsapp');
    if (notificationState.activeChannel === 'sms') sms.loadSms();
    else if (notificationState.activeChannel === 'whatsapp') {
        whatsapp.enableRealtime();
        whatsapp.loadConversations();
        whatsapp.loadTemplates();
    } else {
        whatsapp.disableRealtime();
        email.loadEmails();
    }
}

function activateChannelTab(channel) {
    const tab = document.getElementById('notifications-'+channel+'-tab');
    if (!tab) return;
    if (window.bootstrap?.Tab) {
        window.bootstrap.Tab.getOrCreateInstance(tab).show();
        return;
    }
    $(tab).trigger('click');
}

function clearContactMessageQuery() {
    const url = new URL(window.location.href);
    url.searchParams.delete('contact_id');
    url.searchParams.delete('contact_channel');
    window.history.replaceState({}, document.title, url.pathname+(url.search ? url.search : '')+url.hash);
}

function openContactMessageFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const contactId = params.get('contact_id');
    const channel = params.get('contact_channel');
    if (!contactId || !['email', 'sms', 'whatsapp'].includes(channel)) return;

    PostMethodFunction('/admin/contacts/message-context', {id: contactId}, null, function(response) {
        const contact = response.contact || {};
        const channels = (contact.channels || []).map(function(value) { return String(value); });
        if (!channels.includes(channel)) {
            window.alertWarning?.('El contacto no tiene habilitado el canal seleccionado.');
            clearContactMessageQuery();
            return;
        }
        clearContactMessageQuery();
        activateChannelTab(channel);
        if (channel === 'whatsapp') {
            whatsapp.openContactConversation(contact);
            return;
        }
        if (channel === 'email') {
            email.openNewEmailModal();
            $('#notifications-email-manual').val(contact.email || contact.value || '');
            return;
        }
        sms.openNewSmsModal();
        $('#notifications-sms-manual').val(contact.phone || contact.value || '');
        sms.updateSmsCounter();
    }, function() {
        window.alertWarning?.('No fue posible cargar el contacto para enviar el mensaje.');
        clearContactMessageQuery();
    });
}

$(document).on('shown.bs.tab', '#notifications-sms-tab, #notifications-email-tab, #notifications-whatsapp-tab', changeTab);
$(document).on('click', '#notifications-new-email', email.openNewEmailModal);
$(document).on('click', '#notifications-new-sms', sms.openNewSmsModal);
$(document).on('click', '#notifications-close-modal, #notifications-cancel-modal', closeComposeModal);
$(document).on('click', '#notifications-compose-modal', function(event) { if (event.target === this) closeComposeModal(); });
$(document).on('click', '#notifications-save-modal', function() {
    notificationState.activeChannel === 'email' ? email.saveEmail() : sms.saveSms();
});
$(document).on('click', '.notifications-view-email', email.viewEmail);
$(document).on('click', '.notifications-resend-email', email.resendEmail);
$(document).on('click', '.notifications-change-email-status', email.changeEmailStatus);
$(document).on('click', '.notifications-resend-sms', sms.resendSms);
$(document).on('click', '.notifications-validate-sms', sms.validateDelivery);
$(document).on('click', '#notifications-close-email-view', email.closeEmailView);
$(document).on('click', '#notifications-edit-email-view', email.editEmailFromView);
$(document).on('click', '#notifications-email-refresh', email.loadEmails);
$(document).on('click', '#notifications-sms-refresh', sms.loadSms);
$(document).on('click', '#notifications-whatsapp-refresh', whatsapp.loadConversations);
$(document).on('click', '#notifications-whatsapp-chat-refresh', whatsapp.refreshConversation);
$(document).on('click', '#notifications-whatsapp-new', whatsapp.toggleNewConversation);
$(document).on('click', '#notifications-whatsapp-start-close', whatsapp.closeNewConversation);
$(document).on('change', '#notifications-whatsapp-client', whatsapp.selectClient);
$(document).on('submit', '#notifications-whatsapp-start-form', whatsapp.startConversation);
$(document).on('click', '#notifications-whatsapp-conversation-list .notifications-whatsapp-conversation', whatsapp.openConversation);
$(document).on('click', '#notifications-whatsapp-back', whatsapp.closeConversation);
$(document).on('submit', '#notifications-whatsapp-message-form', whatsapp.sendMessage);
$(document).on('click', '#notifications-whatsapp-templates', whatsapp.openTemplates);
$(document).on('click', '#notifications-whatsapp-templates-close', whatsapp.closeTemplates);
$(document).on('submit', '#notifications-whatsapp-template-form', whatsapp.saveTemplate);
$(document).on('click', '#notifications-whatsapp-template-reset', whatsapp.resetTemplateForm);
$(document).on('click', '.notifications-whatsapp-template-edit', whatsapp.editTemplate);
$(document).on('click', '.notifications-whatsapp-template-delete', whatsapp.deleteTemplate);
$(document).on('click', '.notifications-whatsapp-template-submit', whatsapp.submitTemplate);
$(document).on('change', '#notifications-whatsapp-template', whatsapp.selectTemplate);
$(document).on('input', '#notifications-whatsapp-template-variables-form input[data-template-variable]', whatsapp.updateTemplateVariables);
$(document).on('change', '#notifications-email-status, #notifications-sms-status, #notifications-email-date-from, #notifications-email-date-to, #notifications-sms-date-from, #notifications-sms-date-to', function() {
    const channel = $(this).attr('id').includes('email') ? 'email' : 'sms';
    notificationState[channel+'Pagination'].page = 1;
    channel === 'email' ? email.loadEmails() : sms.loadSms();
});
$(document).on('change', '#notifications-email-search, #notifications-sms-search', function() {
    const channel = $(this).attr('id').includes('email') ? 'email' : 'sms';
    notificationState[channel+'Pagination'].page = 1;
    channel === 'email' ? email.loadEmails() : sms.loadSms();
});
$(document).on('input change', '#notifications-whatsapp-search, #notifications-whatsapp-unread-only', function() {
    notificationState.whatsappPagination.page = 1;
    whatsapp.loadConversations();
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
    else if (!$('#notifications-whatsapp-templates-modal').hasClass('d-none')) whatsapp.closeTemplates();
});
$(document).on('click', '#notifications-whatsapp-templates-modal', function(event) {
    if (event.target === this) whatsapp.closeTemplates();
});

$(document).ready(function() {
    email.initializeEditor();
    whatsapp.initializeWhatsapp();
    loadClients();
    loadNotificationTags();
    const activeTab = $('#nav-tab .active');
    $('#erp-app-content').toggleClass('notifications-whatsapp-active', activeTab.attr('id') === 'notifications-whatsapp-tab');
    if (activeTab.attr('id') === 'notifications-sms-tab') sms.loadSms();
    else if (activeTab.attr('id') === 'notifications-whatsapp-tab') {
        whatsapp.enableRealtime();
        whatsapp.loadConversations();
        whatsapp.loadTemplates();
    } else {
        whatsapp.disableRealtime();
        email.loadEmails();
    }
    openContactMessageFromQuery();
});
