import { incomeState } from './state.js';
import { getIncomesPage } from './list.js';

export function openAdvancesModal(){
    let incomeId = $(this).closest('tr').attr('income-id');
    incomeState.currentAdvanceIncome = incomeState.incomes.find(income => income.id == incomeId);
    if(incomeState.currentAdvanceIncome != null){ $('#advances-modal').fadeIn(300); $('#advances-modal-income-id').text(incomeState.currentAdvanceIncome.unique_id.substr(incomeState.currentAdvanceIncome.unique_id.length - 5)); $('#advances-modal-income-client').text(incomeState.currentAdvanceIncome.client_name); let incomeTotal = parseFloat(incomeState.currentAdvanceIncome.total) || 0; $('#advances-modal-income-total').text('$'+Math.round(incomeTotal).toLocaleString('es-CO')); loadAdvancesList(); }
}
export function closeAdvancesModal(){ $('#advances-modal').fadeOut(300); incomeState.currentAdvanceIncome = null; incomeState.advancesList = []; incomeState.currentAdvance = null; hideAdvanceForm(); }
function loadAdvancesList(){ GetMethodFunction('/admin/incomes/advances/get-by-income/'+incomeState.currentAdvanceIncome.id, null, showAdvancesList, null); }
function showAdvancesList(response){
    if(response.status == 1){
        incomeState.advancesList = response.data.advances;
        let totalAdvances = parseFloat(response.data.total_advances) || 0;
        let balancePending = parseFloat(response.data.balance_pending) || 0;
        $('#advances-modal-total-advances').text('$'+Math.round(totalAdvances).toLocaleString('es-CO'));
        $('#advances-modal-balance-pending').text('$'+Math.round(balancePending).toLocaleString('es-CO'));
        let appendedContent = '';
        if(incomeState.advancesList.length > 0) $.each(incomeState.advancesList, function(index, value){ let amount = parseFloat(value.amount) || 0; appendedContent += '<tr class="advance-item" advance-id="'+value.id+'"><td class="text-center">'+value.payment_date+'</td><td class="text-end">$'+Math.round(amount).toLocaleString('es-CO')+'</td><td class="text-center">'+(value.payment_method || '-')+'</td><td class="text-center">'+(value.reference || '-')+'</td><td class="text-center">'+(value.user ? value.user.name : '-')+'</td><td class="text-end"><i class="fa-solid fa-pen-to-square advance-item-edit" title="Editar"></i><i class="fa-solid fa-trash advance-item-delete" title="Eliminar"></i></td></tr>'; });
        else appendedContent = '<tr><td colspan="6" class="text-center">No hay abonos registrados</td></tr>';
        $('#advances-list-body').empty().append(appendedContent);
    }else alertWarning(response.message);
}
export function showCreateAdvanceForm(){ incomeState.currentAdvance = null; $('#advance-form-title').text('Agregar Abono'); $('#advance-form-amount').val(''); $('#advance-form-date').val(new Date().toISOString().split('T')[0]); $('#advance-form-method').val(''); $('#advance-form-reference').val(''); $('#advance-form-notes').val(''); $('#advance-form-container').slideDown(300); $('#create-advance-button').hide(); }
export function hideAdvanceForm(){ $('#advance-form-container').slideUp(300); $('#create-advance-button').show(); incomeState.currentAdvance = null; }
export function saveAdvance(){
    let amount = $('#advance-form-amount').val();
    let paymentDate = $('#advance-form-date').val();
    let paymentMethod = $('#advance-form-method').val();
    let reference = $('#advance-form-reference').val();
    let notes = $('#advance-form-notes').val();
    if(!amount || amount <= 0){ alertWarning('Debe ingresar un monto válido'); return; }
    if(!paymentDate){ alertWarning('Debe seleccionar una fecha de pago'); return; }
    let endpoint = incomeState.currentAdvance ? '/admin/incomes/advances/update/'+incomeState.currentAdvance.id : '/admin/incomes/advances/create';
    PostMethodFunction(endpoint, {income_id: incomeState.currentAdvanceIncome.id, amount: amount, payment_date: paymentDate, payment_method: paymentMethod, reference: reference, notes: notes}, null, function(response){
        if(response.status == 1){ alertSuccess(incomeState.currentAdvance ? 'Abono actualizado correctamente' : 'Abono creado correctamente'); loadAdvancesList(); hideAdvanceForm(); incomeState.tabsView['nav-list-tab'] = false; getIncomesPage(); }
        else alertWarning(response.message);
    }, null);
}
export function editAdvance(){
    let advanceId = $(this).closest('tr').attr('advance-id');
    incomeState.currentAdvance = incomeState.advancesList.find(advance => advance.id == advanceId);
    if(incomeState.currentAdvance){ $('#advance-form-title').text('Editar Abono'); $('#advance-form-amount').val(incomeState.currentAdvance.amount); $('#advance-form-date').val(incomeState.currentAdvance.payment_date); $('#advance-form-method').val(incomeState.currentAdvance.payment_method || ''); $('#advance-form-reference').val(incomeState.currentAdvance.reference || ''); $('#advance-form-notes').val(incomeState.currentAdvance.notes || ''); $('#advance-form-container').slideDown(300); $('#create-advance-button').hide(); }
}
export function deleteAdvance(){
    let advanceId = $(this).closest('tr').attr('advance-id');
    Swal.fire({title: '<span style="color:#484848 !important;">Eliminar Abono</span>', html: '¿Está seguro de eliminar este abono?', icon: 'warning', iconColor: '#220245', showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#220245', cancelButtonColor: '#C4C4C4', reverseButtons: true, width: (window.innerWidth > 768 ? '768px' : '90%'), customClass: {container: 'swal-high-zindex'}, didOpen: () => { $('.swal2-container').css('z-index', '99999'); }}).then((result) => {
        if(result.isConfirmed) PostMethodFunction('/admin/incomes/advances/delete/'+advanceId, {}, null, function(response){ if(response.status == 1){ alertSuccess('Abono eliminado correctamente'); loadAdvancesList(); incomeState.tabsView['nav-list-tab'] = false; getIncomesPage(); }else alertWarning(response.message); }, null);
    });
}