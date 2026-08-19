import { getOutcomesPage } from './list.js';

export function openMassImportModal(){
    $('#import-form-container').addClass('is-visible').attr('aria-hidden', 'false');
    $('#import-source').trigger('focus');
}

export function closeMassImportModal(){
    $('#import-form-container').removeClass('is-visible').attr('aria-hidden', 'true');
    $('#import-file-input').val('');
}

export function confirmMassImport(){
    const file = $('#import-file-input').prop('files')[0];
    const source = $('#import-source').val();
    if(!file){
        alertWarning('Seleccione un archivo CSV.');
        return;
    }
    if(!source){
        alertWarning('Seleccione una fuente.');
        return;
    }

    $('#import-confirm-btn').prop('disabled', true);
    PostMethodMultimediaFunction('/admin/outcomes/import', $('#import-form'), null, function(response){
        $('#import-confirm-btn').prop('disabled', false);
        closeMassImportModal();
        alertSuccess(response.message || 'Importación completada.');
        getOutcomesPage();
    }, function(){ $('#import-confirm-btn').prop('disabled', false); });
}