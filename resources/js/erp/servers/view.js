import { escapeHtml, formatBytes, formatDateTime, formatNumber, formatSignedBytes } from './formatters.js';

export const renderSortState = (state) => {
    state.sortableHeaders.forEach((header) => {
        const indicator = header.querySelector('.servers-sort-indicator');
        const button = header.querySelector('.servers-sort-button');
        const isActive = header.dataset.sortKey === state.sortState.key;
        const directionLabel = state.sortState.direction === 'asc' ? 'ascendente' : 'descendente';
        header.setAttribute('aria-sort', isActive ? (state.sortState.direction === 'asc' ? 'ascending' : 'descending') : 'none');
        if (button) {
            button.setAttribute('aria-label', `Ordenar por ${button.dataset.sortLabel || ''}${isActive ? `, actualmente ${directionLabel}` : ''}`);
        }
        if (!indicator) return;
        indicator.classList.toggle('fa-sort', !isActive);
        indicator.classList.toggle('fa-sort-up', isActive && state.sortState.direction === 'asc');
        indicator.classList.toggle('fa-sort-down', isActive && state.sortState.direction === 'desc');
    });
};

export const renderFilterOptions = (state, filters) => {
    if (state.filterOptionsLoaded) return;
    const selectedHost = state.hostSelect.value;
    const selectedEnvironment = state.environmentSelect.value;
    const hostOptions = [{value: '', label: 'Todos'}, ...(filters.hosts || []).map((host) => ({
        value: host.key,
        label: host.name,
    }))];
    const environmentOptions = [{value: '', label: 'Todos'}, ...(filters.environments || []).map((environment) => ({
        value: environment,
        label: environment,
    }))];
    if (window.SearchableDropdown) {
        window.SearchableDropdown.setOptions(state.hostSelect, hostOptions);
        window.SearchableDropdown.setOptions(state.environmentSelect, environmentOptions);
    } else {
        state.hostSelect.innerHTML = hostOptions.map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`).join('');
        state.environmentSelect.innerHTML = environmentOptions.map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`).join('');
    }
    state.hostSelect.value = selectedHost;
    state.environmentSelect.value = selectedEnvironment;
    if (window.SearchableDropdown) {
        window.SearchableDropdown.init(state.hostSelect);
        window.SearchableDropdown.init(state.environmentSelect);
    }
    state.filterOptionsLoaded = true;
};

const overviewMetric = (label, value, tone = '') => `
    <div class="servers-overview-item ${tone}">
        <span>${escapeHtml(label)}</span>
        <strong>${escapeHtml(value)}</strong>
    </div>`;

export const renderOverview = (state, totals) => {
    state.overviewContainer.innerHTML = [
        overviewMetric('Proyectos', formatNumber(totals.projects)),
        overviewMetric('Reportando', formatNumber(totals.reporting), 'is-healthy'),
        overviewMetric('Sin reporte', formatNumber(totals.stale + totals.no_data), totals.stale + totals.no_data > 0 ? 'is-warn' : ''),
        overviewMetric('Solicitudes', formatNumber(totals.requests_total)),
        overviewMetric('Errores 5xx', formatNumber(totals.status_5xx), totals.status_5xx > 0 ? 'is-danger' : ''),
        overviewMetric('Salida', formatBytes(totals.response_bytes))
    ].join('');
};

const renderStorageBreakdown = (breakdown) => {
    if (!breakdown || typeof breakdown !== 'object') return '-';
    const entries = Object.entries(breakdown).map(([key, value]) => {
        const displayValue = typeof value === 'number' ? formatBytes(value) : value;
        return `${escapeHtml(key)}: ${escapeHtml(displayValue)}`;
    });
    return entries.length > 0 ? entries.join(' · ') : '-';
};

const detailMetric = (label, value) => `
    <div class="servers-detail-metric">
        <span>${escapeHtml(label)}</span>
        <strong>${escapeHtml(value)}</strong>
    </div>`;

