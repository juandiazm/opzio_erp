const formObject = (form) => {
    const payload = Object.fromEntries(new FormData(form).entries());
    ['secop1_enabled', 'secop2_enabled', 'only_postulable', 'documents_enabled'].forEach((name) => {
        const input = form.elements[name];
        if (input?.type === 'checkbox' && !input.checked) payload[name] = '0';
    });
    return payload;
};

const postJson = async (url, payload = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify(payload),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || result.status === 0) throw new Error(result.message || `HTTP ${response.status}`);
    return result;
};

const setStatus = (element, message, isError = false) => {
    if (!element) return;
    element.textContent = message || '';
    element.classList.toggle('is-error', isError);
};

export function initializeTendersConfiguration(root) {
    const form = root.querySelector('#tenders-configuration-form');
    if (!form || form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';
    const status = root.querySelector('#tenders-configuration-status');
    const testButton = root.querySelector('#tenders-configuration-test');
    const syncButton = root.querySelector('#tenders-configuration-sync');
    const progress = root.querySelector('#tenders-configuration-progress');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const result = await postJson('/admin/tenders/configuration/save', formObject(form));
            setStatus(status, result.message || 'Configuracion guardada.');
            window.setTimeout(() => window.location.reload(), 500);
        } catch (error) {
            setStatus(status, error.message || 'No fue posible guardar la configuracion.', true);
        } finally {
            button.disabled = false;
        }
    });

    testButton?.addEventListener('click', async () => {
        testButton.disabled = true;
        setStatus(status, 'Probando conexion SECOP...');
        try {
            const result = await postJson('/admin/tenders/configuration/test');
            setStatus(status, result.message || 'Conexion verificada.');
        } catch (error) {
            setStatus(status, error.message || 'No fue posible probar la conexion.', true);
        } finally {
            testButton.disabled = false;
        }
    });

    syncButton?.addEventListener('click', async () => {
        syncButton.disabled = true;
        if (progress) {
            progress.hidden = false;
            progress.textContent = 'Sincronizando SECOP...';
        }
        try {
            const sources = ['secop1', 'secop2'].filter((source) => form.elements[`${source}_enabled`]?.checked);
            for (const source of sources) {
                let result = await postJson('/admin/tenders/sync', {source, mode: 'incremental'});
                let batches = 1;
                while (result.data?.has_more) {
                    batches += 1;
                    result = await postJson('/admin/tenders/sync', {
                        source,
                        mode: 'incremental',
                        cursor_at: result.data.next_cursor_at,
                        cursor_id: result.data.next_cursor_id,
                    });
                    if (progress) progress.textContent = `${source}: lote ${batches}`;
                }
            }
            setStatus(status, 'Sincronizacion SECOP completada.');
            window.setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            setStatus(status, error.message || 'No fue posible sincronizar SECOP.', true);
        } finally {
            syncButton.disabled = false;
        }
    });
}
