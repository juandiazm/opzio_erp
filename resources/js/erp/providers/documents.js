import { providerState } from './state.js';

export function addProviderDocument(){
    let container = $(this).parent();
    let name = container.find('.provider-document-input-name').val();
    let file = container.find('.provider-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){ container.find('.provider-document-input-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del documento'); flag = false; }
    if(file == null || file == ''){ container.find('.provider-document-input-file').addClass('is-invalid'); alertWarning('Debe seleccionar el documento'); flag = false; }
    if(flag){
        $('#add-provider-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="provider_id" value="'+providerState.currentProvider.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.provider-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.provider-document-input-file')[0].files = container.find('.provider-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/providers/documents/add', dinamicForm, null, function(response){
            $('#add-provider-documens-button').attr('disabled', false);
            container.find('.provider-document-input-name').val('');
            container.find('.provider-document-input-file').val('');
            swallMessage('Exito', 'Documento agregado', 'success', null, null, 3000, null, null);
            getProviderDocuments();
        }, function(){$('#add-provider-documens-button').attr('disabled', false);});
    }
}
export function getProviderDocuments(){
    PostMethodFunction('/admin/providers/documents/get',{provider_id: providerState.currentProvider.id},null, showProviderDocuments,null);
}
function showProviderDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'"><td class="text-left"><input type="text" name="" class="provider-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td><td class="text-left"><a href="'+value.document_url+'" target="_blank" class="provider-document-input-link">'+value.document_private_name+'</a></td><td class="text-center action-cell">';
        if(providerState.currentProvider.deleted_at == null) appendContent += '<i class="fa-solid fa-pen-to-square update-provider-file-btn"></i><i class="fa-solid fa-trash-can delete-provider-file-btn"></i>';
        appendContent += '</td></tr>';
    });
    $('#provider-documents-table #provider-documents-table-body').empty().append(appendContent);
}
export function updateProviderDocument(){
    let container = $(this).parent().parent();
    let name = container.find('.provider-document-input-name').val();
    if(name == null || name == ''){ container.find('.provider-document-input-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del documento'); return; }
    PostMethodFunction('/admin/providers/documents/update',{id: container.attr('id'), name: name},null,function(){alertSuccess('Documento actualizado');},null);
}
export function deleteProviderDocument(){
    let container = $(this).parent().parent();
    swallMessage('Advertencia','¿Está seguro de eliminar este documento?','error','Si, eliminar','No',null,function(){
        PostMethodFunction('/admin/providers/documents/delete',{id: container.attr('id')},null,function(){alertSuccess('Documento eliminado'); container.remove();},null);
    },null);
}