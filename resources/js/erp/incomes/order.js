import { loadPdfViewer, pdfPrevPage, pdfNextPage, pdfZoomIn, pdfZoomOut, printPdf, downloadPdf, fullscreenPdf, initPdfViewer, destroyPdfViewer } from '../../pdf-viewer.js';
import { incomeState } from './state.js';

export {pdfPrevPage, pdfNextPage, pdfZoomIn, pdfZoomOut, printPdf, downloadPdf, fullscreenPdf};
export function init(){ initPdfViewer(); }
export function showIncomeOrder(openWindow = true){ if(incomeState.currentIncome != null){ loadPdfViewer('/storage/incomes/pdfs/'+incomeState.currentIncome.unique_id+'.pdf?'+Date.now()); if(openWindow){ $('#order-viewer-container').css('display', 'flex'); $('#erp-app-sidebar').css('visibility', 'hidden'); } } }
export function closeOrderViewer(){ destroyPdfViewer(); $('#order-viewer-container').fadeOut(100); $('#erp-app-sidebar').css('visibility', 'visible'); cancelSendOrder(); }
export function shareIncomePdf(){
    if(!incomeState.currentIncome || !incomeState.currentIncome.unique_id) return;
    const payUrl = window.location.origin + '/client/payments/pay/' + incomeState.currentIncome.unique_id;
    if(navigator.share) navigator.share({url: payUrl}).catch(() => {});
    else navigator.clipboard.writeText(payUrl).then(() => { const button = document.getElementById('pdf-share'); if(button){ const icon = button.querySelector('i'); icon.className = 'fa-solid fa-check'; setTimeout(() => { icon.className = 'fa-solid fa-share-nodes'; }, 2000); } }).catch(() => {});
}
export function sendOrder(){
    $('#send-order-button').attr('disabled', true);
    PostMethodFunction('/admin/incomes/get-licenses', {income_id: incomeState.currentIncome.id}, null, function(response){
        let licenseIds = [];
        $.each(response.data, function(index, item){ licenseIds.push(item.license_id); });
        PostMethodFunction('/admin/licenses/notifications/get-by-licenses-ids', {license_ids: licenseIds}, null, function(receiversResponse){ incomeState.receiversList = receiversResponse.data; $('#send-order-button').attr('disabled', false); $('#send-order-container').fadeIn(100); showReceiversList(); }, function(){ $('#send-order-button').attr('disabled', false); });
    }, function(){ $('#send-order-button').attr('disabled', false); });
}
function showReceiversList(){
    let html = '';
    $.each(incomeState.receiversList, function(index, item){ html += '<tr class="receiver-item" index="'+index+'"><td class="columns-send-email text-left"><p>'+item.email+'</p></td><td class="columns-send-phone text-left">'+item.phone+'</td><td class="columns-send-actions text-center"><i class="receiver-item-delete fa-solid fa-trash-can align-self-center"></i></td></tr>'; });
    html += '<tr class="receiver-item"><td class="columns-send-email text-left"><input type="text" class="form-control" name="email" placeholder="Correo"></td><td class="columns-send-phone text-left"><input type="number" class="form-control" name="phone" placeholder="Teléfono"></td><td class="columns-send-actions text-center"><i class="receiver-item-create fa-solid fa-plus align-self-center"></i></td></tr>';
    $('#receivers-list-body').html(html);
}
export function cancelSendOrder(){ $('#send-order-container').fadeOut(100); }
export function confirmSendOrder(){
    if(incomeState.receiversList.length == 0){ alertWarning('Debes ingresar al menos un destinatario'); return; }
    $('#confirm-send-order-button').attr('disabled', true);
    PostMethodFunction('/admin/incomes/send', {income_id: incomeState.currentIncome.id, receivers: incomeState.receiversList}, null, function(){ $('#confirm-send-order-button').attr('disabled', false); swallMessage('Exito', 'Orden de compra enviada correctamente', 'success', null, null, 30000, null, null); $('#send-order-container').fadeOut(100); }, function(){ $('#confirm-send-order-button').attr('disabled', false); });
}
export function deleteReceiver(){ let index = $(this).closest('.receiver-item').attr('index'); incomeState.receiversList.splice(index, 1); showReceiversList(); }
export function createReceiver(){ let email = $('#receivers-list-body input[name="email"]').val(); let phone = $('#receivers-list-body input[name="phone"]').val(); if(email == '' || email == null || !validateEmail(email)){ alertWarning('Debes ingresar un correo válido'); return; } incomeState.receiversList.push({email: email, phone: phone}); showReceiversList(); }