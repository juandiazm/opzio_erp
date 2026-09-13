import { formObject, getJson, postJson, setStatus } from './api.js';
import { initializeJiraMultiSelect } from './dashboard.js';
import { destroyPdfViewer, downloadPdf, fullscreenPdf, initPdfViewer, loadPdfViewer, pdfNextPage, pdfPrevPage, pdfZoomIn, pdfZoomOut, printPdf, sharePdf } from '../../pdf-viewer.js';

const reportStatusLabels = {generated: 'Generado', generating: 'En proceso', failed: 'Con error'};
const reportIntentionLabels = {
	internal_improvement: 'Mejora interna',
	client_report: 'Resultados para cliente',
	executive_summary: 'Resumen ejecutivo',
	team_capacity: 'Capacidad y distribucion del equipo',
	project_progress: 'Avance por proyecto',
	delivery_risks: 'Riesgos de entrega',
	unplanned_work: 'Calidad y trabajo no planificado',
};
const pageSizes = [5, 10, 25, 50];

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
const reportPdfUrl = (uniqueId) => `/admin/jira/reports/${encodeURIComponent(uniqueId)}/pdf`;
const statusLabel = (status) => reportStatusLabels[status] || status || '-';
const intentionLabel = (intention) => reportIntentionLabels[intention] || intention || '-';
const cleanReportLabel = (value) => String(value ?? '')
	.replace(/\bJira\b|Informe\s+para\s+el\s+cliente/gi, '')
	.replace(/\s{2,}/g, ' ')
	.replace(/^[\s\-:·]+|[\s\-:·]+$/g, '')
	.trim() || 'Informe de resultados';

function formatDate(value, includeTime = false) {
	const raw = String(value ?? '');
	const match = raw.match(/(\d{4})-(\d{2})-(\d{2})/);
	if (!match) return '-';
	const date = `${match[3]}/${match[2]}/${match[1]}`;
	if (!includeTime) return date;
	const time = raw.match(/(?:T|\s)(\d{2}:\d{2})/);
	return time ? `${date} ${time[1]}` : date;
}

function renderList(items, formatter = (item) => item) {
	const values = Array.isArray(items) ? items.map(formatter).filter((item) => String(item ?? '').trim() !== '') : [];
	return values.length
		? `<ul>${values.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`
		: '<p class="jira-empty">Sin datos.</p>';
}

function renderReportScope(report, snapshot) {
	const filters = snapshot?.report?.filters || {};
	const labels = (items, fallback) => {
		const values = Array.isArray(items)
			? items.map((item) => typeof item === 'object' ? item.label : item).filter((item) => String(item ?? '').trim() !== '')
			: [];
		return values.length ? values.join(', ') : fallback;
	};
	const projectFallback = report.project
		? `${report.project.project_key || ''} - ${report.project.name || ''}`.replace(/^ - | - $/g, '')
		: 'Todos los proyectos';
	const epicFallback = report.epic
		? `${report.epic.issue_key || ''} - ${report.epic.summary || ''}`.replace(/^ - | - $/g, '')
		: 'Todas las epicas';
	const items = report.intention === 'client_report' || snapshot?.report?.intention === 'client_report'
		? [['Proyectos', labels(filters.projects, projectFallback)]]
		: [
			['Proyectos', labels(filters.projects, projectFallback)],
			['Epicas', labels(filters.epics, epicFallback)],
			['Usuarios', labels(filters.users, 'Todos los usuarios')],
			['Estados', labels(filters.statuses, 'Todos los estados')],
		];
	return `<section class="jira-report-filter-summary" aria-label="Filtros aplicados"><span class="jira-panel-kicker">Filtros aplicados</span><div>${items.map(([label, value]) => `<article><small>${label}</small><strong>${escapeHtml(value)}</strong></article>`).join('')}</div></section>`;
}

