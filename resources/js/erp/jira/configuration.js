import { formObject, postJson, setStatus } from './api.js';

export function initializeJiraConfiguration(root) {
	const form = root.querySelector('[data-jira-connection-form]');
	if (!form || form.dataset.initialized === 'true') return;
	form.dataset.initialized = 'true';
	const testButton = root.querySelector('[data-jira-test]');
	const syncButton = root.querySelector('[data-jira-sync]');
	const status = root.querySelector('[data-jira-config-status]');
	const progress = root.querySelector('[data-jira-sync-progress]');
	const progressLabel = root.querySelector('[data-jira-sync-progress-label]');
	const progressCount = root.querySelector('[data-jira-sync-progress-count]');
	const progressBar = root.querySelector('[data-jira-sync-progress-bar]');

	const chooseSyncMode = async () => {
		if (typeof window.Swal?.fire !== 'function') {
			setStatus(status, 'No fue posible abrir el selector de tipo de sincronizacion.', 'error');
			return null;
		}
		const choice = await window.Swal.fire({
			title: 'Sincronizar historias',
			text: 'Elige si quieres completar desde la ultima historia almacenada o actualizar solo los cambios recientes.',
			icon: 'question',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: 'Actualizadas',
			denyButtonText: 'Completa',
			cancelButtonText: 'Cancelar',
			reverseButtons: true,
		});
		if (choice.isDismissed) return null;
		if (choice.isDenied) return {mode: 'full'};

		const range = await window.Swal.fire({
			title: 'Historias actualizadas',
			text: 'Indica cuantos dias hacia atras quieres consultar.',
			input: 'number',
			inputValue: 1,
			inputAttributes: {min: 1, max: 365, step: 1},
			showCancelButton: true,
			confirmButtonText: 'Sincronizar',
			cancelButtonText: 'Cancelar',
			reverseButtons: true,
			preConfirm: (value) => {
				const days = Number(value);
				if (!Number.isInteger(days) || days < 1 || days > 365) {
					window.Swal.showValidationMessage('Indica un numero entero entre 1 y 365.');
					return false;
				}
				return days;
			},
		});
		return range.isConfirmed ? {mode: 'updated', days: range.value} : null;
	};
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const button = form.querySelector('button[type="submit"]');
		button.disabled = true;
		try {
			const result = await postJson('/admin/jira/configuration/save', formObject(form));
			setStatus(status, result.message, 'success');
			window.setTimeout(() => window.location.reload(), 500);
		} catch (error) {
			setStatus(status, error.message, 'error');
			button.disabled = false;
		}
	});

	const runAction = async (button, action, payload) => {
		button.disabled = true;
		setStatus(status, action === 'test' ? 'Probando conexion...' : 'Sincronizando datos de Jira...');
		try {
			const url = action === 'sync' ? '/admin/jira/sync' : '/admin/jira/configuration/test';
			let processedTotal = 0;
			let batchNumber = 0;
			if (action === 'sync' && progress) {
				progress.hidden = false;
				progressBar?.parentElement.classList.add('is-indeterminate');
				if (progressBar) progressBar.style.width = '0%';
				if (progressCount) progressCount.textContent = '0';
			}
			let result = await postJson(url, payload);
			while (action === 'sync') {
				batchNumber += 1;
				processedTotal += Number(result.records_issues || 0);
				const total = Number(result.total_issues || 0);
				if (progressCount) progressCount.textContent = processedTotal.toLocaleString('es-CO');
				if (progressLabel) progressLabel.textContent = result.has_more ? `Lote ${batchNumber}: sincronizando...` : `Sincronizacion terminada en ${batchNumber} lote${batchNumber === 1 ? '' : 's'}.`;
				if (total > 0 && progressBar) {
					progressBar.parentElement.classList.remove('is-indeterminate');
					progressBar.style.width = `${Math.min(100, (processedTotal / total) * 100)}%`;
				}
				if (!result.has_more) break;
				setStatus(status, `Lote ${batchNumber} completado. Enviando el siguiente lote...`);
				result = await postJson(url, {
					...payload,
					start_at: result.next_start_at,
					next_page_token: result.next_page_token,
					from: result.from,
					to: result.to,
				});
			}
			if (action === 'sync' && progressBar) {
				progressBar.parentElement.classList.remove('is-indeterminate');
				progressBar.style.width = '100%';
			}
			setStatus(status, result.message || 'Operacion completada.', 'success');
			if (action === 'sync') window.setTimeout(() => window.location.reload(), 650);
		} catch (error) {
			setStatus(status, error.message, 'error');
			button.disabled = false;
		}
	};

	testButton?.addEventListener('click', () => runAction(testButton, 'test', {}));
	syncButton?.addEventListener('click', async () => {
		const options = await chooseSyncMode();
		if (options) runAction(syncButton, 'sync', options);
	});
}
