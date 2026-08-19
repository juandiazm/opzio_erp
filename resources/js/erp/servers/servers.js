import { requestPage, exportData, loadPage } from './data.js';
import { initializeProjectConfig, openProjectConfig } from './project-config.js';
import { createServersState } from './state.js';
import { renderSortState } from './view.js';

(() => {
    const init = () => {
        const rangeSelect = document.getElementById('servers-range-select');
        const searchInput = document.getElementById('servers-search');
        const hostSelect = document.getElementById('servers-host-select');
        const environmentSelect = document.getElementById('servers-environment-select');
        const healthSelect = document.getElementById('servers-health-select');
        const queryButton = document.getElementById('servers-query-button');
        const exportButton = document.getElementById('servers-export-button');
        const statusContainer = document.getElementById('servers-status');
        const overviewContainer = document.getElementById('servers-overview');
        const projectsTable = document.getElementById('servers-projects-table');
        const tableBody = document.getElementById('servers-projects-table-body');
        const paginationContainer = document.getElementById('db-pagination');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!rangeSelect || !projectsTable || !tableBody) return;

        const state = createServersState({
            rangeSelect,
            searchInput,
            hostSelect,
            environmentSelect,
            healthSelect,
            queryButton,
            exportButton,
            statusContainer,
            overviewContainer,
            projectsTable,
            tableBody,
            paginationContainer,
            sortableHeaders: Array.from(projectsTable.querySelectorAll('th[data-sort-key]'))
        }, csrfToken);
        initializeProjectConfig(state);

        state.sortableHeaders.forEach((header) => {
            const button = header.querySelector('.servers-sort-button');
            if (!button) return;
            button.addEventListener('click', () => {
                if (state.sortState.key === header.dataset.sortKey) {
                    state.sortState.direction = state.sortState.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortState.key = header.dataset.sortKey;
                    state.sortState.direction = 'asc';
                }
                state.pagination.page = 1;
                renderSortState(state);
                loadPage(state);
            });
        });

        [rangeSelect, hostSelect, environmentSelect, healthSelect].forEach((input) => input.addEventListener('change', () => requestPage(state)));
        searchInput.addEventListener('change', () => requestPage(state));
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') requestPage(state);
        });
        queryButton.addEventListener('click', () => requestPage(state));
        exportButton.addEventListener('click', () => exportData(state));

        paginationContainer.addEventListener('click', (event) => {
            const item = event.target.closest('[data-page]');
            if (!item || item.classList.contains('disabled')) return;
            const selectedPage = Number(item.dataset.page);
            if (selectedPage > 0 && selectedPage <= state.pagination.totalPages && selectedPage !== state.pagination.page) {
                state.pagination.page = selectedPage;
                loadPage(state);
            }
        });
        paginationContainer.addEventListener('change', (event) => {
            if (event.target.id !== 'db-pagination-per-page') return;
            state.pagination.per_page = Number(event.target.value);
            state.pagination.page = 1;
            loadPage(state);
        });
        tableBody.addEventListener('click', (event) => {
            const configButton = event.target.closest('.servers-config-button');
            if (configButton) {
                openProjectConfig(state, Number(configButton.dataset.projectId));
                return;
            }
            const button = event.target.closest('.servers-detail-button');
            if (!button) return;
            const detail = document.getElementById(button.dataset.detailId);
            if (!detail) return;
            detail.hidden = !detail.hidden;
            button.classList.toggle('is-open', !detail.hidden);
        });

        renderSortState(state);
        loadPage(state);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
