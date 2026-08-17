import { iaState } from './state.js';
import { safeJson } from './shared.js';

export function initGenerateToggle(){
    const button = document.querySelector('#ia-generate-toggle .ia-toggle-btn');
    const body = document.getElementById('ia-generate-body');
    const icon = button.querySelector('i');

    button.addEventListener('click', function(){
        const isOpen = !body.classList.contains('d-none');
        body.classList.toggle('d-none', isOpen);
        icon.className = isOpen ? 'fa-light fa-plus' : 'fa-light fa-xmark';
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

export function loadClients(){
    fetch('/admin/ia-assistant/marketing-report/get-clients')
        .then(safeJson)
        .then(function(response){
            const options = document.getElementById('ia-client-options');
            if(response.status === 1 && response.data && response.data.length > 0){
                iaState.allClients = response.data;
                renderClientOptions(iaState.allClients);
            }else{
                options.innerHTML = '<p class="ia-empty-text">No hay clientes disponibles.</p>';
            }
        })
        .catch(function(){
            document.getElementById('ia-client-options').innerHTML = '<p class="ia-empty-text">Error al cargar clientes.</p>';
        });

    document.getElementById('ia-client-trigger').addEventListener('click', function(){
        const panel = document.getElementById('ia-client-panel');
        panel.classList.toggle('d-none');
        if(!panel.classList.contains('d-none')) document.getElementById('ia-client-search').focus();
    });

    document.getElementById('ia-client-search').addEventListener('input', function(event){
        const query = event.target.value.trim().toLowerCase();
        const filtered = query
            ? iaState.allClients.filter(client => [client.name, client.lastname].filter(Boolean).join(' ').toLowerCase().includes(query))
            : iaState.allClients;
        renderClientOptions(filtered);
    });

    document.addEventListener('click', function(event){
        const dropdown = document.getElementById('ia-client-dropdown');
        if(dropdown && !dropdown.contains(event.target)) document.getElementById('ia-client-panel').classList.add('d-none');
    });

    document.getElementById('ia-period-input').addEventListener('input', validateGenerateBtn);
}

function renderClientOptions(clients){
    const options = document.getElementById('ia-client-options');
    if(clients.length === 0){
        options.innerHTML = '<p class="ia-empty-text">Sin resultados.</p>';
        return;
    }
    const currentId = parseInt(document.getElementById('ia-client-dropdown').dataset.value);
    options.innerHTML = '';
    clients.forEach(function(client){
        const name = [client.name, client.lastname].filter(Boolean).join(' ');
        const option = document.createElement('div');
        option.className = 'ia-client-option' + (client.id === currentId ? ' ia-client-option--active' : '');
        option.dataset.id = client.id;
        option.textContent = name;
        option.addEventListener('click', function(){ selectClient(client.id, name); });
        options.appendChild(option);
    });
}

function selectClient(id, name){
    const dropdown = document.getElementById('ia-client-dropdown');
    const triggerText = document.getElementById('ia-client-trigger-text');
    const panel = document.getElementById('ia-client-panel');
    const search = document.getElementById('ia-client-search');

    dropdown.dataset.value = id;
    triggerText.textContent = name;
    triggerText.classList.remove('ia-client-trigger__text--placeholder');

    const client = iaState.allClients.find(item => item.id === id);
    iaState.selectedClientEmail = client && client.email ? client.email : '';

    panel.classList.add('d-none');
    search.value = '';
    renderClientOptions(iaState.allClients);
    validateGenerateBtn();
}

export function initDropzone(){
    const dropzone = document.getElementById('ia-dropzone');
    const fileInput = document.getElementById('ia-file-input');
    const fileName = document.getElementById('ia-file-name');

    dropzone.addEventListener('click', function(){ fileInput.click(); });
    dropzone.addEventListener('dragover', function(event){
        event.preventDefault();
        dropzone.classList.add('ia-dropzone--over');
    });
    dropzone.addEventListener('dragleave', function(){ dropzone.classList.remove('ia-dropzone--over'); });
    dropzone.addEventListener('drop', function(event){
        event.preventDefault();
        dropzone.classList.remove('ia-dropzone--over');
        const file = event.dataTransfer.files[0];
        if(file) setFile(file);
    });
    fileInput.addEventListener('change', function(){
        if(fileInput.files[0]) setFile(fileInput.files[0]);
    });

    function setFile(file){
        fileInput._selectedFile = file;
        fileName.textContent = file.name;
        fileName.classList.remove('d-none');
        validateGenerateBtn();
    }
}

export function validateGenerateBtn(){
    const clientId = document.getElementById('ia-client-dropdown').dataset.value;
    const period = document.getElementById('ia-period-input').value.trim();
    const fileInput = document.getElementById('ia-file-input');
    const hasFile = fileInput._selectedFile || (fileInput.files && fileInput.files.length > 0);
    document.getElementById('ia-generate-btn').disabled = !(clientId && period && hasFile);
}