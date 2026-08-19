import {
    addProjectNotification,
    deleteProjectNotification,
    loadPage,
    loadProjectConfig,
    loadProjectRecipients,
    saveProjectConfig,
    updateProjectNotification,
} from './data.js';
import { escapeHtml } from './formatters.js';

const getSelectedKeys = (config) => Array.from(config.elements.recipientList.querySelectorAll('.servers-recipient-checkbox:checked'))
    .map((input) => input.value);

const setSelectOptions = (select, options) => {
    if (window.SearchableDropdown) {
        window.SearchableDropdown.setOptions(select, options);
        return;
    }
    select.innerHTML = options.map((option) => {
        const selected = option.selected ? ' selected' : '';
        const disabled = option.disabled ? ' disabled' : '';
        return `<option value="${escapeHtml(option.value)}"${selected}${disabled}>${escapeHtml(option.label)}</option>`;
    }).join('');
};

const setSelectValue = (select, value) => {
    if (window.SearchableDropdown) {
        window.SearchableDropdown.setValue(select, value || '', false);
        return;
    }
    select.value = value || '';
};

const setStatus = (config, message, isError = false) => {
    config.elements.status.textContent = message || '';
    config.elements.status.classList.toggle('is-error', isError);
};

const syncRecipientControls = (config) => {
    const recipientCount = config.initialized
        ? config.selectedRecipients.length
        : getSelectedKeys(config).length;
    config.elements.recipientCount.textContent = config.initialized
        ? `${recipientCount} registrado${recipientCount === 1 ? '' : 's'}`
        : `${recipientCount} seleccionado${recipientCount === 1 ? '' : 's'}`;
    config.elements.notificationsEnabled.disabled = recipientCount === 0;
    if (recipientCount === 0) config.elements.notificationsEnabled.checked = false;
};

const renderInitialRecipients = (config) => {
    const recipients = config.availableRecipients;
    const selectedKeys = new Set(config.selectedKeys);
    config.elements.recipientList.innerHTML = recipients.map((recipient) => {
        const channel = recipient.channel === 'email' ? 'Correo' : 'Teléfono';
        return `
            <label class="servers-recipient-option">
                <input type="checkbox" class="servers-recipient-checkbox" value="${escapeHtml(recipient.key)}"${selectedKeys.has(recipient.key) ? ' checked' : ''}>
                <span class="servers-recipient-copy">
                    <strong>${escapeHtml(recipient.value)}</strong>
                    <small>${escapeHtml(channel)} · ${escapeHtml(recipient.source_label || 'Cliente')}</small>
                </span>
                <i class="fa-light fa-check servers-recipient-check" aria-hidden="true"></i>
            </label>`;
    }).join('');
};

const renderStoredRecipients = (config) => {
    const recipients = config.selectedRecipients;
    config.elements.recipientList.innerHTML = recipients.map((recipient) => {
        const channel = recipient.channel === 'email' ? 'Correo' : 'Teléfono';
        const name = recipient.name ? ` · ${escapeHtml(recipient.name)}` : '';
        return `
            <div class="servers-project-notification-row" data-notification-id="${escapeHtml(recipient.id)}">
                <div class="servers-recipient-copy">
                    <strong>${escapeHtml(recipient.value)}</strong>
                    <small>${escapeHtml(channel)} · ${escapeHtml(recipient.source_label || 'Propio del proyecto')}${name}</small>
                </div>
                <div class="servers-project-notification-actions">
                    <button type="button" class="servers-notification-action" data-notification-action="edit" data-notification-id="${escapeHtml(recipient.id)}" aria-label="Editar ${escapeHtml(recipient.value)}" title="Editar destinatario">
                        <i class="fa-light fa-pen" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="servers-notification-action is-danger" data-notification-action="delete" data-notification-id="${escapeHtml(recipient.id)}" aria-label="Eliminar ${escapeHtml(recipient.value)}" title="Eliminar destinatario">
                        <i class="fa-light fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>`;
    }).join('');
};

