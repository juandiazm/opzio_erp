export const initializeTendersContext = () => {
    const form = document.getElementById('tenders-context-form');
    const status = document.getElementById('tenders-context-status');
    if (!form || !status) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        status.textContent = 'Guardando contexto...';
        status.classList.remove('is-error');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch('/admin/tenders/context', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.status === 0) {
                throw new Error(payload.message || `HTTP ${response.status}`);
            }
            status.textContent = payload.message || 'Contexto guardado y sincronizado.';
        } catch (error) {
            status.textContent = error.message || 'No fue posible guardar el contexto.';
            status.classList.add('is-error');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
};