function renderClientReportReading(content, snapshot) {
	const results = Array.isArray(content.documented_results) ? content.documented_results : [];
	const notes = Array.isArray(content.interpretation_notes) ? content.interpretation_notes : [];
	const closing = Array.isArray(content.closing) ? content.closing : [];
	const activities = Array.isArray(content.activity_summaries) ? content.activity_summaries : [];
	const paragraphs = (items, fallback) => {
		const values = Array.isArray(items) ? items.map((item) => String(item ?? '').trim()).filter(Boolean) : [];
		return values.length ? values.map((item) => `<p>${escapeHtml(item)}</p>`).join('') : `<p>${escapeHtml(fallback)}</p>`;
	};
	const resultSections = results.length
		? results.map((result) => `<h3>${escapeHtml(result.title || 'Resultados documentados')}</h3>${paragraphs(result.paragraphs, 'Sin detalle documentado.')}`).join('')
		: '<p>Sin resultados narrativos documentados.</p>';
	const activityRows = activities.map((activity) => `<tr><td><strong>${escapeHtml(activity.summary || 'Sin titulo')}</strong><small>${escapeHtml(activity.issue_key || '-')}</small></td><td>${escapeHtml(activity.issue_type || 'Sin clasificar')}<br>${escapeHtml(activity.priority || 'No registrada')}<br>${activity.effort === null || activity.effort === undefined ? 'Sin estimacion registrada' : `Esfuerzo: ${escapeHtml(activity.effort)}`}</td><td>${escapeHtml(activity.result_summary || 'No se documentaron resultados adicionales.')}</td></tr>`).join('');
	const activityBlock = activities.length
		? `<h3>Detalle de actividades documentadas</h3><div class="jira-client-activity-wrap"><table class="jira-client-activity-table"><thead><tr><th>Actividad</th><th>Referencia</th><th>Descripcion de resultados</th></tr></thead><tbody>${activityRows}</tbody></table></div>`
		: '';
	return `<section class="jira-report-reading jira-client-report-reading"><h3>Resumen ejecutivo</h3><p>${escapeHtml(content.executive_summary || 'Sin resumen.')}</p><h3>Resultados documentados</h3>${resultSections}<h3>Esfuerzo registrado</h3><p>${escapeHtml(content.effort_analysis || 'Sin analisis de esfuerzo disponible.')}</p><h3>Consideraciones de interpretacion</h3>${paragraphs(notes, 'El Esfuerzo corresponde al valor de estimated_hours registrado.') }<h3>Cierre</h3>${paragraphs(closing, content.executive_summary || 'Sin cierre.')}${activityBlock}</section>`;
}

function renderPdfPreview(report) {
	const title = cleanReportLabel(report.report_data?.report_title || report.title);
	return `<section class="jira-pdf-preview" aria-labelledby="jira-pdf-preview-title">
		<div class="jira-pdf-preview-header">
			<div><span class="jira-panel-kicker">Documento almacenado</span><h3 id="jira-pdf-preview-title">${escapeHtml(title)}</h3></div>
			<button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-preview-open="${escapeHtml(report.unique_id)}"><i class="fa-light fa-file-pdf" aria-hidden="true"></i> Abrir vista previa</button>
		</div>
	</section>`;
}

