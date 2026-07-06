// ─── IA Marketing Report JS ───────────────────────────────────────────────────

let currentConversationId = null;
let _historySearchTimer   = null;
let _allClients           = [];
let _selectedClientEmail  = '';
let _currentClientEmail   = '';

// Parses JSON from a fetch Response; throws with the real server message if not JSON
function safeJson(r) {
    const ct = r.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
        return r.text().then(text => {
            const preview = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300);
            const err = new Error('El servidor devolvió un error (' + r.status + '): ' + preview);
            err.httpStatus = r.status;
            throw err;
        });
    }
    return r.json();
}

document.addEventListener('DOMContentLoaded', () => {
    loadClients();
    loadConversations();
    initDropzone();
    initGenerateBtn();
    initRegenerateBtn();
    initDownloadBtn();
    initSendBtn();
    initGenerateToggle();
    initHistorySearch();
    document.getElementById('ia-timeout-ok-btn').addEventListener('click', () => window.location.reload());
});

// ─── Collapsible generate card ────────────────────────────────────────────────
function initGenerateToggle() {
    var btn  = document.querySelector('#ia-generate-toggle .ia-toggle-btn');
    var body = document.getElementById('ia-generate-body');
    var icon = btn.querySelector('i');

    btn.addEventListener('click', function () {
        var isOpen = !body.classList.contains('d-none');
        body.classList.toggle('d-none', isOpen);
        icon.className = isOpen ? 'fa-light fa-plus' : 'fa-light fa-xmark';
        btn.setAttribute('aria-expanded', String(!isOpen));
    });
}

// ─── Load clients into smart dropdown ────────────────────────────────────────
function loadClients() {
    fetch('/admin/ia-assistant/marketing-report/get-clients')
        .then(safeJson)
        .then(function (res) {
            var optionsEl = document.getElementById('ia-client-options');
            if (res.status === 1 && res.data && res.data.length > 0) {
                _allClients = res.data;
                renderClientOptions(_allClients);
            } else {
                optionsEl.innerHTML = '<p class="ia-empty-text">No hay clientes disponibles.</p>';
            }
        })
        .catch(function () {
            document.getElementById('ia-client-options').innerHTML = '<p class="ia-empty-text">Error al cargar clientes.</p>';
        });

    // Toggle panel on trigger click
    document.getElementById('ia-client-trigger').addEventListener('click', function () {
        var panel = document.getElementById('ia-client-panel');
        panel.classList.toggle('d-none');
        if (!panel.classList.contains('d-none')) {
            document.getElementById('ia-client-search').focus();
        }
    });

    // Filter options on search input
    document.getElementById('ia-client-search').addEventListener('input', function (e) {
        var q = e.target.value.trim().toLowerCase();
        var filtered = q
            ? _allClients.filter(function (c) { return [c.name, c.lastname].filter(Boolean).join(' ').toLowerCase().includes(q); })
            : _allClients;
        renderClientOptions(filtered);
    });

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('ia-client-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            document.getElementById('ia-client-panel').classList.add('d-none');
        }
    });

    document.getElementById('ia-period-input').addEventListener('input', validateGenerateBtn);
}

function renderClientOptions(clients) {
    var optionsEl = document.getElementById('ia-client-options');
    if (clients.length === 0) {
        optionsEl.innerHTML = '<p class="ia-empty-text">Sin resultados.</p>';
        return;
    }
    var currentId = parseInt(document.getElementById('ia-client-dropdown').dataset.value);
    optionsEl.innerHTML = '';
    clients.forEach(function (c) {
        var name = [c.name, c.lastname].filter(Boolean).join(' ');
        var div  = document.createElement('div');
        div.className   = 'ia-client-option' + (c.id === currentId ? ' ia-client-option--active' : '');
        div.dataset.id  = c.id;
        div.textContent = name;
        div.addEventListener('click', function () { selectClient(c.id, name); });
        optionsEl.appendChild(div);
    });
}

