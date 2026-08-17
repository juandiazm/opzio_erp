import { contractState } from './state.js';
import { contractableTypeKey, escapeHtml, formatDateTimeInput, loadCatalogs, setContractableOptions, setTemplateOptions } from './shared.js';

export function getSchedules() {
    PostMethodFunction('/admin/contracts/schedules/get', {}, null, renderSchedules, null);
}

function renderSchedules(response) {
    let html = '';
    (response.schedules || []).forEach(function(schedule) {
        const deleted = schedule.deleted_at != null;
        html += '<tr class="contract-catalog-row'+(deleted ? ' deleted' : '')+'" data-schedule-id="'+schedule.id+'">';
        html += '<td>'+escapeHtml(schedule.name)+'</td><td>'+escapeHtml(schedule.type ? schedule.type.name : '')+'</td><td>'+escapeHtml(schedule.contractable_name || 'Todos')+'</td><td>'+escapeHtml(formatDateTimeInput(schedule.next_run_at).replace('T', ' '))+'</td><td>'+(schedule.send_automatically ? 'Sí' : 'No')+'</td><td>'+(schedule.active ? 'Activa' : 'Inactiva')+'</td><td class="action-cell">';
        if(deleted){ html += '<i class="fa-solid fa-rotate-left contract-schedule-restore" title="Restaurar"></i>'; }
        else { html += '<i class="fa-solid fa-pen-to-square contract-schedule-edit" title="Editar"></i><i class="fa-solid fa-trash-can contract-schedule-delete" title="Eliminar"></i>'; }
        html += '</td></tr>';
    });
    $('#contract-schedules-table-body').html(html);
}

function resetScheduleForm() {
    contractState.editingScheduleId = null;
    $('#contract-schedule-name').val('');
    $('#contract-schedule-frequency').val('monthly');
    $('#contract-schedule-interval').val(1);
    $('#contract-schedule-next-run, #contract-schedule-ends').val('');
    $('#contract-schedule-send, #contract-schedule-active').prop('checked', true);
    $('#contract-schedule-form-title').text('Nueva programación');
    $('#contract-schedule-save').html('<i class="fa-solid fa-plus"></i> Agregar');
    $('#contract-schedule-cancel').addClass('d-none');
}

export function saveSchedule() {
    const data = {
        name: $('#contract-schedule-name').val(),
        contract_type_id: $('#contract-schedule-type').val(),
        contract_template_id: $('#contract-schedule-template').val(),
        contractable_type: $('#contract-schedule-target-type').val(),
        contractable_id: $('#contract-schedule-target-id').val() || null,
        frequency: $('#contract-schedule-frequency').val(),
        interval_value: $('#contract-schedule-interval').val(),
        next_run_at: $('#contract-schedule-next-run').val(),
        ends_at: $('#contract-schedule-ends').val(),
        send_automatically: $('#contract-schedule-send').is(':checked') ? 1 : 0,
        active: $('#contract-schedule-active').is(':checked') ? 1 : 0,
    };
    const url = contractState.editingScheduleId ? '/admin/contracts/schedules/update' : '/admin/contracts/schedules/add';
    if(contractState.editingScheduleId) data.id = contractState.editingScheduleId;
    PostMethodFunction(url, data, null, function(){ alertSuccess('Programación guardada'); resetScheduleForm(); getSchedules(); }, null);
}

export function editSchedule() {
    const id = $(this).closest('tr').attr('data-schedule-id');
    PostMethodFunction('/admin/contracts/schedules/get', {}, null, function(response){
        const schedule = (response.schedules || []).find(item => String(item.id) === String(id));
        if(!schedule) return;
        contractState.editingScheduleId = schedule.id;
        const type = contractableTypeKey(schedule.contractable_type);
        $('#contract-schedule-name').val(schedule.name);
        $('#contract-schedule-type').val(schedule.contract_type_id).trigger('change');
        setTemplateOptions('#contract-schedule-type', '#contract-schedule-template', schedule.contract_template_id);
        $('#contract-schedule-template').val(schedule.contract_template_id);
        $('#contract-schedule-target-type').val(type).trigger('change');
        setContractableOptions('#contract-schedule-target-type', '#contract-schedule-target-id', schedule.contractable_id, true);
        $('#contract-schedule-target-id').val(schedule.contractable_id || '');
        $('#contract-schedule-frequency').val(schedule.frequency);
        $('#contract-schedule-interval').val(schedule.interval_value);
        $('#contract-schedule-next-run').val(formatDateTimeInput(schedule.next_run_at));
        $('#contract-schedule-ends').val(formatDateTimeInput(schedule.ends_at));
        $('#contract-schedule-send').prop('checked', schedule.send_automatically == 1 || schedule.send_automatically === true);
        $('#contract-schedule-active').prop('checked', schedule.active == 1 || schedule.active === true);
        $('#contract-schedule-form-title').text('Actualizar programación');
        $('#contract-schedule-save').html('<i class="fa-solid fa-floppy-disk"></i> Actualizar');
        $('#contract-schedule-cancel').removeClass('d-none');
    }, null);
}

export function cancelSchedule() { resetScheduleForm(); }

export function deleteSchedule() {
    PostMethodFunction('/admin/contracts/schedules/delete', {id: $(this).closest('tr').attr('data-schedule-id')}, null, function(){ alertSuccess('Programación eliminada'); getSchedules(); }, null);
}

export function restoreSchedule() {
    PostMethodFunction('/admin/contracts/schedules/restore', {id: $(this).closest('tr').attr('data-schedule-id')}, null, function(){ alertSuccess('Programación restaurada'); getSchedules(); }, null);
}