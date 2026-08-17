import { contractState } from './state.js';

const sourceLabels = {
    client: 'Cliente',
    employee: 'Empleado',
    provider: 'Proveedor',
};

export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function(character) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[character];
    });
}

export function contractableTypeKey(type) {
    const value = String(type ?? '').toLowerCase();
    if(value.indexOf('client') !== -1) return 'client';
    if(value.indexOf('employee') !== -1) return 'employee';
    if(value.indexOf('provider') !== -1) return 'provider';
    return value;
}

export function setOptions(selector, options, selectedValue = null) {
    $(selector).each(function() {
        const previousValue = selectedValue === null ? this.value : selectedValue;
        if(window.SearchableDropdown){
            window.SearchableDropdown.setOptions(this, options);
        }else{
            this.innerHTML = '';
            options.forEach(function(option) {
                const element = document.createElement('option');
                element.value = option.value;
                element.textContent = option.label;
                element.disabled = option.disabled === true;
                this.appendChild(element);
            }, this);
        }
        const validValue = options.some(option => String(option.value) === String(previousValue) && option.disabled !== true);
        const nextValue = validValue ? previousValue : ((options.find(option => option.disabled !== true) || {}).value ?? '');
        this.value = nextValue;
        if(window.SearchableDropdown && window.SearchableDropdown.setValue){
            window.SearchableDropdown.setValue(this, nextValue);
        }
    });
}

export function setTypeOptions() {
    const options = [{value: '', label: 'Seleccionar tipo', disabled: true}].concat(
        contractState.catalogs.types.map(type => ({value: type.id, label: type.name}))
    );
    setOptions('.contract-type-select', options);
    setOptions('#contract-type-filter', [{value: '', label: 'Todos los tipos'}].concat(
        contractState.catalogs.types.map(type => ({value: type.id, label: type.name}))
    ));
}

export function getSourceEntries(type) {
    const source = contractState.catalogs[contractableTypeKey(type) + 's'] || [];
    return source.map(function(item) {
        const lastName = item.lastname || item.last_name || '';
        return {
            value: item.id,
            label: (item.name + ' ' + lastName).trim(),
        };
    });
}

export function setSourceTypeOptions(selector) {
    setOptions(selector, [
        {value: 'client', label: sourceLabels.client},
        {value: 'employee', label: sourceLabels.employee},
        {value: 'provider', label: sourceLabels.provider},
    ]);
}

export function setContractableOptions(typeSelector, idSelector, selectedValue = null, allowAll = false) {
    const type = $(typeSelector).val();
    const options = [{value: '', label: allowAll ? 'Todos' : 'Seleccionar titular', disabled: !allowAll}]
        .concat(getSourceEntries(type));
    setOptions(idSelector, options, selectedValue);
    if(selectedValue === null && !allowAll){
        $(idSelector).val('');
        if(window.SearchableDropdown && window.SearchableDropdown.setValue){
            $(idSelector).each(function(){ window.SearchableDropdown.setValue(this, ''); });
        }
    }
}

export function setTemplateOptions(typeSelector, templateSelector, selectedValue = null) {
    const typeId = $(typeSelector).val();
    const options = [{value: '', label: 'Sin plantilla', disabled: false}].concat(
        contractState.catalogs.templates
            .filter(template => String(template.contract_type_id) === String(typeId))
            .map(template => ({value: template.id, label: template.name + ' (v' + template.version + ')'}))
    );
    setOptions(templateSelector, options, selectedValue);
}

export function renderContractVariables(prefix, selectedValues = {}) {
    const templateId = $('#'+prefix+'-contract-template').val();
    const template = contractState.catalogs.templates.find(function(item) {
        return String(item.id) === String(templateId);
    });
    const variables = template && Array.isArray(template.variables) ? template.variables : [];
    const section = $('#'+prefix+'-contract-variables-section');
    const container = $('#'+prefix+'-contract-variables');
    if(!variables.length){
        section.addClass('d-none');
        container.empty();
        return;
    }

    const values = selectedValues || {};
    let html = '';
    variables.forEach(function(variable, index) {
        const key = String(variable.key || '').trim();
        if(!key) return;
        const shortKey = key.replace(/^custom\./i, '');
        const value = Object.prototype.hasOwnProperty.call(values, key)
            ? values[key]
            : (Object.prototype.hasOwnProperty.call(values, shortKey) ? values[shortKey] : (variable.default_value || ''));
        const inputType = ['text', 'number', 'date', 'email'].includes(variable.type) ? variable.type : 'text';
        html += '<div class="contracts-contract-variable-row"><label for="'+prefix+'-contract-variable-'+index+'"><span>'+escapeHtml(variable.label || shortKey)+'</span><code>{{'+escapeHtml(key)+'}}</code></label><input type="'+inputType+'" id="'+prefix+'-contract-variable-'+index+'" class="form-control" data-contract-variable-key="'+escapeHtml(key)+'" value="'+escapeHtml(value)+'"'+(variable.required ? ' required' : '')+'></div>';
    });
    container.html(html);
    section.toggleClass('d-none', container.children().length === 0);
}

export function collectContractVariables(prefix) {
    const values = {};
    $('#'+prefix+'-contract-variables [data-contract-variable-key]').each(function() {
        values[$(this).attr('data-contract-variable-key')] = $(this).val();
    });
    return values;
}

export function loadCatalogs(onLoaded = null) {
    PostMethodFunction('/admin/contracts/get-catalogs', {}, null, function(response) {
        contractState.catalogs.clients = response.clients || [];
        contractState.catalogs.employees = response.employees || [];
        contractState.catalogs.providers = response.providers || [];
        contractState.catalogs.types = response.types || [];
        contractState.catalogs.templates = response.templates || [];
        contractState.catalogs.variables = response.variables || [];
        setTypeOptions();
        setSourceTypeOptions('#create-contractable-type');
        setSourceTypeOptions('#update-contractable-type');
        setSourceTypeOptions('#contract-schedule-target-type');
        setContractableOptions('#create-contractable-type', '#create-contractable-id');
        setContractableOptions('#update-contractable-type', '#update-contractable-id');
        setContractableOptions('#contract-schedule-target-type', '#contract-schedule-target-id', null, true);
        setTemplateOptions('#create-contract-type', '#create-contract-template');
        setTemplateOptions('#update-contract-type', '#update-contract-template');
        setTemplateOptions('#contract-schedule-type', '#contract-schedule-template');
        if(onLoaded) onLoaded();
    }, null);
}

export function formatDate(value) {
    return value ? String(value).slice(0, 10) : '';
}

export function formatDateTimeInput(value) {
    return value ? String(value).replace(' ', 'T').slice(0, 16) : '';
}