function renderDetail(container, data) {
	destroyPdfViewer();
	const report = data.report || {};
	const content = report.report_data || {};
	const snapshot = report.data_snapshot || {};
	const summary = snapshot.summary || {};
	const isGenerated = report.status === 'generated';
	const projectName = report.project ? `${report.project.project_key || ''} · ${report.project.name || ''}`.replace(/^ · | · $/g, '') : 'Todos los proyectos';
	const reportTitle = cleanReportLabel(content.report_title || report.title);
	const recurrence = report.recurrence;
	const recurrenceUnitLabels = {days: 'dias', months: 'meses'};
	const recurrenceSchedule = recurrence?.frequency_unit === 'months' && recurrence.execution_day
		? `Cada ${escapeHtml(recurrence.frequency_value)} ${recurrenceUnitLabels[recurrence.frequency_unit]} el dia ${escapeHtml(recurrence.execution_day)}`
		: `Cada ${escapeHtml(recurrence?.frequency_value)} ${recurrenceUnitLabels[recurrence?.frequency_unit] || recurrence?.frequency_unit}`;
	const recurrenceBlock = recurrence
		? `<section class="jira-report-recurrence-summary"><span class="jira-panel-kicker">Configuracion recurrente</span><strong>${recurrenceSchedule}</strong><span>Rango por ejecucion: ultimos ${escapeHtml(recurrence.range_value)} ${recurrenceUnitLabels[recurrence.range_unit] || recurrence.range_unit} completos anteriores a la generacion. Proxima generacion: ${formatDate(recurrence.next_run_at, true)}</span></section>`
		: '';
	const recommendations = Array.isArray(content.recommendations)
		? content.recommendations.map((item) => typeof item === 'object' ? `${item.title || 'Recomendacion'}: ${item.action || item.rationale || ''}` : item)
		: [];
	const errorBlock = report.error_message ? `<div class="jira-report-error" role="alert">${escapeHtml(report.error_message)}</div>` : '';
	const pdfBlock = isGenerated ? renderPdfPreview(report) : `<div class="jira-report-unavailable"><i class="fa-light fa-file-circle-exclamation" aria-hidden="true"></i><p>La vista previa PDF estara disponible cuando el reporte este generado.</p></div>`;
	const scopeBlock = renderReportScope(report, snapshot);
	const isClientReport = report.intention === 'client_report' || content.format === 'client_report';
	const clientSource = snapshot.client_report || {};
	const detailMetrics = isClientReport
		? `<div class="jira-detail-grid"><article><span>Registros</span><strong>${escapeHtml(clientSource.total_records ?? summary.issue_count ?? 0)}</strong></article><article><span>Esfuerzo</span><strong>${escapeHtml(clientSource.total_effort ?? '-')}</strong></article><article><span>Sin esfuerzo registrado</span><strong>${escapeHtml(clientSource.unestimated_records ?? 0)}</strong></article></div>`
		: `<div class="jira-detail-grid"><article><span>Story Points</span><strong>${escapeHtml(summary.story_points ?? '-')}</strong></article><article><span>Issues completados</span><strong>${escapeHtml(summary.completed_issues ?? 0)}</strong></article><article><span>Horas registradas</span><strong>${escapeHtml(summary.worklog_hours ?? '-')}</strong></article></div>`;
	const readingBlock = report.intention === 'client_report' || content.format === 'client_report'
		? renderClientReportReading(content, snapshot)
		: `<section class="jira-report-reading"><h3>Resumen ejecutivo</h3><p>${escapeHtml(content.executive_summary || 'Sin resumen.')}</p><h3>Objetivo</h3><p>${escapeHtml(content.objective_alignment || 'Sin alineacion documentada.')}</p><h3>Hallazgos</h3>${renderList(content.key_findings)}<h3>Esfuerzo</h3><p>${escapeHtml(content.effort_analysis || '')}</p><h3>Proyectos</h3><p>${escapeHtml(content.project_analysis || '')}</p><h3>Usuarios</h3><p>${escapeHtml(content.user_analysis || '')}</p><h3>Epicas</h3><p>${escapeHtml(content.epic_analysis || '')}</p><h3>Riesgos</h3>${renderList(content.risks)}<h3>Recomendaciones</h3>${renderList(recommendations)}<h3>Proximos pasos</h3>${renderList(content.next_steps)}<h3>Limitaciones</h3>${renderList(content.limitations)}</section>`;

	container.hidden = false;
	container.innerHTML = `<div class="jira-report-detail-header">
		<button class="btn btn-light jira-report-back" type="button" data-jira-report-back><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Atras</button>
		<div class="jira-report-detail-title"><span class="jira-panel-kicker">Detalle del reporte</span><h2>${escapeHtml(reportTitle)}</h2><p>${escapeHtml(projectName)} · ${formatDate(report.from_date)} a ${formatDate(report.to_date)}</p></div>
		<div class="jira-report-detail-actions"><span class="jira-status-pill jira-status-${escapeHtml(report.status)}">${escapeHtml(statusLabel(report.status))}</span>${isGenerated ? `<button class="btn btn-outline-secondary" type="button" data-jira-report-preview-open="${escapeHtml(report.unique_id)}"><i class="fa-light fa-file-pdf" aria-hidden="true"></i> PDF</button>` : ''}<button class="btn btn-outline-secondary" type="button" data-jira-report-regenerate="${escapeHtml(report.unique_id)}"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i> Regenerar</button></div>
	</div>
	${errorBlock}
	${detailMetrics}
	${scopeBlock}
	${recurrenceBlock}
	${readingBlock}
	${pdfBlock}`;
	container.scrollIntoView({behavior: 'smooth', block: 'start'});
}

