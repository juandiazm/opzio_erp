import { incomeState } from './state.js';
import { getRichTextHtml, getRichTextPlainText } from './rich-text.js';

const previewStates = {
    0: {label: 'Cotización', icon: 'fa-file-pen', className: 'status-state-0'},
    1: {label: 'Rechazada', icon: 'fa-circle-xmark', className: 'status-state-1'},
    2: {label: 'Aprobada', icon: 'fa-circle-check', className: 'status-state-2'},
    3: {label: 'Pagada', icon: 'fa-money-bill-wave', className: 'status-state-3'},
    4: {label: 'Facturada', icon: 'fa-file-invoice-dollar', className: 'status-state-4'},
};

const previewAllowedTags = new Set(['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li']);

function escapeHtml(value){
    return String(value ?? '').replace(/[&<>'"]/g, function(character){ return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]; });
}

function sanitizePreviewFragment(value){
    const template = document.createElement('template');
    template.innerHTML = String(value || '');
    const sanitizeNode = function(node){
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
                Array.from(child.attributes).forEach(attribute => child.removeAttribute(attribute.name));
                sanitizeNode(child);
            }
            child = next;
        }
    };
    sanitizeNode(template.content);
    return template.content;
}

function formatMoney(value){
    return '$'+Math.round(Number(value) || 0).toLocaleString('es-CO');
}

function formatPreviewDate(value){
    const match = String(value ?? '').match(/\d{4}-\d{2}-\d{2}/);
    return match ? match[0] : 'Borrador';
}

function formatRecurrence(value){
    if(value == null || value === '') return '';
    const text = String(value).trim();
    if(!text) return '';
    if(/mes/i.test(text)) return text;
    const months = Number(text);
    return Number.isFinite(months) ? months+' '+(months === 1 ? 'Mes' : 'Meses') : text;
}

function getPreviewIncome(){
    return incomeState.currentTab === 'nav-update-tab' ? incomeState.currentIncome || {} : {};
}

function getClient(container){
    const selectedId = container.find('.input-client').val();
    const selectedClient = incomeState.clientsList.find(client => String(client.id) === String(selectedId));
    if(selectedClient) return selectedClient;
    if(incomeState.currentClient && (!selectedId || String(incomeState.currentClient.id) === String(selectedId))) return incomeState.currentClient;
    return getPreviewIncome().client || null;
}

function getClientName(container, client){
    const clientName = client ? [client.name, client.last_name || client.lastname].filter(Boolean).join(' ') : '';
    if(clientName) return clientName;
    const selectedOption = container.find('.input-client option:selected').text().trim();
    if(selectedOption && selectedOption !== 'Seleccione un cliente') return selectedOption;
    return getPreviewIncome().client_name || 'Cliente por seleccionar';
}

function getClientDetails(client){
    const income = getPreviewIncome();
    const country = typeof client?.country === 'object' ? client.country?.name : client?.country;
    return {
        identification: client?.identification || income.client_identification || '',
        address: client?.address || '',
        phone: client?.phone || '',
        email: client?.email || '',
        country: country || client?.country_name || '',
    };
}

function normalizeItem(item){
    const value = Number(item.value) || 0;
    const rawTaxValue = Number(item.tax_value) || 0;
    const taxValue = rawTaxValue > 1 ? rawTaxValue / 100 : rawTaxValue;
    const itemTotal = Number(item.total);
    return {
        license_name: item.license_name || 'Ítem sin nombre',
        service_name: item.service_name || '',
        recurrence_months: item.recurrence_months,
        value,
        hours: Number(item.hours) || 0,
        tax_value: taxValue,
        tax_name: item.tax_name || '',
        total: Number.isFinite(itemTotal) ? itemTotal : value * (1 + taxValue),
        description: item.description || '',
        description_html: item.description_html || '',
    };
}

