export function openMassImportModal(){ $('#import-form-container').css('display', 'flex'); }
export function closeMassImportModal(){ $('#import-form-container').css('display', 'none'); }
export function confirmMassImport(){
    let flag = true;
    let file = $('#import-file-input').prop('files')[0];
    if(file === undefined){ alertWarning('Porfavor seleccione un archivo'); flag = false; }
    if(flag){
        $('#nav-create #import-confirm-btn').prop('disabled', true);
        let dynamicForm = document.createElement('form');
        dynamicForm.setAttribute('id', 'temporal-form');
        dynamicForm.setAttribute('class', 'd-none');
        dynamicForm.appendChild($('#import-file-input').clone(true)[0]);
        dynamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dynamicForm);
        dynamicForm = $('#temporal-form');
        dynamicForm.find('#import-file-input')[0].files = $('#import-file-input')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/outcomes/import', dynamicForm, null, function(){
            $('#nav-create #import-confirm-btn').attr('disabled', false);
            closeMassImportModal();
            alertSuccess('Importación exitosa');
        }, function(){ $('#nav-create #import-confirm-btn').attr('disabled', false); });
    }
}