function renderRows(container, reports) {
	if (!reports.length) {
		container.innerHTML = '<tr><td colspan="7" class="jira-empty">No hay reportes para los filtros seleccionados.</td></tr>';
		return;
	}
	container.innerHTML = reports.map((report) => {
		const project = report.project || {};
		const epic = report.epic || {};
		const uniqueId = escapeHtml(report.unique_id);
		const title = escapeHtml(cleanReportLabel(report.title));
		const generated = report.status === 'generated';
		const recurrenceIndicator = report.recurrence_id ? '<span class="jira-report-recurrence-indicator" title="Reporte recurrente" aria-label="Reporte recurrente"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i></span>' : '';
		return `<tr data-report-id="${uniqueId}"><td class="text-start"><div class="jira-report-table-identity"><div class="jira-report-title-line"><strong>${title}</strong>${recurrenceIndicator}</div><small>${uniqueId}</small></div></td><td class="text-start"><div class="jira-report-table-meta"><strong>${escapeHtml(project.project_key || 'Todos los proyectos')}</strong><small>${escapeHtml(project.name || 'Alcance global')}</small></div></td><td class="text-start">${escapeHtml(intentionLabel(report.intention))}</td><td class="text-center"><div class="jira-report-table-meta"><span>${formatDate(report.from_date)} a ${formatDate(report.to_date)}</span><small>${escapeHtml(epic.issue_key || 'Todas las epicas')}</small></div></td><td class="text-center">${formatDate(report.updated_at || report.created_at, true)}</td><td class="text-center"><span class="jira-status-pill jira-status-${escapeHtml(report.status)}">${escapeHtml(statusLabel(report.status))}</span></td><td class="text-end"><div class="jira-row-actions"><button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-view="${uniqueId}" aria-label="Ver reporte ${title}" title="Ver reporte"><i class="fa-light fa-eye" aria-hidden="true"></i></button>${generated ? `<button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-preview="${uniqueId}" aria-label="Abrir PDF de ${title}" title="Abrir vista previa"><i class="fa-light fa-file-pdf" aria-hidden="true"></i></button>` : ''}<button class="btn btn-sm btn-outline-secondary" type="button" data-jira-report-regenerate="${uniqueId}" aria-label="Regenerar ${title}" title="Regenerar"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i></button><button class="btn btn-sm btn-outline-danger" type="button" data-jira-report-delete="${uniqueId}" aria-label="Eliminar ${title}" title="Eliminar"><i class="fa-light fa-trash-can" aria-hidden="true"></i></button></div></td></tr>`;
	}).join('');
}

function renderPagination(container, pagination, onPageChange, onPageSizeChange) {
	const totalPages = Number(pagination.totalPages || 0);
	const currentPage = Number(pagination.page || 1);
	container.innerHTML = '';
	container.hidden = totalPages === 0;
	if (totalPages === 0) return;

	const pages = [];
	const addPage = (page) => { if (pages[pages.length - 1] !== page) pages.push(page); };
	addPage(1);
	if (currentPage > 3) addPage('...');
	for (let page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) addPage(page);
	if (currentPage < totalPages - 2) addPage('...');
	if (totalPages > 1) addPage(totalPages);

	const pageItems = pages.map((page) => page === '...'
		? '<li class="page-item disabled"><span class="page-link">...</span></li>'
		: `<li class="page-item page-item-number${page === currentPage ? ' active' : ''}"><button type="button" class="page-link" data-jira-report-page="${page}"${page === currentPage ? ' aria-current="page"' : ''}>${page}</button></li>`).join('');
	container.innerHTML = `<li class="page-item${currentPage <= 1 ? ' disabled' : ''}"><button type="button" class="page-link" data-jira-report-page="${currentPage - 1}" aria-label="Pagina anterior"${currentPage <= 1 ? ' disabled' : ''}>&lt;</button></li>${pageItems}<li class="page-item${currentPage >= totalPages ? ' disabled' : ''}"><button type="button" class="page-link" data-jira-report-page="${currentPage + 1}" aria-label="Pagina siguiente"${currentPage >= totalPages ? ' disabled' : ''}>&gt;</button></li><li class="page-item"><span class="page-link"><select data-jira-report-page-size aria-label="Registros por pagina">${pageSizes.map((size) => `<option value="${size}"${size === Number(pagination.perPage) ? ' selected' : ''}>${size}</option>`).join('')}</select></span></li>`;
	container.querySelector('[data-jira-report-page-size]')?.addEventListener('change', (event) => onPageSizeChange(Number(event.target.value)));
	container.querySelectorAll('[data-jira-report-page]').forEach((button) => button.addEventListener('click', () => {
		if (!button.disabled) onPageChange(Number(button.dataset.jiraReportPage));
	}));
}

