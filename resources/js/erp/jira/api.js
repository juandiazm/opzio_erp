const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

export async function postJson(url, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.status === 0) {
        const errors = body.errors ? Object.values(body.errors).flat().join(' ') : '';
        throw new Error(errors || body.message || `La solicitud fallo (${response.status}).`);
    }
    return body.data ?? body;
}

export async function getJson(url) {
    const response = await fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.status === 0) throw new Error(body.message || `La solicitud fallo (${response.status}).`);
    return body.data ?? body;
}

export function setStatus(element, message, type = '') {
    if (!element) return;
    element.textContent = message || '';
    element.classList.toggle('is-error', type === 'error');
    element.classList.toggle('is-success', type === 'success');
}

export function formObject(form) {
    const data = {};
    new FormData(form).forEach((value, key) => {
        if (key.endsWith('[]')) {
            const normalizedKey = key.slice(0, -2);
            data[normalizedKey] = data[normalizedKey] || [];
            data[normalizedKey].push(value);
            return;
        }
        data[key] = value;
    });
    return data;
}
