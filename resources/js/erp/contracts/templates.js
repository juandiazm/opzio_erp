import { contractState } from './state.js';
import { escapeHtml, loadCatalogs, setContractsFormExpanded } from './shared.js';

let templateEditor = null;
let savedEditorRange = null;

const previewValues = {
    'contractable.name': 'Cliente de ejemplo',
    'contractable.email': 'cliente@ejemplo.com',
    'contractable.phone': '300 000 0000',
    'contractable.identification': '900000000-1',
    'contract.name': 'Contrato de servicios',
    'contract.subject': 'Contrato de servicios',
    'contract.start_date': '2026-08-17',
    'contract.end_date': '2027-08-17',
    'contract.type': 'Prestación de servicios',
    'contract.unique_id': 'CONTRATO-DEMO',
    'client.name': 'Cliente',
    'client.lastname': 'de ejemplo',
    'client.complete_name': 'Cliente de ejemplo',
    'client.email': 'cliente@ejemplo.com',
    'client.phone': '300 000 0000',
    'client.identification': '900000000-1',
    'client.address': 'Carrera 1 # 2-03',
    'employee.name': 'Empleado',
    'employee.last_name': 'de ejemplo',
    'employee.complete_name': 'Empleado de ejemplo',
    'employee.identification': '1000000000',
    'employee.phone': '300 000 0000',
    'employee.personal_email': 'empleado@ejemplo.com',
    'employee.work_email': 'empleado@opzio.com',
    'provider.name': 'Proveedor de ejemplo',
    'provider.lastname': '',
    'provider.complete_name': 'Proveedor de ejemplo',
    'provider.email': 'proveedor@ejemplo.com',
    'provider.phone': '300 000 0000',
    'provider.identification': '900000000-1',
    'provider.address': 'Carrera 1 # 2-03',
    'department.name': 'Operaciones',
    'department.budget': '1000000',
    'department.director_name': 'Director de ejemplo',
    'license.name': 'Licencia de ejemplo',
    'license.value': '250000',
    'license.value_string': '6.000.000',
    'license.description': 'Servicio recurrente',
    'license.type': '1',
    'license.type_string': 'Recurrente',
    'license.active': '1',
    'license.active_string': 'Activa',
    'license.recurrence_months': '6',
    'license.billing_day': '20',
    'license.days_to_expire': '4',
    'license.last_billing_date': '2026-08-01',
    'license.next_billing_date': '2027-02-05',
    'license.last_payed_date': '2026-08-01',
    'license.remaining_days': '4',
    'licenses.count': '2',
    'licenses.total_value': '500000',
    'licenses.names': 'Licencia de ejemplo, Licencia adicional',
    'licenses.first_name': 'Licencia de ejemplo',
    'income.description': 'Servicio mensual',
    'income.total': '500000',
    'incomes.count': '3',
    'incomes.total': '1500000',
    'incomes.first_description': 'Servicio mensual',
    'incomes.first_total': '500000',
};

function customVariableDefinitions() {
    return $('#contract-template-custom-variables .contracts-custom-variable-row').map(function() {
        const row = $(this);
        const key = String(row.find('[data-variable-field="key"]').val() || '').trim().replace(/^custom\./i, '');
        return {
            key,
            label: String(row.find('[data-variable-field="label"]').val() || '').trim(),
            type: row.find('[data-variable-field="type"]').val() || 'text',
            default_value: String(row.find('[data-variable-field="default_value"]').val() || ''),
            required: row.find('[data-variable-field="required"]').is(':checked'),
        };
    }).get();
}

function currentPreviewValues() {
    const values = Object.assign({}, previewValues);
    customVariableDefinitions().forEach(function(variable) {
        if(variable.key) values['custom.'+variable.key] = variable.default_value || '['+variable.label+']';
    });
    (contractState.catalogs.variables || []).forEach(function(variable) {
        if(variable.key && !Object.prototype.hasOwnProperty.call(values, variable.key)) {
            values[variable.key] = '['+(variable.label || variable.key)+']';
        }
    });
    values['licence.name'] = values['license.name'];
    values['licence.value'] = values['license.value'];
    values['licence.description'] = values['license.description'];
    values['licences.count'] = values['licenses.count'];
    values['licences.total_value'] = values['licenses.total_value'];
    values['licences.names'] = values['licenses.names'];
    values['licences.first_name'] = values['licenses.first_name'];
    return values;
}