function getFocusableElements(container) {
	return [...container.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')];
}

function showReportGenerationLoader() {
	if (typeof window.Swal?.fire !== 'function') return;
	window.Swal.fire({
		title: 'Generando reporte',
		html: 'La IA esta preparando el reporte. Esto puede tardar unos instantes.',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		customClass: {container: 'jira-report-swal-container'},
		didOpen: () => window.Swal.showLoading(),
	});
}

function hideReportGenerationLoader() {
	if (typeof window.Swal?.close === 'function') window.Swal.close();
}

export async function initializeJiraReports(root) {
	const form = root.querySelector('[data-jira-report-form]');
	const listView = root.querySelector('[data-jira-report-list-view]');
	const listContainer = root.querySelector('[data-jira-report-list]');
	const detail = root.querySelector('[data-jira-report-detail]');
	const modal = root.querySelector('[data-jira-report-modal]');
	const pdfViewer = root.querySelector('[data-jira-report-pdf-viewer]');
	if (!form || !listView || !listContainer || !detail || !modal || !pdfViewer || root.dataset.jiraReportsInitialized === 'true') return;
	root.dataset.jiraReportsInitialized = 'true';
	if (modal.parentElement !== document.body) document.body.append(modal);
	if (pdfViewer.parentElement !== document.body) document.body.append(pdfViewer);

	const formStatus = root.querySelector('[data-jira-report-status]');
	const listStatus = root.querySelector('[data-jira-report-list-status]');
	const count = root.querySelector('[data-jira-report-count]');
	const paginationContainer = root.querySelector('[data-jira-report-pagination]');
	const search = root.querySelector('[data-jira-report-search]');
	const projectFilter = root.querySelector('[data-jira-report-project-filter]');
	const statusFilter = root.querySelector('[data-jira-report-status-filter]');
	const intentionFilter = root.querySelector('[data-jira-report-intention-filter]');
	const recurrenceFilter = root.querySelector('[data-jira-report-recurrence-filter]');
	const openButton = root.querySelector('[data-jira-report-open]');
	const state = {page: 1, perPage: 10, total: 0, totalPages: 0, request: 0};
	let searchTimeout = null;
	let lastViewedId = null;
	let modalReturnFocus = openButton;
	let epics = [];
	let activeReport = null;
	const reportProjectField = form.querySelector('[data-jira-report-project-filter]');
	const reportEpicField = form.querySelector('[data-jira-report-epic-filter]');
	const reportUserField = form.querySelector('[data-jira-report-user-filter]');
	const reportStatusField = form.querySelector('[data-jira-report-status-filter]');
	const projectSelect = form.querySelector('[data-jira-report-project-native]');
	const epicSelect = form.querySelector('[data-jira-report-epic-native]');
	const recurrenceToggle = form.querySelector('[data-jira-recurrence-toggle]');
	const recurrenceOptions = form.querySelector('[data-jira-recurrence-options]');
	const recurrenceInputs = [...form.querySelectorAll('[data-jira-recurrence-input]')];
	const frequencyUnitInput = form.querySelector('[name="frequency_unit"]');
	const monthlyExecutionDayField = form.querySelector('[data-jira-monthly-day-field]');
	const recurrenceHelp = form.querySelector('[data-jira-recurrence-help]');
	const recurrenceUnitLabels = {days: 'dias', months: 'meses'};
	const updateRecurrenceHelp = () => {
		if (!recurrenceHelp) return;
		const value = Number(form.querySelector('[name="range_value"]')?.value || 1);
		const unit = form.querySelector('[name="range_unit"]')?.value || 'days';
		const frequencyValue = Number(form.querySelector('[name="frequency_value"]')?.value || 1);
		const frequencyUnit = frequencyUnitInput?.value || 'days';
		const executionDay = Number(form.querySelector('[name="execution_day"]')?.value || 1);
		const schedule = frequencyUnit === 'months'
			? `Se ejecutara cada ${frequencyValue} ${recurrenceUnitLabels[frequencyUnit]} el dia ${executionDay}. `
			: `Se ejecutara cada ${frequencyValue} ${recurrenceUnitLabels[frequencyUnit]}. `;
		recurrenceHelp.textContent = `${schedule}Cada ejecucion tomara los ultimos ${value} ${recurrenceUnitLabels[unit] || unit} completos anteriores a la fecha de generacion.`;
	};
	const syncRecurrenceOptions = () => {
		const enabled = Boolean(recurrenceToggle?.checked);
		const monthly = enabled && frequencyUnitInput?.value === 'months';
		if (recurrenceOptions) recurrenceOptions.hidden = !enabled;
		if (monthlyExecutionDayField) monthlyExecutionDayField.hidden = !monthly;
		recurrenceInputs.forEach((input) => {
			input.disabled = !enabled || (input.name === 'execution_day' && !monthly);
		});
	};
	recurrenceToggle?.addEventListener('change', syncRecurrenceOptions);
	frequencyUnitInput?.addEventListener('change', syncRecurrenceOptions);
	frequencyUnitInput?.addEventListener('change', updateRecurrenceHelp);
	recurrenceInputs.forEach((input) => input.addEventListener('input', updateRecurrenceHelp));
	recurrenceInputs.forEach((input) => input.addEventListener('change', updateRecurrenceHelp));
	syncRecurrenceOptions();
	updateRecurrenceHelp();

	const populateEpics = () => {
		if (!epicSelect || !projectSelect) return;
		const selectedProjects = new Set(Array.from(projectSelect.selectedOptions).map((option) => String(option.value)));
		const selectedEpics = new Set(Array.from(epicSelect.selectedOptions).map((option) => String(option.value)));
		epicSelect.innerHTML = '';
		epics
			.filter((epic) => selectedProjects.size === 0 || selectedProjects.has(String(epic.jira_project_id)))
			.forEach((epic) => {
				const option = document.createElement('option');
				option.value = epic.id;
				option.textContent = `${epic.issue_key} · ${epic.summary}`;
				option.selected = selectedEpics.has(String(epic.id));
				epicSelect.append(option);
			});
		epicSelect.dispatchEvent(new Event('change', {bubbles: true}));
	};

	initializeJiraMultiSelect(reportProjectField, {
		placeholder: 'Todos los proyectos',
		searchPlaceholder: 'Buscar proyecto...',
		searchAriaLabel: 'Buscar proyectos',
		emptyText: 'Sin proyectos coincidentes',
		selectedLabel: 'proyectos seleccionados',
		itemLabel: 'proyectos',
		onChange: populateEpics,
	});
	initializeJiraMultiSelect(reportEpicField, {
		placeholder: 'Todas las epicas',
		searchPlaceholder: 'Buscar epica...',
		searchAriaLabel: 'Buscar epicas',
		emptyText: 'Sin epicas coincidentes',
		selectedLabel: 'epicas seleccionadas',
		itemLabel: 'epicas',
	});
	initializeJiraMultiSelect(reportUserField, {
		placeholder: 'Todos los usuarios',
		searchPlaceholder: 'Buscar usuario...',
		searchAriaLabel: 'Buscar usuarios',
		emptyText: 'Sin usuarios coincidentes',
		selectedLabel: 'usuarios seleccionados',
		itemLabel: 'usuarios',
	});
	initializeJiraMultiSelect(reportStatusField, {
		placeholder: 'Todos los estados',
		searchPlaceholder: 'Buscar estado...',
		searchAriaLabel: 'Buscar estados',
		emptyText: 'Sin estados coincidentes',
		selectedLabel: 'estados seleccionados',
		itemLabel: 'estados',
	});

	const loadReports = async (page = state.page) => {
		const requestNumber = ++state.request;
		setStatus(listStatus, 'Cargando reportes...');
		const params = new URLSearchParams({page: String(page), per_page: String(state.perPage)});
		if (search.value.trim()) params.set('search', search.value.trim());
		if (projectFilter.value) params.set('project_id', projectFilter.value);
		if (statusFilter.value) params.set('status', statusFilter.value);
		if (intentionFilter.value) params.set('intention', intentionFilter.value);
		if (recurrenceFilter.value) params.set('recurrence', recurrenceFilter.value);
		try {
			const data = await getJson(`/admin/jira/reports/data?${params.toString()}`);
			if (requestNumber !== state.request) return;
			const result = data.reports || {};
			const reports = Array.isArray(result.data) ? result.data : [];
			if (reports.length === 0 && page > 1 && Number(result.last_page || 1) < page) {
				state.page = Number(result.last_page || 1);
				return loadReports(state.page);
			}
			state.page = Number(result.current_page || page);
			state.perPage = Number(result.per_page || state.perPage);
			state.total = Number(result.total || 0);
			state.totalPages = Number(result.last_page || 0);
			renderRows(listContainer, reports);
			renderPagination(paginationContainer, {page: state.page, perPage: state.perPage, totalPages: state.totalPages}, (selectedPage) => loadReports(selectedPage), (selectedSize) => { state.perPage = selectedSize; state.page = 1; loadReports(1); });
			count.textContent = String(state.total);
			setStatus(listStatus, state.total ? `${state.total} reporte${state.total === 1 ? '' : 's'}` : 'Sin reportes para los filtros seleccionados.', state.total ? '' : '');
		} catch (error) {
			if (requestNumber !== state.request) return;
			listContainer.innerHTML = '<tr><td colspan="7" class="jira-empty">No fue posible cargar los reportes.</td></tr>';
			paginationContainer.hidden = true;
			setStatus(listStatus, error.message, 'error');
		}
	};

	const closeModal = (restoreFocus = true) => {
		modal.hidden = true;
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('jira-report-modal-open');
		if (restoreFocus && modalReturnFocus && document.contains(modalReturnFocus)) modalReturnFocus.focus();
	};

	const closePdfViewer = (restoreFocus = true) => {
		destroyPdfViewer();
		pdfViewer.hidden = true;
		pdfViewer.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('jira-report-pdf-open');
		if (restoreFocus && document.contains(modalReturnFocus)) modalReturnFocus.focus();
	};

	const openPdfViewer = (report) => {
		if (!report?.unique_id) return;
		modalReturnFocus = document.activeElement;
		activeReport = report;
		const title = cleanReportLabel(report.report_data?.report_title || report.title);
		pdfViewer.querySelector('[data-jira-report-pdf-title]')?.replaceChildren(document.createTextNode(title));
		const period = `${formatDate(report.from_date)} a ${formatDate(report.to_date)}`;
		const periodElement = pdfViewer.querySelector('[data-jira-report-pdf-period]');
		if (periodElement) periodElement.textContent = period;
		const emailForm = pdfViewer.querySelector('[data-jira-report-pdf-email]');
		if (emailForm) {
			emailForm.dataset.jiraReportPdfEmail = report.unique_id;
			emailForm.hidden = true;
			emailForm.reset();
		}
		pdfViewer.hidden = false;
		pdfViewer.setAttribute('aria-hidden', 'false');
		document.body.classList.add('jira-report-pdf-open');
		loadPdfViewer(`${reportPdfUrl(report.unique_id)}?preview=1`);
		window.setTimeout(() => pdfViewer.querySelector('#order-viewer')?.focus(), 0);
	};

	const openModal = () => {
		modalReturnFocus = document.activeElement || openButton;
		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('jira-report-modal-open');
		window.setTimeout(() => form.querySelector('input[name="title"]')?.focus(), 0);
	};

	openButton.addEventListener('click', openModal);
	modal.querySelectorAll('[data-jira-report-close]').forEach((button) => button.addEventListener('click', () => closeModal()));
	modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
	modal.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') { event.preventDefault(); closeModal(); return; }
		if (event.key !== 'Tab') return;
		const focusable = getFocusableElements(modal);
		if (!focusable.length) return;
		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
		else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
	});
	pdfViewer.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			event.preventDefault();
			closePdfViewer();
		}
	});

	try {
		const relationData = await getJson('/admin/jira/relations/data');
		epics = relationData.epics || [];
		populateEpics();
	} catch (error) {
		setStatus(formStatus, error.message, 'error');
	}

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		setStatus(formStatus, 'Generando reporte con IA...');
		showReportGenerationLoader();
		try {
			const result = await postJson('/admin/jira/reports/generate', formObject(form));
			setStatus(formStatus, result.report?.title ? `Reporte generado: ${result.report.title}` : 'Reporte generado.', 'success');
			form.reset();
			syncRecurrenceOptions();
			updateRecurrenceHelp();
			form.querySelectorAll('select[multiple]').forEach((select) => {
				Array.from(select.options).forEach((option) => { option.selected = option.defaultSelected; });
				select.dispatchEvent(new Event('change', {bubbles: true}));
			});
			populateEpics();
			closeModal();
			state.page = 1;
			await loadReports(1);
		} catch (error) {
			setStatus(formStatus, error.message, 'error');
		} finally {
			hideReportGenerationLoader();
			button.disabled = false;
		}
	});

	pdfViewer.querySelector('[data-jira-report-pdf-email]')?.addEventListener('submit', async (event) => {
		event.preventDefault();
		const emailForm = event.currentTarget;
		const status = emailForm.querySelector('[data-jira-report-pdf-email-status]');
		const button = emailForm.querySelector('button[type="submit"]');
		button.disabled = true;
		setStatus(status, 'Enviando PDF...');
		try {
			const result = await postJson(`/admin/jira/reports/${encodeURIComponent(emailForm.dataset.jiraReportPdfEmail)}/email`, formObject(emailForm));
			setStatus(status, result.message || 'Correo encolado.', 'success');
		} catch (error) {
			setStatus(status, error.message, 'error');
		} finally {
			button.disabled = false;
		}
	});

	pdfViewer.addEventListener('click', async (event) => {
		if (event.target === pdfViewer || event.target.closest('[data-jira-report-pdf-close]')) {
			closePdfViewer();
			return;
		}
		const emailToggle = event.target.closest('[data-jira-report-pdf-email-toggle]');
		if (emailToggle) {
			const emailForm = pdfViewer.querySelector('[data-jira-report-pdf-email]');
			if (emailForm) {
				emailForm.hidden = !emailForm.hidden;
				if (!emailForm.hidden) emailForm.querySelector('input')?.focus();
			}
			return;
		}
		const pdfAction = event.target.closest('[data-jira-pdf-action]');
		if (!pdfAction) return;
		const actions = {previous: pdfPrevPage, next: pdfNextPage, 'zoom-out': pdfZoomOut, 'zoom-in': pdfZoomIn, print: printPdf, download: downloadPdf, share: sharePdf, fullscreen: fullscreenPdf};
		await actions[pdfAction.dataset.jiraPdfAction]?.();
	});

	const requestFilteredReports = () => {
		state.page = 1;
		window.clearTimeout(searchTimeout);
		searchTimeout = window.setTimeout(() => loadReports(1), 250);
	};
	search.addEventListener('input', requestFilteredReports);
	[projectFilter, statusFilter, intentionFilter, recurrenceFilter].forEach((filter) => filter.addEventListener('change', () => loadReports(1)));
	root.querySelector('[data-jira-report-clear-filters]')?.addEventListener('click', () => {
		search.value = '';
		projectFilter.value = '';
		statusFilter.value = '';
		intentionFilter.value = '';
		recurrenceFilter.value = '';
		loadReports(1);
	});

	const showList = () => {
		closePdfViewer(false);
		detail.hidden = true;
		listView.hidden = false;
		const trigger = lastViewedId ? [...listContainer.querySelectorAll('[data-jira-report-view]')].find((button) => button.dataset.jiraReportView === lastViewedId) : null;
		(trigger || search).focus();
		listView.scrollIntoView({behavior: 'smooth', block: 'start'});
	};

	root.addEventListener('click', async (event) => {
		const closePdf = event.target.closest('[data-jira-report-pdf-close]');
		if (closePdf) { closePdfViewer(); return; }
		const previewOpen = event.target.closest('[data-jira-report-preview-open]');
		if (previewOpen) { openPdfViewer(activeReport); return; }
		const rowPreview = event.target.closest('[data-jira-report-preview]');
		if (rowPreview) {
			try {
				const data = await getJson(`/admin/jira/reports/${encodeURIComponent(rowPreview.dataset.jiraReportPreview)}`);
				if (data.report?.status === 'generated') openPdfViewer(data.report);
			} catch (error) {
				setStatus(listStatus, error.message, 'error');
			}
			return;
		}
		const emailToggle = event.target.closest('[data-jira-report-pdf-email-toggle]');
		if (emailToggle) {
			const emailForm = pdfViewer.querySelector('[data-jira-report-pdf-email]');
			if (emailForm) { emailForm.hidden = !emailForm.hidden; if (!emailForm.hidden) emailForm.querySelector('input')?.focus(); }
			return;
		}
		const pdfAction = event.target.closest('[data-jira-pdf-action]');
		if (pdfAction) {
			const actions = {previous: pdfPrevPage, next: pdfNextPage, 'zoom-out': pdfZoomOut, 'zoom-in': pdfZoomIn, print: printPdf, download: downloadPdf, share: sharePdf, fullscreen: fullscreenPdf};
			await actions[pdfAction.dataset.jiraPdfAction]?.();
			return;
		}
		const back = event.target.closest('[data-jira-report-back]');
		if (back) { showList(); return; }
		const view = event.target.closest('[data-jira-report-view]');
		if (view) {
			lastViewedId = view.dataset.jiraReportView;
			try {
				const data = await getJson(`/admin/jira/reports/${encodeURIComponent(lastViewedId)}`);
				listView.hidden = true;
				renderDetail(detail, data);
				if (data.report?.status === 'generated') openPdfViewer(data.report);
			} catch (error) {
				setStatus(listStatus, error.message, 'error');
			}
			return;
		}
		const regenerate = event.target.closest('[data-jira-report-regenerate]');
		if (regenerate) {
			regenerate.disabled = true;
			try {
				await postJson(`/admin/jira/reports/${encodeURIComponent(regenerate.dataset.jiraReportRegenerate)}/regenerate`, {});
				showList();
				await loadReports(state.page);
			} catch (error) {
				setStatus(listStatus, error.message, 'error');
				regenerate.disabled = false;
			}
			return;
		}
		const remove = event.target.closest('[data-jira-report-delete]');
		if (remove) {
			remove.disabled = true;
			try {
				await postJson(`/admin/jira/reports/${encodeURIComponent(remove.dataset.jiraReportDelete)}/delete`, {});
				await loadReports(state.page);
			} catch (error) {
				setStatus(listStatus, error.message, 'error');
				remove.disabled = false;
			}
		}
	});

	initPdfViewer();
	loadReports(1);
}
