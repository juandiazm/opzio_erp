import { getIncomesPage } from './list.js';

export function getImportAssistantsExcel(){
    if(!$('#import-report-excel-icon').hasClass('fa-bounce')) swallMessage('<i class="fas fa-file-excel" style="font-size:100px; color:#220245; margin-bottom:1vh;"></i><br>Importar ingresos', 'Asegúrate de usar la plantilla oficial para que el proceso se realice correctamente.<br>Si aún no tienes la plantilla, puedes descargarla haciendo clic en el botón.<a href="/admin/incomes/download-template" id="download-assistant-template" target="_blank">Descargar plantilla <i class="fas fa-download"></i></a></a>¿deseas continuar?', null, 'Importar', 'Cancelar', null, function(){ $('#import-report-excel-input').click(); }, null, '#10BE16');
}

export function importAssistantsExcel(){
    let button = $(this);
    let icon = $('#import-report-excel-icon');
    let file = button.prop('files')[0];
    if(file != null){
        icon.addClass('fa-bounce');
        let dynamicForm = document.createElement('form');
        dynamicForm.setAttribute('id', 'temporal-form');
        dynamicForm.setAttribute('class', 'd-none');
        dynamicForm.appendChild($('#import-report-excel-input').clone(true)[0]);
        dynamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dynamicForm);
        dynamicForm = $('#temporal-form');
        dynamicForm.find('#import-report-excel-input')[0].files = $('#import-report-excel-input')[0].files;
        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/incomes/import-massive-quotations', dynamicForm, null, function(response){
            if(response.status == 0){
                let message = 'Algunos ingresos no pudieron ser importados, por favor verifica que los datos sean correctos y vuelve a intentarlo.<div class="assistant-import-error-container">';
                $.each(response.data, function(index, value){ message += '<div class="assistant-import-error-item"><strong>Fila: '+value.row+': </strong> - '+value.message+'</div>'; });
                message += '</div>';
                swallMessage('Algunos inconvenientes', message, 'warning', 'Entendido', null, null, null, null);
            }else{ swallMessage('Exitoso', 'Ingresos importados correctamente', 'success', null, null, 3000, null, null); getIncomesPage(); }
            $('#import-report-excel-input').val('');
            icon.removeClass('fa-bounce');
        }, function(){ button.val(''); icon.removeClass('fa-bounce'); });
    }
}