const renderRecipients = (config) => {
    config.elements.crud.hidden = !config.initialized;
    config.elements.recipientDescription.textContent = config.initialized
        ? 'Registros propios de este proyecto. Los cambios en las licencias no modifican esta lista.'
        : 'Estos contactos se consultan una sola vez desde el cliente y sus licencias activas.';
    if (config.initialized) {
        renderStoredRecipients(config);
        config.elements.recipientEmpty.textContent = 'Este proyecto todavía no tiene destinatarios registrados.';
        config.elements.recipientEmpty.hidden = config.selectedRecipients.length > 0;
    } else {
        renderInitialRecipients(config);
        config.elements.recipientEmpty.hidden = config.availableRecipients.length > 0;
        config.elements.recipientEmpty.textContent = config.clientId
            ? 'El cliente no tiene datos de contacto o notificadores activos.'
            : 'Selecciona un cliente para consultar sus notificadores.';
    }
    syncRecipientControls(config);
};

const showModal = (config) => {
    config.elements.modal.hidden = false;
    config.elements.modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('servers-modal-open');
};

const closeModal = (config) => {
    config.elements.modal.hidden = true;
    config.elements.modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('servers-modal-open');
};

const confirmImportRecipients = async (clientName, count) => {
    const message = `Se encontraron ${count} notificadores para ${clientName || 'este cliente'}. ¿Deseas agregarlos para seleccionar cuáles recibirán alertas?`;
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: 'Agregar notificadores',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, mostrar',
            cancelButtonText: 'Ahora no',
            reverseButtons: true,
        });
        return result.isConfirmed;
    }
    return window.confirm(message);
};

const promptForRecipients = async (config, clientName) => {
    const recipients = config.availableRecipients;
    if (config.initialized || !config.clientId || recipients.length === 0 || config.promptedClientIds.has(config.clientId)) return;
    config.promptedClientIds.add(config.clientId);
    if (await confirmImportRecipients(clientName, recipients.length)) {
        config.selectedKeys = new Set(recipients.map((recipient) => recipient.key));
        renderRecipients(config);
    }
};

const loadRecipientsForClient = async (state) => {
    const config = state.projectConfig;
    const clientId = config.elements.clientSelect.value || null;
    config.clientId = clientId;
    if (config.initialized) {
        renderRecipients(config);
        return;
    }
    config.availableRecipients = [];
    config.selectedRecipients = [];
    config.selectedKeys = new Set();
    renderRecipients(config);
    if (!clientId) return;

    setStatus(config, 'Consultando notificadores...');
    try {
        const data = await loadProjectRecipients(state, clientId);
        config.availableRecipients = data.recipients || [];
        renderRecipients(config);
        setStatus(config, '');
        const clientName = data.client?.complete_name || config.elements.clientSelect.options[config.elements.clientSelect.selectedIndex]?.textContent;
        await promptForRecipients(config, clientName);
    } catch (error) {
        setStatus(config, error.message || 'No fue posible consultar los notificadores.', true);
        renderRecipients(config);
    }
};

const loadClients = (config, clients, selectedClientId) => {
    const options = [
        { value: '', label: 'Sin cliente' },
        ...(clients || []).map((client) => ({
            value: client.id,
            label: client.complete_name || client.name,
        })),
    ];
    setSelectOptions(config.elements.clientSelect, options);
    setSelectValue(config.elements.clientSelect, selectedClientId);
};

const applyConfigData = (config, data) => {
    const project = data.project || {};
    config.clientId = project.client_id || null;
    config.initialized = Boolean(data.notification_recipients_initialized);
    config.initialImportRequired = Boolean(data.needs_initial_import);
    config.availableRecipients = data.available_recipients || [];
    config.selectedRecipients = data.selected_recipients || [];
    config.selectedKeys = new Set(config.selectedRecipients.map((recipient) => recipient.key));
    loadClients(config, data.clients || [], config.clientId);
    config.elements.notificationsEnabled.checked = Boolean(project.notifications_enabled);
    renderRecipients(config);
};