function getDraftItem(container){
    const row = container.find('.income-items-table .add-row');
    if(!row.length) return null;
    const licenseId = String(row.find('.input-item-license').val() || '');
    const licenseName = row.find('.input-item-license option:selected').text().trim();
    const value = Number(row.find('.input-item-value').val()) || 0;
    const hours = Number(row.find('.input-item-hours').val()) || 0;
    const descriptionHtml = getRichTextHtml(row, '.input-item-description-editor');
    const description = getRichTextPlainText(descriptionHtml);
    if((!licenseId || licenseId === '0') && value === 0 && hours === 0 && !description) return null;
    const taxPercentage = parseFloat(row.find('.input-item-tax').text().replace(',', '.')) || 0;
    const taxValue = taxPercentage / 100;
    return normalizeItem({
        license_name: licenseId && licenseId !== '0' && licenseName !== 'Seleccione una licencia' ? licenseName : 'Ítem en edición',
        service_name: row.find('.input-item-service').text().trim(),
        recurrence_months: row.find('.input-item-recurrence').text().trim(),
        value,
        hours,
        tax_value: taxValue,
        total: value * (1 + taxValue),
        description,
        description_html: descriptionHtml,
    });
}

function getPreviewItems(container){
    const items = (incomeState.currentLicencesList || []).map(normalizeItem);
    const draftItem = getDraftItem(container);
    if(draftItem) items.push(draftItem);
    return items;
}

function getPreviewColumns(hasTaxes, hasHours, showTotalColumn){
    const hasOptionalColumns = hasTaxes || hasHours;
    const hasBothOptionalColumns = hasTaxes && hasHours;
    const descriptionWidth = showTotalColumn ? (hasBothOptionalColumns ? '42%' : hasOptionalColumns ? '48%' : '62%') : (hasBothOptionalColumns ? '50%' : hasOptionalColumns ? '55%' : '78%');
    const valueWidth = showTotalColumn ? (hasBothOptionalColumns ? '19%' : hasOptionalColumns ? '20%' : '19%') : (hasBothOptionalColumns ? '26%' : hasOptionalColumns ? '30%' : '22%');
    const optionalWidth = showTotalColumn ? (hasBothOptionalColumns ? '10%' : '13%') : (hasBothOptionalColumns ? '12%' : '15%');
    const totalWidth = showTotalColumn ? (hasBothOptionalColumns ? '19%' : hasOptionalColumns ? '19%' : '19%') : '0%';
    const columns = [
        {key: 'description', label: 'Descripción', className: 'text-start income-pdf-preview-description-cell', width: descriptionWidth},
    ];
    if(hasTaxes) columns.push({key: 'tax', label: 'Impuestos', className: 'text-center', width: optionalWidth});
    if(hasHours) columns.push({key: 'hours', label: 'Horas', className: 'text-center', width: optionalWidth});
    columns.push({key: 'value', label: 'Valor Und', className: 'text-end income-pdf-preview-money', width: valueWidth});
    if(showTotalColumn) columns.push({key: 'total', label: 'Total', className: 'text-end income-pdf-preview-money', width: totalWidth});
    return columns;
}

function renderPreviewItems(preview, items, showTotalColumn){
    const hasTaxes = items.some(item => item.tax_value > 0);
    const hasHours = items.some(item => item.hours > 0);
    const columns = getPreviewColumns(hasTaxes, hasHours, showTotalColumn);
    const table = preview.find('table');
    const header = table.find('[data-preview-column-headers]').empty();
    const body = preview.find('[data-preview-items]').empty();
    columns.forEach(function(column){ $('<th>', {class: column.className}).css('width', column.width).text(column.label).appendTo(header); });
    preview.find('[data-preview-item-count]').text(items.length+' '+(items.length === 1 ? 'ítem' : 'ítems'));
    preview.find('[data-preview-empty]').prop('hidden', items.length > 0);

    let currentService = '';
    items.forEach(function(item){
        const service = [item.service_name, item.license_name].filter(Boolean).join(' - ');
        if(service !== currentService){
            currentService = service;
            const serviceRow = $('<tr>', {class: 'income-pdf-preview-service-row'}).appendTo(body);
            $('<td>').attr('colspan', columns.length).text(service).appendTo(serviceRow);
        }

        const row = $('<tr>').appendTo(body);
        const taxText = item.tax_value > 0 ? (item.tax_name ? item.tax_name+'(' : '')+(item.tax_value * 100).toLocaleString('es-CO')+'%'+(item.tax_name ? ')' : '') : '';
        columns.forEach(function(column){
            const cell = $('<td>', {class: column.className}).css('width', column.width).appendTo(row);
            if(column.key === 'description'){
                const recurrence = formatRecurrence(item.recurrence_months);
                const descriptionHtml = item.description_html || escapeHtml(item.description);
                if(recurrence) $('<span>', {class: 'income-pdf-preview-recurrence'}).text(recurrence).appendTo(cell);
                if(descriptionHtml) cell.append(sanitizePreviewFragment(descriptionHtml));
            }else if(column.key === 'tax') cell.text(taxText);
            else if(column.key === 'hours') cell.text(item.hours > 0 ? item.hours : '');
            else if(column.key === 'value') cell.text(formatMoney(item.value)+' COP');
            else if(column.key === 'total') cell.text(formatMoney(item.total)+' COP');
        });
    });
}

