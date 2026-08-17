import { employeeState } from './state.js';

export function addEmployeeDocument(){
    let container = $(this).parent();
    let name = container.find('.employee-document-input-name').val();
    let file = container.find('.employee-document-input-file').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.employee-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(file == null || file == ''){
        container.find('.employee-document-input-file').addClass('is-invalid');
        alertWarning('Debe seleccionar el documento');
        flag = false;
    }
    if(flag){
        $('#add-employee-documens-button').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('<input type="hidden" name="employee_id" value="'+employeeState.currentEmployee.id+'">')[0]);
        dinamicForm.appendChild($('<input type="hidden" name="name" value="'+name+'">')[0]);
        dinamicForm.appendChild($('.employee-document-input-file').clone(true)[0]);
        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('.employee-document-input-file')[0].files = container.find('.employee-document-input-file')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/employees/documents/add', dinamicForm, null, function(response){
            $('#add-employee-documens-button').attr('disabled', false);
            container.find('.employee-document-input-name').val('');
            container.find('.employee-document-input-file').val('');
            swallMessage('Exito', 'Documento agregado', 'success', null, null, 3000, null, null);
            getEmployeeDocuments();
        }, function(){$('#add-employee-documens-button').attr('disabled', false);});
    }
}

export function getEmployeeDocuments(){
    let dataSend = {employee_id: employeeState.currentEmployee.id};
    PostMethodFunction('/admin/employees/documents/get',dataSend,null, showEmployeeDocuments,null);
}

function showEmployeeDocuments(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr id="'+value.id+'">';
            appendContent += '<td class="text-left"><input type="text" name="" class="employee-document-input-name align-self-end input-value" placeholder="Nombre..." value="'+value.document_public_name+'"></td>';
            appendContent += '<td class="text-left"><a href="'+value.document_url+'" target="_blank" class="employee-document-input-link">'+value.document_private_name+'</a></td>';
            appendContent += '<td class="text-center action-cell">';
                appendContent += '<i class="fa-solid fa-pen-to-square update-employee-file-btn"></i>';
                appendContent += '<i class="fa-solid fa-trash-can delete-employee-file-btn"></i>';
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#employee-documents-table #employee-documents-table-body').empty().append(appendContent);
}

export function updateEmployeeDocument(){
    let container = $(this).parent().parent();
    let id = container.attr('id');
    let name = container.find('.employee-document-input-name').val();
    let flag = true;
    if(name == null || name == ''){
        container.find('.employee-document-input-name').addClass('is-invalid');
        alertWarning('Debe ingresar el nombre del documento');
        flag = false;
    }
    if(flag){
        let dataSend = {id: id, name: name};
        PostMethodFunction('/admin/employees/documents/update',dataSend,null, function(response){
            alertSuccess('Documento actualizado');
        },null);
    }
}

export function deleteEmployeeDocument(){
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
            let dataSend = {id: id};
            PostMethodFunction('/admin/employees/documents/delete',dataSend,null, function(response){
                alertSuccess('Documento eliminado');
                container.remove();
            },null);
        }
        , null
    );
}