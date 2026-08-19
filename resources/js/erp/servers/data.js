import { formatBytes, formatDateTime, formatNumber } from './formatters.js';
import { renderFilterOptions, renderOverview, renderPagination, renderRows } from './view.js';

const requestJson = async (url, state, body) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': state.csrfToken || '' },
        body: JSON.stringify(body)
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.status === 0) {
        throw new Error(payload.message || `HTTP ${response.status}`);
    }
    return payload;
};

export const loadPage = async (state) => {
    state.statusContainer.textContent = 'Consultando...';
    state.statusContainer.classList.remove('is-error');
    try {
        const response = await fetch('/admin/servers/get-page', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': state.csrfToken || '' },
            body: JSON.stringify({
                minutes: Number(state.rangeSelect.value),
                search: state.searchInput.value,
                host_key: state.hostSelect.value,
                environment: state.environmentSelect.value,
                health: state.healthSelect.value,
                notifications: state.notificationsSelect.value,
                sort_by: state.sortState.key,
                sort_direction: state.sortState.direction,
                pagination: state.pagination
            })
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const payload = await response.json();
        state.pagination = payload.pagination || state.pagination;
        renderFilterOptions(state, payload.filters || {});
        renderOverview(state, payload.totals || {});
        renderRows(state, payload.data || []);
        renderPagination(state);
        state.statusContainer.textContent = `Consulta realizada ${formatDateTime(payload.generated_at)} · ${formatNumber(state.pagination.total)} proyectos`;
    } catch (error) {
        state.statusContainer.textContent = 'No se pudo consultar la servidores';
        state.statusContainer.classList.add('is-error');
        state.tableBody.innerHTML = '<tr><td colspan="11" class="servers-empty">No fue posible cargar los datos.</td></tr>';
        state.paginationContainer.innerHTML = '';
    }
};

export const requestPage = (state) => {
    state.pagination.page = 1;
    return loadPage(state);
};

export const exportData = (state) => {
    const params = new URLSearchParams({
        minutes: state.rangeSelect.value,
        search: state.searchInput.value,
        host_key: state.hostSelect.value,
        environment: state.environmentSelect.value,
        health: state.healthSelect.value,
        notifications: state.notificationsSelect.value,
        sort_by: state.sortState.key,
        sort_direction: state.sortState.direction
    });
    window.location.href = `/admin/servers/export?${params.toString()}`;
};

export const loadProjectConfig = async (state, projectId) => {
    const payload = await requestJson('/admin/servers/project-config/get', state, { project_id: projectId });
    return payload.data || {};
};

export const loadProjectRecipients = async (state, clientId) => {
    const payload = await requestJson('/admin/servers/project-config/recipients', state, { client_id: clientId });
    return payload.data || {};
};

export const saveProjectConfig = async (state, config) => {
    const payload = await requestJson('/admin/servers/project-config/update', state, config);
    return payload.data || {};
};

export const addProjectNotification = async (state, notification) => {
    const payload = await requestJson('/admin/servers/project-config/notifications/add', state, notification);
    return payload.data || {};
};

export const updateProjectNotification = async (state, notification) => {
    const payload = await requestJson('/admin/servers/project-config/notifications/update', state, notification);
    return payload.data || {};
};

export const deleteProjectNotification = async (state, notification) => {
    const payload = await requestJson('/admin/servers/project-config/notifications/delete', state, notification);
    return payload.data || {};
};