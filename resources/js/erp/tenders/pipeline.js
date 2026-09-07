const stages = [
    ['saved', 'Guardada'],
    ['reviewing', 'En revisión'],
    ['preparing', 'En preparación'],
    ['submitted', 'Presentada'],
    ['won', 'Ganada'],
    ['lost', 'Perdida'],
    ['archived', 'Archivada']
];

const stageLabels = Object.fromEntries(stages);
const pipelineColumnCount = 8;

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

const formatDateTime = (value) => {
    if (!value) return 'Fecha no disponible';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Fecha no disponible';
    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(date);
};

const formatDateTimeInput = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (part) => String(part).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const appendText = (parent, tagName, className, text) => {
    const element = document.createElement(tagName);
    element.className = className;
    element.textContent = text ?? '';
    parent.append(element);
    return element;
};

const appendIconButton = (parent, icon, label, dataAttribute) => {
    const button = document.createElement('button');
    button.className = 'tenders-icon-action';
    button.type = 'button';
    button.dataset[dataAttribute] = 'true';
    button.title = label;
    button.setAttribute('aria-label', label);
    const iconElement = document.createElement('i');
    iconElement.className = `fa-light ${icon}`;
    iconElement.setAttribute('aria-hidden', 'true');
    button.append(iconElement);
    parent.append(button);
    return button;
};

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const confirmPipelineAction = async (title, text, confirmButtonText) => {
    if (typeof window.Swal?.fire !== 'function') {
        window.alertWarning?.('No fue posible abrir la confirmación. La acción no se ejecutó.');
        return false;
    }
    const result = await window.Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });
    return result.isConfirmed;
};

const createStageSelect = (item) => {
    const select = document.createElement('select');
    select.className = `form-select form-select-sm tenders-pipeline-stage-select tenders-pipeline-stage-${item.stage || 'saved'}`;
    select.dataset.tendersPipelineStage = item.opportunity_id;
    select.setAttribute('aria-label', `Etapa de ${item.title || 'la oportunidad'}`);
    stages.forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        option.selected = value === (item.stage || 'saved');
        select.append(option);
    });
    return select;
};

