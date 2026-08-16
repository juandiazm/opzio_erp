(() => {
    const init = () => {
        const rangeSelect = document.getElementById('observability-range-select');
        const searchInput = document.getElementById('observability-search');
        const hostSelect = document.getElementById('observability-host-select');
        const environmentSelect = document.getElementById('observability-environment-select');
        const healthSelect = document.getElementById('observability-health-select');
        const queryButton = document.getElementById('observability-query-button');
        const exportButton = document.getElementById('observability-export-button');
        const statusContainer = document.getElementById('observability-status');
        const overviewContainer = document.getElementById('observability-overview');
        const projectsTable = document.getElementById('observability-projects-table');
        const tableBody = document.getElementById('observability-projects-table-body');
        const paginationContainer = document.getElementById('db-pagination');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let pagination = { page: 1, per_page: 10, total: 0, totalPages: 0 };
        const sortState = { key: 'name', direction: 'desc' };
        let filterOptionsLoaded = false;

        if (!rangeSelect || !projectsTable || !tableBody) return;

        const healthLabels = {
            reporting: 'Reportando',
            stale: 'Sin reporte reciente',
            no_data: 'Sin datos'
        };

        const formatBytes = (value) => {
            if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let number = Number(value);
            let unit = 0;
            const sign = number < 0 ? '-' : '';
            number = Math.abs(number);
            while (number >= 1024 && unit < units.length - 1) {
                number /= 1024;
                unit += 1;
            }
            return `${sign}${number.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
        };

        const formatSignedBytes = (value) => {
            if (value === null || value === undefined) return '-';
            return `${Number(value) >= 0 ? '+' : ''}${formatBytes(value)}`;
        };

        const formatNumber = (value, suffix = '') => {
            if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
            return `${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 2 })}${suffix}`;
        };

        const formatDateTime = (value) => {
            if (!value) return '-';
            const date = new Date(value);
            return Number.isNaN(date.getTime())
                ? '-'
                : date.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
        };

        const escapeHtml = (value) => String(value ?? '-').replace(/[&<>'"]/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        }[character]));

        const sortableHeaders = Array.from(projectsTable.querySelectorAll('th[data-sort-key]'));

        const renderSortState = () => {
            sortableHeaders.forEach((header) => {
                const indicator = header.querySelector('.observability-sort-indicator');
                const button = header.querySelector('.observability-sort-button');
                const isActive = header.dataset.sortKey === sortState.key;
                const directionLabel = sortState.direction === 'asc' ? 'ascendente' : 'descendente';
                header.setAttribute('aria-sort', isActive ? (sortState.direction === 'asc' ? 'ascending' : 'descending') : 'none');
                if (button) {
                    button.setAttribute('aria-label', `Ordenar por ${button.dataset.sortLabel || ''}${isActive ? `, actualmente ${directionLabel}` : ''}`);
                }
                if (!indicator) return;
                indicator.classList.toggle('fa-sort', !isActive);
                indicator.classList.toggle('fa-sort-up', isActive && sortState.direction === 'asc');
                indicator.classList.toggle('fa-sort-down', isActive && sortState.direction === 'desc');
            });
        };

        const renderFilterOptions = (filters) => {
            if (filterOptionsLoaded) return;
            const selectedHost = hostSelect.value;
            const selectedEnvironment = environmentSelect.value;
            hostSelect.innerHTML = '<option value="">Todos</option>' + (filters.hosts || []).map((host) =>
                `<option value="${escapeHtml(host.key)}">${escapeHtml(host.name)}</option>`
            ).join('');
            environmentSelect.innerHTML = '<option value="">Todos</option>' + (filters.environments || []).map((environment) =>
                `<option value="${escapeHtml(environment)}">${escapeHtml(environment)}</option>`
            ).join('');
            hostSelect.value = selectedHost;
            environmentSelect.value = selectedEnvironment;
            filterOptionsLoaded = true;
        };

        const overviewMetric = (label, value, tone = '') => `
            <div class="observability-overview-item ${tone}">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>`;

        const renderOverview = (totals) => {
            overviewContainer.innerHTML = [
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
            <div class="observability-detail-metric">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>`;

        const formatLatencyPercentiles = (project) => {
            if (project.p50_ms === null && project.p95_ms === null && project.p99_ms === null) return '-';
            return `${formatNumber(project.p50_ms, ' ms')} / ${formatNumber(project.p95_ms, ' ms')} / ${formatNumber(project.p99_ms, ' ms')}`;
        };

        const renderProjectDetails = (project) => `
            <div class="observability-detail-grid">
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

        const renderRows = (projects) => {
            if (!projects.length) {
                tableBody.innerHTML = '<tr><td colspan="11" class="observability-empty">No hay proyectos para los filtros seleccionados.</td></tr>';
                return;
            }
            tableBody.innerHTML = projects.map((project) => {
                const detailId = `observability-detail-${project.id}`;
                const healthClass = project.health === 'reporting' ? 'is-healthy' : (project.health === 'stale' ? 'is-warn' : 'is-neutral');
                return `
                    <tr class="observability-project-row">
                        <td><strong>${escapeHtml(project.name)}</strong><small>${escapeHtml(project.key)} · ${escapeHtml(project.attribution_mode || '-')}</small></td>
                        <td><strong>${escapeHtml(project.host_name || project.hostname || '-')}</strong><small>${escapeHtml(project.environment)}</small></td>
                        <td><span class="observability-pill ${healthClass}">${escapeHtml(healthLabels[project.health] || project.health)}</span><small>${escapeHtml(formatDateTime(project.last_sample_at))}</small></td>
                        <td><strong>${formatNumber(project.requests_per_minute, ' /min')}</strong><small>${formatNumber(project.requests_total)} total · ${formatNumber(project.coverage_percent, '%')} cobertura</small></td>
                        <td><strong>${formatNumber(project.availability_percent, '%')}</strong><small>2xx ${formatNumber(project.success_rate_percent, '%')}</small></td>
                        <td><strong>${formatNumber(project.p95_ms, ' ms')}</strong><small>media ${formatNumber(project.latency_average_ms, ' ms')} · p99 ${formatNumber(project.p99_ms, ' ms')}</small></td>
                        <td><strong>${formatNumber(project.status_5xx)} 5xx</strong><small>${formatNumber(project.status_4xx)} 4xx · ${formatNumber(project.error_rate_percent, '%')}</small></td>
                        <td><strong>${formatNumber(project.cpu_percent, '%')}</strong><small>${formatBytes(project.memory_rss_bytes)} RSS</small></td>
                        <td><strong>${formatNumber(project.fpm_listen_queue)} / ${formatNumber(project.fpm_max_listen_queue)}</strong><small>${formatNumber(project.fpm_utilization_percent, '%')} utilización</small></td>
                        <td><strong>${formatBytes(project.storage_total_bytes)}</strong><small>${formatSignedBytes(project.storage_growth_bytes)}</small></td>
                        <td class="text-end"><button type="button" class="observability-detail-button" data-detail-id="${escapeHtml(detailId)}" aria-label="Ver detalle de ${escapeHtml(project.name)}" title="Ver detalle"><i class="fa-light fa-eye"></i></button></td>
                    </tr>
                    <tr id="${escapeHtml(detailId)}" class="observability-detail-row" hidden>
                        <td colspan="11">${renderProjectDetails(project)}</td>
                    </tr>`;
            }).join('');
        };

        const renderPagination = () => {
            const totalPages = Number(pagination.totalPages || 0);
            if (totalPages === 0) {
                paginationContainer.innerHTML = '';
                return;
            }
            const currentPage = Number(pagination.page || 1);
            const pages = [];
            const addPage = (page) => {
                if (pages[pages.length - 1] !== page) pages.push(page);
            };
            addPage(1);
            if (currentPage > 4) addPage('...');
            for (let page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) {
                addPage(page);
            }
            if (currentPage < totalPages - 3) addPage('...');
            if (totalPages > 1) addPage(totalPages);

            const pageItems = pages.map((page) => page === '...'
                ? '<li class="page-item disabled"><span class="page-link">...</span></li>'
                : `<li class="page-item page-item-number${page === currentPage ? ' active' : ''}" data-page="${page}"><button type="button" class="page-link">${page}</button></li>`
            ).join('');
            paginationContainer.innerHTML = `
                <li class="page-item${currentPage <= 1 ? ' disabled' : ''}" data-page="${currentPage - 1}"><button type="button" class="page-link" aria-label="Página anterior">&lt;</button></li>
                ${pageItems}
                <li class="page-item${currentPage >= totalPages ? ' disabled' : ''}" data-page="${currentPage + 1}"><button type="button" class="page-link" aria-label="Página siguiente">&gt;</button></li>
                <li class="page-item"><span class="page-link"><select id="db-pagination-per-page" aria-label="Registros por página"><option value="5"${pagination.per_page === 5 ? ' selected' : ''}>5</option><option value="10"${pagination.per_page === 10 ? ' selected' : ''}>10</option><option value="25"${pagination.per_page === 25 ? ' selected' : ''}>25</option><option value="50"${pagination.per_page === 50 ? ' selected' : ''}>50</option></select></span></li>`;
        };

        const requestPage = () => {
            pagination.page = 1;
            loadPage();
        };

        const loadPage = async () => {
            statusContainer.textContent = 'Consultando...';
            statusContainer.classList.remove('is-error');
            try {
                const response = await fetch('/admin/observability/get-page', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                    body: JSON.stringify({
                        minutes: Number(rangeSelect.value),
                        search: searchInput.value,
                        host_key: hostSelect.value,
                        environment: environmentSelect.value,
                        health: healthSelect.value,
                        sort_by: sortState.key,
                        sort_direction: sortState.direction,
                        pagination
                    })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                pagination = payload.pagination || pagination;
                renderFilterOptions(payload.filters || {});
                renderOverview(payload.totals || {});
                renderRows(payload.data || []);
                renderPagination();
                statusContainer.textContent = `Consulta realizada ${formatDateTime(payload.generated_at)} · ${formatNumber(pagination.total)} proyectos`;
            } catch (error) {
                statusContainer.textContent = 'No se pudo consultar la observabilidad';
                statusContainer.classList.add('is-error');
                tableBody.innerHTML = '<tr><td colspan="11" class="observability-empty">No fue posible cargar los datos.</td></tr>';
                paginationContainer.innerHTML = '';
            }
        };

        const exportData = () => {
            const params = new URLSearchParams({
                minutes: rangeSelect.value,
                search: searchInput.value,
                host_key: hostSelect.value,
                environment: environmentSelect.value,
                health: healthSelect.value,
                sort_by: sortState.key,
                sort_direction: sortState.direction
            });
            window.location.href = `/admin/observability/export?${params.toString()}`;
        };

        sortableHeaders.forEach((header) => {
            const button = header.querySelector('.observability-sort-button');
            if (!button) return;
            button.addEventListener('click', () => {
                if (sortState.key === header.dataset.sortKey) {
                    sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState.key = header.dataset.sortKey;
                    sortState.direction = 'asc';
                }
                pagination.page = 1;
                renderSortState();
                loadPage();
            });
        });

        [rangeSelect, hostSelect, environmentSelect, healthSelect].forEach((input) => input.addEventListener('change', requestPage));
        searchInput.addEventListener('change', requestPage);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') requestPage();
        });
        queryButton.addEventListener('click', requestPage);
        exportButton.addEventListener('click', exportData);
        paginationContainer.addEventListener('click', (event) => {
            const item = event.target.closest('[data-page]');
            if (!item || item.classList.contains('disabled')) return;
            const selectedPage = Number(item.dataset.page);
            if (selectedPage > 0 && selectedPage <= pagination.totalPages && selectedPage !== pagination.page) {
                pagination.page = selectedPage;
                loadPage();
            }
        });
        paginationContainer.addEventListener('change', (event) => {
            if (event.target.id !== 'db-pagination-per-page') return;
            pagination.per_page = Number(event.target.value);
            pagination.page = 1;
            loadPage();
        });
        tableBody.addEventListener('click', (event) => {
            const button = event.target.closest('.observability-detail-button');
            if (!button) return;
            const detail = document.getElementById(button.dataset.detailId);
            if (!detail) return;
            detail.hidden = !detail.hidden;
            button.classList.toggle('is-open', !detail.hidden);
        });

        renderSortState();
        loadPage();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
