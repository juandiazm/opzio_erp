import { loadPage, loadProjectConfig, loadProjectRecipients, saveProjectConfig } from './data.js';
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

const mergeRecipients = (config) => {
    const recipients = new Map();
    [...config.availableRecipients, ...config.selectedRecipients].forEach((recipient) => {
        if (recipient?.key && !recipients.has(recipient.key)) recipients.set(recipient.key, recipient);
    });
    return Array.from(recipients.values());
};

const syncRecipientControls = (config) => {
    const selectedCount = getSelectedKeys(config).length;
    config.elements.recipientCount.textContent = `${selectedCount} seleccionado${selectedCount === 1 ? '' : 's'}`;
    config.elements.notificationsEnabled.disabled = selectedCount === 0;
    if (selectedCount === 0) config.elements.notificationsEnabled.checked = false;
};

const renderRecipients = (config) => {
    const recipients = mergeRecipients(config);
    const selectedKeys = new Set(config.selectedKeys);
    config.elements.recipientList.innerHTML = recipients.map((recipient) => {
        const channel = recipient.channel === 'email' ? 'Correo' : 'Teléfono';
        const unavailable = recipient.available === false ? ' is-unavailable' : '';
        return `
            <label class="servers-recipient-option${unavailable}">
                <input type="checkbox" class="servers-recipient-checkbox" value="${escapeHtml(recipient.key)}"${selectedKeys.has(recipient.key) ? ' checked' : ''}>
                <span class="servers-recipient-copy">
                    <strong>${escapeHtml(recipient.value)}</strong>
                    <small>${escapeHtml(channel)} · ${escapeHtml(recipient.source_label || 'Cliente')}</small>
                </span>
                <i class="fa-light fa-check servers-recipient-check" aria-hidden="true"></i>
            </label>`;
    }).join('');
    config.elements.recipientEmpty.hidden = recipients.length > 0;
    if (recipients.length === 0 && config.clientId) {
        config.elements.recipientEmpty.textContent = 'El cliente no tiene datos de contacto o notificadores activos.';
    } else if (recipients.length === 0) {
        config.elements.recipientEmpty.textContent = 'Selecciona un cliente para consultar sus notificadores.';
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
    if (config.hasExistingRecipients || !config.clientId || recipients.length === 0 || config.promptedClientIds.has(config.clientId)) return;
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

const save = async (state, event) => {
    event.preventDefault();
    const config = state.projectConfig;
    const recipientKeys = getSelectedKeys(config);
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
        config.hasExistingRecipients = Boolean(data.has_recipients);
        closeModal(config);
        state.statusContainer.textContent = 'Configuración de notificaciones actualizada';
        await loadPage(state);
    } catch (error) {
        setStatus(config, error.message || 'No fue posible guardar la configuración.', true);
    } finally {
        config.elements.save.disabled = false;
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
            recipientCount: document.getElementById('servers-project-config-recipient-count'),
            notificationsEnabled: document.getElementById('servers-project-config-notifications-enabled'),
            save: document.getElementById('servers-project-config-save'),
        },
        projectId: null,
        clientId: null,
        hasExistingRecipients: false,
        promptedClientIds: new Set(),
        availableRecipients: [],
        selectedRecipients: [],
        selectedKeys: new Set(),
    };
    state.projectConfig = config;

    config.elements.clientSelect.addEventListener('change', () => loadRecipientsForClient(state));
    config.elements.recipientList.addEventListener('change', (event) => {
        if (!event.target.classList.contains('servers-recipient-checkbox')) return;
        config.selectedKeys = new Set(getSelectedKeys(config));
        syncRecipientControls(config);
    });
    config.elements.notificationsEnabled.addEventListener('change', () => syncRecipientControls(config));
    config.elements.form.addEventListener('submit', (event) => save(state, event));
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
    config.hasExistingRecipients = false;
    config.promptedClientIds = new Set();
    config.availableRecipients = [];
    config.selectedRecipients = [];
    config.selectedKeys = new Set();
    config.elements.clientSelect.disabled = true;
    config.elements.notificationsEnabled.disabled = true;
    config.elements.notificationsEnabled.checked = false;
    config.elements.project.textContent = 'Cargando proyecto...';
    setStatus(config, 'Consultando configuración...');
    renderRecipients(config);
    showModal(config);

    try {
        const data = await loadProjectConfig(state, projectId);
        const project = data.project || {};
        config.clientId = project.client_id || null;
        config.hasExistingRecipients = Boolean(data.has_recipients);
        config.availableRecipients = data.available_recipients || [];
        config.selectedRecipients = data.selected_recipients || [];
        config.selectedKeys = new Set(config.selectedRecipients.map((recipient) => recipient.key));
        config.elements.project.textContent = `${project.name || 'Proyecto'} · ${project.key || ''}`;
        loadClients(config, data.clients || [], config.clientId);
        config.elements.notificationsEnabled.checked = Boolean(project.notifications_enabled);
        config.elements.clientSelect.disabled = false;
        renderRecipients(config);
        setStatus(config, '');
        const clientName = data.client?.complete_name || '';
        await promptForRecipients(config, clientName);
    } catch (error) {
        setStatus(config, error.message || 'No fue posible cargar la configuración.', true);
        config.elements.clientSelect.disabled = false;
    }
};