const save = async (state, event) => {
    event.preventDefault();
    const config = state.projectConfig;
    const recipientKeys = config.initialized ? [] : getSelectedKeys(config);
    const notificationsEnabled = config.elements.notificationsEnabled.checked;
    if (notificationsEnabled && recipientKeys.length === 0) {
        setStatus(config, 'Selecciona al menos un destinatario para activar las notificaciones.', true);
        return;
    }

    config.elements.save.disabled = true;
    setStatus(config, 'Guardando configuración...');
    try {
        const data = await saveProjectConfig(state, {
            project_id: config.projectId,
            client_id: config.elements.clientSelect.value || null,
            notifications_enabled: notificationsEnabled,
            recipient_keys: recipientKeys,
        });
        applyConfigData(config, data);
        closeModal(config);
        state.statusContainer.textContent = 'Configuración de notificaciones actualizada';
        await loadPage(state);
    } catch (error) {
        setStatus(config, error.message || 'No fue posible guardar la configuración.', true);
    } finally {
        config.elements.save.disabled = false;
    }
};

const resetNotificationForm = (config) => {
    config.editingNotificationId = null;
    setSelectValue(config.elements.notificationChannel, 'email');
    config.elements.notificationValue.value = '';
    config.elements.notificationName.value = '';
    config.elements.notificationFormTitle.textContent = 'Agregar destinatario';
    config.elements.notificationSubmitLabel.textContent = 'Agregar';
    config.elements.notificationSubmit.querySelector('i').className = 'fa-light fa-plus';
    config.elements.notificationCancelEdit.hidden = true;
};

const editNotification = (config, notificationId) => {
    const recipient = config.selectedRecipients.find((item) => String(item.id) === String(notificationId));
    if (!recipient) return;
    config.editingNotificationId = recipient.id;
    setSelectValue(config.elements.notificationChannel, recipient.channel);
    config.elements.notificationValue.value = recipient.value || '';
    config.elements.notificationName.value = recipient.name || '';
    config.elements.notificationFormTitle.textContent = 'Editar destinatario';
    config.elements.notificationSubmitLabel.textContent = 'Actualizar';
    config.elements.notificationSubmit.querySelector('i').className = 'fa-light fa-pen';
    config.elements.notificationCancelEdit.hidden = false;
    config.elements.notificationValue.focus();
};

const confirmDeleteNotification = async (recipient) => {
    const message = `¿Eliminar ${recipient.value} de los destinatarios de este proyecto?`;
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: 'Eliminar destinatario',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        });
        return result.isConfirmed;
    }
    return window.confirm(message);
};

const submitNotification = async (state) => {
    const config = state.projectConfig;
    const value = config.elements.notificationValue.value.trim();
    if (!value) {
        setStatus(config, 'Ingresa un correo o teléfono.', true);
        config.elements.notificationValue.focus();
        return;
    }

    const payload = {
        project_id: config.projectId,
        channel: config.elements.notificationChannel.value,
        value,
        recipient_name: config.elements.notificationName.value.trim() || null,
    };
    const isEditing = Boolean(config.editingNotificationId);
    if (isEditing) payload.notification_id = config.editingNotificationId;
    config.elements.notificationSubmit.disabled = true;
    setStatus(config, isEditing ? 'Actualizando destinatario...' : 'Agregando destinatario...');
    try {
        const data = isEditing
            ? await updateProjectNotification(state, payload)
            : await addProjectNotification(state, payload);
        applyConfigData(config, data);
        resetNotificationForm(config);
        setStatus(config, isEditing ? 'Destinatario actualizado.' : 'Destinatario agregado.');
    } catch (error) {
        setStatus(config, error.message || 'No fue posible guardar el destinatario.', true);
    } finally {
        config.elements.notificationSubmit.disabled = false;
    }
};

const deleteNotification = async (state, notificationId) => {
    const config = state.projectConfig;
    const recipient = config.selectedRecipients.find((item) => String(item.id) === String(notificationId));
    if (!recipient || !(await confirmDeleteNotification(recipient))) return;

    setStatus(config, 'Eliminando destinatario...');
    try {
        const data = await deleteProjectNotification(state, {
            project_id: config.projectId,
            notification_id: notificationId,
        });
        applyConfigData(config, data);
        resetNotificationForm(config);
        setStatus(config, 'Destinatario eliminado.');
    } catch (error) {
        setStatus(config, error.message || 'No fue posible eliminar el destinatario.', true);
    }
};

