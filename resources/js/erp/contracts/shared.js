import { contractState } from './state.js';

const sourceLabels = {
    client: 'Cliente',
    employee: 'Empleado',
    provider: 'Proveedor',
    license: 'Licencia',
};

const sourceRequirementLabels = {
    contractable: 'Fuente requerida por la plantilla',
    client: 'Cliente requerido por la plantilla',
    employee: 'Empleado requerido por la plantilla',
    provider: 'Proveedor requerido por la plantilla',
    license: 'Licencia requerida por la plantilla',
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

export function setOptions(selector, options, selectedValue = null, triggerChange = true) {
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
            window.SearchableDropdown.setValue(this, nextValue, triggerChange);
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
    const sourceKey = String(type || '').toLowerCase();
    if(['license', 'licenses', 'licence', 'licences'].includes(sourceKey)) {
        return getLicenseEntries();
    }
    const source = contractState.catalogs[contractableTypeKey(type) + 's'] || [];
    return source.map(function(item) {
        const lastName = item.lastname || item.last_name || '';
        return {
            value: item.id,
            label: (item.name + ' ' + lastName).trim(),
        };
    });
}

export function getLicenseEntries() {
    return contractState.catalogs.licenses.map(function(license) {
        const client = license.client || {};
        const clientName = (client.name + ' ' + (client.lastname || client.last_name || '')).trim();
        const recurrence = Number(license.type) === 1 && license.recurrence_months
            ? ' - '+license.recurrence_months+' meses'
            : '';
        return {
            value: license.id,
            label: String(license.name || 'Licencia')+(clientName ? ' - '+clientName : '')+recurrence,
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

export function setLicenseOptions(selector, selectedValue = null) {
    setOptions(selector, [{value: '', label: 'Sin licencia'}].concat(getLicenseEntries()), selectedValue);
}

function getContractSourceTypeOptions() {
    return [
        {value: '', label: 'Seleccionar fuente', disabled: true},
        {value: 'client', label: sourceLabels.client},
        {value: 'employee', label: sourceLabels.employee},
        {value: 'provider', label: sourceLabels.provider},
        {value: 'license', label: sourceLabels.license},
    ];
}

export function setContractableOptions(typeSelector, idSelector, selectedValue = null, allowAll = false) {
    const type = $(typeSelector).val();
    const options = [{value: '', label: allowAll ? 'Todos' : 'Seleccionar titular', disabled: !allowAll}]
        .concat(getSourceEntries(type));
    setOptions(idSelector, options, selectedValue);
    if(selectedValue === null && !allowAll){
        clearSelect(idSelector);
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

export function getTemplateSourceRequirements(prefix) {
    const templateId = $('#'+prefix+'-contract-template').val();
    const template = contractState.catalogs.templates.find(function(item) {
        return String(item.id) === String(templateId);
    });
    if(template && Array.isArray(template.source_requirements)) return template.source_requirements;

    const source = template ? String(template.subject || '')+'\n'+String(template.content || '') : '';
    const requirements = [];
    const add = function(requirement) {
        if(!requirements.includes(requirement)) requirements.push(requirement);
    };
    const matches = source.match(/\{\{\s*([a-zA-Z][a-zA-Z0-9_.]*)\s*\}\}/g) || [];
    matches.forEach(function(match) {
        const path = match.replace(/^\{\{\s*|\s*\}\}$/g, '').toLowerCase();
        const prefixName = path.split('.')[0];
        if(['client', 'employee', 'provider', 'contractable'].includes(prefixName)) add(prefixName);
        if(prefixName === 'department') add('employee');
        if(['license', 'licence', 'licenses', 'licences'].includes(prefixName)) add('license');
        if(['income', 'incomes'].includes(prefixName)) add('client');
    });
    if(requirements.includes('license')) add('client');
    return requirements;
}

function normalizeSourceRows(sources) {
    if(sources === null || sources === undefined || sources === '') return [];
    if(typeof sources === 'string') {
        try { sources = JSON.parse(sources); } catch(error) { return []; }
    }
    if(!Array.isArray(sources) && sources && (sources.type || sources.id)) sources = [sources];
    if(!Array.isArray(sources) && sources && typeof sources === 'object') {
        sources = Object.keys(sources).map(function(key) {
            return {type: key, id: sources[key]};
        });
    }
    if(!Array.isArray(sources)) return [];
    return sources.map(function(source) {
        return {
            type: String(source && (source.type || source.source) || '').toLowerCase(),
            id: source && (source.id ?? source.value ?? ''),
        };
    });
}

function clearSelect(selector, triggerChange = false) {
    $(selector).each(function(){
        if(window.SearchableDropdown && window.SearchableDropdown.setValue) {
            window.SearchableDropdown.setValue(this, '', triggerChange);
        }else{
            this.value = '';
        }
    });
}

function collectContractSourceRows(prefix) {
    return $('#'+prefix+'-contract-sources [data-contract-source-row]').map(function() {
        const row = $(this);
        return {
            type: String(row.find('[data-contract-source-role="type"]').val() || '').toLowerCase(),
            id: row.find('[data-contract-source-role="id"]').val() || '',
        };
    }).get();
}

export function collectContractSources(prefix) {
    return collectContractSourceRows(prefix).filter(function(source) {
        return source.type && source.id;
    });
}

export function refreshContractSourceRow(row, selectedId = '') {
    const sourceRow = $(row);
    const typeSelector = sourceRow.find('[data-contract-source-role="type"]');
    const idSelector = sourceRow.find('[data-contract-source-role="id"]');
    const type = String(typeSelector.val() || '').toLowerCase();
    const options = [{value: '', label: type ? 'Seleccionar '+(sourceLabels[type] || 'registro').toLowerCase() : 'Seleccionar fuente primero', disabled: true}]
        .concat(type ? getSourceEntries(type) : []);
    setOptions(idSelector, options, selectedId || null, false);
    if(!selectedId || !type) clearSelect(idSelector);
}

export function renderContractSources(prefix, selectedSources = null) {
    const requirements = getTemplateSourceRequirements(prefix).filter(function(requirement) {
        return Object.prototype.hasOwnProperty.call(sourceRequirementLabels, requirement);
    });
    let rows = selectedSources === null ? collectContractSourceRows(prefix) : normalizeSourceRows(selectedSources);
    requirements.forEach(function(requirement) {
        if(!rows.some(function(row) { return row.type === requirement; })) {
            rows.push({type: requirement === 'contractable' ? '' : requirement, id: ''});
        }
    });
    if(!rows.length) rows = [{type: '', id: ''}];
    const section = $('#'+prefix+'-contract-sources-section');
    const container = $('#'+prefix+'-contract-sources');
    let html = '';
    rows.forEach(function(row, index) {
        html += '<div class="contracts-source-row" data-contract-source-row data-contract-source-index="'+index+'">';
        html += '<div class="input-container"><label class="input-title">Tipo de fuente</label><select class="form-select input-value" data-contract-source-role="type" data-contract-source-prefix="'+prefix+'"></select></div>';
        html += '<div class="input-container"><label class="input-title">Registro</label><select class="form-select input-value" data-contract-source-role="id" data-contract-source-prefix="'+prefix+'"></select></div>';
        html += '<div class="contracts-source-row-actions"><button type="button" class="btn btn-link contracts-remove-source" title="Quitar fuente" aria-label="Quitar fuente"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button></div>';
        html += '</div>';
    });
    container.html(html);
    rows.forEach(function(row, index) {
        const sourceRow = container.find('[data-contract-source-row]').eq(index);
        const typeSelector = sourceRow.find('[data-contract-source-role="type"]');
        setOptions(typeSelector, getContractSourceTypeOptions(), row.type || null, false);
        if(!row.type) clearSelect(typeSelector);
        refreshContractSourceRow(sourceRow, row.id || '');
    });
    section.removeClass('d-none');
    const requirementText = requirements.map(function(requirement) {
        return sourceRequirementLabels[requirement];
    }).join(', ');
    $('#'+prefix+'-contract-source-requirements').text(requirementText
        ? 'Requeridas por la plantilla: '+requirementText+'. Puedes agregar otras fuentes.'
        : 'Agrega todas las fuentes que quieras utilizar en las variables de la plantilla.');
}

export function addContractSource(prefix) {
    const rows = collectContractSourceRows(prefix);
    rows.push({type: '', id: ''});
    renderContractSources(prefix, rows);
}

export function removeContractSource(row) {
    const sourceRow = $(row);
    const prefix = sourceRow.find('[data-contract-source-role="type"]').attr('data-contract-source-prefix');
    const rows = collectContractSourceRows(prefix);
    rows.splice(Number(sourceRow.attr('data-contract-source-index')), 1);
    renderContractSources(prefix, rows);
}

function shiftDate(value, months) {
    const parts = formatDate(value).split('-').map(Number);
    if(parts.length !== 3 || parts.some(isNaN)) return '';
    const year = parts[0];
    const month = parts[1] - 1;
    const day = parts[2];
    const date = new Date(Date.UTC(year, month, 1));
    date.setUTCMonth(date.getUTCMonth() + months);
    const lastDay = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
    date.setUTCDate(Math.min(day, lastDay));
    return date.toISOString().slice(0, 10);
}

export function applyLicenseToSources(prefix) {
    const rows = collectContractSourceRows(prefix);
    const licenseRow = rows.find(function(source) { return source.type === 'license' && source.id; });
    const license = contractState.catalogs.licenses.find(function(item) {
        return licenseRow && String(item.id) === String(licenseRow.id);
    });
    if(license && license.client_id){
        const clientRow = rows.find(function(source) { return source.type === 'client'; });
        if(clientRow) clientRow.id = license.client_id;
        else rows.push({type: 'client', id: license.client_id});
    }
    const recurrenceEnabled = $('#'+prefix+'-contract-recurrence-enabled').is(':checked');
    renderContractSources(prefix, rows);
    if(license) {
        const startInput = $('#'+prefix+'-contract-start-date');
        const endInput = $('#'+prefix+'-contract-end-date');
        const endDate = formatDate(license.next_billing_date);
        const startDate = formatDate(license.last_billing_date || license.last_payed_date)
            || (endDate && license.recurrence_months ? shiftDate(endDate, -Number(license.recurrence_months)) : '');
        if((recurrenceEnabled || !startInput.val()) && startDate) startInput.val(startDate);
        if((recurrenceEnabled || !endInput.val()) && endDate) endInput.val(endDate);
    }
}

export function setContractsFormExpanded(toggleSelector, bodySelector, formSelector, expanded) {
    const body = $(bodySelector);
    const toggle = $(toggleSelector);
    if(!body.length || !toggle.length) return;
    const collapsedLabel = toggle.attr('data-collapsed-label') || toggle.attr('aria-label') || 'Agregar';
    const label = expanded ? 'Ocultar formulario' : collapsedLabel;
    body.prop('hidden', !expanded);
    toggle.attr({
        'aria-expanded': expanded ? 'true' : 'false',
        'aria-label': label,
        title: label,
    });
    toggle.find('i').toggleClass('fa-plus', !expanded).toggleClass('fa-minus', expanded);
    $(formSelector).toggleClass('is-collapsed', !expanded);
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
        contractState.catalogs.licenses = response.licenses || [];
        contractState.catalogs.types = response.types || [];
        contractState.catalogs.templates = response.templates || [];
        contractState.catalogs.variables = response.variables || [];
        setTypeOptions();
        setTemplateOptions('#create-contract-type', '#create-contract-template');
        setTemplateOptions('#update-contract-type', '#update-contract-template');
        renderContractSources('create');
        renderContractSources('update');
        if(onLoaded) onLoaded();
    }, null);
}

export function formatDate(value) {
    return value ? String(value).slice(0, 10) : '';
}

export function formatDateTimeInput(value) {
    return value ? String(value).replace(' ', 'T').slice(0, 16) : '';
}