const previewAllowedTags = new Set(['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'div', 'span', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td']);
const previewAllowedAttributes = new Set(['style', 'href', 'target', 'rel', 'colspan', 'rowspan']);
const previewAllowedStyles = new Set(['text-align', 'font-weight', 'font-style', 'text-decoration', 'color', 'background-color', 'font-size', 'font-family', 'line-height', 'padding', 'margin', 'width', 'height', 'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-collapse', 'vertical-align', 'float', 'display', 'box-sizing', 'page-break-inside', 'page-break-before', 'page-break-after']);

function sanitizePreviewFragment(value) {
    const template = document.createElement('template');
    template.innerHTML = String(value || '');
    const sanitizeNode = function(node) {
        for(let child = node.firstChild; child;) {
            const next = child.nextSibling;
            if(child.nodeType === Node.ELEMENT_NODE) {
                const tag = child.tagName.toLowerCase();
                if(['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'meta', 'link'].includes(tag)) {
                    node.removeChild(child);
                    child = next;
                    continue;
                }
                if(!previewAllowedTags.has(tag)) {
                    while(child.firstChild) node.insertBefore(child.firstChild, child);
                    node.removeChild(child);
                    child = next;
                    continue;
                }
                Array.from(child.attributes).forEach(function(attribute) {
                    const name = attribute.name.toLowerCase();
                    if(!previewAllowedAttributes.has(name)) {
                        child.removeAttribute(attribute.name);
                        return;
                    }
                    if(name === 'style') {
                        const styles = attribute.value.split(';').map(function(declaration) {
                            const parts = declaration.split(':');
                            const property = String(parts.shift() || '').trim().toLowerCase();
                            const styleValue = parts.join(':').trim();
                            if(!property || !styleValue || !previewAllowedStyles.has(property) || /url\s*\(|expression\s*\(|javascript\s*:|vbscript\s*:|[<>]/i.test(styleValue)) return '';
                            return property+': '+styleValue;
                        }).filter(Boolean);
                        if(styles.length) child.setAttribute('style', styles.join('; '));
                        else child.removeAttribute('style');
                    }
                    if(name === 'href' && !/^(https?:|mailto:|\/|#)/i.test(attribute.value.trim())) child.removeAttribute('href');
                });
                sanitizeNode(child);
            }
            child = next;
        }
    };
    sanitizeNode(template.content);
    return template.content;
}

function replacePreviewVariables(fragment) {
    const values = currentPreviewValues();
    const walker = document.createTreeWalker(fragment, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    while(walker.nextNode()) textNodes.push(walker.currentNode);
    textNodes.forEach(function(node) {
        const text = node.nodeValue;
        const regex = /\{\{\s*([a-zA-Z][a-zA-Z0-9_.]*)\s*\}\}/g;
        if(!regex.test(text)) return;
        regex.lastIndex = 0;
        const replacement = document.createDocumentFragment();
        let lastIndex = 0;
        text.replace(regex, function(match, key, offset) {
            if(offset > lastIndex) replacement.appendChild(document.createTextNode(text.slice(lastIndex, offset)));
            if(Object.prototype.hasOwnProperty.call(values, key)) {
                replacement.appendChild(document.createTextNode(values[key]));
            } else {
                const marker = document.createElement('span');
                marker.className = 'contracts-template-preview-variable';
                marker.textContent = match;
                replacement.appendChild(marker);
            }
            lastIndex = offset + match.length;
            return match;
        });
        if(lastIndex < text.length) replacement.appendChild(document.createTextNode(text.slice(lastIndex)));
        node.parentNode.replaceChild(replacement, node);
    });
}

function renderTemplatePreview() {
    const subject = $('#contract-template-subject').val();
    $('#contract-template-preview-subject').text(subject.replace(/\{\{\s*([a-zA-Z][a-zA-Z0-9_.]*)\s*\}\}/g, function(match, key) {
        return Object.prototype.hasOwnProperty.call(currentPreviewValues(), key) ? currentPreviewValues()[key] : match;
    }));
    const fragment = sanitizePreviewFragment($('#contract-template-content-editor').html());
    replacePreviewVariables(fragment);
    const preview = document.getElementById('contract-template-preview');
    preview.replaceChildren(fragment);
}

function rememberEditorSelection() {
    if(!templateEditor) return;
    const selection = window.getSelection();
    if(!selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    if(templateEditor.contains(range.commonAncestorContainer)) savedEditorRange = range.cloneRange();
}

function restoreEditorSelection() {
    if(!templateEditor || !savedEditorRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedEditorRange);
}

function syncEditor() {
    if(!templateEditor) return;
    $('#contract-template-content').val(templateEditor.innerHTML);
    renderTemplatePreview();
}

function insertVariable(key) {
    if(!templateEditor) return;
    restoreEditorSelection();
    templateEditor.focus();
    document.execCommand('insertText', false, '{{'+key+'}}');
    rememberEditorSelection();
    syncEditor();
}

function addCustomVariableRow(variable = {}) {
    const type = ['text', 'number', 'date', 'email'].includes(variable.type) ? variable.type : 'text';
    const row = $('<div class="contracts-custom-variable-row"></div>');
    row.html(
        '<div class="contracts-custom-variable-field contracts-custom-variable-key"><label>Nombre</label><input type="text" class="form-control" data-variable-field="key" value="'+escapeHtml(String(variable.key || '').replace(/^custom\./i, ''))+'" placeholder="valor_mensual"></div>'+
        '<div class="contracts-custom-variable-field"><label>Etiqueta</label><input type="text" class="form-control" data-variable-field="label" value="'+escapeHtml(variable.label || '')+'" placeholder="Valor mensual"></div>'+
        '<div class="contracts-custom-variable-field contracts-custom-variable-type"><label>Tipo</label><select class="form-select" data-variable-field="type"><option value="text"'+(type === 'text' ? ' selected' : '')+'>Texto</option><option value="number"'+(type === 'number' ? ' selected' : '')+'>Número</option><option value="date"'+(type === 'date' ? ' selected' : '')+'>Fecha</option><option value="email"'+(type === 'email' ? ' selected' : '')+'>Correo</option></select></div>'+
        '<div class="contracts-custom-variable-field"><label>Valor por defecto</label><input type="text" class="form-control" data-variable-field="default_value" value="'+escapeHtml(variable.default_value || '')+'" placeholder="Opcional"></div>'+
        '<label class="contracts-custom-variable-required"><input type="checkbox" class="form-check-input" data-variable-field="required"'+(variable.required ? ' checked' : '')+'> Obligatoria</label>'+
        '<div class="contracts-custom-variable-actions"><button type="button" class="btn btn-light" data-template-variable="'+escapeHtml(String(variable.key || '').replace(/^custom\./i, ''))+'" title="Insertar variable" aria-label="Insertar variable"><i class="fa-solid fa-code"></i></button><button type="button" class="btn btn-light text-danger" data-remove-custom-variable title="Eliminar variable" aria-label="Eliminar variable"><i class="fa-solid fa-trash-can"></i></button></div>'
    );
    $('#contract-template-custom-variables').append(row);
    renderVariablePalette();
    renderTemplatePreview();
}

function renderCustomVariableRows(variables) {
    $('#contract-template-custom-variables').empty();
    (variables || []).forEach(addCustomVariableRow);
    renderVariablePalette();
}

function renderVariablePalette() {
    const definitions = (contractState.catalogs.variables || []).concat(customVariableDefinitions().filter(function(variable) {
        return variable.key;
    }).map(function(variable) {
        return {key: 'custom.'+variable.key, label: variable.label || variable.key, group: 'Propias'};
    }));
    const groups = {};
    definitions.forEach(function(definition) {
        if(!definition.key) return;
        const group = definition.group || 'Otras';
        if(!groups[group]) groups[group] = [];
        groups[group].push(definition);
    });
    let html = '';
    Object.keys(groups).forEach(function(group) {
        html += '<div class="contracts-variable-group"><span class="contracts-variable-group-label">'+escapeHtml(group)+'</span><div class="contracts-variable-group-items">';
        groups[group].forEach(function(definition) {
            html += '<button type="button" class="contracts-variable-chip" data-template-variable="'+escapeHtml(definition.key)+'" title="'+escapeHtml(definition.label || definition.key)+'"><span>{{'+escapeHtml(definition.key)+'}}</span></button>';
        });
        html += '</div></div>';
    });
    $('#contract-template-variable-palette').html(html);
}

export function initializeTemplateEditor() {
    if(templateEditor) return;
    templateEditor = document.getElementById('contract-template-content-editor');
    if(!templateEditor) return;
    setContractsFormExpanded('#contract-template-toggle', '#contract-template-form-body', '.contracts-template-form', false);
    templateEditor.addEventListener('input', syncEditor);
    templateEditor.addEventListener('keyup', rememberEditorSelection);
    templateEditor.addEventListener('mouseup', rememberEditorSelection);
    templateEditor.addEventListener('blur', rememberEditorSelection);
    $(document).on('mousedown.contractTemplateEditor', '#contract-template-toolbar button, #contract-template-variable-palette button, #contract-template-custom-variables [data-template-variable]', function(event) {
        event.preventDefault();
    });
    $(document).on('click.contractTemplateEditor', '#contract-template-toolbar [data-template-command]', function() {
        restoreEditorSelection();
        templateEditor.focus();
        document.execCommand($(this).data('template-command'), false, null);
        rememberEditorSelection();
        syncEditor();
    });
    $(document).on('change.contractTemplateEditor', '#contract-template-block-format', function() {
        restoreEditorSelection();
        templateEditor.focus();
        document.execCommand('formatBlock', false, $(this).val());
        rememberEditorSelection();
        syncEditor();
    });
    $(document).on('click.contractTemplateEditor', '[data-template-variable]', function() {
        const row = $(this).closest('.contracts-custom-variable-row');
        const rowKey = row.length ? String(row.find('[data-variable-field="key"]').val() || '').trim().replace(/^custom\./i, '') : '';
        const key = rowKey ? 'custom.'+rowKey : String($(this).attr('data-template-variable') || '').trim();
        if(key) insertVariable(key);
    });
    $(document).on('click.contractTemplateEditor', '#contract-template-add-variable', function() {
        addCustomVariableRow();
    });
    $(document).on('click.contractTemplateEditor', '#contract-template-toggle', function() {
        setContractsFormExpanded('#contract-template-toggle', '#contract-template-form-body', '.contracts-template-form', $(this).attr('aria-expanded') !== 'true');
    });
    $(document).on('click.contractTemplateEditor', '[data-remove-custom-variable]', function() {
        $(this).closest('.contracts-custom-variable-row').remove();
        renderVariablePalette();
        renderTemplatePreview();
    });
    $(document).on('input.contractTemplateEditor change.contractTemplateEditor', '#contract-template-subject, #contract-template-custom-variables input, #contract-template-custom-variables select', function() {
        renderVariablePalette();
        renderTemplatePreview();
    });
    syncEditor();
}

export function refreshVariablePalette() {
    renderVariablePalette();
    renderTemplatePreview();
}

function setEditorContent(content) {
    if(templateEditor) templateEditor.replaceChildren(sanitizePreviewFragment(content));
    $('#contract-template-content').val(content || '');
    renderTemplatePreview();
}

function getEditorContent() {
    syncEditor();
    return $('#contract-template-content').val() || '';
}

export function getTemplates() {
    PostMethodFunction('/admin/contracts/templates/get', {}, null, renderTemplates, null);
}

function renderTemplates(response) {
    let html = '';
    (response.templates || []).forEach(function(template) {
        const deleted = template.deleted_at != null;
        html += '<tr class="contract-catalog-row'+(deleted ? ' deleted' : '')+'" data-template-id="'+template.id+'">';
        html += '<td>'+escapeHtml(template.name)+'</td><td>'+escapeHtml(template.type ? template.type.name : '')+'</td><td>'+template.version+'</td><td>'+(template.active ? 'Activa' : 'Inactiva')+'</td><td class="action-cell">';
        if(deleted){ html += '<i class="fa-solid fa-rotate-left contract-template-restore" title="Restaurar"></i>'; }
        else { html += '<i class="fa-solid fa-pen-to-square contract-template-edit" title="Editar"></i><i class="fa-solid fa-trash-can contract-template-delete" title="Eliminar"></i>'; }
        html += '</td></tr>';
    });
    $('#contract-templates-table-body').html(html);
}

function resetTemplateForm() {
    contractState.editingTemplateId = null;
    $('#contract-template-type').val('');
    $('#contract-template-name, #contract-template-subject').val('');
    setEditorContent('');
    renderCustomVariableRows([]);
    $('#contract-template-active').prop('checked', true);
    $('#contract-template-form-title').text('Nueva plantilla');
    $('#contract-template-save').html('<i class="fa-solid fa-plus"></i> Agregar');
    $('#contract-template-cancel').addClass('d-none');
    setContractsFormExpanded('#contract-template-toggle', '#contract-template-form-body', '.contracts-template-form', false);
}

export function saveTemplate() {
    const data = {
        contract_type_id: $('#contract-template-type').val(),
        name: $('#contract-template-name').val(),
        subject: $('#contract-template-subject').val(),
        content: getEditorContent(),
        variables: customVariableDefinitions(),
        active: $('#contract-template-active').is(':checked') ? 1 : 0,
    };
    const url = contractState.editingTemplateId ? '/admin/contracts/templates/update' : '/admin/contracts/templates/add';
    if(contractState.editingTemplateId) data.id = contractState.editingTemplateId;
    PostMethodFunction(url, data, null, function(){ alertSuccess('Plantilla guardada'); resetTemplateForm(); getTemplates(); loadCatalogs(refreshVariablePalette); }, null);
}

export function editTemplate() {
    const id = $(this).closest('tr').attr('data-template-id');
    PostMethodFunction('/admin/contracts/templates/get', {}, null, function(response){
        const template = (response.templates || []).find(item => String(item.id) === String(id));
        fillTemplateForm(template);
    }, null);
}

function fillTemplateForm(template) {
    if(!template) return;
    setContractsFormExpanded('#contract-template-toggle', '#contract-template-form-body', '.contracts-template-form', true);
    contractState.editingTemplateId = template.id;
    $('#contract-template-type').val(template.contract_type_id);
    $('#contract-template-name').val(template.name);
    $('#contract-template-subject').val(template.subject);
    setEditorContent(template.content || '');
    renderCustomVariableRows(template.variables || []);
    $('#contract-template-active').prop('checked', template.active == 1 || template.active === true);
    $('#contract-template-form-title').text('Actualizar plantilla');
    $('#contract-template-save').html('<i class="fa-solid fa-floppy-disk"></i> Actualizar');
    $('#contract-template-cancel').removeClass('d-none');
}

export function cancelTemplate() { resetTemplateForm(); }

export function deleteTemplate() {
    PostMethodFunction('/admin/contracts/templates/delete', {id: $(this).closest('tr').attr('data-template-id')}, null, function(){ alertSuccess('Plantilla eliminada'); getTemplates(); loadCatalogs(); }, null);
}

export function restoreTemplate() {
    PostMethodFunction('/admin/contracts/templates/restore', {id: $(this).closest('tr').attr('data-template-id')}, null, function(){ alertSuccess('Plantilla restaurada'); getTemplates(); loadCatalogs(); }, null);
}