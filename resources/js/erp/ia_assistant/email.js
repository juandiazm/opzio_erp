import { iaState } from './state.js';
import { getCsrfToken, safeJson } from './shared.js';

export function initSendBtn(){
    const modal = document.getElementById('ia-send-modal');
    const emailInput = document.getElementById('ia-send-email-input');
    const confirmButton = document.getElementById('ia-send-confirm-btn');
    const cancelButton = document.getElementById('ia-send-cancel-btn');
    const closeButton = document.getElementById('ia-send-modal-close');

    function openModal(){
        emailInput.value = iaState.currentClientEmail || iaState.selectedClientEmail || '';
        modal.classList.remove('d-none');
        emailInput.focus();
        confirmButton.disabled = false;
        confirmButton.querySelector('span').textContent = 'Enviar';
    }

    function closeModal(){
        modal.classList.add('d-none');
    }

    document.getElementById('ia-send-btn').addEventListener('click', openModal);
    cancelButton.addEventListener('click', closeModal);
    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', function(event){
        if(event.target === modal) closeModal();
    });

    confirmButton.addEventListener('click', function(){
        const email = emailInput.value.trim();
        if(!email || !iaState.currentConversationId) return;

        confirmButton.disabled = true;
        confirmButton.querySelector('span').textContent = 'Enviando...';
        const formData = new FormData();
        formData.append('conversation_id', iaState.currentConversationId);
        formData.append('email', email);
        formData.append('_token', getCsrfToken());

        fetch('/admin/ia-assistant/marketing-report/send-email', { method: 'POST', body: formData })
            .then(safeJson)
            .then(function(response){
                if(response.status === 1){
                    confirmButton.querySelector('span').textContent = '¡Enviado!';
                    setTimeout(closeModal, 1200);
                }else{
                    resetConfirmButton();
                    alert('Error: ' + (typeof response.message === 'string' ? response.message : JSON.stringify(response.message)));
                }
            })
            .catch(function(error){
                resetConfirmButton();
                alert('Error de conexión: ' + error.message);
            });
    });

    function resetConfirmButton(){
        confirmButton.disabled = false;
        confirmButton.querySelector('span').textContent = 'Enviar';
    }
}