export const initializeProjectConfig = (state) => {
    const modal = document.getElementById('servers-project-config-modal');
    if (!modal) return;

    const config = {
        elements: {
            modal,
            close: document.getElementById('servers-project-config-close'),
            cancel: document.getElementById('servers-project-config-cancel'),
            form: document.getElementById('servers-project-config-form'),
            status: document.getElementById('servers-project-config-status'),
            project: document.getElementById('servers-project-config-project'),
            clientSelect: document.getElementById('servers-project-config-client-select'),
            recipientList: document.getElementById('servers-project-config-recipient-list'),
            recipientEmpty: document.getElementById('servers-project-config-recipient-empty'),
            recipientDescription: document.getElementById('servers-project-config-recipient-description'),
            recipientCount: document.getElementById('servers-project-config-recipient-count'),
            crud: document.getElementById('servers-project-notification-crud'),
            notificationChannel: document.getElementById('servers-project-notification-channel'),
            notificationValue: document.getElementById('servers-project-notification-value'),
            notificationName: document.getElementById('servers-project-notification-name'),
            notificationFormTitle: document.getElementById('servers-project-notification-form-title'),
            notificationSubmit: document.getElementById('servers-project-notification-submit'),
            notificationSubmitLabel: document.getElementById('servers-project-notification-submit-label'),
            notificationCancelEdit: document.getElementById('servers-project-notification-cancel-edit'),
            notificationsEnabled: document.getElementById('servers-project-config-notifications-enabled'),
            save: document.getElementById('servers-project-config-save'),
        },
        projectId: null,
        clientId: null,
        initialized: false,
        initialImportRequired: false,
        editingNotificationId: null,
        promptedClientIds: new Set(),
        availableRecipients: [],
        selectedRecipients: [],
        selectedKeys: new Set(),
    };
    state.projectConfig = config;

    config.elements.clientSelect.addEventListener('change', () => loadRecipientsForClient(state));
    config.elements.recipientList.addEventListener('change', (event) => {
        if (config.initialized || !event.target.classList.contains('servers-recipient-checkbox')) return;
        config.selectedKeys = new Set(getSelectedKeys(config));
        syncRecipientControls(config);
    });
    config.elements.recipientList.addEventListener('click', (event) => {
        const action = event.target.closest('[data-notification-action]');
        if (!action || !config.initialized) return;
        const notificationId = action.dataset.notificationId;
        if (action.dataset.notificationAction === 'edit') editNotification(config, notificationId);
        if (action.dataset.notificationAction === 'delete') deleteNotification(state, notificationId);
    });
    config.elements.notificationsEnabled.addEventListener('change', () => syncRecipientControls(config));
    config.elements.form.addEventListener('submit', (event) => save(state, event));
    config.elements.notificationSubmit.addEventListener('click', () => submitNotification(state));
    config.elements.notificationCancelEdit.addEventListener('click', () => resetNotificationForm(config));
    [config.elements.close, config.elements.cancel].forEach((button) => button.addEventListener('click', () => closeModal(config)));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal(config);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal(config);
    });
};

export const openProjectConfig = async (state, projectId) => {
    const config = state.projectConfig;
    if (!config || !projectId) return;
    config.projectId = projectId;
    config.clientId = null;
    config.initialized = false;
    config.initialImportRequired = false;
    config.editingNotificationId = null;
    config.promptedClientIds = new Set();
    config.availableRecipients = [];
    config.selectedRecipients = [];
    config.selectedKeys = new Set();
    config.elements.clientSelect.disabled = true;
    config.elements.notificationsEnabled.disabled = true;
    config.elements.notificationsEnabled.checked = false;
    config.elements.project.textContent = 'Cargando proyecto...';
    resetNotificationForm(config);
    setStatus(config, 'Consultando configuración...');
    renderRecipients(config);
    showModal(config);

    try {
        const data = await loadProjectConfig(state, projectId);
        const project = data.project || {};
        applyConfigData(config, data);
        config.elements.project.textContent = `${project.name || 'Proyecto'} · ${project.key || ''}`;
        config.elements.clientSelect.disabled = false;
        setStatus(config, '');
        const clientName = data.client?.complete_name || '';
        await promptForRecipients(config, clientName);
    } catch (error) {
        setStatus(config, error.message || 'No fue posible cargar la configuración.', true);
        config.elements.clientSelect.disabled = false;
    }
};
