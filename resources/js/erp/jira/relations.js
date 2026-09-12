import { formObject, getJson, postJson } from './api.js';

let relationData = null;

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
	'&': '&amp;',
	'<': '&lt;',
	'>': '&gt;',
	'"': '&quot;',
	"'": '&#039;',
}[character]));

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

function setButtonBusy(button, busy) {
	if (!button) return;
	button.disabled = busy;
	button.classList.toggle('is-loading', busy);
}

function selectedValues(container, selector) {
	return [...container.querySelectorAll(`${selector}:checked`)].map((input) => input.value);
}

function renderChoiceList(container, items, selectedIds, type, emptyText) {
	const selected = new Set((selectedIds || []).map(String));
	if (!items.length) {
		container.innerHTML = `<div class="jira-relations-empty"><i class="fa-light fa-folder-open"></i><span>${escapeHtml(emptyText)}</span></div>`;
		return;
	}
	const orderedItems = [...items].sort((left, right) => {
		const leftLabel = `${left.name || ''} ${left.client_name || ''}`.trim();
		const rightLabel = `${right.name || ''} ${right.client_name || ''}`.trim();
		return leftLabel.localeCompare(rightLabel, 'es', {sensitivity: 'base'});
	});
	container.innerHTML = orderedItems.map((item) => {
		const description = type === 'client'
			? 'Cliente ERP'
			: `${item.client_name || 'Sin cliente'} · Licencia ERP`;
		const label = type === 'client' ? item.name : item.name;
		return `<label class="jira-choice-card" data-choice-card data-search-text="${escapeHtml(`${label} ${description}`.toLowerCase())}"><input type="checkbox" name="${type}_ids[]" value="${item.id}" data-relation-choice="${type}" ${selected.has(String(item.id)) ? 'checked' : ''}><span class="jira-choice-check" aria-hidden="true"><i class="fa-light fa-check"></i></span><span class="jira-choice-copy"><strong>${escapeHtml(label)}</strong><small>${escapeHtml(description)}</small></span></label>`;
	}).join('');
}

function filterChoices(container, value) {
	const query = String(value || '').trim().toLowerCase();
	container?.querySelectorAll('[data-choice-card]').forEach((card) => {
		card.hidden = query !== '' && !card.dataset.searchText.includes(query);
	});
}

