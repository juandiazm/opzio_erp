const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Valor no publicado';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
    }).format(amount);
};

const formatDate = (value) => {
    if (!value) return 'Fecha no disponible';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Fecha no disponible';
    return new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium' }).format(date);
};

const appendText = (parent, tagName, className, text) => {
    const element = document.createElement(tagName);
    element.className = className;
    element.textContent = text;
    parent.append(element);
    return element;
};

const renderPipeline = (container, items) => {
    container.replaceChildren();
    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'tenders-pipeline-empty';
        appendText(empty, 'strong', '', 'No hay oportunidades en seguimiento');
        appendText(empty, 'p', '', 'Guarda una oportunidad desde Discovery para verla aquí.');
        container.append(empty);
        return;
    }

    items.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'tenders-pipeline-item';
        card.setAttribute('role', 'listitem');
        appendText(card, 'span', 'tenders-pipeline-stage', item.stage || 'saved');
        appendText(card, 'h3', '', item.title || 'Oportunidad sin título');
        appendText(card, 'p', 'tenders-pipeline-entity', item.entity || 'Entidad no disponible');
        const metadata = document.createElement('div');
        metadata.className = 'tenders-pipeline-metadata';
        appendText(metadata, 'span', '', formatCurrency(item.amount));
        appendText(metadata, 'span', '', `Cierre: ${formatDate(item.deadline)}`);
        appendText(metadata, 'span', '', `Actualizada: ${formatDate(item.updated_at)}`);
        card.append(metadata);
        if (item.notes) appendText(card, 'p', 'tenders-pipeline-notes', item.notes);
        container.append(card);
    });
};

export const initializeTendersPipeline = () => {
    const stageFilter = document.getElementById('tenders-pipeline-stage');
    const status = document.getElementById('tenders-pipeline-status');
    const list = document.getElementById('tenders-pipeline-list');
    if (!stageFilter || !status || !list) return;

    const load = async () => {
        status.textContent = 'Consultando seguimiento...';
        status.classList.remove('is-error');
        try {
            const query = stageFilter.value ? `?stage=${encodeURIComponent(stageFilter.value)}` : '';
            const response = await fetch(`/admin/tenders/applications${query}`, {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.status === 0) {
                throw new Error(payload.message || `HTTP ${response.status}`);
            }
            const items = Array.isArray(payload.data) ? payload.data : [];
            status.textContent = `${items.length} oportunidades en seguimiento.`;
            renderPipeline(list, items);
        } catch (error) {
            status.textContent = error.message || 'No fue posible consultar el seguimiento.';
            status.classList.add('is-error');
            renderPipeline(list, []);
        }
    };

    stageFilter.addEventListener('change', load);
    load();
};
