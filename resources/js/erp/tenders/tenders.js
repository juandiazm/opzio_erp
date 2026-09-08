import { initializeTendersContext } from './context.js';
import { initializeTendersPipeline, openTendersOpportunity } from './pipeline.js';

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Valor no publicado';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
    }).format(amount);
};

const scrollDiscoveryToTop = (elements) => {
    [elements.container, elements.pageContent].forEach((scrollTarget) => {
        if (!scrollTarget) return;
        if (typeof scrollTarget.scrollTo === 'function') {
            scrollTarget.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
        scrollTarget.scrollTop = 0;
    });
};

const formatDate = (value) => {
    if (!value) return 'Fecha no disponible';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Fecha no disponible';
    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(date);
};

const formatShortDate = (value) => {
    if (!value) return 'Fecha no disponible';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Fecha no disponible';
    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'short',
        timeStyle: 'short'
    }).format(date);
};

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') return 'No disponible';
    if (typeof value === 'object') {
        if (value.url) return value.url;
        try {
            return JSON.stringify(value);
        } catch (error) {
            return String(value);
        }
    }
    return String(value);
};

const hasValue = (value) => {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim() !== '';
    if (Array.isArray(value)) return value.length > 0;
    if (typeof value === 'object') return Object.keys(value).length > 0;
    return true;
};

