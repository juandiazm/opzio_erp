import { licenseState } from './state.js';

export function addLicenseDocument(){
    let container = $(this).parent();
    let name = container.find('.license-document-input-name').val();
    let file = container.find('.license-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.license-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.license-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-license-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="license_id" value="'+licenseState.currentLicense.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.license-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.license-document-input-file')[0].files = container.find('.license-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/licenses/documents/add', dinamicForm, null, function(response){
            $('#add-license-documens-button').attr('disabled', false);
            container.find('.license-document-input-name').val('');
            container.find('.license-document-input-file').val('');
            swallMessage(
                'Exito'
                , 'Documento agregado'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getLicenseDocuments();
        }, function(){$('#add-license-documens-button').attr('disabled', false);});
    }
}

export function getLicenseDocuments(){
    let dataSend = {
        license_id: licenseState.currentLicense.id
    };
    PostMethodFunction('/admin/licenses/documents/get',dataSend,null, showLicenseDocuments,null);
}

function showLicenseDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="license-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="license-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-license-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-license-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#license-documents-table #license-documents-table-body').empty().append(appendContent);
}

export function updateLicenseDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.license-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.license-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let dataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/admin/licenses/documents/update',dataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}

export function deleteLicenseDocument(){
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
            PostMethodFunction('/admin/licenses/documents/delete',dataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
}