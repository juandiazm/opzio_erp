export function exportReport(sheets = null, button = null){
    let exportButton = $(button || '#export-report-excel-container');
    if(exportButton.prop('disabled')) return;
    if(sheets == null || sheets.length == 0){ swallMessage('Error', 'No hay datos para exportar<br>Por favor seleccione al menos un reporte', 'error', 'Ok', null, 3000, null, null); return; }
    exportButton.prop('disabled', true);
    PostMethodFunction('/admin/reports/export', {sheets: sheets}, null, function(response){ exportButton.prop('disabled', false); location.href = '/admin/reports/download/'+response.data; }, function(){ exportButton.prop('disabled', false); });
}