export async function initializeJiraRelations(root) {
	const contextSelect = root.querySelector('[data-jira-project-context]');
	const projectForm = root.querySelector('[data-jira-project-relation-form]');
	const employeeList = root.querySelector('[data-jira-employee-mappings]');
	const employeeSearch = root.querySelector('[data-jira-employee-search]');
	const employeeCount = root.querySelector('[data-jira-employee-count]');
	const epicForm = root.querySelector('[data-jira-epic-relation-form]');
	if (!contextSelect || !projectForm || !employeeList || !epicForm || root.dataset.jiraRelationsInitialized === 'true') return;
	root.dataset.jiraRelationsInitialized = 'true';

	const projectWorkspace = root.querySelector('[data-jira-project-workspace]');
	const epicWorkspace = root.querySelector('[data-jira-epic-workspace]');
	const projectIdInput = projectForm.querySelector('[data-jira-project-id]');
	const epicIdInput = epicForm.querySelector('[data-jira-epic-id]');
	const projectById = () => new Map((relationData?.projects || []).map((project) => [String(project.id), project]));

	const clientList = projectForm.querySelector('[data-jira-client-list]');
	const licenseList = projectForm.querySelector('[data-jira-license-list]');
	const clientCount = projectForm.querySelector('[data-jira-client-count]');
	const licenseCount = projectForm.querySelector('[data-jira-license-count]');
	const clientSearch = projectForm.querySelector('[data-jira-client-search]');
	const licenseSearch = projectForm.querySelector('[data-jira-license-search]');
	const projectSaveState = projectForm.closest('.jira-panel').querySelector('[data-jira-project-save-state]');
	const contextState = root.querySelector('[data-jira-context-state]');
	const contextSummary = root.querySelector('[data-jira-context-summary]');

	const updateCounts = () => {
		const clients = selectedValues(projectForm, '[data-relation-choice="client"]');
		const licenses = selectedValues(projectForm, '[data-relation-choice="license"]');
		clientCount.textContent = `${clients.length} seleccionado${clients.length === 1 ? '' : 's'}`;
		licenseCount.textContent = `${licenses.length} seleccionada${licenses.length === 1 ? '' : 's'}`;
		projectSaveState.textContent = clients.length || licenses.length ? 'Cambios listos para guardar' : 'Sin relaciones';
	};

	const renderLicenseChoices = (project) => {
		const clientIds = selectedValues(projectForm, '[data-relation-choice="client"]');
		const available = (relationData?.licenses || []).filter((license) => clientIds.includes(String(license.client_id)));
		const existing = project?.license_ids || [];
		const selected = existing.filter((id) => available.some((license) => String(license.id) === String(id)));
		renderChoiceList(licenseList, available, selected, 'license', 'Selecciona primero uno o más clientes.');
		licenseSearch.value = '';
		updateCounts();
	};

	const renderEpics = (project) => {
		const epicSelect = epicForm.querySelector('[data-jira-epic-select]');
		const licenseSelect = epicForm.querySelector('[data-jira-epic-license-select]');
		const epics = (relationData?.epics || []).filter((epic) => String(epic.jira_project_id) === String(project.id));
		epicSelect.innerHTML = '<option value="">Selecciona una épica</option>' + epics.map((epic) => `<option value="${epic.id}" data-license-id="${epic.license_id || ''}">${escapeHtml(epic.issue_key)} · ${escapeHtml(epic.summary)}</option>`).join('');
		const allowedLicenseIds = new Set((project.license_ids || []).map(String));
		const licenses = (relationData?.licenses || []).filter((license) => allowedLicenseIds.has(String(license.id)));
		licenseSelect.innerHTML = '<option value="">Hereda el alcance del proyecto</option>' + licenses.map((license) => `<option value="${license.id}">${escapeHtml(license.name)} · ${escapeHtml(license.client_name || 'Sin cliente')}</option>`).join('');
		epicIdInput.value = '';
		licenseSelect.value = '';
	};

	const renderEmployeeRows = (query = '') => {
		const normalizedQuery = String(query).trim().toLowerCase();
		const mappingByEmployee = new Map(
			(relationData?.users || [])
				.filter((user) => user.mapping?.employee_id)
				.map((user) => [String(user.mapping.employee_id), user]),
		);
		const employees = (relationData?.employees || []).filter((employee) => {
			const haystack = `${employee.name} ${employee.id}`.toLowerCase();
			return normalizedQuery === '' || haystack.includes(normalizedQuery);
		}).sort((left, right) => left.name.localeCompare(right.name, 'es', {sensitivity: 'base'}));
		employeeCount.textContent = `${mappingByEmployee.size} asignado${mappingByEmployee.size === 1 ? '' : 's'}`;
		if (!employees.length) {
			employeeList.innerHTML = '<tr><td colspan="3" class="jira-empty">No hay empleados que coincidan con la búsqueda.</td></tr>';
			return;
		}
		employeeList.innerHTML = employees.map((employee) => {
			const mapped = mappingByEmployee.get(String(employee.id));
			const options = ['<option value="">Sin asignar</option>'].concat((relationData?.users || []).map((user) => `<option value="${user.id}" ${mapped?.id === user.id ? 'selected' : ''}>${escapeHtml(user.display_name)} · ${escapeHtml(user.account_id)}</option>`)).join('');
			return `<tr data-jira-employee-row data-search-text="${escapeHtml(`${employee.name} ${employee.id}`.toLowerCase())}"><td><strong>${escapeHtml(employee.name)}</strong><small>Empleado #${escapeHtml(employee.id)}</small></td><td><select class="jira-employee-mapping-select" data-employee-id="${employee.id}" data-previous-value="${mapped?.id || ''}" aria-label="Usuario Jira para ${escapeHtml(employee.name)}">${options}</select></td><td><span class="jira-mapping-state ${mapped ? 'is-assigned' : ''}">${mapped ? 'Asignado' : 'Sin asignar'}</span></td></tr>`;
		}).join('');
	};

	const renderProject = () => {
		const project = projectById().get(String(contextSelect.value));
		const selected = Boolean(project);
		projectWorkspace.hidden = !selected;
		epicWorkspace.hidden = !selected;
		contextSummary.hidden = !selected;
		contextState.textContent = selected ? project.project_key : 'Sin seleccionar';
		if (!selected) return;
		contextSummary.querySelector('[data-jira-context-name]').textContent = `${project.project_key} · ${project.name}`;
		contextSummary.querySelector('[data-jira-context-clients]').textContent = project.client_ids.length;
		contextSummary.querySelector('[data-jira-context-licenses]').textContent = project.license_ids.length;
		contextSummary.querySelector('[data-jira-context-epics]').textContent = (relationData.epics || []).filter((epic) => String(epic.jira_project_id) === String(project.id)).length;
		projectIdInput.value = project.id;
		renderChoiceList(clientList, relationData.clients, project.client_ids, 'client', 'No hay clientes activos en el ERP.');
		renderLicenseChoices(project);
		renderEpics(project);
	};

	try {
		relationData = await getJson('/admin/jira/relations/data');
		contextSelect.addEventListener('change', renderProject);
		clientList.addEventListener('change', (event) => {
			if (event.target.matches('[data-relation-choice="client"]')) renderLicenseChoices(projectById().get(String(contextSelect.value)));
		});
		licenseList.addEventListener('change', updateCounts);
		clientSearch.addEventListener('input', () => filterChoices(clientList, clientSearch.value));
		licenseSearch.addEventListener('input', () => filterChoices(licenseList, licenseSearch.value));
		employeeSearch.addEventListener('input', () => renderEmployeeRows(employeeSearch.value));
		employeeList.addEventListener('change', async (event) => {
			const select = event.target.closest('[data-employee-id]');
			if (!select) return;
			const previousValue = select.dataset.previousValue || '';
			if (!select.value && typeof window.Swal?.fire === 'function') {
				const confirmation = await window.Swal.fire({
					title: 'Quitar asignación',
					text: 'El empleado quedará sin usuario Jira asignado.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'Quitar asignación',
					cancelButtonText: 'Cancelar',
					reverseButtons: true,
				});
				if (!confirmation.isConfirmed) {
					select.value = previousValue;
					return;
				}
			}
			select.disabled = true;
			try {
				await postJson('/admin/jira/relations/user', {jira_user_id: select.value || null, employee_id: select.dataset.employeeId});
				relationData = await getJson('/admin/jira/relations/data');
				renderEmployeeRows(employeeSearch.value);
				toast(select.value ? 'Usuario Jira asignado al empleado.' : 'Asignación eliminada.');
			} catch (error) {
				select.disabled = false;
				select.value = previousValue;
				dialogError(error.message);
			}
		});
		epicForm.querySelector('[data-jira-epic-select]').addEventListener('change', (event) => {
			const option = event.target.selectedOptions[0];
			epicIdInput.value = event.target.value;
			epicForm.querySelector('[data-jira-epic-license-select]').value = option?.dataset.licenseId || '';
		});
		projectForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = projectForm.querySelector('button[type="submit"]');
			setButtonBusy(button, true);
			try {
				await postJson('/admin/jira/relations/project', formObject(projectForm));
				relationData = await getJson('/admin/jira/relations/data');
				renderProject();
				toast('Alcance comercial guardado.');
			} catch (error) { dialogError(error.message); }
			finally { setButtonBusy(button, false); }
		});
		epicForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = epicForm.querySelector('button[type="submit"]');
			setButtonBusy(button, true);
			try { await postJson('/admin/jira/relations/epic-license', formObject(epicForm)); toast('Relación de épica guardada.'); }
			catch (error) { dialogError(error.message); }
			finally { setButtonBusy(button, false); }
		});
		renderEmployeeRows();
		renderProject();
	} catch (error) {
		dialogError(error.message);
	}
}
