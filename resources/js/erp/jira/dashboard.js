import { formObject, postJson, setStatus } from './api.js';
import { jiraState } from './state.js';

const number = (value, digits = 0) => new Intl.NumberFormat('es-CO', {maximumFractionDigits: digits}).format(Number(value || 0));
const labels = (items) => items.map((item) => item.label);
const values = (items, key) => items.map((item) => Number(item[key] || 0));

function renderChart(name, items, datasets) {
	const canvas = document.querySelector(`[data-jira-chart="${name}"]`);
	if (!canvas || typeof Chart === 'undefined') return;
	jiraState.charts[name]?.destroy();
	jiraState.charts[name] = new Chart(canvas, {type: 'bar', data: {labels: labels(items), datasets}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: true, position: 'top'}}, scales: {y: {beginAtZero: true}}}});
}

export async function initializeJiraDashboard(root) {
	const form = root.querySelector('[data-jira-dashboard-form]');
	if (!form || form.dataset.initialized === 'true') return;
	form.dataset.initialized = 'true';
	const status = root.querySelector('[data-jira-dashboard-status]');
	try {
		const catalog = await (await fetch('/admin/jira/relations/data', {headers: {'Accept': 'application/json'}})).json();
		const epics = catalog.data?.epics || [];
		const epicSelect = form.querySelector('[name="epic_id"]');
		const projectSelect = form.querySelector('[name="project_id"]');
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
	const load = async () => {
		const button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		setStatus(status, 'Consultando metricas...');
		try {
			const data = await postJson('/admin/jira/dashboard/data', formObject(form));
			const summary = data.summary || {};
			root.querySelector('[data-metric="story_points"]').textContent = number(summary.story_points, 2);
			root.querySelector('[data-metric="completed_issues"]').textContent = number(summary.completed_issues);
			root.querySelector('[data-metric="worklog_hours"]').textContent = number(summary.worklog_hours, 2);
			root.querySelector('[data-metric="active_projects"]').textContent = number(summary.active_projects);
			root.querySelector('[data-metric="active_users"]').textContent = number(summary.active_users);
			root.querySelector('[data-metric="last_sync"]').textContent = summary.last_sync ? new Date(summary.last_sync).toLocaleDateString('es-CO') : '-';
			root.querySelector('[data-metric="freshness"]').textContent = summary.last_sync ? 'Ultima sincronizacion' : 'Sin sincronizacion';
			const projects = (data.projects || []).slice(0, 10);
			const users = (data.users || []).slice(0, 10);
			const epics = (data.epics || []).slice(0, 10);
			renderChart('projects', projects, [{label: 'Story Points', data: values(projects, 'story_points'), backgroundColor: '#220245'}, {label: 'Horas', data: values(projects, 'hours'), backgroundColor: '#885FAE'}]);
			renderChart('users', users, [{label: 'Story Points', data: values(users, 'story_points'), backgroundColor: '#885FAE'}, {label: 'Horas', data: values(users, 'hours'), backgroundColor: '#F36803'}]);
			renderChart('epics', epics, [{label: 'Story Points', data: values(epics, 'story_points'), backgroundColor: '#220245'}, {label: 'Horas', data: values(epics, 'hours'), backgroundColor: '#16A34A'}]);
			const body = root.querySelector('[data-jira-issues]');
			body.innerHTML = '';
			(data.issues || []).forEach((issue) => { const row = document.createElement('tr'); [issue.key, issue.project, issue.epic, issue.assignee, number(issue.story_points, 2), issue.resolved_at || '-'].forEach((value) => { const cell = document.createElement('td'); cell.textContent = value; row.append(cell); }); body.append(row); });
			if (!(data.issues || []).length) body.innerHTML = '<tr><td colspan="6" class="jira-empty">No hay issues completados en el rango.</td></tr>';
			root.querySelector('[data-jira-issue-count]').textContent = number((data.issues || []).length);
			setStatus(status, 'Metricas actualizadas.', 'success');
		} catch (error) { setStatus(status, error.message, 'error'); }
		finally { button.disabled = false; }
	};
	form.addEventListener('submit', (event) => { event.preventDefault(); load(); });
	load();
}
