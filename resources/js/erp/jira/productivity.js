import { getJson, postJson } from './api.js';

const PRODUCTIVITY_PAGE_SIZES = [5, 10, 50];
const productivityPagination = {page: 1, perPage: 10, search: ''};

function toast(message, type = 'success') {
	if (typeof window.Toastify === 'function') {
		window.Toastify({
			text: message,
			duration: 3200,
			gravity: 'bottom',
			close: true,
			style: {background: '#ffffff', color: type === 'success' ? '#220245' : '#a0342a'},
		}).showToast();
		return;
	}
	if (type === 'success' && typeof window.alertSuccess === 'function') window.alertSuccess(message);
}

function dialogError(message) {
	if (typeof window.swallMessage === 'function') {
		window.swallMessage('No se pudo guardar', message, 'error', null, null, 4200);
		return;
	}
	window.Swal?.fire({title: 'No se pudo guardar', text: message, icon: 'error'});
}

function formatMultiplier(value) {
	return Number(value || 0).toFixed(2);
}

function updateExample(row) {
	const input = row.querySelector('[data-jira-productivity-input]');
	const example = row.querySelector('[data-jira-productivity-example]');
	if (input && example) example.textContent = `1 SP = ${formatMultiplier(input.value)} h`;
}

function appendPageItem(container, label, {page = null, currentPage = null, className = '', disabled = false, onClick, ariaLabel = label} = {}) {
	const item = document.createElement('li');
	item.className = `page-item ${className}`.trim();
	if (disabled) item.classList.add('disabled');
	const link = document.createElement('button');
	link.type = 'button';
	link.className = 'page-link';
	link.textContent = label;
	link.disabled = disabled;
	link.setAttribute('aria-label', ariaLabel);
	if (page === currentPage) {
		link.classList.add('active');
		link.setAttribute('aria-current', 'page');
	}
	if (!disabled && onClick) link.addEventListener('click', onClick);
	item.append(link);
	container.append(item);
}

function renderPagination(container, total, currentPage, perPage, onPageChange, onPageSizeChange) {
	if (!container) return;
	container.innerHTML = '';
	container.hidden = total === 0;
	if (total === 0) return;
	const totalPages = Math.max(1, Math.ceil(total / perPage));
	appendPageItem(container, '<', {className: 'page-item-back', disabled: currentPage === 1, onClick: () => onPageChange(currentPage - 1), ariaLabel: 'Página anterior'});
	for (let page = 1; page <= totalPages; page += 1) {
		appendPageItem(container, String(page), {className: 'page-item-number', page, currentPage, onClick: () => onPageChange(page), ariaLabel: `Página ${page}`});
	}
	appendPageItem(container, '>', {className: 'page-item-next', disabled: currentPage === totalPages, onClick: () => onPageChange(currentPage + 1), ariaLabel: 'Página siguiente'});

	if (total > 5) {
		const sizeItem = document.createElement('li');
		sizeItem.className = 'page-item';
		const sizeLink = document.createElement('span');
		sizeLink.className = 'page-link';
		const sizeSelect = document.createElement('select');
		sizeSelect.setAttribute('aria-label', 'Registros por página');
		PRODUCTIVITY_PAGE_SIZES.forEach((size) => {
			const option = document.createElement('option');
			option.value = String(size);
			option.textContent = String(size);
			option.selected = size === perPage;
			sizeSelect.append(option);
		});
		sizeSelect.addEventListener('change', () => onPageSizeChange(Number(sizeSelect.value)));
		sizeLink.append(sizeSelect);
		sizeItem.append(sizeLink);
		container.append(sizeItem);
	}
}

