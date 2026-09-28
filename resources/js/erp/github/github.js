import { formObject, getJson, postJson, setStatus } from '../jira/api.js';

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
const dateTime = (value) => value ? new Date(value).toLocaleString('es-CO', {dateStyle: 'short', timeStyle: 'short'}) : '-';
const statusLabels = {
	awaiting_approval: 'Esperando aprobacion', approved: 'Aprobada', rejected: 'Rechazada', preparing: 'Preparando', analyzing: 'Analizando', planning: 'Planificando', developing: 'Desarrollando', testing: 'Testeando', fixing: 'Corrigiendo', integrating_qa: 'Integrando QA', waiting_qa_pipeline: 'Esperando CI QA', waiting_quality_review: 'Esperando QA', quality_feedback: 'Feedback QA', integrating_main: 'Publicando', waiting_main_pipeline: 'Esperando CI Main', completed: 'Completada', blocked: 'Bloqueada', failed: 'Con error', candidate: 'Candidata', pending: 'Pendiente', expired: 'Expirada', active: 'Activa', disabled: 'Deshabilitada', error: 'Con error', draft: 'Borrador',
};
const eventLabels = {candidate_detected: 'Candidata detectada', approval_sent: 'Aprobacion enviada', approved: 'Aprobada', rejected: 'Rechazada', agent_started: 'Agente iniciado', analysis_started: 'Analisis iniciado', plan_created: 'Plan creado', code_changed: 'Codigo modificado', tests_started: 'Pruebas iniciadas', tests_passed: 'Pruebas aprobadas', tests_failed: 'Pruebas fallidas', qa_merge_started: 'Merge hacia QA', qa_pipeline_started: 'Pipeline QA iniciado', qa_pipeline_failed: 'Pipeline QA fallido', qa_pipeline_passed: 'Pipeline QA aprobado', quality_feedback_detected: 'Feedback QA detectado', main_merge_started: 'Merge hacia main', main_pipeline_started: 'Pipeline main iniciado', main_pipeline_failed: 'Pipeline main fallido', main_pipeline_passed: 'Pipeline main aprobado', completed: 'Completada', blocked: 'Bloqueada', execution_failed: 'Ejecucion fallida'};
const activeStatuses = new Set(['candidate', 'awaiting_approval', 'approved', 'preparing', 'analyzing', 'planning', 'developing', 'testing', 'fixing', 'integrating_qa', 'waiting_qa_pipeline', 'waiting_quality_review', 'quality_feedback', 'integrating_main', 'waiting_main_pipeline']);
const tabQueryParameter = 'tab';

const labelStatus = (value) => statusLabels[value] || String(value || '-').replaceAll('_', ' ');
const labelEvent = (value) => eventLabels[value] || String(value || '-').replaceAll('_', ' ');

function choiceMarkup(items, selected, name, description) {
	const selectedSet = new Set((selected || []).map(String));
	return items.length ? items.map((item) => {
		const key = typeof item === 'string' ? item : item.key;
		const label = typeof item === 'string' ? item : item.label;
		return `<label class="github-choice-card"><input type="checkbox" name="${name}[]" value="${escapeHtml(key)}" ${selectedSet.has(String(key)) ? 'checked' : ''}><span class="github-choice-check"><i class="fa-light fa-check"></i></span><span><strong>${escapeHtml(label)}</strong><small>${escapeHtml(description)}</small></span></label>`;
	}).join('') : '<span class="github-empty">Sin opciones sincronizadas.</span>';
}