const renderPipeline = (container, items) => {
    container.replaceChildren();
    if (!items.length) {
        const row = document.createElement('tr');
        row.className = 'tenders-pipeline-empty-row';
        const cell = document.createElement('td');
        cell.colSpan = pipelineColumnCount;
        const empty = document.createElement('div');
        empty.className = 'tenders-pipeline-empty';
        appendText(empty, 'strong', '', 'No hay oportunidades para mostrar');
        appendText(empty, 'p', '', 'Guarda una oportunidad desde Discovery para verla aquí.');
        cell.append(empty);
        row.append(cell);
        container.append(row);
        return;
    }

    items.forEach((item) => {
        const row = document.createElement('tr');
        row.className = 'tenders-pipeline-item';
        row.dataset.opportunityId = item.opportunity_id;

        const opportunity = document.createElement('td');
        opportunity.className = 'tenders-pipeline-opportunity';
        appendText(opportunity, 'span', 'tenders-pipeline-source', item.reference || item.opportunity_id || 'SECOP');
        appendText(opportunity, 'strong', 'tenders-pipeline-title', item.title || 'Oportunidad sin título');
        row.append(opportunity);

        const entity = document.createElement('td');
        entity.className = 'tenders-pipeline-entity';
        entity.textContent = item.entity || 'Entidad no disponible';
        row.append(entity);

        const stage = document.createElement('td');
        stage.className = 'tenders-pipeline-stage-cell';
        stage.append(createStageSelect(item));
        row.append(stage);

        const amount = document.createElement('td');
        amount.className = 'tenders-pipeline-amount text-end';
        amount.textContent = formatCurrency(item.amount);
        row.append(amount);

        const deadline = document.createElement('td');
        deadline.className = 'tenders-pipeline-date text-center';
        deadline.textContent = formatDate(item.deadline);
        row.append(deadline);

        const dueAt = document.createElement('td');
        dueAt.className = 'tenders-pipeline-date text-center';
        dueAt.textContent = formatDate(item.due_at);
        row.append(dueAt);

        const followUp = document.createElement('td');
        followUp.className = 'tenders-pipeline-follow-up';
        if (item.outcome) appendText(followUp, 'span', 'tenders-pipeline-outcome', item.outcome);
        if (item.notes) appendText(followUp, 'span', 'tenders-pipeline-notes', item.notes);
        if (!item.outcome && !item.notes) appendText(followUp, 'span', 'tenders-pipeline-muted-value', 'Sin notas');
        row.append(followUp);

        const actions = document.createElement('div');
        actions.className = 'tenders-pipeline-actions';
        if (item.source_url) {
            const sourceLink = document.createElement('a');
            sourceLink.className = 'btn btn-outline-secondary btn-sm';
            sourceLink.href = item.source_url;
            sourceLink.target = '_blank';
            sourceLink.rel = 'noopener noreferrer';
            sourceLink.textContent = 'Ver fuente';
            actions.append(sourceLink);
        }
        const actionsCell = document.createElement('td');
        actionsCell.className = 'tenders-pipeline-actions-cell text-end';
        const detailButton = appendIconButton(actions, 'fa-eye', 'Ver detalle', 'tendersPipelineDetail');
        detailButton.dataset.tendersPipelineDetail = item.opportunity_id;
        detailButton.setAttribute('aria-expanded', 'false');
        const historyButton = appendIconButton(actions, 'fa-clock-rotate-left', 'Ver seguimientos', 'tendersPipelineHistory');
        historyButton.dataset.tendersPipelineHistory = item.opportunity_id;
        if (item.stage !== 'archived') {
            const archiveButton = appendIconButton(actions, 'fa-box-archive', 'Archivar oportunidad', 'tendersPipelineArchive');
            archiveButton.dataset.tendersPipelineArchive = item.opportunity_id;
        }
        actionsCell.append(actions);
        row.append(actionsCell);

        const detailRow = document.createElement('tr');
        detailRow.className = 'tenders-pipeline-detail-row';
        detailRow.hidden = true;
        detailRow.dataset.opportunityDetailFor = item.opportunity_id;
        const detailCell = document.createElement('td');
        detailCell.colSpan = pipelineColumnCount;
        const detail = document.createElement('div');
        detail.className = 'tenders-pipeline-detail';
        detail.hidden = true;
        detailCell.append(detail);
        detailRow.append(detailCell);
        container.append(row, detailRow);
    });
};