function selectClient(id, name) {
    var dropdown    = document.getElementById('ia-client-dropdown');
    var triggerText = document.getElementById('ia-client-trigger-text');
    var panel       = document.getElementById('ia-client-panel');
    var search      = document.getElementById('ia-client-search');

    dropdown.dataset.value = id;
    triggerText.textContent = name;
    triggerText.classList.remove('ia-client-trigger__text--placeholder');

    // Store the client email for pre-filling the send modal
    var client = _allClients.find(function (c) { return c.id === id; });
    _selectedClientEmail = (client && client.email) ? client.email : '';

    panel.classList.add('d-none');
    search.value = '';
    renderClientOptions(_allClients);
    validateGenerateBtn();
}

// ─── Load conversation history list ──────────────────────────────────────────
function loadConversations(q) {
    var url = '/admin/ia-assistant/marketing-report/get-conversations' + (q ? '?q=' + encodeURIComponent(q) : '');
    fetch(url)
        .then(safeJson)
        .then(function (res) {
            var list = document.getElementById('ia-history-list');
            if (res.status === 1 && res.data && res.data.length > 0) {
                list.innerHTML = '';
                res.data.forEach(function (conv) {
                    var item = document.createElement('div');
                    item.className = 'ia-history-item' + (conv.id === currentConversationId ? ' ia-history-item--active' : '');
                    item.dataset.id = conv.id;
                    item.dataset.clientEmail = conv.client_email || '';

                    var statusLabel = 'Finalizado';
                    var statusClass = 'ia-status-badge--completed';
                    if (conv.status === 'processing') {
                        statusLabel = 'En proceso';
                        statusClass = 'ia-status-badge--processing';
                    } else if (conv.status === 'failed') {
                        statusLabel = 'Error';
                        statusClass = 'ia-status-badge--failed';
                    }

                    item.innerHTML =
                        '<div class="ia-history-item__header">' +
                            '<p class="ia-history-item__title">' + escapeHtml(conv.title) + '</p>' +
                            '<span class="ia-status-badge ' + statusClass + '">' + statusLabel + '</span>' +
                        '</div>' +
                        '<p class="ia-history-item__meta">' + escapeHtml(conv.client_name) + ' &mdash; ' + escapeHtml(conv.updated_at) + '</p>' +
                        '<p class="ia-history-item__turns">' + conv.turn_count + ' iteraci\u00f3n' + (conv.turn_count !== 1 ? 'es' : '') + '</p>';
                    item.addEventListener('click', function () { loadConversationTurn(conv.id); });
                    list.appendChild(item);
                });
            } else {
                list.innerHTML = '<p class="ia-empty-text">' + (q ? 'Sin resultados para esa b\u00fasqueda.' : 'No hay conversaciones anteriores.') + '</p>';
            }
        })
        .catch(function () {});
}

// ─── History search with debounce ─────────────────────────────────────────────
function initHistorySearch() {
    document.getElementById('ia-history-search').addEventListener('input', function (e) {
        clearTimeout(_historySearchTimer);
        _historySearchTimer = setTimeout(function () { loadConversations(e.target.value.trim()); }, 300);
    });
}

// ─── Load a historical conversation ──────────────────────────────────────────
function loadConversationTurn(conversationId) {
    fetch('/admin/ia-assistant/marketing-report/conversation/' + conversationId)
        .then(safeJson)
        .then(function (res) {
            if (res.status === 1) {
                currentConversationId = res.conversation.id;
                _currentClientEmail = (res.conversation.client && res.conversation.client.email) ? res.conversation.client.email : '';
                var turns    = res.conversation.turns;
                var lastTurn = turns[turns.length - 1];
                showReportPanel(
                    res.conversation.id,
                    res.conversation.title,
                    lastTurn ? lastTurn.turn_number : 1
                );
            }
        })
        .catch(function () {});
}

