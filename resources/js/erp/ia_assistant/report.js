import { iaState } from './state.js';
import { validateGenerateBtn } from './clients.js';

export function initDownloadBtn(){
    document.getElementById('ia-download-btn').addEventListener('click', function(){
        const conversationId = document.getElementById('ia-download-btn').dataset.conversationId || iaState.currentConversationId;
        if(!conversationId) return;
        window.location.href = '/admin/ia-assistant/marketing-report/download-pdf/' + conversationId;
    });
}

export function showReportPanel(conversationId, title, turnNumber){
    iaState.currentConversationId = conversationId;
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.remove('d-none');
    document.getElementById('ia-report-turn-badge').textContent = 'Iteraci\u00f3n ' + turnNumber;
    document.getElementById('ia-report-title-display').textContent = title || '';
    document.getElementById('ia-download-btn').dataset.conversationId = conversationId;
    document.getElementById('ia-report-iframe').src = '/admin/ia-assistant/marketing-report/preview-pdf/' + conversationId;
    highlightActiveHistory(conversationId);
}

export function showLoading(){
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.add('d-none');
    document.getElementById('ia-loading-state').classList.remove('d-none');
    document.getElementById('ia-generate-btn').disabled = true;
    document.getElementById('ia-regenerate-btn').disabled = true;
}

export function hideLoading(){
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-generate-btn').disabled = false;
    document.getElementById('ia-regenerate-btn').disabled = false;
    validateGenerateBtn();
}

export function showError(message){
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-timeout-state').classList.add('d-none');
    document.getElementById('ia-empty-state').classList.remove('d-none');
    document.getElementById('ia-empty-state').querySelector('.ia-empty-state__text').textContent = message;
    validateGenerateBtn();
}

export function showTimeout(){
    document.getElementById('ia-loading-state').classList.add('d-none');
    document.getElementById('ia-empty-state').classList.add('d-none');
    document.getElementById('ia-report-state').classList.add('d-none');
    document.getElementById('ia-timeout-state').classList.remove('d-none');
    validateGenerateBtn();
}

function highlightActiveHistory(activeId){
    document.querySelectorAll('.ia-history-item').forEach(function(element){
        element.classList.toggle('ia-history-item--active', parseInt(element.dataset.id) === parseInt(activeId));
    });
}