const renderPagination = (elements, state) => {
    elements.pagination.replaceChildren();
    if (state.totalPages <= 1 && state.total <= 5) return;

    const addPage = (label, page, disabled = false, active = false) => {
        const item = document.createElement('li');
        item.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
        const button = document.createElement('button');
        button.className = 'page-link';
        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;
        if (!disabled) button.dataset.page = page;
        if (active) button.setAttribute('aria-current', 'page');
        item.append(button);
        elements.pagination.append(item);
    };

    addPage('<', state.page - 1, state.page <= 1);
    for (let page = 1; page <= state.totalPages; page += 1) {
        if (page <= 3 || page >= state.totalPages - 2 || Math.abs(page - state.page) <= 1) {
            addPage(String(page), page, false, page === state.page);
        } else if (page === 4 || page === state.totalPages - 3) {
            const ellipsis = document.createElement('li');
            ellipsis.className = 'page-item disabled';
            appendText(ellipsis, 'span', 'page-link', '...');
            elements.pagination.append(ellipsis);
        }
    }
    addPage('>', state.page + 1, state.page >= state.totalPages);

    const sizeItem = document.createElement('li');
    sizeItem.className = 'page-item';
    const sizeLabel = document.createElement('label');
    sizeLabel.className = 'page-link tenders-pipeline-page-size';
    sizeLabel.setAttribute('for', 'tenders-pipeline-per-page');
    sizeLabel.textContent = 'Por página';
    const sizeSelect = document.createElement('select');
    sizeSelect.id = 'tenders-pipeline-per-page';
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

const renderPipelineHistory = (elements, entries) => {
    elements.historyList.replaceChildren();
    if (!entries.length) {
        const empty = document.createElement('p');
        empty.className = 'tenders-pipeline-history-empty';
        empty.textContent = 'Aún no hay seguimientos para esta oportunidad.';
        elements.historyList.append(empty);
        return;
    }

    entries.forEach((entry) => {
        const record = document.createElement('article');
        record.className = 'tenders-pipeline-history-item';
        record.dataset.pipelineEntryId = entry.id;
        record.setAttribute('role', 'listitem');

        const header = document.createElement('div');
        header.className = 'tenders-pipeline-history-item-header';
        appendText(header, 'strong', 'tenders-pipeline-history-stage', stageLabels[entry.stage] || entry.stage);
        const createdAt = appendText(header, 'time', 'tenders-pipeline-history-date', formatDateTime(entry.created_at));
        createdAt.dateTime = entry.created_at || '';
        record.append(header);

        const metadata = document.createElement('div');
        metadata.className = 'tenders-pipeline-history-metadata';
        if (entry.due_at) appendText(metadata, 'span', '', `Próxima tarea: ${formatDate(entry.due_at)}`);
        if (entry.outcome) appendText(metadata, 'span', '', `Resultado: ${entry.outcome}`);
        if (metadata.childElementCount) record.append(metadata);
        if (entry.notes) appendText(record, 'p', 'tenders-pipeline-history-notes', entry.notes);

        const actions = document.createElement('div');
        actions.className = 'tenders-pipeline-history-actions';
        const editButton = appendIconButton(actions, 'fa-pen-to-square', 'Editar registro', 'tendersPipelineHistoryEdit');
        editButton.dataset.tendersPipelineHistoryEdit = entry.id;
        const deleteButton = appendIconButton(actions, 'fa-trash-can', 'Eliminar registro', 'tendersPipelineHistoryDelete');
        deleteButton.dataset.tendersPipelineHistoryDelete = entry.id;
        record.append(actions);
        elements.historyList.append(record);
    });
};

const moveModalToBody = (modal) => {
    if (modal && modal.parentElement !== document.body) document.body.append(modal);
};

const showModal = (modal) => {
    if (!modal) return;
    moveModalToBody(modal);
    if (window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal, {
            backdrop: true,
            focus: true,
            keyboard: true
        }).show();
        return;
    }
    modal.hidden = false;
    modal.classList.add('show');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
};

const hideModal = (modal) => {
    if (!modal) return;
    if (window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        return;
    }
    modal.hidden = true;
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
};

