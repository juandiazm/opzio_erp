export function initSync(getClientsPage){
    let isSyncing = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function getClientErrorRow(errorText) {
        const raw = String(errorText || '');
        const regex = /cliente\s+(\d+)\s*(?:\(([^)]*)\))?\s*:\s*(.*)$/i;
        const match = raw.match(regex);
        if (!match) {
            return {clientId: 'N/A', code: 'N/A', field: 'N/A', detail: raw};
        }
        let code = 'N/A';
        let field = 'N/A';
        if (match[2]) {
            const metaParts = match[2].split(':');
            code = (metaParts[0] || '').trim() || 'N/A';
            field = (metaParts[1] || '').trim() || 'N/A';
        }
        return {
            clientId: (match[1] || 'N/A').trim(),
            code,
            field,
            detail: (match[3] || '').trim() || raw
        };
    }

    function renderSyncResult(response) {
        const syncedCount = Number(response.synced_count || 0);
        const rawErrors = Array.isArray(response.errors) ? response.errors : [];
        const rows = rawErrors.map(getClientErrorRow);
        let html = '';
        html += `<div class="alert ${syncedCount > 0 ? 'alert-success' : 'alert-info'} mb-3">${escapeHtml(response.message || 'Proceso finalizado')}</div>`;
        html += `<div class="sync-summary mb-3">`;
        html += `<span class="badge bg-success me-2">Sincronizados: ${syncedCount}</span>`;
        html += `<span class="badge bg-danger">Con error: ${rows.length}</span>`;
        html += `</div>`;
        if (rows.length > 0) {
            html += `<div class="table-responsive sync-errors-table-container">`;
            html += `<table class="table table-sm table-striped align-middle sync-errors-table">`;
            html += `<thead><tr><th>Cliente ID</th><th>Código</th><th>Campo</th><th>Detalle</th></tr></thead><tbody>`;
            rows.forEach(row => {
                html += `<tr>`;
                html += `<td><span class="badge bg-secondary">${escapeHtml(row.clientId)}</span></td>`;
                html += `<td>${escapeHtml(row.code)}</td>`;
                html += `<td>${escapeHtml(row.field)}</td>`;
                html += `<td>${escapeHtml(row.detail)}</td>`;
                html += `</tr>`;
            });
            html += `</tbody></table></div>`;
        }
        $('#syncResultMessage').html(html);
    }

    function showSyncError(message) {
        $('#syncResultMessage').html(`<div class="alert alert-danger mb-0">${escapeHtml(message || 'Error durante la sincronización')}</div>`);
    }

    $('#syncResultCloseBtn, #syncResultCloseFooterBtn').on('click', function() {
        $('#syncResultModal').modal('hide');
    });
    $('#sync-siigo-btn').on('click', function() {
        if (isSyncing) return;
        const button = $(this);
        const icon = button.find('i');
        isSyncing = true;
        button.prop('disabled', true);
        icon.addClass('fa-spin');
        PostMethodFunction('/admin/clients/sincronize', {}, null, function(response) {
            renderSyncResult(response);
            $('#syncResultModal').modal('show');
            if (response.synced_count > 0) getClientsPage();
            isSyncing = false;
            button.prop('disabled', false);
            icon.removeClass('fa-spin');
        }, function(xhr) {
            let errorMessage = 'Error durante la sincronización';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {}
            showSyncError(errorMessage);
            $('#syncResultModal').modal('show');
            isSyncing = false;
            button.prop('disabled', false);
            icon.removeClass('fa-spin');
        });
    });
    $('#syncResultModal').on('hidden.bs.modal', function () {
        isSyncing = false;
        $('#sync-siigo-btn').prop('disabled', false).find('i').removeClass('fa-spin');
    });
}