const deduplicateItems = (items) => {
    const seen = new Set();
    return items.filter((item) => {
        const processId = item.source_process_id || item.opportunity_id || `${item.reference || ''}:${item.title || ''}`;
        const key = `${item.source || ''}:${processId}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
};

const wait = (milliseconds) => new Promise((resolve) => window.setTimeout(resolve, milliseconds));

const eligibilityLabels = {
    probably_fit: 'Probablemente apta',
    requires_validation: 'Requiere validacion',
    high_risk: 'Riesgo alto',
    unknown: 'Sin informacion'
};

const confidenceLabels = {
    high: 'alta',
    medium: 'media',
    low: 'baja'
};

const eligibilityIcons = {
    probably_fit: 'fa-circle-check',
    requires_validation: 'fa-circle-exclamation',
    high_risk: 'fa-triangle-exclamation',
    unknown: 'fa-circle-question'
};

const confidenceIcons = {
    high: 'fa-shield-check',
    medium: 'fa-shield-exclamation',
    low: 'fa-shield-question'
};

const appendText = (parent, tagName, className, text) => {
    const element = document.createElement(tagName);
    element.className = className;
    element.textContent = text;
    parent.append(element);
    return element;
};

const appendBadge = (parent, className, iconClass, label, title) => {
    const badge = document.createElement('span');
    badge.className = className;
    badge.title = title;
    badge.setAttribute('aria-label', title);
    const icon = document.createElement('i');
    icon.className = `fa-light ${iconClass}`;
    icon.setAttribute('aria-hidden', 'true');
    const labelElement = document.createElement('span');
    labelElement.className = 'licitaciones-badge-label';
    labelElement.textContent = label;
    badge.append(icon, labelElement);
    parent.append(badge);
    return badge;
};

const setFeedbackState = (card, feedbackState) => {
    if (!card) return;
    card.querySelectorAll('[data-tenders-feedback]').forEach((button) => {
        const selected = button.dataset.eventType === feedbackState;
        button.classList.toggle('is-selected', selected);
        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        button.title = selected ? button.dataset.selectedTitle : button.dataset.defaultTitle;
    });
    card.dataset.feedbackState = feedbackState || '';
};

const appendDetailField = (parent, label, value) => {
    const field = document.createElement('div');
    field.className = 'tenders-detail-field';
    appendText(field, 'dt', '', label);
    appendText(field, 'dd', '', formatValue(value));
    parent.append(field);
};

const sourceFieldLabels = {
    id_del_proceso: 'ID del proceso',
    id_del_portafolio: 'ID del portafolio',
    referencia_del_proceso: 'Referencia del proceso',
    nombre_del_procedimiento: 'Nombre del procedimiento',
    descripci_n_del_procedimiento: 'Descripción del procedimiento',
    entidad: 'Entidad',
    nit_entidad: 'NIT de la entidad',
    departamento_entidad: 'Departamento de la entidad',
    ciudad_entidad: 'Ciudad de la entidad',
    fecha_de_publicacion_del: 'Fecha de publicación',
    fecha_de_ultima_publicaci: 'Última publicación',
    fecha_de_recepcion_de: 'Fecha de recepción de respuestas',
    fecha_de_apertura_de_respuesta: 'Fecha de apertura de respuesta',
    precio_base: 'Precio base',
    modalidad_de_contratacion: 'Modalidad de contratación',
    estado_del_procedimiento: 'Estado del procedimiento',
    estado_de_apertura_del_proceso: 'Estado de apertura',
    fase: 'Fase',
    codigo_principal_de_categoria: 'Código de categoría',
    tipo_de_contrato: 'Tipo de contrato',
    subtipo_de_contrato: 'Subtipo de contrato',
    categorias_adicionales: 'Categorías adicionales',
    urlproceso: 'URL del proceso',
    uid: 'UID',
    numero_de_constancia: 'Número de constancia',
    numero_de_proceso: 'Número de proceso',
    objeto_a_contratar: 'Objeto a contratar',
    detalle_del_objeto_a_contratar: 'Detalle del objeto',
    nombre_entidad: 'Nombre de la entidad',
    nit_de_la_entidad: 'NIT de la entidad',
    municipio_entidad: 'Municipio de la entidad',
    cuantia_proceso: 'Cuantía del proceso',
    cuantia_contrato: 'Cuantía del contrato',
    moneda: 'Moneda',
    estado_del_proceso: 'Estado del proceso',
    ruta_proceso_en_secop_i: 'URL del proceso en SECOP I'
};

const renderSourceData = (container, sourceData) => {
    if (!sourceData || typeof sourceData !== 'object') return;
    const entries = Object.entries(sourceData).filter(([, value]) => hasValue(value));
    if (!entries.length) return;

    const section = document.createElement('section');
    section.className = 'tenders-source-data';
    appendText(section, 'h4', '', 'Datos disponibles en SECOP');
    const grid = document.createElement('dl');
    grid.className = 'tenders-source-data-grid';
    entries.forEach(([key, value]) => {
        appendDetailField(grid, sourceFieldLabels[key] || key.replaceAll('_', ' '), value);
    });
    section.append(grid);
    container.append(section);
};

const renderList = (container, items) => {
    const uniqueItems = deduplicateItems(items);
    container.replaceChildren();

    if (!uniqueItems.length) {
        const emptyState = document.createElement('div');
        emptyState.className = 'licitaciones-empty';
        appendText(emptyState, 'strong', '', 'No hay oportunidades para mostrar');
        appendText(emptyState, 'p', '', 'El servicio aun no ha calculado resultados para este tenant.');
        container.append(emptyState);
        return;
    }

    uniqueItems.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'licitaciones-opportunity';
        card.setAttribute('role', 'listitem');

        const heading = document.createElement('div');
        heading.className = 'licitaciones-opportunity-heading';
        const titleBlock = document.createElement('div');
        appendText(titleBlock, 'span', 'licitaciones-source', item.source || 'SECOP');
        appendText(titleBlock, 'h3', '', item.title || 'Oportunidad sin titulo');
        heading.append(titleBlock);

        const score = document.createElement('div');
        score.className = 'licitaciones-score';
        appendText(score, 'strong', '', `${item.fit_score ?? 0}`);
        appendText(score, 'span', '', 'compatibilidad');
        heading.append(score);
        card.append(heading);

        const metadata = document.createElement('div');
        metadata.className = 'licitaciones-metadata';
        appendText(metadata, 'span', '', item.entity || 'Entidad no disponible');
        if (item.reference) appendText(metadata, 'span', '', `Ref.: ${item.reference}`);
        const location = [item.city, item.department].filter(Boolean).join(', ');
        if (location) appendText(metadata, 'span', '', location);
        appendText(metadata, 'span', '', `Cierre: ${formatDate(item.deadline)}`);
        appendText(metadata, 'span', '', formatCurrency(item.amount));
        if (item.last_published_at) appendText(metadata, 'span', '', `Actualizada: ${formatDate(item.last_published_at)}`);
        card.append(metadata);

        const detail = document.createElement('div');
        detail.className = 'licitaciones-detail';
        const reasons = document.createElement('div');
        appendText(reasons, 'h4', '', 'Coincidencias');
        const reasonsList = document.createElement('ul');
        (item.reasons || []).slice(0, 3).forEach((reason) => appendText(reasonsList, 'li', '', reason));
        reasons.append(reasonsList);
        detail.append(reasons);

        const risks = document.createElement('div');
        appendText(risks, 'h4', '', 'Por validar');
        const risksList = document.createElement('ul');
        (item.risks || []).slice(0, 3).forEach((risk) => appendText(risksList, 'li', '', risk));
        risks.append(risksList);
        detail.append(risks);
        card.append(detail);

        const actions = document.createElement('div');
        actions.className = 'licitaciones-actions';
        const sourceLink = document.createElement('a');
        sourceLink.className = 'btn btn-outline-secondary btn-sm';
        sourceLink.href = item.source_url || '#';
        sourceLink.target = '_blank';
        sourceLink.rel = 'noopener noreferrer';
        sourceLink.textContent = 'Ver fuente';
        actions.append(sourceLink);
        const states = document.createElement('div');
        states.className = 'licitaciones-states';
        const eligibilityState = item.eligibility_state || 'unknown';
        const dataConfidence = item.data_confidence || 'low';
        appendBadge(
            states,
            `licitaciones-state licitaciones-state-${eligibilityState}`,
            eligibilityIcons[eligibilityState] || eligibilityIcons.unknown,
            eligibilityLabels[eligibilityState] || 'Sin informacion',
            `Aptitud: ${eligibilityLabels[eligibilityState] || 'Sin informacion'}`
        );
        appendBadge(
            states,
            `licitaciones-confidence licitaciones-confidence-${dataConfidence}`,
            confidenceIcons[dataConfidence] || confidenceIcons.low,
            `Confianza ${confidenceLabels[dataConfidence] || 'baja'}`,
            `Confianza de los datos: ${confidenceLabels[dataConfidence] || 'baja'}`
        );
        actions.append(states);
        const interestedButton = document.createElement('button');
        interestedButton.className = 'tenders-icon-action licitaciones-feedback-action licitaciones-feedback-interested';
        interestedButton.type = 'button';
        interestedButton.dataset.tendersFeedback = item.opportunity_id;
        interestedButton.dataset.eventType = 'interested';
        interestedButton.dataset.defaultTitle = 'Marcar como interesada';
        interestedButton.dataset.selectedTitle = 'Interés registrado';
        interestedButton.title = interestedButton.dataset.defaultTitle;
        interestedButton.setAttribute('aria-pressed', 'false');
        interestedButton.setAttribute('aria-label', 'Marcar como interesada');
        interestedButton.innerHTML = '<i class="fa-light fa-thumbs-up" aria-hidden="true"></i>';
        actions.append(interestedButton);
        const dismissButton = document.createElement('button');
        dismissButton.className = 'tenders-icon-action licitaciones-feedback-action licitaciones-feedback-not_interested';
        dismissButton.type = 'button';
        dismissButton.dataset.tendersFeedback = item.opportunity_id;
        dismissButton.dataset.eventType = 'not_interested';
        dismissButton.dataset.defaultTitle = 'Marcar como no interesada';
        dismissButton.dataset.selectedTitle = 'Marcada como no interesada';
        dismissButton.title = dismissButton.dataset.defaultTitle;
        dismissButton.setAttribute('aria-pressed', 'false');
        dismissButton.setAttribute('aria-label', 'Marcar como no interesada');
        dismissButton.innerHTML = '<i class="fa-light fa-thumbs-down" aria-hidden="true"></i>';
        actions.append(dismissButton);
        const saveButton = document.createElement('button');
        saveButton.className = 'tenders-icon-action';
        saveButton.type = 'button';
        saveButton.dataset.tendersPipeline = item.opportunity_id;
        saveButton.title = 'Guardar oportunidad';
        saveButton.setAttribute('aria-label', 'Guardar oportunidad');
        saveButton.innerHTML = '<i class="fa-light fa-bookmark" aria-hidden="true"></i>';
        actions.append(saveButton);
        const detailButton = document.createElement('button');
        detailButton.className = 'tenders-icon-action';
        detailButton.type = 'button';
        detailButton.dataset.tendersDetail = item.opportunity_id;
        detailButton.title = 'Ver detalle y evidencia';
        detailButton.setAttribute('aria-label', 'Ver detalle y evidencia');
        detailButton.innerHTML = '<i class="fa-light fa-eye" aria-hidden="true"></i>';
        actions.append(detailButton);
        card.append(actions);

        const extendedDetail = document.createElement('div');
        extendedDetail.className = 'tenders-extended-detail';
        extendedDetail.hidden = true;
        extendedDetail.dataset.detailFor = item.opportunity_id;
        card.append(extendedDetail);
        setFeedbackState(card, item.feedback_state || null);

        container.append(card);
    });
};

const renderPagination = (elements, state) => {
    const totalPages = Number(state.totalPages || 0);
    elements.pagination.replaceChildren();
    if (totalPages === 0) return;

    const currentPage = Number(state.page || 1);
    const addNavigation = (label, page, disabled, ariaLabel) => {
        const item = document.createElement('li');
        item.className = `page-item${disabled ? ' disabled' : ''}`;
        const button = document.createElement('button');
        button.className = 'page-link';
        button.type = 'button';
        button.dataset.page = page;
        button.disabled = disabled;
        button.setAttribute('aria-label', ariaLabel);
        button.textContent = label;
        item.append(button);
        elements.pagination.append(item);
    };

    addNavigation('<', currentPage - 1, currentPage <= 1, 'Página anterior');

    const pages = [];
    const addPage = (page) => {
        if (pages[pages.length - 1] !== page) pages.push(page);
    };
    addPage(1);
    if (currentPage > 4) addPage('...');
    for (let page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) {
        addPage(page);
    }
    if (currentPage < totalPages - 3) addPage('...');
    if (totalPages > 1) addPage(totalPages);

    pages.forEach((page) => {
        const item = document.createElement('li');
        if (page === '...') {
            item.className = 'page-item disabled';
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-link';
            ellipsis.textContent = page;
            item.append(ellipsis);
        } else {
            item.className = `page-item${page === currentPage ? ' active' : ''}`;
            const button = document.createElement('button');
            button.className = 'page-link';
            button.type = 'button';
            button.dataset.page = page;
            button.textContent = page;
            if (page === currentPage) button.setAttribute('aria-current', 'page');
            item.append(button);
        }
        elements.pagination.append(item);
    });

    addNavigation('>', currentPage + 1, currentPage >= totalPages, 'Página siguiente');

    const sizeItem = document.createElement('li');
    sizeItem.className = 'page-item';
    const sizeLabel = document.createElement('label');
    sizeLabel.className = 'page-link licitaciones-page-size';
    sizeLabel.setAttribute('for', 'licitaciones-per-page');
    sizeLabel.textContent = 'Por página';
    const sizeSelect = document.createElement('select');
    sizeSelect.id = 'licitaciones-per-page';
    sizeSelect.setAttribute('aria-label', 'Oportunidades por página');
    [5, 10, 25, 50].forEach((size) => {
        const option = document.createElement('option');
        option.value = size;
        option.textContent = size;
        option.selected = size === state.perPage;
        sizeSelect.append(option);
    });
    sizeLabel.append(sizeSelect);
    sizeItem.append(sizeLabel);
    elements.pagination.append(sizeItem);
};

const loadDiscovery = async (elements, state, resetPage = false) => {
    if (resetPage) state.page = 1;
    const requestVersion = (state.discoveryRequestVersion || 0) + 1;
    state.discoveryRequestVersion = requestVersion;
    const isLatestRequest = () => state.discoveryRequestVersion === requestVersion;
    elements.status.textContent = 'Consultando resultados...';
    elements.status.classList.remove('is-error');
    elements.query.disabled = true;

    try {
        const response = await fetch('/admin/tenders/discovery', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': elements.csrfToken || ''
            },
            body: JSON.stringify({
                page: state.page,
                per_page: state.perPage,
                search: elements.search.value.trim() || null,
                status: elements.statusFilter.value || null,
                eligibility_state: elements.eligibilityFilter.value || null,
                data_confidence: elements.confidenceFilter.value || null,
                feedback_state: elements.feedbackFilter.value || null
            })
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }
        if (!isLatestRequest()) return;

        const items = Array.isArray(payload.data) ? payload.data : [];
        const meta = payload.meta || {};
        state.page = Number(meta.page || state.page);
        state.perPage = Number(meta.per_page || state.perPage);
        state.total = Number(meta.total || 0);
        state.totalPages = Number(meta.total_pages || 0);
        const calculatedAt = meta.calculated_at || payload.generated_at;
        elements.overview.textContent = `${state.total} oportunidades · pág. ${state.page}/${state.totalPages || 1} · ${formatShortDate(calculatedAt)}`;
        elements.status.textContent = '';
        renderList(elements.list, items);
        renderPagination(elements, state);
        loadSyncStatus(elements);
    } catch (error) {
        if (!isLatestRequest()) return;
        elements.status.textContent = error.message || 'No fue posible consultar Licitaciones.';
        elements.status.classList.add('is-error');
        elements.overview.textContent = '';
        renderList(elements.list, []);
        elements.pagination.replaceChildren();
    } finally {
        if (isLatestRequest()) elements.query.disabled = false;
    }
};

const syncFromSecop = async (elements, state) => {
    const syncPollMaxAttempts = 36;
    elements.sync.disabled = true;
    elements.query.disabled = true;
    elements.status.textContent = 'Solicitando actualización desde SECOP...';
    elements.status.classList.remove('is-error');

    try {
        const response = await fetch('/admin/tenders/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': elements.csrfToken || ''
            },
            body: JSON.stringify({
                source: 'all',
                page_size: 250,
                max_pages: 20,
                recheck_days: 7,
                recheck_page_size: 250,
                reset_cursor: false
            })
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }

        const syncData = payload.data || {};
        const sources = Array.isArray(syncData.sources) ? syncData.sources : ['secop1', 'secop2'];
        const syncRequestId = payload.request_id;
        const started = syncData.started !== false;
        let matchingRuns = [];

        let syncCompleted = false;
        for (let attempt = 0; attempt < syncPollMaxAttempts; attempt += 1) {
            await wait(attempt === 0 ? 500 : 10000);
            const runs = await loadSyncStatus(elements);
            matchingRuns = syncRequestId
                ? runs.filter((run) => run.parameters?.request_id === syncRequestId)
                : [];
            const relevantRuns = matchingRuns.length
                ? matchingRuns
                : runs.filter((run) => sources.includes(run.source));
            const failedRun = relevantRuns.find((run) => run.status === 'failed');
            if (failedRun) {
                throw new Error(failedRun.error || 'La actualización desde SECOP terminó con errores.');
            }
            if (relevantRuns.some((run) => run.status === 'running')) {
                const activeSources = relevantRuns
                    .filter((run) => run.status === 'running')
                    .map((run) => run.source.toUpperCase())
                    .join(' y ');
                elements.status.textContent = `Actualizando SECOP${activeSources ? ` (${activeSources})` : ''}...`;
                continue;
            }
            if (started && matchingRuns.length < sources.length) continue;
            if (!relevantRuns.length && started) continue;
            syncCompleted = true;
            break;
        }

        if (!syncCompleted) {
            elements.status.textContent = 'La actualización desde SECOP sigue en curso. El estado se actualizará en la próxima consulta.';
            elements.status.classList.remove('is-error');
            return;
        }
        await loadDiscovery(elements, state, true);
        elements.status.textContent = '';
    } catch (error) {
        elements.status.textContent = error.message || 'No fue posible actualizar desde SECOP.';
        elements.status.classList.add('is-error');
    } finally {
        elements.query.disabled = false;
        elements.sync.disabled = false;
    }
};

const requestTendersAction = async (elements, button, url, body, idempotencyKey = null, onSuccess = null) => {
    if (!button || button.dataset.pending === 'true') return false;
    button.disabled = true;
    button.dataset.pending = 'true';
    elements.status.classList.remove('is-error');
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': elements.csrfToken || ''
    };
    if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey;
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers,
            body: JSON.stringify(body)
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }
        elements.status.textContent = payload.message || 'Acción guardada.';
        if (onSuccess) await onSuccess(payload);
        return true;
    } catch (error) {
        elements.status.textContent = error.message || 'No fue posible guardar la acción.';
        elements.status.classList.add('is-error');
        return false;
    } finally {
        button.disabled = false;
        delete button.dataset.pending;
    }
};

const loadOpportunityDetail = async (elements, button) => {
    const opportunityId = button.dataset.tendersDetail;
    const detail = elements.list.querySelector(`[data-detail-for="${CSS.escape(opportunityId)}"]`);
    if (!detail) return;
    if (!detail.hidden) {
        detail.hidden = true;
        return;
    }
    button.disabled = true;
    try {
        const response = await fetch(`/admin/tenders/opportunities/${encodeURIComponent(opportunityId)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }
        const item = payload.data || {};
        detail.replaceChildren();
        appendText(detail, 'p', 'tenders-detail-description', item.description || item.title || 'Descripción no disponible.');
        const fields = document.createElement('dl');
        fields.className = 'tenders-detail-grid';
        [
            ['Referencia', item.reference],
            ['ID del proceso', item.opportunity_id],
            ['Entidad', item.entity],
            ['NIT de la entidad', item.entity_nit],
            ['Ubicación', [item.city, item.department].filter(Boolean).join(', ')],
            ['Estado', item.source_status || item.status],
            ['Estado de apertura', item.opening_status],
            ['Fase', item.phase],
            ['Modalidad', item.procurement_method],
            ['Tipo de contrato', item.contract_type],
            ['Código de categoría', item.category_code],
            ['Categorías', item.category_text],
            ['Valor', item.amount === null || item.amount === undefined ? null : formatCurrency(item.amount)],
            ['Fecha de cierre', item.deadline ? formatDate(item.deadline) : null],
            ['Publicado', item.published_at ? formatDate(item.published_at) : null],
            ['Última actualización', item.last_published_at ? formatDate(item.last_published_at) : null]
        ].forEach(([label, value]) => appendDetailField(fields, label, value));
        detail.append(fields);

        const documents = item.documents || [];
        if (documents.length) {
            const documentsSection = document.createElement('section');
            documentsSection.className = 'tenders-detail-documents';
            appendText(documentsSection, 'h4', '', `Documentos (${documents.length})`);
            const documentList = document.createElement('ul');
            documents.forEach((documentItem) => {
                const listItem = document.createElement('li');
                const link = document.createElement('a');
                link.href = documentItem.source_url || '#';
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = documentItem.filename || documentItem.document_id || 'Documento';
                listItem.append(link);
                if (documentItem.status) appendText(listItem, 'span', '', ` (${documentItem.status})`);
                documentList.append(listItem);
            });
            documentsSection.append(documentList);
            detail.append(documentsSection);
        }

        renderSourceData(detail, item.source_data);
        const sourceMeta = document.createElement('div');
        sourceMeta.className = 'tenders-extended-detail-meta';
        appendText(sourceMeta, 'span', '', `Referencia: ${item.reference || 'No disponible'}`);
        appendText(sourceMeta, 'span', '', `Documentos: ${documents.length}`);
        appendText(sourceMeta, 'span', '', `Evidencias: ${(item.evidence || []).length}`);
        detail.append(sourceMeta);
        if ((item.evidence || []).length) {
            const evidenceList = document.createElement('ul');
            evidenceList.className = 'tenders-evidence-list';
            item.evidence.slice(0, 5).forEach((evidence) => {
                appendText(evidenceList, 'li', '', `${evidence.page ? `Página ${evidence.page}: ` : ''}${evidence.quote || ''}`);
            });
            detail.append(evidenceList);
        }
        detail.hidden = false;
    } catch (error) {
        elements.status.textContent = error.message || 'No fue posible consultar la evidencia.';
        elements.status.classList.add('is-error');
    } finally {
        button.disabled = false;
    }
};

const loadSyncStatus = async (elements) => {
    try {
        const response = await fetch('/admin/tenders/sync-status?limit=20', {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0 || !Array.isArray(payload.data)) return [];
        const sourceLabels = { secop1: 'SECOP I', secop2: 'SECOP II' };
        const stateLabels = {
            succeeded: 'correcta',
            partial: 'parcial',
            failed: 'fallida',
            running: 'en curso'
        };
        const latestBySource = new Map();
        payload.data.forEach((run) => {
            if (!latestBySource.has(run.source)) latestBySource.set(run.source, run);
        });
        const summary = Array.from(latestBySource.values()).map((run) => (
            `${sourceLabels[run.source] || run.source}: ${stateLabels[run.status] || run.status}`
        ));
        if (summary.length) elements.syncStatus.textContent = summary.join(' · ');
        return payload.data;
    } catch (error) {
        // Discovery stays usable when the optional operational summary is unavailable.
        return [];
    }
};

const init = () => {
    initializeTendersContext();
    initializeTendersPipeline();

    const elements = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        query: document.getElementById('licitaciones-query-button'),
        sync: document.getElementById('licitaciones-sync-button'),
        search: document.getElementById('licitaciones-search'),
        statusFilter: document.getElementById('licitaciones-status-filter'),
        eligibilityFilter: document.getElementById('licitaciones-eligibility-filter'),
        confidenceFilter: document.getElementById('licitaciones-confidence-filter'),
        feedbackFilter: document.getElementById('licitaciones-feedback-filter'),
        container: document.getElementById('licitaciones-container'),
        pageContent: document.getElementById('erp-app-content'),
        status: document.getElementById('licitaciones-status'),
        syncStatus: document.getElementById('licitaciones-sync-status'),
        overview: document.getElementById('licitaciones-overview'),
        list: document.getElementById('licitaciones-list'),
        pagination: document.getElementById('licitaciones-pagination')
    };
    const state = {
        page: 1,
        perPage: 10,
        total: 0,
        totalPages: 0,
        discoveryRequestVersion: 0
    };

    if (!elements.query || !elements.sync || !elements.search || !elements.statusFilter || !elements.eligibilityFilter || !elements.confidenceFilter || !elements.feedbackFilter || !elements.container || !elements.pageContent || !elements.status || !elements.syncStatus || !elements.list || !elements.pagination) return;
    const requestPage = () => {
        scrollDiscoveryToTop(elements);
        loadDiscovery(elements, state, true);
    };
    elements.query.addEventListener('click', requestPage);
    elements.sync.addEventListener('click', () => syncFromSecop(elements, state));
    elements.list.addEventListener('click', (event) => {
        const detailButton = event.target.closest('[data-tenders-detail]');
        if (detailButton) {
            loadOpportunityDetail(elements, detailButton);
            return;
        }
        const feedbackButton = event.target.closest('[data-tenders-feedback]');
        if (feedbackButton) {
            const key = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;
            const eventType = feedbackButton.dataset.eventType;
            requestTendersAction(
                elements,
                feedbackButton,
                '/admin/tenders/feedback',
                {
                    opportunity_id: feedbackButton.dataset.tendersFeedback,
                    event_type: eventType,
                    reason_code: eventType === 'not_interested' ? 'manual_dismissal' : null
                },
                key,
                (payload) => setFeedbackState(
                    feedbackButton.closest('.licitaciones-opportunity'),
                    payload.data?.feedback_state || eventType
                )
            );
            return;
        }
        const pipelineButton = event.target.closest('[data-tenders-pipeline]');
        if (pipelineButton) {
            requestTendersAction(
                elements,
                pipelineButton,
                `/admin/tenders/opportunities/${encodeURIComponent(pipelineButton.dataset.tendersPipeline)}/save`,
                {},
                null,
                () => openTendersOpportunity(pipelineButton.dataset.tendersPipeline)
            );
        }
    });
    [elements.statusFilter, elements.eligibilityFilter, elements.confidenceFilter, elements.feedbackFilter].forEach((filter) => {
        filter.addEventListener('change', requestPage);
    });
    elements.search.addEventListener('change', requestPage);
    elements.search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') requestPage();
    });
    elements.pagination.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');
        if (!button || button.disabled) return;
        const page = Number(button.dataset.page);
        if (!Number.isInteger(page) || page < 1 || page === state.page) return;
        state.page = page;
        scrollDiscoveryToTop(elements);
        loadDiscovery(elements, state);
    });
    elements.pagination.addEventListener('change', (event) => {
        if (event.target.id !== 'licitaciones-per-page') return;
        state.perPage = Number(event.target.value);
        state.page = 1;
        scrollDiscoveryToTop(elements);
        loadDiscovery(elements, state);
    });
    loadDiscovery(elements, state);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}