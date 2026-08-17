import { loadClients, initDropzone, initGenerateToggle } from './clients.js';
import { loadConversations, initHistorySearch } from './history.js';
import { initGenerateBtn, initRegenerateBtn } from './generation.js';
import { initDownloadBtn } from './report.js';
import { initSendBtn } from './email.js';

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
