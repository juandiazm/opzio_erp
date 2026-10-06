import { getOutcomesPage } from './list.js';

export function openMassImportModal(){
    updateImportFormat();
    $('#import-form-container').addClass('is-visible').attr('aria-hidden', 'false');
    $('#import-format').trigger('focus');
}

export function closeMassImportModal(){
    $('#import-form-container').removeClass('is-visible').attr('aria-hidden', 'true');
    $('#import-file-input').val('');
    $('#import-paste-text').val('');
    $('#import-format').val('csv');
    updateImportFormat();
}

export function updateImportFormat(){
    const pasteMode = $('#import-format').val() === 'paste';
    $('#import-file-field').prop('hidden', pasteMode);
    $('#import-paste-field').prop('hidden', !pasteMode);
    $('#import-file-input').prop('required', !pasteMode).prop('disabled', pasteMode);
    $('#import-paste-text').prop('required', pasteMode);
}

export function confirmMassImport(){
    const pasteMode = $('#import-format').val() === 'paste';
    const fileInput = $('#import-file-input')[0];
    if(pasteMode){
        const pastedText = $('#import-paste-text').val().trim();
        if(!pastedText){
            alertWarning('Pegue la tabla de Bold antes de importar.');
            return;
        }
        const transfer = new DataTransfer();
        transfer.items.add(new File([pastedText], 'bold-copiar-pegar.csv', { type: 'text/csv' }));
        fileInput.files = transfer.files;
        fileInput.disabled = false;
    }else if(!fileInput.files[0]){
        alertWarning('Seleccione un archivo CSV.');
        return;
    }

    if(!$('#import-format').val()){
        alertWarning('Seleccione un formato de importación.');
        return;
    }

    $('#import-confirm-btn').prop('disabled', true);
    PostMethodMultimediaFunction('/admin/outcomes/import', $('#import-form'), null, function(response){
        $('#import-confirm-btn').prop('disabled', false);
        closeMassImportModal();
        alertSuccess(response.message || 'Importación completada.');
        getOutcomesPage();
    }, function(){
        $('#import-confirm-btn').prop('disabled', false);
        updateImportFormat();
    });
}