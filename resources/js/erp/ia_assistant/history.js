import { iaState } from './state.js';
import { escapeHtml, safeJson } from './shared.js';
import { showReportPanel } from './report.js';

export function loadConversations(query){
    const url = '/admin/ia-assistant/marketing-report/get-conversations' + (query ? '?q=' + encodeURIComponent(query) : '');
    fetch(url)
        .then(safeJson)
        .then(function(response){
            const list = document.getElementById('ia-history-list');
            if(response.status === 1 && response.data && response.data.length > 0){
                list.innerHTML = '';
                response.data.forEach(function(conversation){
                    const item = document.createElement('div');
                    item.className = 'ia-history-item' + (conversation.id === iaState.currentConversationId ? ' ia-history-item--active' : '');
                    item.dataset.id = conversation.id;
                    item.dataset.clientEmail = conversation.client_email || '';

                    let statusLabel = 'Finalizado';
                    let statusClass = 'ia-status-badge--completed';
                    if(conversation.status === 'processing'){
                        statusLabel = 'En proceso';
                        statusClass = 'ia-status-badge--processing';
                    }else if(conversation.status === 'failed'){
                        statusLabel = 'Error';
                        statusClass = 'ia-status-badge--failed';
                    }

                    item.innerHTML =
                        '<div class="ia-history-item__header">' +
                            '<p class="ia-history-item__title">' + escapeHtml(conversation.title) + '</p>' +
                            '<span class="ia-status-badge ' + statusClass + '">' + statusLabel + '</span>' +
                        '</div>' +
                        '<p class="ia-history-item__meta">' + escapeHtml(conversation.client_name) + ' &mdash; ' + escapeHtml(conversation.updated_at) + '</p>' +
                        '<p class="ia-history-item__turns">' + conversation.turn_count + ' iteraci\u00f3n' + (conversation.turn_count !== 1 ? 'es' : '') + '</p>';
                    item.addEventListener('click', function(){ loadConversationTurn(conversation.id); });
                    list.appendChild(item);
                });
            }else{
                list.innerHTML = '<p class="ia-empty-text">' + (query ? 'Sin resultados para esa b\u00fasqueda.' : 'No hay conversaciones anteriores.') + '</p>';
            }
        })
        .catch(function() {});
}

export function initHistorySearch(){
    document.getElementById('ia-history-search').addEventListener('input', function(event){
        clearTimeout(iaState.historySearchTimer);
        iaState.historySearchTimer = setTimeout(function(){ loadConversations(event.target.value.trim()); }, 300);
    });
}

function loadConversationTurn(conversationId){
    fetch('/admin/ia-assistant/marketing-report/conversation/' + conversationId)
        .then(safeJson)
        .then(function(response){
            if(response.status === 1){
                iaState.currentConversationId = response.conversation.id;
                iaState.currentClientEmail = response.conversation.client && response.conversation.client.email
                    ? response.conversation.client.email
                    : '';
                const turns = response.conversation.turns;
                const lastTurn = turns[turns.length - 1];
                showReportPanel(response.conversation.id, response.conversation.title, lastTurn ? lastTurn.turn_number : 1);
            }
        })
        .catch(function() {});
}