export function initializeJiraProductivity(root) {
	const table = root.querySelector('[data-jira-productivity-table]');
	if (!table || root.dataset.jiraProductivityInitialized === 'true') return;
	root.dataset.jiraProductivityInitialized = 'true';
	const body = root.querySelector('[data-jira-productivity-body]');
	const search = root.querySelector('[data-jira-productivity-search]');
	const count = root.querySelector('[data-jira-productivity-count]');
	const pagination = root.querySelector('[data-jira-productivity-pagination]');
	let requestNumber = 0;
	let searchTimeout = null;

	const renderProjectRows = (projects) => {
		body.innerHTML = '';
		if (!projects.length) {
			body.innerHTML = '<tr><td colspan="4" class="jira-empty">No hay proyectos que coincidan con la búsqueda.</td></tr>';
			return;
		}
		projects.forEach((project) => {
			const row = document.createElement('tr');
			row.dataset.jiraProductivityRow = 'true';
			row.dataset.projectId = project.id;

			const projectCell = document.createElement('td');
			const projectCopy = document.createElement('div');
			projectCopy.className = 'jira-productivity-project';
			const projectName = document.createElement('strong');
			projectName.textContent = project.name;
			const projectKey = document.createElement('small');
			projectKey.textContent = project.project_key;
			projectCopy.append(projectName, projectKey);
			projectCell.append(projectCopy);

			const relationCell = document.createElement('td');
			const rate = document.createElement('label');
			rate.className = 'jira-productivity-rate';
			const rateLabel = document.createElement('span');
			rateLabel.className = 'visually-hidden';
			rateLabel.textContent = 'Horas por Story Point';
			const input = document.createElement('input');
			input.type = 'number';
			input.min = '0.01';
			input.max = '9999';
			input.step = '0.01';
			input.value = formatMultiplier(project.story_point_hours_multiplier);
			input.dataset.jiraProductivityInput = 'true';
			input.setAttribute('aria-label', `Horas por Story Point para ${project.project_key}`);
			const unit = document.createElement('span');
			unit.textContent = 'h / SP';
			rate.append(rateLabel, input, unit);
			relationCell.append(rate);

			const exampleCell = document.createElement('td');
			const example = document.createElement('span');
			example.className = 'jira-productivity-example';
			example.dataset.jiraProductivityExample = 'true';
			example.textContent = `1 SP = ${formatMultiplier(project.story_point_hours_multiplier)} h`;
			exampleCell.append(example);

			const actionCell = document.createElement('td');
			actionCell.className = 'text-end';
			const action = document.createElement('div');
			action.className = 'jira-productivity-action';
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'btn jira-productivity-save';
			button.dataset.jiraProductivitySave = 'true';
			button.title = `Guardar productividad de ${project.project_key}`;
			button.setAttribute('aria-label', `Guardar productividad de ${project.project_key}`);
			const icon = document.createElement('i');
			icon.className = 'fa-light fa-floppy-disk';
			icon.setAttribute('aria-hidden', 'true');
			button.append(icon);
			const rowStatus = document.createElement('span');
			rowStatus.className = 'visually-hidden';
			rowStatus.dataset.jiraProductivityRowStatus = 'true';
			rowStatus.setAttribute('aria-live', 'polite');
			rowStatus.textContent = 'Sin cambios';
			action.append(button, rowStatus);
			actionCell.append(action);

			row.append(projectCell, relationCell, exampleCell, actionCell);
			body.append(row);
			updateExample(row);
			input.addEventListener('input', () => updateExample(row));
		});
	};

	const setLoading = () => {
		body.innerHTML = '<tr><td colspan="4" class="jira-empty">Cargando proyectos...</td></tr>';
		pagination.hidden = true;
	};

	const loadProjects = async () => {
		const currentRequest = ++requestNumber;
		setLoading();
		const params = new URLSearchParams({page: String(productivityPagination.page), per_page: String(productivityPagination.perPage), search: productivityPagination.search});
		try {
			const result = await getJson(`/admin/jira/productivity/data?${params.toString()}`);
			if (currentRequest !== requestNumber) return;
			const serverPagination = result.pagination || {};
			productivityPagination.page = Number(serverPagination.page || 1);
			productivityPagination.perPage = Number(serverPagination.per_page || productivityPagination.perPage);
			if (count) count.textContent = `${Number(serverPagination.total || 0)} proyectos`;
			renderProjectRows(Array.isArray(result.projects) ? result.projects : []);
			renderPagination(pagination, Number(serverPagination.total || 0), productivityPagination.page, productivityPagination.perPage, (page) => {
				productivityPagination.page = page;
				loadProjects();
			}, (perPage) => {
				productivityPagination.perPage = perPage;
				productivityPagination.page = 1;
				loadProjects();
			});
		} catch (error) {
			if (currentRequest !== requestNumber) return;
			body.innerHTML = '<tr><td colspan="4" class="jira-empty">No se pudieron cargar los proyectos.</td></tr>';
			dialogError(error.message);
		}
	};

	search?.addEventListener('input', () => {
		productivityPagination.search = search.value.trim();
		productivityPagination.page = 1;
		window.clearTimeout(searchTimeout);
		searchTimeout = window.setTimeout(loadProjects, 250);
	});

	table.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-jira-productivity-save]');
		if (!button) return;
		const row = button.closest('[data-jira-productivity-row]');
		const input = row?.querySelector('[data-jira-productivity-input]');
		const rowStatus = row?.querySelector('[data-jira-productivity-row-status]');
		if (!row || !input || !input.reportValidity()) return;
		button.disabled = true;
		button.classList.add('is-loading');
		if (rowStatus) rowStatus.textContent = 'Guardando...';
		try {
			const result = await postJson('/admin/jira/productivity/project', {
				jira_project_id: row.dataset.projectId,
				story_point_hours_multiplier: input.value,
			});
			input.value = formatMultiplier(result.story_point_hours_multiplier ?? input.value);
			updateExample(row);
			if (rowStatus) rowStatus.textContent = `${result.recalculated_issues || 0} historias actualizadas`;
			toast(result.message || 'Productividad actualizada.');
		} catch (error) {
			if (rowStatus) rowStatus.textContent = 'No guardado';
			dialogError(error.message);
		} finally {
			button.disabled = false;
			button.classList.remove('is-loading');
		}
	});
	loadProjects();
}