const formatLatencyPercentiles = (project) => {
    if (project.p50_ms === null && project.p95_ms === null && project.p99_ms === null) return '-';
    return `${formatNumber(project.p50_ms, ' ms')} / ${formatNumber(project.p95_ms, ' ms')} / ${formatNumber(project.p99_ms, ' ms')}`;
};

const renderProjectDetails = (project) => `
    <div class="servers-detail-grid">
        ${detailMetric('Requests totales', formatNumber(project.requests_total))}
        ${detailMetric('Pico de tráfico', formatNumber(project.peak_requests_per_minute, ' /min'))}
        ${detailMetric('Cobertura', `${formatNumber(project.coverage_minutes, ' min')} (${formatNumber(project.coverage_percent, '%')})`)}
        ${detailMetric('Entrada / salida', `${formatBytes(project.request_bytes)} / ${formatBytes(project.response_bytes)}`)}
        ${detailMetric('Latencia media', formatNumber(project.latency_average_ms, ' ms'))}
        ${detailMetric('p50 / p95 / p99', formatLatencyPercentiles(project))}
        ${detailMetric('HTTP', `2xx ${formatNumber(project.status_2xx)} · 3xx ${formatNumber(project.status_3xx)} · 4xx ${formatNumber(project.status_4xx)}`)}
        ${detailMetric('Errores específicos', `499 ${formatNumber(project.status_499)} · 500 ${formatNumber(project.status_500)} · 502 ${formatNumber(project.status_502)} · 503 ${formatNumber(project.status_503)} · 504 ${formatNumber(project.status_504)}`)}
        ${detailMetric('CPU promedio / pico', `${formatNumber(project.cpu_average_percent, '%')} / ${formatNumber(project.cpu_peak_percent, '%')}`)}
        ${detailMetric('RAM RSS pico', formatBytes(project.memory_rss_peak_bytes))}
        ${detailMetric('FPM activos / inactivos', `${formatNumber(project.fpm_active_processes)} / ${formatNumber(project.fpm_idle_processes)}`)}
        ${detailMetric('FPM queue pico', `${formatNumber(project.fpm_listen_queue_peak)} (${formatNumber(project.fpm_queue_percent, '%')} actual)`)}
        ${detailMetric('FPM contadores', `max children +${formatNumber(project.fpm_max_children_reached_delta)} · lentas +${formatNumber(project.fpm_slow_requests_delta)}`)}
        ${detailMetric('Storage crecimiento', formatSignedBytes(project.storage_growth_bytes))}
        ${detailMetric('Archivos / directorios', `${formatNumber(project.storage_files)} / ${formatNumber(project.storage_directories)}`)}
        ${detailMetric('Desglose storage', renderStorageBreakdown(project.storage_breakdown))}
        ${detailMetric('Runtime', `PHP ${project.php_version || '-'} · pool ${project.fpm_pool || '-'}`)}
        ${detailMetric('Agente', `${project.agent_version || '-'} · spool ${formatBytes(project.agent_spool_bytes)}`)}
    </div>`;

