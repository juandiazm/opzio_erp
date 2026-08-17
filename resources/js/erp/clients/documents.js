import { clientState } from './state.js';

export function addClientDocument(e) {
    e.preventDefault();
    const container = $('#client-documents-add-container');
    const fileInput = container.find('.client-document-input-file')[0];
    const files = fileInput.files;
    if (!files || files.length === 0) {
        container.find('.client-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar al menos un documento');
        return;
    }

    const btn = $('#add-client-documens-button').prop('disabled', true);
    let completed = 0;
    Array.from(files).forEach(file => {
        const publicName = file.name;
        const dinamicForm = document.createElement('form');
        dinamicForm.classList.add('d-none');

        const inputClient = document.createElement('input');
        inputClient.type = 'hidden';
        inputClient.name = 'client_id';
        inputClient.value = clientState.currentClient.id;
        dinamicForm.appendChild(inputClient);

        const inputName = document.createElement('input');
        inputName.type = 'hidden';
        inputName.name = 'name';
        inputName.value = publicName;
        dinamicForm.appendChild(inputName);

        const inputFile = document.createElement('input');
        inputFile.type = 'file';
        inputFile.name = 'file';
        const dt = new DataTransfer();
        dt.items.add(file);
        inputFile.files = dt.files;
        dinamicForm.appendChild(inputFile);

        const csrf = $('input[name="_token"]')[0].cloneNode();
        dinamicForm.appendChild(csrf);
        document.body.appendChild(dinamicForm);

        PostMethodMultimediaFunction(
            '/admin/clients/documents/add',
            $(dinamicForm),
            null,
            function(response) {
                completed++;
                if (completed === files.length) {
                    btn.prop('disabled', false);
                    container.find('.client-document-input-file').val('');
                    swallMessage('Éxito', 'Todos los documentos se han agregado', 'success', null, null, 3000);
                    getClientDocuments();
                }
            },
            function() {
                completed++;
                if (completed === files.length) {
                    btn.prop('disabled', false);
                    swallMessage('Error', 'Hubo un problema al subir alguno de los documentos', 'error');
                }
            }
        );
        document.body.removeChild(dinamicForm);
    });
}

export function getClientDocuments(){
    let dataSend = {
        client_id: clientState.currentClient.id
    };
    PostMethodFunction('/admin/clients/documents/get',dataSend,null, showClientDocuments,null);
}

function showClientDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="client-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="client-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-client-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-client-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#client-documents-table #client-documents-table-body').empty().append(appendContent);
}

export function updateClientDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.client-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.client-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let dataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/admin/clients/documents/update',dataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}

export function deleteClientDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este documento?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let dataSend = {
                id: id,
            };
            PostMethodFunction('/admin/clients/documents/delete',dataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
}