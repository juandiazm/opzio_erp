import { formObject, postJson, setStatus } from './api.js';
import { jiraState } from './state.js';

const number = (value, digits = 0) => new Intl.NumberFormat('es-CO', {maximumFractionDigits: digits}).format(Number(value || 0));
const labels = (items) => items.map((item) => item.label);
const values = (items, key) => items.map((item) => Number(item[key] || 0));
const ISSUE_PAGE_SIZES = [5, 10, 50];
const DEFAULT_ISSUES_PER_PAGE = 10;
const issuePagination = {page: 1, perPage: DEFAULT_ISSUES_PER_PAGE};

function userInitials(label) {
	return String(label || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || '?';
}

function renderUserStoryPoints(root, users) {
	const list = root.querySelector('[data-jira-user-points-list]');
	const totalElement = root.querySelector('[data-jira-user-points-total]');
	if (!list || !totalElement) return;

	const items = (users || [])
		.filter((item) => Number(item.story_points || 0) > 0)
		.sort((left, right) => {
			const hoursDifference = Number(right.estimated_hours || 0) - Number(left.estimated_hours || 0);
			if (hoursDifference !== 0) return hoursDifference;
			const pointsDifference = Number(right.story_points || 0) - Number(left.story_points || 0);
			if (pointsDifference !== 0) return pointsDifference;
			return String(left.label || '').localeCompare(String(right.label || ''), 'es', {sensitivity: 'base'});
		});
	const total = items.reduce((sum, item) => sum + Number(item.story_points || 0), 0);
	totalElement.textContent = number(total, 2);
	list.innerHTML = '';
	if (!items.length) {
		const empty = document.createElement('div');
		empty.className = 'jira-empty';
		empty.textContent = 'No hay Story Points registrados en el rango.';
		list.append(empty);
		return;
	}

	items.forEach((item) => {
		const storyPoints = Number(item.story_points || 0);
		const name = item.label || 'Sin responsable';
		const card = document.createElement('article');
		card.className = 'jira-user-points-card';
		card.tabIndex = 0;
		card.setAttribute('aria-label', `${name}: ${number(storyPoints, 2)} Story Points, ${number(item.issues)} historias y ${number(item.estimated_hours, 2)} horas estimadas`);

		const visual = document.createElement('div');
		visual.className = 'jira-user-points-visual';
		const avatar = document.createElement('div');
		avatar.className = 'jira-user-points-avatar';
		const initials = document.createElement('span');
		initials.className = 'jira-user-points-initials';
		initials.textContent = userInitials(name);
		initials.hidden = Boolean(item.avatar);
		avatar.append(initials);
		if (item.avatar) {
			const image = document.createElement('img');
			image.src = item.avatar;
			image.alt = `Foto de ${name}`;
			image.loading = 'lazy';
			image.addEventListener('error', () => {
				image.remove();
				initials.hidden = false;
			}, {once: true});
			avatar.append(image);
		}
		const points = document.createElement('strong');
		points.className = 'jira-user-points-value';
		points.textContent = number(storyPoints, 2);
		const pointsUnit = document.createElement('small');
		pointsUnit.textContent = 'SP';
		points.append(pointsUnit);
		visual.append(avatar);

		const copy = document.createElement('div');
		copy.className = 'jira-user-points-copy';
		const nameElement = document.createElement('strong');
		nameElement.textContent = name;
		nameElement.title = name;
		const meta = document.createElement('small');
		meta.textContent = `${number(item.issues)} historias · ${number(item.estimated_hours, 2)} h estimadas`;
		copy.append(nameElement, points, meta);
		card.append(visual, copy);
		list.append(card);
	});
}

function renderUserHoursTable(root, users) {
	const body = root.querySelector('[data-jira-user-table]');
	if (!body) return;

	const items = (users || [])
		.filter((item) => Number(item.estimated_hours || 0) > 0 || Number(item.story_points || 0) > 0)
		.sort((left, right) => {
			const hoursDifference = Number(right.estimated_hours || 0) - Number(left.estimated_hours || 0);
			if (hoursDifference !== 0) return hoursDifference;
			const pointsDifference = Number(right.story_points || 0) - Number(left.story_points || 0);
			if (pointsDifference !== 0) return pointsDifference;
			return String(left.label || '').localeCompare(String(right.label || ''), 'es', {sensitivity: 'base'});
		});
	body.innerHTML = '';
	if (!items.length) {
		body.innerHTML = '<tr><td colspan="4" class="jira-empty">No hay esfuerzo registrado en el rango.</td></tr>';
		return;
	}

	items.forEach((item) => {
		const row = document.createElement('tr');
		const userCell = document.createElement('td');
		const identity = document.createElement('span');
		identity.className = 'jira-user-table-identity';
		const avatar = document.createElement('span');
		avatar.className = 'jira-user-table-avatar';
		const initials = document.createElement('span');
		initials.textContent = userInitials(item.label);
		initials.hidden = Boolean(item.avatar);
		avatar.append(initials);
		if (item.avatar) {
			const image = document.createElement('img');
			image.src = item.avatar;
			image.alt = `Foto de ${item.label || 'usuario'}`;
			image.loading = 'lazy';
			image.addEventListener('error', () => {
				image.remove();
				initials.hidden = false;
			}, {once: true});
			avatar.append(image);
		}
		const label = document.createElement('strong');
		label.textContent = item.label || 'Sin responsable';
		label.title = label.textContent;
		identity.append(avatar, label);
		userCell.append(identity);
		row.append(userCell);

		const projectCellValues = [number(item.estimated_hours, 2), number(item.story_points, 2), item.top_project?.label || 'Sin Story Points'];
		projectCellValues.forEach((value) => {
			const cell = document.createElement('td');
			cell.textContent = value;
			row.append(cell);
		});
		body.append(row);
	});
}

const jiraPiePercentages = {
	id: 'jiraPiePercentages',
	afterDatasetsDraw(chart) {
		const dataset = chart.data.datasets[0];
		const meta = chart.getDatasetMeta(0);
		const values = (dataset?.data || []).map((value) => Number(value || 0));
		const total = values.reduce((sum, value) => sum + value, 0);
		if (!total) return;

		const context = chart.ctx;
		meta.data.forEach((arc, index) => {
			if (!chart.getDataVisibility(index) || values[index] <= 0) return;
			const percentage = (values[index] / total) * 100;
			const properties = arc.getProps(['x', 'y', 'startAngle', 'endAngle', 'innerRadius', 'outerRadius'], true);
			const angle = (properties.startAngle + properties.endAngle) / 2;
			const radius = properties.innerRadius + (properties.outerRadius - properties.innerRadius) * .56;
			const label = `${number(percentage, 1)}%`;
			const fontSize = Math.max(8, Math.min(13, (properties.endAngle - properties.startAngle) * 22));
			context.save();
			context.font = `700 ${fontSize}px sans-serif`;
			context.fillStyle = '#ffffff';
			context.textAlign = 'center';
			context.textBaseline = 'middle';
			context.shadowColor = 'rgba(0, 0, 0, .38)';
			context.shadowBlur = 3;
			context.fillText(label, properties.x + Math.cos(angle) * radius, properties.y + Math.sin(angle) * radius);
			context.restore();
		});
	},
};

function initializeJiraMultiSelect(field, config = {}) {
	const select = field?.querySelector('select[multiple]');
	if (!field || !select || field.dataset.initialized === 'true') return;
	field.dataset.initialized = 'true';

	const wrapper = document.createElement('div');
	wrapper.className = 'jira-multiselect';
	const trigger = document.createElement('button');
	trigger.type = 'button';
	trigger.className = 'jira-multiselect__trigger';
	trigger.setAttribute('aria-haspopup', 'listbox');
	trigger.setAttribute('aria-expanded', 'false');
	const triggerText = document.createElement('span');
	triggerText.className = 'jira-multiselect__text';
	const triggerCount = document.createElement('span');
	triggerCount.className = 'jira-multiselect__count';
	const triggerIcon = document.createElement('i');
	triggerIcon.className = 'fa-light fa-chevron-down jira-multiselect__icon';
	triggerIcon.setAttribute('aria-hidden', 'true');
	trigger.append(triggerText, triggerCount, triggerIcon);

	const panel = document.createElement('div');
	panel.className = 'jira-multiselect__panel';
	panel.hidden = true;
	const searchWrapper = document.createElement('div');
	searchWrapper.className = 'jira-multiselect__search-wrapper';
	const searchIcon = document.createElement('i');
	searchIcon.className = 'fa-light fa-magnifying-glass jira-multiselect__search-icon';
	searchIcon.setAttribute('aria-hidden', 'true');
	const searchInput = document.createElement('input');
	searchInput.type = 'search';
	searchInput.className = 'jira-multiselect__search';
	searchInput.placeholder = config.searchPlaceholder || 'Buscar...';
	searchInput.setAttribute('aria-label', config.searchAriaLabel || 'Buscar opciones');
	searchWrapper.append(searchIcon, searchInput);

	const actions = document.createElement('div');
	actions.className = 'jira-multiselect__actions';
	const createAction = (label, iconName, handler) => {
		const action = document.createElement('button');
		action.type = 'button';
		action.className = 'jira-multiselect__action';
		action.addEventListener('click', handler);
		const icon = document.createElement('i');
		icon.className = `fa-light ${iconName}`;
		icon.setAttribute('aria-hidden', 'true');
		action.append(icon, document.createTextNode(label));
		return action;
	};

	const optionsList = document.createElement('ul');
	optionsList.className = 'jira-multiselect__options';
	optionsList.setAttribute('role', 'listbox');
	optionsList.setAttribute('aria-multiselectable', 'true');
	const selectedOptions = () => Array.from(select.options).filter((option) => option.selected);
	const normalizeSearch = (value) => String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

	const syncTrigger = () => {
		const selected = selectedOptions();
		const labels = selected.map((option) => option.textContent.trim());
		triggerText.textContent = labels.length === 0
			? (config.placeholder || 'Todos')
			: labels.length <= 2 ? labels.join(', ') : `${labels.length} ${config.selectedLabel || 'seleccionados'}`;
		triggerText.classList.toggle('is-placeholder', labels.length === 0);
		triggerCount.textContent = labels.length > 2 ? String(labels.length) : '';
		triggerCount.hidden = labels.length <= 2;
		trigger.setAttribute('aria-label', labels.length ? `${labels.length} ${config.itemLabel || 'opciones'} seleccionados` : (config.placeholder || 'Todos'));
	};

	const renderOptions = () => {
		const query = normalizeSearch(searchInput.value.trim());
		const options = Array.from(select.options).filter((option) => normalizeSearch(`${option.textContent} ${option.value}`).includes(query));
		optionsList.innerHTML = '';
		if (!options.length) {
			const empty = document.createElement('li');
			empty.className = 'jira-multiselect__empty';
			empty.textContent = config.emptyText || 'Sin resultados';
			optionsList.append(empty);
			return;
		}
		options.forEach((option) => {
			const item = document.createElement('li');
			const optionButton = document.createElement('button');
			optionButton.type = 'button';
			optionButton.className = 'jira-multiselect__option';
			optionButton.setAttribute('role', 'option');
			optionButton.setAttribute('aria-selected', option.selected ? 'true' : 'false');
			const check = document.createElement('span');
			check.className = 'jira-multiselect__check';
			const checkIcon = document.createElement('i');
			checkIcon.className = 'fa-light fa-check';
			checkIcon.setAttribute('aria-hidden', 'true');
			check.append(checkIcon);
			const label = document.createElement('span');
			label.textContent = option.textContent.trim();
			optionButton.append(check, label);
			optionButton.addEventListener('click', () => {
				option.selected = !option.selected;
				syncTrigger();
				renderOptions();
				select.dispatchEvent(new Event('change', {bubbles: true}));
			});
			item.append(optionButton);
			optionsList.append(item);
		});
	};

	const selectAll = () => {
		Array.from(select.options).forEach((option) => { option.selected = true; });
		syncTrigger();
		renderOptions();
		select.dispatchEvent(new Event('change', {bubbles: true}));
	};
	const clearAll = () => {
		Array.from(select.options).forEach((option) => { option.selected = false; });
		syncTrigger();
		renderOptions();
		select.dispatchEvent(new Event('change', {bubbles: true}));
	};
	actions.append(
		createAction('Seleccionar todos', 'fa-check-double', selectAll),
		createAction('Limpiar', 'fa-xmark', clearAll),
	);

	const close = () => {
		wrapper.classList.remove('is-open');
		panel.hidden = true;
		trigger.setAttribute('aria-expanded', 'false');
	};
	const open = () => {
		wrapper.classList.add('is-open');
		panel.hidden = false;
		trigger.setAttribute('aria-expanded', 'true');
		searchInput.value = '';
		renderOptions();
		window.requestAnimationFrame(() => searchInput.focus());
	};

	trigger.addEventListener('click', () => (panel.hidden ? open() : close()));
	trigger.addEventListener('keydown', (event) => {
		if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
			event.preventDefault();
			open();
		}
	});
	searchInput.addEventListener('input', renderOptions);
	searchInput.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			event.preventDefault();
			close();
			trigger.focus();
		}
	});
	document.addEventListener('click', (event) => {
		if (!wrapper.contains(event.target)) close();
	});
	select.addEventListener('change', () => {
		syncTrigger();
		renderOptions();
		if (typeof config.onChange === 'function') config.onChange();
	});

	field.insertBefore(wrapper, select);
	wrapper.append(select, trigger, panel);
	panel.append(searchWrapper, actions, optionsList);
	select.classList.add('jira-multiselect__native');
	const instanceId = `jira-multiselect-${select.name.replace(/[^a-z0-9]+/gi, '-')}`;
	trigger.setAttribute('aria-controls', `${instanceId}-options`);
	optionsList.id = `${instanceId}-options`;
	syncTrigger();
	renderOptions();
}