// ─── Dropzone ─────────────────────────────────────────────────────────────────
function initDropzone() {
    var dropzone   = document.getElementById('ia-dropzone');
    var fileInput  = document.getElementById('ia-file-input');
    var fileNameEl = document.getElementById('ia-file-name');

    dropzone.addEventListener('click', function () { fileInput.click(); });

    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('ia-dropzone--over');
    });
    dropzone.addEventListener('dragleave', function () { dropzone.classList.remove('ia-dropzone--over'); });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('ia-dropzone--over');
        var file = e.dataTransfer.files[0];
        if (file) setFile(file);
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    });

    function setFile(file) {
        fileInput._selectedFile = file;
        fileNameEl.textContent = file.name;
        fileNameEl.classList.remove('d-none');
        validateGenerateBtn();
    }
}

// ─── Validate generate button state ──────────────────────────────────────────
function validateGenerateBtn() {
    var clientId = document.getElementById('ia-client-dropdown').dataset.value;
    var period   = document.getElementById('ia-period-input').value.trim();
    var fileInput = document.getElementById('ia-file-input');
    var hasFile  = fileInput._selectedFile || (fileInput.files && fileInput.files.length > 0);
    document.getElementById('ia-generate-btn').disabled = !(clientId && period && hasFile);
}

// ─── Generate report ──────────────────────────────────────────────────────────
function initGenerateBtn() {
    document.getElementById('ia-generate-btn').addEventListener('click', function () {
        var clientId  = document.getElementById('ia-client-dropdown').dataset.value;
        var period    = document.getElementById('ia-period-input').value.trim();
        var fileInput = document.getElementById('ia-file-input');
        var file      = fileInput._selectedFile || (fileInput.files && fileInput.files[0]);

        if (!clientId || !period || !file) return;

        showLoading();

        var formData = new FormData();
        formData.append('client_id', clientId);
        formData.append('report_period', period);
        formData.append('file', file);
        formData.append('_token', getCsrfToken());

        // Track potential email from selected client for after generation
        _currentClientEmail = _selectedClientEmail;

        fetch('/admin/ia-assistant/marketing-report/generate', {
            method: 'POST',
            body: formData,
        })
        .then(safeJson)
        .then(function (res) {
            if (res.status === 1) {
                currentConversationId = res.conversation_id;
                var title = (res.report_json && res.report_json.report_header && res.report_json.report_header.report_title)
                    ? res.report_json.report_header.report_title
                    : ('Reporte ' + period);
                showReportPanel(res.conversation_id, title, res.turn_number);
                loadConversations();
            } else {
                hideLoading();
                showError('Error al generar el reporte: ' + (typeof res.message === 'string' ? res.message : JSON.stringify(res.message)));
            }
        })
        .catch(function (err) {
            hideLoading();
            if (err.httpStatus === 504 || err.httpStatus === 502 || err.httpStatus === 503) {
                showTimeout();
            } else {
                showError('Error de conexi\u00f3n: ' + err.message);
            }
        });
    });
}

// ─── Regenerate with feedback ─────────────────────────────────────────────────
function initRegenerateBtn() {
    document.getElementById('ia-regenerate-btn').addEventListener('click', function () {
        var feedback = document.getElementById('ia-feedback-input').value.trim();
        if (!feedback || !currentConversationId) return;

        showLoading();

        var formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('feedback', feedback);
        formData.append('_token', getCsrfToken());

        fetch('/admin/ia-assistant/marketing-report/regenerate', {
            method: 'POST',
            body: formData,
        })
        .then(safeJson)
        .then(function (res) {
            if (res.status === 1) {
                currentConversationId = res.conversation_id;
                var title = (res.report_json && res.report_json.report_header && res.report_json.report_header.report_title)
                    ? res.report_json.report_header.report_title
                    : document.getElementById('ia-report-title-display').textContent;
                showReportPanel(res.conversation_id, title, res.turn_number);
                document.getElementById('ia-feedback-input').value = '';
                loadConversations();
            } else {
                hideLoading();
                showError('Error al regenerar: ' + (typeof res.message === 'string' ? res.message : JSON.stringify(res.message)));
            }
        })
        .catch(function (err) {
            hideLoading();
            if (err.httpStatus === 504 || err.httpStatus === 502 || err.httpStatus === 503) {
                showTimeout();
            } else {
                showError('Error de conexi\u00f3n: ' + err.message);
            }
        });
    });
}

