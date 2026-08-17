import { iaState } from './state.js';
import { getCsrfToken, safeJson } from './shared.js';
import { validateGenerateBtn } from './clients.js';
import { loadConversations } from './history.js';
import { hideLoading, showError, showLoading, showReportPanel, showTimeout } from './report.js';

export function initGenerateBtn(){
    document.getElementById('ia-generate-btn').addEventListener('click', function(){
        const clientId = document.getElementById('ia-client-dropdown').dataset.value;
        const period = document.getElementById('ia-period-input').value.trim();
        const fileInput = document.getElementById('ia-file-input');
        const file = fileInput._selectedFile || (fileInput.files && fileInput.files[0]);
        if(!clientId || !period || !file) return;

        showLoading();
        const formData = new FormData();
        formData.append('client_id', clientId);
        formData.append('report_period', period);
        formData.append('file', file);
        formData.append('_token', getCsrfToken());
        iaState.currentClientEmail = iaState.selectedClientEmail;

        fetch('/admin/ia-assistant/marketing-report/generate', { method: 'POST', body: formData })
            .then(safeJson)
            .then(function(response){
                if(response.status === 1){
                    iaState.currentConversationId = response.conversation_id;
                    const title = response.report_json && response.report_json.report_header && response.report_json.report_header.report_title
                        ? response.report_json.report_header.report_title
                        : 'Reporte ' + period;
                    showReportPanel(response.conversation_id, title, response.turn_number);
                    loadConversations();
                }else{
                    hideLoading();
                    showError('Error al generar el reporte: ' + (typeof response.message === 'string' ? response.message : JSON.stringify(response.message)));
                }
            })
            .catch(handleRequestError);
    });
}

export function initRegenerateBtn(){
    document.getElementById('ia-regenerate-btn').addEventListener('click', function(){
        const feedback = document.getElementById('ia-feedback-input').value.trim();
        if(!feedback || !iaState.currentConversationId) return;

        showLoading();
        const formData = new FormData();
        formData.append('conversation_id', iaState.currentConversationId);
        formData.append('feedback', feedback);
        formData.append('_token', getCsrfToken());

        fetch('/admin/ia-assistant/marketing-report/regenerate', { method: 'POST', body: formData })
            .then(safeJson)
            .then(function(response){
                if(response.status === 1){
                    iaState.currentConversationId = response.conversation_id;
                    const title = response.report_json && response.report_json.report_header && response.report_json.report_header.report_title
                        ? response.report_json.report_header.report_title
                        : document.getElementById('ia-report-title-display').textContent;
                    showReportPanel(response.conversation_id, title, response.turn_number);
                    document.getElementById('ia-feedback-input').value = '';
                    loadConversations();
                }else{
                    hideLoading();
                    showError('Error al regenerar: ' + (typeof response.message === 'string' ? response.message : JSON.stringify(response.message)));
                }
            })
            .catch(handleRequestError);
    });
}

function handleRequestError(error){
    hideLoading();
    if(error.httpStatus === 504 || error.httpStatus === 502 || error.httpStatus === 503){
        showTimeout();
    }else{
        showError('Error de conexi\u00f3n: ' + error.message);
    }
}