export function refreshIncomePreview(){
    const container = incomeState.currentContainer;
    if(!container || !container.length) return;
    const preview = container.find('[data-income-preview]');
    if(!preview.length) return;

    const selectedState = container.find('.state-input.selected').attr('value');
    const currentIncome = getPreviewIncome();
    const state = String(selectedState ?? currentIncome.state ?? '0');
    const presentation = previewStates[state] || previewStates[0];
    const isPurchaseDocument = ['2', '3', '4'].includes(state);
    const stateElement = preview.find('[data-preview-state]');
    stateElement.attr('class', 'income-pdf-preview-status '+presentation.className);
    stateElement.find('.income-preview-status-icon').attr('class', 'income-preview-status-icon fa-solid '+presentation.icon);
    stateElement.find('[data-preview-state-label]').text(presentation.label);
    stateElement.attr('aria-label', 'Estado: '+presentation.label);
    preview.find('[data-preview-save-status]').text(currentIncome.unique_id ? 'Edición en vivo' : 'Sin guardar');
    preview.find('[data-preview-document-title]').text(isPurchaseDocument ? 'ORDEN DE COMPRA' : 'COTIZACIÓN');

    const client = getClient(container);
    const clientName = getClientName(container, client);
    const clientDetails = getClientDetails(client);
    preview.find('[data-preview-client]').text(clientName);
    preview.find('[data-preview-identification]').text(clientDetails.identification || '-');
    preview.find('[data-preview-address]').text(clientDetails.address || '-');
    preview.find('[data-preview-phone]').text(clientDetails.phone || '-');
    preview.find('[data-preview-email]').text(clientDetails.email || '-');
    preview.find('[data-preview-country]').text(clientDetails.country || '-');

    const uniqueId = String(currentIncome.unique_id || '');
    preview.find('[data-preview-id]').text(uniqueId ? uniqueId.slice(-10) : 'BORRADOR');
    preview.find('[data-preview-created-at]').text(formatPreviewDate(currentIncome.created_at_string || currentIncome.created_at));
    preview.find('[data-preview-payment-line]').prop('hidden', !isPurchaseDocument);
    preview.find('[data-preview-cutoff-line]').prop('hidden', !isPurchaseDocument);
    preview.find('[data-preview-payment-date]').text(container.find('.input-timely-payment').val() || '-');
    preview.find('[data-preview-cutoff-date]').text(container.find('.input-cutoff-date').val() || '-');
    preview.find('[data-preview-payment-tools]').prop('hidden', !isPurchaseDocument);
    preview.find('[data-preview-department]').text(isPurchaseDocument ? 'Departamento Contable' : 'Departamento Comercial');
    preview.find('[data-preview-commercial-email]').prop('hidden', isPurchaseDocument);
    preview.find('[data-preview-accounting-email]').prop('hidden', !isPurchaseDocument);
    preview.find('[data-preview-bank]').prop('hidden', !isPurchaseDocument);

    const descriptionHtml = getRichTextHtml(container, '.input-description-editor');
    const descriptionSection = preview.find('[data-preview-description-section]');
    const descriptionTarget = preview.find('[data-preview-description]').empty();
    descriptionSection.prop('hidden', !descriptionHtml);
    if(descriptionHtml) descriptionTarget.append(sanitizePreviewFragment(descriptionHtml));

    const items = getPreviewItems(container);
    const showTotalColumn = state !== '0' || container.find('.input-quotation-totalize').is(':checked');
    renderPreviewItems(preview, items, showTotalColumn);
    const subtotal = items.reduce((sum, item) => sum + item.value, 0);
    const total = items.reduce((sum, item) => sum + item.total, 0);
    preview.find('[data-preview-subtotal]').text(formatMoney(subtotal)+' COP');
    preview.find('[data-preview-taxes]').text(formatMoney(total - subtotal)+' COP');
    preview.find('[data-preview-total]').text(formatMoney(total)+' COP');
    preview.find('[data-preview-totals]').prop('hidden', !showTotalColumn);
}