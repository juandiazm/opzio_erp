import { formObject, getJson, postJson, setStatus } from './api.js';

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
const list = (items) => Array.isArray(items) && items.length ? `<ul>${items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>` : '<p class="jira-empty">Sin datos.</p>';

function renderDetail(container, data) {
	const report = data.report || {};
	const content = report.report_data || {};
	const snapshot = report.data_snapshot || {};
	const summary = snapshot.summary || {};
	container.hidden = false;
	container.innerHTML = `<div class="jira-panel-title"><h2>${escapeHtml(content.report_title || report.title)}</h2><span class="jira-status-pill jira-status-${escapeHtml(report.status)}">${escapeHtml(report.status)}</span></div><div class="jira-detail-grid"><article><span>Story Points</span><strong>${escapeHtml(summary.story_points ?? '-')}</strong></article><article><span>Issues</span><strong>${escapeHtml(summary.completed_issues ?? 0)}</strong></article><article><span>Horas</span><strong>${escapeHtml(summary.worklog_hours ?? '-')}</strong></article><article><span>Periodo</span><strong>${escapeHtml(report.from_date || '')} / ${escapeHtml(report.to_date || '')}</strong></article></div><section class="jira-report-reading"><h3>Resumen ejecutivo</h3><p>${escapeHtml(content.executive_summary || 'Sin resumen.')}</p><h3>Hallazgos</h3>${list(content.key_findings)}<h3>Esfuerzo</h3><p>${escapeHtml(content.effort_analysis || '')}</p><h3>Proyectos</h3><p>${escapeHtml(content.project_analysis || '')}</p><h3>Usuarios</h3><p>${escapeHtml(content.user_analysis || '')}</p><h3>Epicas</h3><p>${escapeHtml(content.epic_analysis || '')}</p><h3>Riesgos</h3>${list(content.risks)}<h3>Recomendaciones</h3>${list((content.recommendations || []).map((item) => `${item.title || ''}: ${item.action || item.rationale || ''}`))}<h3>Limitaciones</h3>${list(content.limitations)}</section><form class="jira-email-form" data-jira-report-email="${escapeHtml(report.unique_id)}"><label class="jira-field"><span>Enviar por correo</span><textarea name="recipients" rows="3" placeholder="correo@empresa.com, equipo@empresa.com"></textarea></label><button class="btn btn-secondary" type="submit"><i class="fa-light fa-paper-plane"></i> Enviar PDF</button><p class="jira-status" data-jira-email-status role="status"></p></form>`;
	container.scrollIntoView({behavior: 'smooth', block: 'start'});
}

export async function initializeJiraReports(root) {
	const form = root.querySelector('[data-jira-report-form]');
	const listContainer = root.querySelector('[data-jira-report-list]');
	if (!form || form.dataset.initialized === 'true') return;
	form.dataset.initialized = 'true';
	const status = root.querySelector('[data-jira-report-status]');
	try {
		const catalogResponse = await fetch('/admin/jira/relations/data', {headers: {'Accept': 'application/json'}});
		const catalogBody = await catalogResponse.json();
		const epics = catalogBody.data?.epics || [];
		const epicSelect = form.querySelector('[name="jira_epic_issue_id"]');
		const projectSelect = form.querySelector('[name="jira_project_id"]');
		const populateEpics = () => {
			const selected = epicSelect.value;
			epicSelect.innerHTML = '<option value="">Todas</option>';
			epics.filter((epic) => !projectSelect.value || String(epic.jira_project_id) === String(projectSelect.value)).forEach((epic) => {
				const option = document.createElement('option'); option.value = epic.id; option.textContent = `${epic.issue_key} · ${epic.summary}`; option.selected = String(epic.id) === selected; epicSelect.append(option);
			});
		};
		projectSelect.addEventListener('change', populateEpics);
		populateEpics();
	} catch (error) { setStatus(status, error.message, 'error'); }
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		setStatus(status, 'Generando reporte con IA...');
		try { const result = await postJson('/admin/jira/reports/generate', formObject(form)); setStatus(status, result.report?.title ? `Reporte generado: ${result.report.title}` : 'Reporte generado.', 'success'); window.setTimeout(() => window.location.reload(), 600); }
		catch (error) { setStatus(status, error.message, 'error'); button.disabled = false; }
	});
	listContainer.addEventListener('click', async (event) => {
		const view = event.target.closest('[data-jira-report-view]');
		const regenerate = event.target.closest('[data-jira-report-regenerate]');
		const remove = event.target.closest('[data-jira-report-delete]');
		try {
			if (view) renderDetail(root.querySelector('[data-jira-report-detail]'), await getJson(`/admin/jira/reports/${view.dataset.jiraReportView}`));
			if (regenerate) { regenerate.disabled = true; await postJson(`/admin/jira/reports/${regenerate.dataset.jiraReportRegenerate}/regenerate`, {}); window.location.reload(); }
			if (remove) { remove.disabled = true; await postJson(`/admin/jira/reports/${remove.dataset.jiraReportDelete}/delete`, {}); window.location.reload(); }
		} catch (error) { setStatus(status, error.message, 'error'); }
	});
	const detail = root.querySelector('[data-jira-report-detail]');
	detail.addEventListener('submit', async (event) => {
		const emailForm = event.target.closest('[data-jira-report-email]');
		if (!emailForm) return;
		event.preventDefault();
		const emailStatus = emailForm.querySelector('[data-jira-email-status]');
		try {
			const result = await postJson(`/admin/jira/reports/${emailForm.dataset.jiraReportEmail}/email`, formObject(emailForm));
			setStatus(emailStatus, result.message || 'Correo encolado.', 'success');
		} catch (error) { setStatus(emailStatus, error.message, 'error'); }
	});
}
