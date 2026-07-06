//////////
$(document).on('click', '#update-client-button', updateClient);
$(document).on('click','#add-client-documens-button', addClientDocument);
$(document).on('click', '.update-client-file-btn', updateClientDocument);
$(document).on('click', '.delete-client-file-btn', deleteClientDocument);
////VAR TABS
$(document).ready(function(){
    getClientDocuments();
});
//////////////////////////////////////////////////////
//Update User functions
function updateClient(){
    let container = $(this).parent();
    let flag = true;
    let image = $('#update-client-img').val();
    let id_type = $('#update-client-id-type').val();
    let country = $('#update-client-country').attr('item-id');
    let address = $('#update-client-address').val();
    let phone = $('#update-client-phone').val();
    let email = $('#update-client-email').val();
    let sector = $('#update-client-sector').attr('item-id');
    if(id_type == null || id_type == ''){
        $('#create-client-id-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de identificación');
        flag = false;
    }
    if(country == null || country == ''){
        $('#create-client-country').addClass('is-invalid');
        alertWarning('Debe seleccionar un país');
        flag = false;
    }
    if(address == null || address == ''){
        $('#create-client-address').addClass('is-invalid');
        alertWarning('Debe ingresar la dirección del cliente');
        flag = false;
    }
    if(phone == null || phone == ''){
        $('#create-client-phone').addClass('is-invalid');
        alertWarning('Debe ingresar el teléfono del cliente');
        flag = false;
    }
    if(email == null || email == '' || !validateEmail(email)){
        $('#create-client-email').addClass('is-invalid');
        alertWarning('Debe ingresar el correo del cliente');
        flag = false;
    }
    if(sector == null || sector == ''){
        $('#create-client-sector').addClass('is-invalid');
        alertWarning('Debe seleccionar un sector');
        flag = false;
    }
    if(flag){
        swallMessage(
            'Seguridad'
            , 'Se cerrará tu sesión, ¿Deseas continuar?'
            , 'warning'
            , 'Si, actualizar'
            , 'Cancelar'
            , null
            , function(){
                $('#update-client-button').prop('disabled', true);
                let dinamicForm = document.createElement("form");
                dinamicForm.setAttribute('id', 'temporal-form');
                dinamicForm.setAttribute('class', 'd-none');
                dinamicForm.appendChild($('<input type="hidden" name="identification_type" value="'+id_type+'">')[0]);
                dinamicForm.appendChild($('<input type="hidden" name="sector" value="'+sector+'">')[0]);
                dinamicForm.appendChild($('<input type="hidden" name="country" value="'+country+'">')[0]);
                dinamicForm.appendChild($('#update-client-address').clone(true)[0]);
                dinamicForm.appendChild($('#update-client-phone').clone(true)[0]);
                dinamicForm.appendChild($('#update-client-email').clone(true)[0]);
                dinamicForm.appendChild($('#update-client-img').clone(true)[0]);
                dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
                document.body.appendChild(dinamicForm);
                dinamicForm = $('#temporal-form');
                dinamicForm.find('.input_image')[0].files = $('#update-client-img')[0].files;
                $('#temporal-form').remove();
                PostMethodMultimediaFunction('/client/my-companies/update', dinamicForm, null, function(response){
                    $('#update-client-button').attr('disabled', false);
                    swallMessage(
                        'Exito'
                        , 'Cliente actualizado'
                        , 'success'
                        , null
                        , null
                        , 3000
                        , null
                        , function(){
                            location.reload();
                        }
                    );
                }, function(){$('#update-client-button').attr('disabled', false);});
            }
            , null
        ); 
        
    }  
}
function addClientDocument(){
    let container = $(this).parent();
    let name = container.find('.client-document-input-name').val();
    let file = container.find('.client-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.client-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.client-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-client-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="client_id" value="'+current_client.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.client-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.client-document-input-file')[0].files =  container.find('.client-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/client/my-companies/documents/add', dinamicForm, null, function(response){
            $('#add-client-documens-button').attr('disabled', false);
            container.find('.client-document-input-name').val('');
            container.find('.client-document-input-file').val('');
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
            getClientDocuments();
        }, function(){$('#add-client-documens-button').attr('disabled', false);});
    }
}
function getClientDocuments(){
    let DataSend = {
        client_id: current_client.id
    };
    PostMethodFunction('/client/my-companies/documents/get',DataSend,null, showClientDocuments,null);
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
function updateClientDocument(){
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
        let DataSend = {
            id: id,
            name: name,
        };
        PostMethodFunction('/client/my-companies/documents/update',DataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}
function deleteClientDocument(){
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
            let DataSend = {
                id: id,
            };
            PostMethodFunction('/client/my-companies/documents/delete',DataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
    
}