export const renderRows = (state, projects) => {
    if (!projects.length) {
        state.tableBody.innerHTML = '<tr><td colspan="11" class="servers-empty">No hay proyectos para los filtros seleccionados.</td></tr>';
        return;
    }
    state.tableBody.innerHTML = projects.map((project) => {
        const detailId = `servers-detail-${project.id}`;
        const healthClass = project.health === 'reporting' ? 'is-healthy' : (project.health === 'stale' ? 'is-warn' : 'is-neutral');
        return `
            <tr class="servers-project-row">
                <td><strong>${escapeHtml(project.name)}</strong><small>${escapeHtml(project.key)} · ${escapeHtml(project.attribution_mode || '-')}</small></td>
                <td><strong>${escapeHtml(project.host_name || project.hostname || '-')}</strong><small>${escapeHtml(project.environment)}</small></td>
                <td><span class="servers-pill ${healthClass}">${escapeHtml(state.healthLabels[project.health] || project.health)}</span><small>${escapeHtml(formatDateTime(project.last_sample_at))}</small></td>
                <td><strong>${formatNumber(project.requests_per_minute, ' /min')}</strong><small>${formatNumber(project.requests_total)} total · ${formatNumber(project.coverage_percent, '%')} cobertura</small></td>
                <td><strong>${formatNumber(project.availability_percent, '%')}</strong><small>2xx ${formatNumber(project.success_rate_percent, '%')}</small></td>
                <td><strong>${formatNumber(project.p95_ms, ' ms')}</strong><small>media ${formatNumber(project.latency_average_ms, ' ms')} · p99 ${formatNumber(project.p99_ms, ' ms')}</small></td>
                <td><strong>${formatNumber(project.status_5xx)} 5xx</strong><small>${formatNumber(project.status_4xx)} 4xx · ${formatNumber(project.error_rate_percent, '%')}</small></td>
                <td><strong>${formatNumber(project.cpu_percent, '%')}</strong><small>${formatBytes(project.memory_rss_bytes)} RSS</small></td>
                <td><strong>${formatNumber(project.fpm_listen_queue)} / ${formatNumber(project.fpm_max_listen_queue)}</strong><small>${formatNumber(project.fpm_utilization_percent, '%')} utilización</small></td>
                <td><strong>${formatBytes(project.storage_total_bytes)}</strong><small>${formatSignedBytes(project.storage_growth_bytes)}</small></td>
                <td class="text-end">
                    <button type="button" class="servers-config-button${project.notifications_enabled ? ' is-enabled' : ''}" data-project-id="${escapeHtml(project.id)}" aria-label="Configurar notificaciones de ${escapeHtml(project.name)}" title="Configurar notificaciones"><i class="fa-light fa-bell"></i></button>
                    <button type="button" class="servers-detail-button" data-detail-id="${escapeHtml(detailId)}" aria-label="Ver detalle de ${escapeHtml(project.name)}" title="Ver detalle"><i class="fa-light fa-eye"></i></button>
                </td>
            </tr>
            <tr id="${escapeHtml(detailId)}" class="servers-detail-row" hidden>
                <td colspan="11">${renderProjectDetails(project)}</td>
            </tr>`;
    }).join('');
};

export const renderPagination = (state) => {
    const totalPages = Number(state.pagination.totalPages || 0);
    if (totalPages === 0) {
        state.paginationContainer.innerHTML = '';
        return;
    }
    const currentPage = Number(state.pagination.page || 1);
    const pages = [];
    const addPage = (page) => {
        if (pages[pages.length - 1] !== page) pages.push(page);
    };
    addPage(1);
    if (currentPage > 4) addPage('...');
    for (let page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) addPage(page);
    if (currentPage < totalPages - 3) addPage('...');
    if (totalPages > 1) addPage(totalPages);

    const pageItems = pages.map((page) => page === '...'
        ? '<li class="page-item disabled"><span class="page-link">...</span></li>'
        : `<li class="page-item page-item-number${page === currentPage ? ' active' : ''}" data-page="${page}"><button type="button" class="page-link">${page}</button></li>`
    ).join('');
    state.paginationContainer.innerHTML = `
        <li class="page-item${currentPage <= 1 ? ' disabled' : ''}" data-page="${currentPage - 1}"><button type="button" class="page-link" aria-label="Página anterior">&lt;</button></li>
        ${pageItems}
        <li class="page-item${currentPage >= totalPages ? ' disabled' : ''}" data-page="${currentPage + 1}"><button type="button" class="page-link" aria-label="Página siguiente">&gt;</button></li>
        <li class="page-item"><span class="page-link"><select id="db-pagination-per-page" aria-label="Registros por página"><option value="5"${state.pagination.per_page === 5 ? ' selected' : ''}>5</option><option value="10"${state.pagination.per_page === 10 ? ' selected' : ''}>10</option><option value="25"${state.pagination.per_page === 25 ? ' selected' : ''}>25</option><option value="50"${state.pagination.per_page === 50 ? ' selected' : ''}>50</option></select></span></li>`;
    const perPageSelect = state.paginationContainer.querySelector('#db-pagination-per-page');
    if (perPageSelect && window.SearchableDropdown) window.SearchableDropdown.init(perPageSelect);
};