export async function initializeGithubModule(root) {
	if (!root || root.dataset.initialized === 'true') return;
	root.dataset.initialized = 'true';
	const endpoint = (root.dataset.githubEndpoint || '/admin/github').replace(/\/+$/, '');
	const endpointUrl = (path = '') => `${endpoint}/${path}`.replace(/\/+$/, '');
	const tabs = [...root.querySelectorAll('#github-module-tabs .nav-link')];
	const updateTabUrl = (tabId) => {
		const url = new URL(window.location.href);
		if (tabId) url.searchParams.set(tabQueryParameter, tabId);
		else url.searchParams.delete(tabQueryParameter);
		window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
	};
	const showTabFromUrl = () => {
		const tabId = new URLSearchParams(window.location.search).get(tabQueryParameter);
		const tab = tabs.find((item) => item.id === tabId);
		if (tab) tab.click();
	};
	tabs.forEach((tab) => tab.addEventListener('click', () => updateTabUrl(tab.id)));
	window.addEventListener('popstate', showTabFromUrl);
	showTabFromUrl();
	const state = {data: null, selectedProjectId: null, projectSearch: '', approvalFilter: '', executionFilter: ''};
	const connectionForm = root.querySelector('[data-github-connection-form]');
	const projectForm = root.querySelector('[data-github-project-form]');
	const agentForm = root.querySelector('[data-github-agent-form]');
	const agentSelector = root.querySelector('[data-github-agent-selector]');
	const supervisorForm = root.querySelector('[data-github-supervisor-form]');
	const projectEditor = root.querySelector('[data-github-project-editor]');
	const projectEmpty = root.querySelector('[data-github-project-empty]');

	const run = async (button, callback, statusElement) => {
		if (button) button.disabled = true;
		try { await callback(); }
		catch (error) { setStatus(statusElement, error.message, 'error'); }
		finally { if (button) button.disabled = false; }
	};

	const selectedProject = () => state.data?.projects?.find((project) => String(project.id) === String(state.selectedProjectId));

	function renderConnection() {
		const connection = state.data?.github;
		const stateLabel = root.querySelector('[data-github-connection-state]');
		if (!connection) {
			if (stateLabel) stateLabel.textContent = 'Sin configurar';
			return;
		}
		connectionForm.querySelector('[name="name"]').value = connection.name || '';
		connectionForm.querySelector('[name="base_url"]').value = connection.base_url || 'https://api.github.com';
		if (stateLabel) stateLabel.textContent = labelStatus(connection.status);
		const facts = root.querySelector('[data-github-connection-facts]');
		if (facts) facts.innerHTML = `<div><span>Ultima prueba</span><strong>${escapeHtml(dateTime(connection.last_tested_at))}</strong></div><div><span>Token</span><strong>${connection.token_configured ? 'Configurado' : 'Pendiente'}</strong></div>${connection.last_error ? `<div class="github-fact-error"><span>Ultimo error</span><strong>${escapeHtml(connection.last_error)}</strong></div>` : ''}`;
	}

	function renderOverview() {
		const summary = state.data?.summary || {};
		const connection = state.data?.github;
		root.querySelector('[data-github-metric="connection"]').textContent = connection ? labelStatus(connection.status) : 'Pendiente';
		root.querySelector('[data-github-metric-detail="connection"]').textContent = connection?.last_tested_at ? `Probada ${dateTime(connection.last_tested_at)}` : 'Configura la conexion';
		[['projects', summary.projects_enabled || 0], ['approvals', summary.approvals_pending || 0], ['active', summary.executions_active || 0], ['blocked', summary.executions_blocked || 0], ['completed', summary.executions_completed || 0]].forEach(([key, value]) => { root.querySelector(`[data-github-metric="${key}"]`).textContent = Number(value).toLocaleString('es-CO'); });
		const activity = root.querySelector('[data-github-activity]');
		const events = state.data?.activity || [];
		activity.innerHTML = events.length ? events.slice(0, 12).map((item) => `<div class="github-activity-item"><span class="github-activity-icon"><i class="fa-light fa-${item.event.includes('failed') || item.event === 'blocked' ? 'triangle-exclamation' : item.event === 'completed' ? 'check' : 'bolt'}"></i></span><div><strong>${escapeHtml(labelEvent(item.event))}</strong><span>${escapeHtml(item.jira_key || 'Sistema')} · ${escapeHtml(labelStatus(item.phase))}</span></div><time>${escapeHtml(dateTime(item.created_at))}</time></div>`).join('') : '<div class="github-empty">Todavia no hay actividad registrada.</div>';
		const attention = root.querySelector('[data-github-attention]');
		const approvals = (state.data?.approvals || []).filter((item) => item.status === 'pending');
		const blocked = (state.data?.executions || []).filter((item) => item.status === 'blocked');
		const waiting = (state.data?.executions || []).filter((item) => item.status === 'waiting_quality_review');
		const items = [...approvals.map((item) => ({icon: 'shield-check', title: `Aprobacion pendiente: ${item.jira_key}`, detail: item.project, target: 'github-approvals-tab'})), ...blocked.map((item) => ({icon: 'triangle-exclamation', title: `Ejecucion bloqueada: ${item.jira_key}`, detail: item.blocked_reason || 'Requiere intervencion', target: 'github-executions-tab'})), ...waiting.map((item) => ({icon: 'user-check', title: `Esperando QA: ${item.jira_key}`, detail: item.project, target: 'github-executions-tab'}))];
		attention.innerHTML = items.length ? items.slice(0, 8).map((item) => `<button type="button" class="github-attention-item" data-github-open-tab="${item.target}"><i class="fa-light fa-${item.icon}"></i><span><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.detail || '')}</small></span><i class="fa-light fa-chevron-right"></i></button>`).join('') : '<div class="github-empty">No hay elementos que requieran atencion.</div>';
	}

	function renderProjects() {
		const projects = (state.data?.projects || []).filter((item) => `${item.project_key} ${item.name}`.toLowerCase().includes(state.projectSearch.toLowerCase()));
		const list = root.querySelector('[data-github-project-list]');
		root.querySelector('[data-github-project-count]').textContent = `${projects.length} proyectos`;
		list.innerHTML = projects.length ? projects.map((item) => `<button type="button" class="github-project-list-item ${String(item.id) === String(state.selectedProjectId) ? 'is-selected' : ''}" data-github-project-id="${item.id}"><span class="github-project-avatar"><i class="fa-brands fa-github"></i></span><span><strong>${escapeHtml(item.project_key)} · ${escapeHtml(item.name)}</strong><small>${item.configuration?.enabled ? 'Automatizacion activa' : 'Sin activar'} · ${escapeHtml(item.configuration?.github_repository || 'Sin repositorio')}</small></span><i class="fa-light fa-chevron-right"></i></button>`).join('') : '<div class="github-empty">No hay proyectos que coincidan.</div>';
		list.querySelectorAll('[data-github-project-id]').forEach((button) => button.addEventListener('click', () => { state.selectedProjectId = Number(button.dataset.githubProjectId); renderProjects(); renderProjectEditor(); }));
	}

	function renderProjectEditor() {
		const project = selectedProject();
		if (!project) { projectEditor.hidden = true; projectEmpty.hidden = false; return; }
		projectEditor.hidden = false; projectEmpty.hidden = true;
		const configuration = project.configuration || {};
		root.querySelector('[data-github-project-title]').textContent = `${project.project_key} · ${project.name}`;
		root.querySelector('[data-github-project-subtitle]').textContent = configuration.github_repository ? `${configuration.github_owner}/${configuration.github_repository}` : 'Sin repositorio asignado';
		root.querySelector('[data-github-project-state]').textContent = configuration.enabled ? 'Activo' : 'Inactivo';
		projectForm.querySelector('[name="github_connection_id"]').value = state.data?.github?.id || '';
		projectForm.querySelector('[name="jira_project_id"]').value = project.id;
		projectForm.querySelector('[name="enabled"]').checked = Boolean(configuration.enabled);
		['github_owner', 'github_repository', 'base_branch', 'max_execution_attempts', 'max_ci_attempts', 'max_execution_minutes', 'max_consecutive_failures'].forEach((name) => { const input = projectForm.querySelector(`[name="${name}"]`); if (input) input.value = configuration[name] ?? ({base_branch: 'qa', max_execution_attempts: 5, max_ci_attempts: 3, max_execution_minutes: 120, max_consecutive_failures: 3}[name] ?? ''); });
		const agentSelect = projectForm.querySelector('[data-github-default-agent]');
		agentSelect.innerHTML = (state.data?.agents || []).filter((agent) => agent.enabled).map((agent) => `<option value="${agent.id}">${escapeHtml(agent.name)} · ${escapeHtml(agent.model)} · ${escapeHtml(agent.cost_label || 'Costo medio')}</option>`).join('');
		agentSelect.value = configuration.default_agent_id || state.data?.agents?.find((agent) => agent.is_default)?.id || '';
		root.querySelector('[data-github-issue-types]').innerHTML = choiceMarkup(project.available_issue_types || [], configuration.issue_types || [], 'issue_types', 'Tipo sincronizado desde Jira');
		root.querySelector('[data-github-assignees]').innerHTML = choiceMarkup(project.available_assignees || [], configuration.assignee_keys || [], 'assignee_keys', 'Assignee permitido');
	}

	function renderAgents() {
		const agents = state.data?.agents || [];
		if (agentSelector) agentSelector.innerHTML = '<option value="">Nuevo agente</option>' + agents.map((agent) => `<option value="${agent.id}">${escapeHtml(agent.name)} · ${escapeHtml(agent.model)} · ${escapeHtml(agent.cost_label || 'Costo medio')}</option>`).join('');
		root.querySelector('[data-github-agent-count]').textContent = `${agents.length} agentes`;
		const body = root.querySelector('[data-github-agents]');
		body.innerHTML = agents.length ? agents.map((agent) => `<tr><td><strong>${escapeHtml(agent.name)}</strong><small>${escapeHtml(agent.provider)}</small></td><td>${escapeHtml(agent.model)}</td><td>${escapeHtml(agent.description || '-')}</td><td>${escapeHtml(agent.cost_label || 'Costo medio')}</td><td><span class="github-status-pill ${agent.enabled ? 'is-success' : 'is-muted'}">${agent.enabled ? 'Habilitado' : 'Deshabilitado'}</span>${agent.is_default ? '<small class="github-default-label">Predeterminado</small>' : ''}</td><td>GitHub Copilot cloud agent</td><td><button class="github-icon-button" type="button" data-github-agent-edit="${agent.id}" title="Editar agente"><i class="fa-light fa-pen"></i></button></td></tr>`).join('') : '<tr><td colspan="7" class="github-empty">No hay agentes configurados.</td></tr>';
		body.querySelectorAll('[data-github-agent-edit]').forEach((button) => button.addEventListener('click', () => { agentSelector.value = button.dataset.githubAgentEdit; fillAgentForm(agents.find((item) => String(item.id) === String(button.dataset.githubAgentEdit))); agentForm.scrollIntoView({behavior: 'smooth', block: 'center'}); }));
	}

	function fillAgentForm(agent) {
		if (!agent) { clearAgentForm(); return; }
		agentForm.querySelector('[name="name"]').value = agent.name;
		agentForm.querySelector('[name="provider"]').value = 'github_copilot';
		const model = agentForm.querySelector('[data-github-model]');
		if (![...model.options].some((option) => option.value === agent.model)) model.add(new Option(agent.model, agent.model));
		model.value = agent.model;
		agentForm.querySelector('[name="description"]').value = agent.description || '';
		agentForm.querySelector('[name="cost_tier"]').value = agent.cost_tier || 'medium';
		agentForm.querySelector('[name="enabled"]').checked = agent.enabled;
		agentForm.querySelector('[name="is_default"]').checked = agent.is_default;
	}

	function renderSupervisors() {
		const supervisors = state.data?.supervisors || [];
		root.querySelector('[data-github-supervisor-count]').textContent = `${supervisors.length} supervisores`;
		root.querySelector('[data-github-supervisors]').innerHTML = supervisors.length ? supervisors.map((item) => `<tr><td><strong>${escapeHtml(item.name || '-')}</strong></td><td>${escapeHtml(item.email)}</td><td>${escapeHtml(item.project || 'Global')}</td><td><span class="github-status-pill ${item.enabled ? 'is-success' : 'is-muted'}">${item.enabled ? 'Activo' : 'Inactivo'}</span></td></tr>`).join('') : '<tr><td colspan="4" class="github-empty">No hay supervisores configurados.</td></tr>';
		const projectSelect = root.querySelector('[data-github-supervisor-project]');
		projectSelect.innerHTML = '<option value="">Global</option>' + (state.data?.projects || []).filter((item) => item.configuration).map((item) => `<option value="${item.configuration.id}">${escapeHtml(item.project_key)} · ${escapeHtml(item.name)}</option>`).join('');
	}

	function renderApprovals() {
		const approvals = (state.data?.approvals || []).filter((item) => !state.approvalFilter || item.status === state.approvalFilter);
		root.querySelector('[data-github-approval-count]').textContent = `${approvals.length} solicitudes`;
		root.querySelector('[data-github-approvals]').innerHTML = approvals.length ? approvals.map((item) => `<tr><td><strong>${escapeHtml(item.jira_key)}</strong><small>${escapeHtml(item.summary || '')}</small></td><td>${escapeHtml(item.project || '-')}</td><td>${escapeHtml(item.issue_type || '-')}</td><td>${escapeHtml(item.jira_status || '-')}</td><td><span class="github-status-pill github-status-${escapeHtml(item.status)}">${escapeHtml(labelStatus(item.status))}</span>${item.decider ? `<small class="github-default-label">${escapeHtml(item.decider)}</small>` : ''}</td><td>${item.story_point_estimate ?? '-'}</td><td>${escapeHtml(item.agent || '-')}</td><td>${escapeHtml(dateTime(item.created_at))}</td><td>${escapeHtml(dateTime(item.expires_at))}</td></tr>`).join('') : '<tr><td colspan="9" class="github-empty">No hay solicitudes para este filtro.</td></tr>';
	}

	function renderExecutions() {
		const executions = (state.data?.executions || []).filter((item) => !state.executionFilter || item.status === state.executionFilter);
		root.querySelector('[data-github-execution-count]').textContent = `${executions.length} ejecuciones`;
		root.querySelector('[data-github-executions]').innerHTML = executions.length ? executions.map((item) => `<tr><td><strong>${escapeHtml(item.jira_key)}</strong><small>${escapeHtml(item.summary || '')}</small></td><td>${escapeHtml(item.project || '-')}</td><td>${escapeHtml(item.agent || '-')}</td><td><span class="github-status-pill github-status-${escapeHtml(item.status)}">${escapeHtml(labelStatus(item.status))}</span></td><td>${escapeHtml(labelStatus(item.phase))}</td><td>${escapeHtml(item.branch || '-')}</td><td>${item.attempt || 0}</td><td>${item.ci_attempts || 0} / ${item.main_ci_attempts || 0}</td><td>${escapeHtml(dateTime(item.last_activity_at))}</td><td><button type="button" class="github-icon-button" data-github-execution-open="${item.id}" title="Ver detalle"><i class="fa-light fa-eye"></i></button></td></tr>`).join('') : '<tr><td colspan="10" class="github-empty">No hay ejecuciones para este filtro.</td></tr>';
		root.querySelectorAll('[data-github-execution-open]').forEach((button) => button.addEventListener('click', () => openExecution(Number(button.dataset.githubExecutionOpen))));
	}

	async function openExecution(id) {
		const detail = root.querySelector('[data-github-execution-detail]');
		const summary = root.querySelector('[data-github-execution-summary]');
		const events = root.querySelector('[data-github-execution-events]');
		detail.hidden = false; summary.innerHTML = '<div class="github-empty">Cargando detalle...</div>'; events.innerHTML = '';
		try {
			const data = await getJson(endpointUrl(`executions/${id}/data`));
			const item = data.execution;
			root.querySelector('[data-github-execution-detail-title]').textContent = `${item.jira_key} · ${item.summary || ''}`;
			summary.innerHTML = `<div><span>Estado</span><strong>${escapeHtml(labelStatus(item.status))}</strong></div><div><span>Repositorio</span><strong>${escapeHtml(item.repository || '-')}</strong></div><div><span>Copilot task</span><strong>${escapeHtml(item.github_task_state || '-')} · ${escapeHtml(item.github_task_id || '-')}</strong></div><div><span>Pull request</span><strong>${item.github_pull_request_number ? `#${item.github_pull_request_number}` : '-'}</strong></div><div><span>Branch</span><strong>${escapeHtml(item.feature_branch || '-')}</strong></div><div><span>Intentos</span><strong>${item.attempt || 0} · CI ${item.ci_attempts || 0}/${item.main_ci_attempts || 0}</strong></div><div><span>Ultima actividad</span><strong>${escapeHtml(dateTime(item.last_activity_at))}</strong></div>${item.github_task_url ? `<div><a href="${escapeHtml(item.github_task_url)}" target="_blank" rel="noreferrer">Abrir sesion de Copilot</a></div>` : ''}${item.blocked_reason || item.error ? `<div class="github-detail-error"><span>Resultado</span><strong>${escapeHtml(item.blocked_reason || item.error)}</strong></div>` : ''}`;
			events.innerHTML = data.events?.length ? data.events.map((event) => `<div class="github-event-item"><span class="github-event-marker"></span><div><strong>${escapeHtml(labelEvent(event.event))}</strong><small>${escapeHtml(labelStatus(event.phase))} · intento ${event.attempt || 0}</small>${Object.keys(event.metadata || {}).length ? `<code>${escapeHtml(JSON.stringify(event.metadata))}</code>` : ''}</div><time>${escapeHtml(dateTime(event.created_at))}</time></div>`).join('') : '<div class="github-empty">No hay eventos registrados.</div>';
		} catch (error) { summary.innerHTML = `<div class="github-empty github-empty-error">${escapeHtml(error.message)}</div>`; }
	}

	async function load() {
		state.data = await getJson(endpointUrl('data'));
		renderConnection(); renderOverview(); renderProjects(); renderProjectEditor(); renderAgents(); renderSupervisors(); renderApprovals(); renderExecutions();
	}

	connectionForm.addEventListener('submit', (event) => { event.preventDefault(); run(connectionForm.querySelector('button[type="submit"]'), async () => { await postJson(endpointUrl('connection/save'), formObject(connectionForm)); setStatus(root.querySelector('[data-github-connection-status]'), 'Conexion guardada correctamente.', 'success'); await load(); }, root.querySelector('[data-github-connection-status]')); });
	root.querySelector('[data-github-connection-test]').addEventListener('click', (event) => run(event.currentTarget, async () => { const result = await postJson(endpointUrl('connection/test')); setStatus(root.querySelector('[data-github-connection-status]'), result.message, result.ok === false ? 'error' : 'success'); await load(); }, root.querySelector('[data-github-connection-status]')));
	projectForm.addEventListener('submit', (event) => { event.preventDefault(); run(projectForm.querySelector('button[type="submit"]'), async () => { await postJson(endpointUrl('projects/save'), formObject(projectForm)); setStatus(root.querySelector('[data-github-project-status]'), 'Reglas guardadas correctamente.', 'success'); await load(); }, root.querySelector('[data-github-project-status]')); });
	root.querySelector('[data-github-project-scan]').addEventListener('click', (event) => run(event.currentTarget, async () => { const configuration = selectedProject()?.configuration; if (!configuration) throw new Error('Guarda primero la configuracion del proyecto.'); const result = await postJson(endpointUrl('projects/scan'), {jira_automation_project_id: configuration.id}); setStatus(root.querySelector('[data-github-project-status]'), `${result.message} Candidatas detectadas: ${result.detected}.`, 'success'); await load(); }, root.querySelector('[data-github-project-status]')));
	agentForm.addEventListener('submit', (event) => { event.preventDefault(); run(agentForm.querySelector('button[type="submit"]'), async () => { await postJson(endpointUrl('agents/save'), formObject(agentForm)); clearAgentForm(); setStatus(root.querySelector('[data-github-agent-status]'), 'Agente guardado correctamente.', 'success'); await load(); }, root.querySelector('[data-github-agent-status]')); });
	root.querySelector('[data-github-agent-clear]').addEventListener('click', clearAgentForm);
	agentSelector.addEventListener('change', () => fillAgentForm((state.data?.agents || []).find((agent) => String(agent.id) === String(agentSelector.value))));
	supervisorForm.addEventListener('submit', (event) => { event.preventDefault(); run(supervisorForm.querySelector('button[type="submit"]'), async () => { await postJson(endpointUrl('supervisors/save'), formObject(supervisorForm)); supervisorForm.reset(); supervisorForm.querySelector('[name="enabled"]').checked = true; setStatus(root.querySelector('[data-github-supervisor-status]'), 'Supervisor guardado correctamente.', 'success'); await load(); }, root.querySelector('[data-github-supervisor-status]')); });
	root.querySelector('[data-github-project-search]').addEventListener('input', (event) => { state.projectSearch = event.target.value; renderProjects(); });
	root.querySelector('[data-github-approval-filter]').addEventListener('change', (event) => { state.approvalFilter = event.target.value; renderApprovals(); });
	root.querySelector('[data-github-execution-filter]').addEventListener('change', (event) => { state.executionFilter = event.target.value; renderExecutions(); });
	root.querySelectorAll('[data-github-refresh]').forEach((button) => button.addEventListener('click', () => run(button, load, root.querySelector('[data-github-connection-status]'))));
	root.querySelector('[data-github-execution-close]').addEventListener('click', () => { root.querySelector('[data-github-execution-detail]').hidden = true; });
	root.addEventListener('click', (event) => { const target = event.target.closest('[data-github-open-tab]'); if (!target) return; document.getElementById(target.dataset.githubOpenTab)?.click(); });

	function clearAgentForm() { agentForm.reset(); agentSelector.value = ''; agentForm.querySelector('[name="enabled"]').checked = true; agentForm.querySelector('[name="provider"]').value = 'github_copilot'; agentForm.querySelector('[data-github-model]').value = 'gpt-5.6-luna'; agentForm.querySelector('[name="description"]').value = 'Rapido y economico para cambios rutinarios.'; agentForm.querySelector('[name="cost_tier"]').value = 'low'; }

	try { await load(); } catch (error) { setStatus(root.querySelector('[data-github-connection-status]'), error.message, 'error'); }
}

const moduleRoot = document.querySelector('[data-github-module]');
if (moduleRoot) initializeGithubModule(moduleRoot);