// ─── Download PDF ─────────────────────────────────────────────────────────────
function initDownloadBtn() {
    document.getElementById('ia-download-btn').addEventListener('click', function () {
        var convId = document.getElementById('ia-download-btn').dataset.conversationId || currentConversationId;
        if (!convId) return;
        window.location.href = '/admin/ia-assistant/marketing-report/download-pdf/' + convId;
    });
}

// ─── Show report panel with inline PDF ───────────────────────────────────────
function showReportPanel(conversationId, title, turnNumber) {
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.remove('d-none');

    document.getElementById('ia-report-turn-badge').textContent    = 'Iteraci\u00f3n ' + turnNumber;
    document.getElementById('ia-report-title-display').textContent = title || '';
    document.getElementById('ia-download-btn').dataset.conversationId = conversationId;

    document.getElementById('ia-report-iframe').src =
        '/admin/ia-assistant/marketing-report/preview-pdf/' + conversationId;

    highlightActiveHistory(conversationId);
}

// ─── UI helpers ───────────────────────────────────────────────────────────────
function showLoading() {
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.add('d-none');
    document.getElementById('ia-loading-state').classList.remove('d-none');
    document.getElementById('ia-generate-btn').disabled = true;
    document.getElementById('ia-regenerate-btn').disabled = true;
}

function hideLoading() {
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-generate-btn').disabled = false;
    document.getElementById('ia-regenerate-btn').disabled = false;
    validateGenerateBtn();
}

function showError(msg) {
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-timeout-state').classList.add('d-none');
    document.getElementById('ia-empty-state').classList.remove('d-none');
    document.getElementById('ia-empty-state').querySelector('.ia-empty-state__text').textContent = msg;
    validateGenerateBtn();
}

function showTimeout() {
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.add('d-none');
    document.getElementById('ia-timeout-state').classList.remove('d-none');
    validateGenerateBtn();
}

function highlightActiveHistory(activeId) {
    document.querySelectorAll('.ia-history-item').forEach(function (el) {
        el.classList.toggle('ia-history-item--active', parseInt(el.dataset.id) === parseInt(activeId));
    });
}

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ─── Send email modal ────────────────────────────────────────────────────────
function initSendBtn() {
    var modal     = document.getElementById('ia-send-modal');
    var emailIn   = document.getElementById('ia-send-email-input');
    var confirmBtn = document.getElementById('ia-send-confirm-btn');
    var cancelBtn  = document.getElementById('ia-send-cancel-btn');
    var closeBtn   = document.getElementById('ia-send-modal-close');

    function openModal() {
        emailIn.value = _currentClientEmail || _selectedClientEmail || '';
        modal.classList.remove('d-none');
        emailIn.focus();
        confirmBtn.disabled = false;
        confirmBtn.querySelector('span').textContent = 'Enviar';
    }

    function closeModal() {
        modal.classList.add('d-none');
    }

    document.getElementById('ia-send-btn').addEventListener('click', openModal);
    cancelBtn.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    confirmBtn.addEventListener('click', function () {
        var email = emailIn.value.trim();
        if (!email || !currentConversationId) return;

        confirmBtn.disabled = true;
        confirmBtn.querySelector('span').textContent = 'Enviando...';

        var formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('email', email);
        formData.append('_token', getCsrfToken());

        fetch('/admin/ia-assistant/marketing-report/send-email', {
            method: 'POST',
            body: formData,
        })
        .then(safeJson)
        .then(function (res) {
            if (res.status === 1) {
                confirmBtn.querySelector('span').textContent = '¡Enviado!';
                setTimeout(closeModal, 1200);
            } else {
                confirmBtn.disabled = false;
                confirmBtn.querySelector('span').textContent = 'Enviar';
                alert('Error: ' + (typeof res.message === 'string' ? res.message : JSON.stringify(res.message)));
            }
        })
        .catch(function (err) {
            confirmBtn.disabled = false;
            confirmBtn.querySelector('span').textContent = 'Enviar';
            alert('Error de conexión: ' + err.message);
        });
    });
}
