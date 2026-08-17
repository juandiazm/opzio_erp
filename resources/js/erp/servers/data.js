import { formatBytes, formatDateTime, formatNumber } from './formatters.js';
import { renderFilterOptions, renderOverview, renderPagination, renderRows } from './view.js';

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
        sort_by: state.sortState.key,
        sort_direction: state.sortState.direction
    });
    window.location.href = `/admin/servers/export?${params.toString()}`;
};