const loadPipelineHistory = async (elements, opportunityId) => {
    const requestVersion = (elements.historyRequestVersion || 0) + 1;
    elements.historyRequestVersion = requestVersion;
    elements.historyStatus.textContent = 'Consultando seguimientos...';
    elements.historyStatus.classList.remove('is-error');
    try {
        const response = await fetch(`/admin/tenders/opportunities/${encodeURIComponent(opportunityId)}/pipeline?page=1&per_page=50`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        if (elements.historyRequestVersion !== requestVersion) return;
        elements.historyEntries = Array.isArray(payload.data) ? payload.data : [];
        elements.historyStatus.textContent = `${elements.historyEntries.length} registro${elements.historyEntries.length === 1 ? '' : 's'}`;
        renderPipelineHistory(elements, elements.historyEntries);
    } catch (error) {
        if (elements.historyRequestVersion !== requestVersion) return;
        elements.historyEntries = [];
        elements.historyStatus.textContent = error.message || 'No fue posible consultar los seguimientos.';
        elements.historyStatus.classList.add('is-error');
        renderPipelineHistory(elements, []);
    }
};

const resetEditorForNew = (elements, item) => {
    elements.editorForm.elements.pipeline_entry_id.value = '';
    elements.editorModal.querySelector('.modal-title').textContent = 'Nuevo seguimiento';
    elements.editorStage.value = item.stage || 'saved';
    elements.editorStage.dispatchEvent(new Event('change', { bubbles: true }));
    elements.editorDueAt.value = '';
    elements.editorOutcome.value = '';
    elements.editorNotes.value = '';
};

const initializeEditor = (elements, item, entry = null) => {
    elements.editorForm.elements.opportunity_id.value = item.opportunity_id || '';
    elements.editorTitle.textContent = item.title || 'Oportunidad sin título';
    elements.editorEntity.textContent = item.entity || 'Entidad no disponible';
    resetEditorForNew(elements, item);
    if (entry) {
        elements.editorForm.elements.pipeline_entry_id.value = entry.id;
        elements.editorModal.querySelector('.modal-title').textContent = 'Editar registro';
        elements.editorStage.value = entry.stage || item.stage || 'saved';
        elements.editorStage.dispatchEvent(new Event('change', { bubbles: true }));
        elements.editorDueAt.value = formatDateTimeInput(entry.due_at);
        elements.editorOutcome.value = entry.outcome || '';
        elements.editorNotes.value = entry.notes || '';
    }
    elements.editorStatus.textContent = '';
    elements.editorStatus.classList.remove('is-error');
    showModal(elements.editorModal);
    loadPipelineHistory(elements, item.opportunity_id);
};

const findItem = (state, opportunityId) => state.items.find((item) => item.opportunity_id === opportunityId);

const updatePipeline = async (elements, state, opportunityId, values, control = null) => {
    const item = findItem(state, opportunityId);
    if (!item) return false;
    const previousStage = item.stage;
    const setStatus = (message, isError = false) => {
        elements.status.textContent = message;
        elements.status.classList.toggle('is-error', isError);
        if (elements.editorStatus) {
            elements.editorStatus.textContent = message;
            elements.editorStatus.classList.toggle('is-error', isError);
        }
    };
    if (control) control.disabled = true;
    setStatus('Guardando seguimiento de la oportunidad...');

    try {
        const response = await fetch('/admin/tenders/pipeline', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                opportunity_id: opportunityId,
                stage: values.stage ?? item.stage ?? 'saved',
                due_at: values.due_at ?? item.due_at ?? null,
                outcome: values.outcome ?? item.outcome ?? null,
                notes: values.notes ?? item.notes ?? null
            })
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        setStatus(payload.message || 'Seguimiento de la oportunidad actualizado.');
        await loadPipeline(elements, state);
        return true;
    } catch (error) {
        setStatus(error.message || 'No fue posible actualizar el seguimiento de la oportunidad.', true);
        if (control) control.value = previousStage;
        return false;
    } finally {
        if (control) control.disabled = false;
    }
};