function appendIssuePageItem(container, label, {page = null, currentPage = null, className = '', disabled = false, onClick, ariaLabel = label} = {}) {
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

function renderIssuePagination(container, currentPage, totalPages, total, perPage, onPageChange, onPageSizeChange) {
	if (!container) return;
	container.innerHTML = '';
	container.hidden = total === 0;
	if (total === 0) return;

	appendIssuePageItem(container, '<', {className: 'page-item-back', disabled: currentPage === 1, onClick: () => onPageChange(currentPage - 1), ariaLabel: 'Pagina anterior'});
	const visiblePages = new Set();
	for (let page = 1; page <= totalPages; page += 1) {
		if (Math.abs(currentPage - page) <= 3 || page <= 3 || page >= totalPages - 2) visiblePages.add(page);
	}
	let previousPage = 0;
	[...visiblePages].forEach((page) => {
		if (page - previousPage > 1) appendIssuePageItem(container, '...', {className: 'page-item-ellipsis', disabled: true, ariaLabel: 'Paginas omitidas'});
		appendIssuePageItem(container, String(page), {className: 'page-item-number', page, currentPage, onClick: () => onPageChange(page), ariaLabel: `Pagina ${page}`});
		previousPage = page;
	});
	appendIssuePageItem(container, '>', {className: 'page-item-next', disabled: currentPage === totalPages, onClick: () => onPageChange(currentPage + 1), ariaLabel: 'Pagina siguiente'});

	if (total > 5) {
		const sizeItem = document.createElement('li');
		sizeItem.className = 'page-item';
		const sizeLink = document.createElement('span');
		sizeLink.className = 'page-link';
		const sizeSelect = document.createElement('select');
		sizeSelect.setAttribute('aria-label', 'Registros por pagina');
		ISSUE_PAGE_SIZES.forEach((size) => {
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

function renderIssuesTable(root, issues, requestedPage, requestedPerPage = issuePagination.perPage) {
	const body = root.querySelector('[data-jira-issues]');
	const pagination = root.querySelector('[data-jira-pagination]');
	const perPage = ISSUE_PAGE_SIZES.includes(Number(requestedPerPage)) ? Number(requestedPerPage) : DEFAULT_ISSUES_PER_PAGE;
	const totalPages = Math.max(1, Math.ceil(issues.length / perPage));
	const currentPage = Math.min(Math.max(requestedPage, 1), totalPages);
	const start = (currentPage - 1) * perPage;
	const pageIssues = issues.slice(start, start + perPage);
	issuePagination.page = currentPage;
	issuePagination.perPage = perPage;

	body.innerHTML = '';
	pageIssues.forEach((issue) => {
		const row = document.createElement('tr');
		[issue.key, issue.project, issue.epic].forEach((value) => {
			const cell = document.createElement('td');
			cell.textContent = value || '-';
			row.append(cell);
		});

		const assigneeCell = document.createElement('td');
		const assignee = document.createElement('span');
		assignee.className = 'jira-assignee';
		const assigneeName = issue.assignee || 'Sin responsable';
		if (issue.assignee_avatar) {
			const image = document.createElement('img');
			image.src = issue.assignee_avatar;
			image.alt = `Foto de ${assigneeName}`;
			image.loading = 'lazy';
			image.addEventListener('error', () => image.remove(), {once: true});
			assignee.append(image);
		}
		const assigneeLabel = document.createElement('span');
		assigneeLabel.textContent = assigneeName;
		assignee.append(assigneeLabel);
		assigneeCell.append(assignee);
		row.append(assigneeCell);

		const hoursCell = document.createElement('td');
		const hoursInput = document.createElement('input');
		hoursInput.type = 'number';
		hoursInput.min = '0';
		hoursInput.step = '0.01';
		hoursInput.value = Number(issue.estimated_hours || 0).toFixed(2);
		hoursInput.className = 'jira-issue-hours-input';
		hoursInput.dataset.jiraIssueHours = 'true';
		hoursInput.dataset.jiraIssueId = issue.id;
		hoursInput.setAttribute('aria-label', `Horas estimadas para ${issue.key}`);
		hoursCell.append(hoursInput);
		row.append(hoursCell);

		[issue.status || 'Sin estado', number(issue.story_points, 2), issue.resolved_at || '-'].forEach((value) => {
			const cell = document.createElement('td');
			cell.textContent = value;
			row.append(cell);
		});
		body.append(row);
	});
	if (!pageIssues.length) body.innerHTML = '<tr><td colspan="8" class="jira-empty">No hay historias de usuario en el rango.</td></tr>';
	renderIssuePagination(
		pagination,
		currentPage,
		totalPages,
		issues.length,
		perPage,
		(page) => renderIssuesTable(root, issues, page, perPage),
		(nextPerPage) => renderIssuesTable(root, issues, 1, nextPerPage),
	);
}

function renderChart(name, items, datasets, type = 'bar') {
	const canvas = document.querySelector(`[data-jira-chart="${name}"]`);
	if (!canvas || typeof Chart === 'undefined') return;
	jiraState.charts[name]?.destroy();
	let options;
	if (type === 'pie') {
		options = {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: true,
					position: 'right',
					labels: {
						generateLabels: (chart) => {
							const colors = chart.data.datasets[0]?.backgroundColor || [];
							return items.map((project, index) => ({
								text: `${project.label || 'Sin proyecto'} · ${number(project.story_points, 2)} SP`,
								fillStyle: Array.isArray(colors) ? colors[index] : colors,
								strokeStyle: '#ffffff',
								lineWidth: 2,
								hidden: !chart.getDataVisibility(index),
								index,
								datasetIndex: 0,
							}));
						},
					},
				},
				tooltip: {
					callbacks: {
						label: (context) => {
							const project = items[context.dataIndex];
							const total = items.reduce((sum, item) => sum + Number(item.story_points || 0), 0);
							const percentage = total > 0 ? (Number(project?.story_points || 0) / total) * 100 : 0;
							return `${project?.label || 'Sin proyecto'}: ${number(project?.story_points, 2)} SP · ${number(percentage, 1)}%`;
						},
					},
				},
			},
		};
	} else {
		options = {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'top'}}, scales: {y: {beginAtZero: true}}};
	}
	jiraState.charts[name] = new Chart(canvas, {type, data: {labels: labels(items), datasets}, options, plugins: type === 'pie' ? [jiraPiePercentages] : []});
}

export async function initializeJiraDashboard(root) {
	const form = root.querySelector('[data-jira-dashboard-form]');
	if (!form || form.dataset.initialized === 'true') return;
	form.dataset.initialized = 'true';
	initializeJiraMultiSelect(form.querySelector('[data-jira-project-filter]'), {
		placeholder: 'Todos los proyectos',
		searchPlaceholder: 'Buscar proyecto...',
		searchAriaLabel: 'Buscar proyectos',
		emptyText: 'Sin proyectos coincidentes',
		selectedLabel: 'proyectos seleccionados',
		itemLabel: 'proyectos',
	});
	initializeJiraMultiSelect(form.querySelector('[data-jira-epic-filter]'), {
		placeholder: 'Todas las epicas',
		searchPlaceholder: 'Buscar epica...',
		searchAriaLabel: 'Buscar epicas',
		emptyText: 'Sin epicas coincidentes',
		selectedLabel: 'epicas seleccionadas',
		itemLabel: 'epicas',
	});
	initializeJiraMultiSelect(form.querySelector('[data-jira-user-filter]'), {
		placeholder: 'Todos los usuarios',
		searchPlaceholder: 'Buscar usuario...',
		searchAriaLabel: 'Buscar usuarios',
		emptyText: 'Sin usuarios coincidentes',
		selectedLabel: 'usuarios seleccionados',
		itemLabel: 'usuarios',
	});
	initializeJiraMultiSelect(form.querySelector('[data-jira-status-filter]'), {
		placeholder: 'Todos los estados',
		searchPlaceholder: 'Buscar estado...',
		searchAriaLabel: 'Buscar estados',
		emptyText: 'Sin estados coincidentes',
		selectedLabel: 'estados seleccionados',
		itemLabel: 'estados',
	});
	const status = root.querySelector('[data-jira-dashboard-status]');
	try {
		const catalog = await (await fetch('/admin/jira/relations/data', {headers: {'Accept': 'application/json'}})).json();
		const epics = catalog.data?.epics || [];
		const epicSelect = form.querySelector('[data-jira-epic-native]');
		const projectSelect = form.querySelector('[data-jira-project-native]');
		const populateEpics = () => {
			const selectedProjects = new Set(Array.from(projectSelect.selectedOptions).map((option) => String(option.value)));
			const selectedEpics = new Set(Array.from(epicSelect.selectedOptions).map((option) => String(option.value)));
			epicSelect.innerHTML = '';
			epics.filter((epic) => selectedProjects.size === 0 || selectedProjects.has(String(epic.jira_project_id))).forEach((epic) => {
				const option = document.createElement('option');
				option.value = epic.id;
				option.textContent = `${epic.issue_key} · ${epic.summary}`;
				option.selected = selectedEpics.has(String(epic.id));
				epicSelect.append(option);
			});
			epicSelect.dispatchEvent(new Event('change', {bubbles: true}));
		};
		projectSelect.addEventListener('change', populateEpics);
		populateEpics();
	} catch (error) { setStatus(status, error.message, 'error'); }
	let issues = [];
	const load = async () => {
		const button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		setStatus(status, 'Consultando metricas...');
		try {
			const data = await postJson('/admin/jira/dashboard/data', formObject(form));
			const summary = data.summary || {};
			root.querySelector('[data-metric="story_points"]').textContent = number(summary.story_points, 2);
			root.querySelector('[data-metric="story_issues"]').textContent = number(summary.story_issues);
			root.querySelector('[data-metric="estimated_hours"]').textContent = number(summary.estimated_hours, 2);
			root.querySelector('[data-metric="active_projects"]').textContent = number(summary.active_projects);
			root.querySelector('[data-metric="active_users"]').textContent = number(summary.active_users);
			root.querySelector('[data-metric="last_sync"]').textContent = summary.last_sync ? new Date(summary.last_sync).toLocaleDateString('es-CO') : '-';
			root.querySelector('[data-metric="freshness"]').textContent = summary.last_sync ? 'Ultima sincronizacion' : 'Sin sincronizacion';
			const projects = (data.projects || [])
				.filter((project) => Number(project.story_points || 0) > 0)
				.sort((left, right) => Number(right.story_points || 0) - Number(left.story_points || 0))
				.slice(0, 10);
			const users = data.users || [];
			renderUserStoryPoints(root, users);
			renderUserHoursTable(root, users);
			renderChart('projects', projects, [{label: 'Story Points', data: values(projects, 'story_points'), backgroundColor: ['#220245', '#885FAE', '#F36803', '#16A34A', '#C2410C', '#2563EB', '#0F766E', '#A21CAF', '#B45309', '#4F46E5'], borderColor: '#ffffff', borderWidth: 2}], 'pie');
			issues = Array.isArray(data.issues) ? data.issues : [];
			renderIssuesTable(root, issues, 1);
			root.querySelector('[data-jira-issue-count]').textContent = number(issues.length);
			setStatus(status, 'Metricas actualizadas.', 'success');
		} catch (error) { setStatus(status, error.message, 'error'); }
		finally { button.disabled = false; }
	};
	root.querySelector('[data-jira-issues]').addEventListener('change', async (event) => {
		const input = event.target.closest('[data-jira-issue-hours="true"]');
		if (!input) return;
		input.disabled = true;
		try {
			await postJson('/admin/jira/dashboard/issue-hours', {jira_issue_id: input.dataset.jiraIssueId, estimated_hours: input.value});
			setStatus(status, 'Horas estimadas actualizadas.', 'success');
			await load();
		} catch (error) {
			setStatus(status, error.message, 'error');
			input.disabled = false;
		}
	});
	form.addEventListener('submit', (event) => { event.preventDefault(); load(); });
	load();
}