const savePipelineEntry = async (elements, state, opportunityId, values, entryId = null) => {
    const item = findItem(state, opportunityId);
    if (!item) return false;
    elements.editorStatus.textContent = entryId ? 'Guardando cambios...' : 'Creando registro...';
    elements.editorStatus.classList.remove('is-error');
    const url = entryId
        ? `/admin/tenders/opportunities/${encodeURIComponent(opportunityId)}/pipeline/${encodeURIComponent(entryId)}`
        : '/admin/tenders/pipeline';
    const body = entryId
        ? values
        : { opportunity_id: opportunityId, ...values };
    try {
        const response = await fetch(url, {
            method: entryId ? 'PATCH' : 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(body)
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        await loadPipeline(elements, state);
        await loadPipelineHistory(elements, opportunityId);
        elements.editorStatus.textContent = entryId ? 'Registro actualizado.' : 'Registro creado.';
        return true;
    } catch (error) {
        elements.editorStatus.textContent = error.message || 'No fue posible guardar el registro.';
        elements.editorStatus.classList.add('is-error');
        return false;
    }
};

const deletePipelineEntry = async (elements, state, opportunityId, entryId, button) => {
    if (!(await confirmPipelineAction(
        'Eliminar registro',
        'El registro se ocultará del historial y el estado actual se recalculará.',
        'Sí, eliminar'
    ))) return;
    button.disabled = true;
    elements.editorStatus.textContent = 'Eliminando registro...';
    elements.editorStatus.classList.remove('is-error');
    try {
        const response = await fetch(
            `/admin/tenders/opportunities/${encodeURIComponent(opportunityId)}/pipeline/${encodeURIComponent(entryId)}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            }
        );
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        await loadPipeline(elements, state);
        await loadPipelineHistory(elements, opportunityId);
        const currentItem = findItem(state, opportunityId);
        if (currentItem) resetEditorForNew(elements, currentItem);
        elements.editorStatus.textContent = 'Registro eliminado.';
    } catch (error) {
        elements.editorStatus.textContent = error.message || 'No fue posible eliminar el registro.';
        elements.editorStatus.classList.add('is-error');
    } finally {
        button.disabled = false;
    }
};

const renderOpportunityDetail = (container, item) => {
    container.replaceChildren();
    appendText(container, 'p', 'tenders-pipeline-detail-description', item.description || item.title || 'Descripción no disponible.');
    const fields = document.createElement('dl');
    fields.className = 'tenders-pipeline-detail-grid';
    [
        ['Referencia', item.reference],
        ['Proceso', item.opportunity_id],
        ['Estado', item.source_status || item.status],
        ['Fase', item.phase],
        ['Modalidad', item.procurement_method],
        ['Tipo de contrato', item.contract_type],
        ['Ubicación', [item.city, item.department].filter(Boolean).join(', ')],
        ['Valor', item.amount === null || item.amount === undefined ? null : formatCurrency(item.amount)],
        ['Fecha de cierre', formatDate(item.deadline)]
    ].forEach(([label, value]) => {
        const field = document.createElement('div');
        appendText(field, 'dt', '', label);
        appendText(field, 'dd', '', value || 'No disponible');
        fields.append(field);
    });
    container.append(fields);
    if (Array.isArray(item.documents) && item.documents.length) {
        const documents = document.createElement('section');
        appendText(documents, 'h4', '', `Documentos (${item.documents.length})`);
        const list = document.createElement('ul');
        item.documents.forEach((documentItem) => {
            const listItem = document.createElement('li');
            const link = document.createElement('a');
            link.href = documentItem.source_url || '#';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = documentItem.filename || documentItem.document_id || 'Documento';
            listItem.append(link);
            documents.append(listItem);
        });
        documents.append(list);
        container.append(documents);
    }
};

const loadOpportunityDetail = async (elements, button) => {
    const row = button.closest('tr[data-opportunity-id]');
    const detailRow = row?.nextElementSibling;
    const detail = detailRow?.querySelector('.tenders-pipeline-detail');
    if (!row || !detailRow || !detail) return;
    if (!detailRow.hidden) {
        detailRow.hidden = true;
        detail.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        return;
    }
    button.disabled = true;
    detailRow.hidden = false;
    detail.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    detail.replaceChildren();
    appendText(detail, 'p', '', 'Consultando detalle...');
    try {
        const opportunityId = button.dataset.tendersPipelineDetail;
        const response = await fetch(`/admin/tenders/opportunities/${encodeURIComponent(opportunityId)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        renderOpportunityDetail(detail, payload.data || {});
    } catch (error) {
        detail.replaceChildren();
        appendText(detail, 'p', 'tenders-pipeline-detail-error', error.message || 'No fue posible consultar el detalle.');
    } finally {
        button.disabled = false;
    }
};

const loadPipeline = async (elements, state, resetPage = false) => {
    if (resetPage) state.page = 1;
    const requestVersion = state.requestVersion + 1;
    state.requestVersion = requestVersion;
    elements.status.textContent = 'Consultando oportunidades...';
    elements.status.classList.remove('is-error');
    elements.refresh.disabled = true;

    try {
        const params = new URLSearchParams({
            page: String(state.page),
            per_page: String(state.perPage)
        });
        if (elements.stageFilter.value) params.set('stage', elements.stageFilter.value);
        if (elements.search.value.trim()) params.set('search', elements.search.value.trim());
        const response = await fetch(`/admin/tenders/applications?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.status === 0) throw new Error(payload.message || `HTTP ${response.status}`);
        if (state.requestVersion !== requestVersion) return;
        const meta = payload.meta || {};
        state.items = Array.isArray(payload.data) ? payload.data : [];
        state.page = Number(meta.page || state.page);
        state.perPage = Number(meta.per_page || state.perPage);
        state.total = Number(meta.total ?? state.items.length);
        state.totalPages = Number(meta.total_pages || (state.total ? Math.ceil(state.total / state.perPage) : 0));
        elements.overview.textContent = `${state.total} oportunidades · página ${state.page}/${state.totalPages || 1}`;
        elements.status.textContent = state.total === 0 ? 'No hay oportunidades para los filtros seleccionados.' : '';
        renderPipeline(elements.list, state.items);
        renderPagination(elements, state);
        state.loaded = true;
    } catch (error) {
        if (state.requestVersion !== requestVersion) return;
        elements.status.textContent = error.message || 'No fue posible consultar las oportunidades.';
        elements.status.classList.add('is-error');
        elements.overview.textContent = '';
        state.items = [];
        renderPipeline(elements.list, []);
        elements.pagination.replaceChildren();
    } finally {
        if (state.requestVersion === requestVersion) elements.refresh.disabled = false;
    }
};

let pipelineElements = null;
let pipelineState = null;

export const refreshTendersPipeline = ({ resetPage = false } = {}) => {
    if (!pipelineElements || !pipelineState) return Promise.resolve();
    return loadPipeline(pipelineElements, pipelineState, resetPage);
};

export const openTendersOpportunity = async (opportunityId) => {
    if (!pipelineElements || !pipelineState) return;
    if (pipelineElements.stageFilter && window.SearchableDropdown) {
        window.SearchableDropdown.setValue(pipelineElements.stageFilter, '', false);
    } else if (pipelineElements.stageFilter) {
        pipelineElements.stageFilter.value = '';
    }
    pipelineElements.search.value = '';
    const pipelineTab = document.getElementById('tenders-pipeline-tab');
    if (pipelineTab && !pipelineTab.classList.contains('active')) pipelineTab.click();
    await loadPipeline(pipelineElements, pipelineState, true);
    const row = pipelineElements.list.querySelector(
        `tr[data-opportunity-id="${CSS.escape(opportunityId)}"]`
    );
    if (!row) return;
    row.classList.remove('is-targeted');
    void row.offsetWidth;
    row.classList.add('is-targeted');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    window.setTimeout(() => row.classList.remove('is-targeted'), 2200);
};

export const initializeTendersPipeline = () => {
    if (pipelineElements) return refreshTendersPipeline();
    const stageFilter = document.getElementById('tenders-pipeline-stage');
    const search = document.getElementById('tenders-pipeline-search');
    const refresh = document.getElementById('tenders-pipeline-refresh');
    const status = document.getElementById('tenders-pipeline-status');
    const overview = document.getElementById('tenders-pipeline-overview');
    const list = document.getElementById('tenders-pipeline-list');
    const pagination = document.getElementById('tenders-pipeline-pagination');
    const editorModal = document.getElementById('tenders-pipeline-editor');
    const editorForm = document.getElementById('tenders-pipeline-editor-form');
    const editorTitle = document.getElementById('tenders-pipeline-editor-title');
    const editorEntity = document.getElementById('tenders-pipeline-editor-entity');
    const editorStage = document.getElementById('tenders-pipeline-editor-stage');
    const editorDueAt = document.getElementById('tenders-pipeline-editor-due-at');
    const editorOutcome = document.getElementById('tenders-pipeline-editor-outcome');
    const editorNotes = document.getElementById('tenders-pipeline-editor-notes');
    const editorStatus = document.getElementById('tenders-pipeline-editor-status');
    const historyStatus = document.getElementById('tenders-pipeline-history-status');
    const historyList = document.getElementById('tenders-pipeline-history-list');
    const editorSubmit = editorForm?.querySelector('button[type="submit"]');
    if (!stageFilter || !search || !refresh || !status || !overview || !list || !pagination || !editorModal || !editorForm || !editorStage || !editorDueAt || !editorOutcome || !editorNotes || !editorStatus || !historyStatus || !historyList || !editorSubmit) return;

    moveModalToBody(editorModal);

    pipelineElements = {
        stageFilter,
        search,
        refresh,
        status,
        overview,
        list,
        pagination,
        editorModal,
        editorForm,
        editorTitle,
        editorEntity,
        editorStage,
        editorDueAt,
        editorOutcome,
        editorNotes,
        editorStatus,
        historyStatus,
        historyList,
        historyEntries: [],
        historyRequestVersion: 0,
        editorSubmit
    };
    pipelineState = {
        items: [],
        page: 1,
        perPage: 10,
        total: 0,
        totalPages: 0,
        loaded: false,
        requestVersion: 0
    };

    stageFilter.addEventListener('change', () => loadPipeline(pipelineElements, pipelineState, true));
    search.addEventListener('change', () => loadPipeline(pipelineElements, pipelineState, true));
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') loadPipeline(pipelineElements, pipelineState, true);
    });
    refresh.addEventListener('click', () => loadPipeline(pipelineElements, pipelineState));
    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');
        if (!button || button.disabled) return;
        const page = Number(button.dataset.page);
        if (!Number.isInteger(page) || page < 1 || page === pipelineState.page) return;
        pipelineState.page = page;
        loadPipeline(pipelineElements, pipelineState);
    });
    pagination.addEventListener('change', (event) => {
        if (event.target.id !== 'tenders-pipeline-per-page') return;
        pipelineState.perPage = Number(event.target.value);
        loadPipeline(pipelineElements, pipelineState, true);
    });
    list.addEventListener('click', async (event) => {
        const detailButton = event.target.closest('[data-tenders-pipeline-detail]');
        if (detailButton) {
            await loadOpportunityDetail(pipelineElements, detailButton);
            return;
        }
        const historyButton = event.target.closest('[data-tenders-pipeline-history]');
        if (historyButton) {
            const item = findItem(pipelineState, historyButton.dataset.tendersPipelineHistory);
            if (item) initializeEditor(pipelineElements, item);
            return;
        }
        const archiveButton = event.target.closest('[data-tenders-pipeline-archive]');
        if (archiveButton) {
            const item = findItem(pipelineState, archiveButton.dataset.tendersPipelineArchive);
            if (!item || !(await confirmPipelineAction(
                'Archivar oportunidad',
                'Se creará un registro de archivado y dejará de aparecer en las etapas activas.',
                'Sí, archivar'
            ))) return;
            await updatePipeline(pipelineElements, pipelineState, item.opportunity_id, { stage: 'archived' }, archiveButton);
        }
    });
    list.addEventListener('change', async (event) => {
        const stageSelect = event.target.closest('[data-tenders-pipeline-stage]');
        if (!stageSelect) return;
        const item = findItem(pipelineState, stageSelect.dataset.tendersPipelineStage);
        if (!item || stageSelect.value === item.stage) return;
        await updatePipeline(pipelineElements, pipelineState, item.opportunity_id, { stage: stageSelect.value }, stageSelect);
    });
    historyList.addEventListener('click', async (event) => {
        const item = findItem(pipelineState, editorForm.elements.opportunity_id.value);
        if (!item) return;
        const editButton = event.target.closest('[data-tenders-pipeline-history-edit]');
        if (editButton) {
            const entry = pipelineElements.historyEntries.find((historyEntry) => String(historyEntry.id) === editButton.dataset.tendersPipelineHistoryEdit);
            if (entry) initializeEditor(pipelineElements, item, entry);
            return;
        }
        const deleteButton = event.target.closest('[data-tenders-pipeline-history-delete]');
        if (deleteButton) {
            await deletePipelineEntry(
                pipelineElements,
                pipelineState,
                item.opportunity_id,
                deleteButton.dataset.tendersPipelineHistoryDelete,
                deleteButton
            );
        }
    });
    editorForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (editorSubmit.disabled) return;
        editorSubmit.disabled = true;
        const opportunityId = editorForm.elements.opportunity_id.value;
        const entryId = editorForm.elements.pipeline_entry_id.value || null;
        try {
            const saved = await savePipelineEntry(pipelineElements, pipelineState, opportunityId, {
                stage: editorStage.value,
                due_at: editorDueAt.value || null,
                outcome: editorOutcome.value.trim() || null,
                notes: editorNotes.value.trim() || null
            }, entryId);
            if (saved && !entryId) hideModal(editorModal);
        } finally {
            editorSubmit.disabled = false;
        }
    });
    editorModal.querySelectorAll('[data-tenders-pipeline-close]').forEach((button) => {
        button.addEventListener('click', () => hideModal(editorModal));
    });

    const pipelineTab = document.getElementById('tenders-pipeline-tab');
    pipelineTab?.addEventListener('click', () => loadPipeline(pipelineElements, pipelineState, !pipelineState.loaded));
    if (document.getElementById('tenders-pipeline')?.classList.contains('active')) loadPipeline(pipelineElements, pipelineState);
};
