import { outcomeState } from './state.js';

const selectCatalogs = ['providers', 'employees', 'departments', 'users', 'clients'];

function setSelectOptions(select, items, emptyLabel){
    if(select.length === 0) return;
    const selectedValue = select.val() || '';
    const options = [{value: '', label: emptyLabel}].concat((items || []).map(function(item){
        return {value: item.id, label: item.label};
    }));
    const hasSelectedValue = options.some(function(option){ return String(option.value) === String(selectedValue); });
    if(window.SearchableDropdown){
        window.SearchableDropdown.setOptions(select[0], options);
        window.SearchableDropdown.setValue(select[0], hasSelectedValue ? selectedValue : '', false);
        return;
    }
    select.empty();
    options.forEach(function(option){
        select.append($('<option>', {value: option.value, text: option.label}));
    });
    select.val(hasSelectedValue ? selectedValue : '');
}

function fillCatalog(prefix, catalogName){
    const fieldName = catalogName.slice(0, -1);
    const select = $('#'+prefix+'-outcome-'+fieldName);
    setSelectOptions(select, outcomeState.catalogs[catalogName], select.find('option').first().text());
}

function fillOutcomeTypeCatalog(prefix){
    const container = $('#'+prefix+'-outcome-type').closest('.crud-input-container');
    const list = container.find('.crud-list');
    if(container.length === 0 || list.length === 0) return;
    list.find('.crud-item-update').remove();
    $.each(outcomeState.catalogs.types || [], function(index, item){
        const listItem = $('<li>', {class: 'crud-item-update justify-content-between'}).attr('item-id', item.id);
        listItem.append($('<input>', {type: 'text', class: 'crud-item-update-input align-self-center', placeholder: 'Actualizar', value: item.label}));
        listItem.append($('<i>', {class: 'crud-item-update-icon fa-solid fa-pencil align-self-center'}));
        listItem.append($('<i>', {class: 'crud-item-delete-icon fa-solid fa-trash-can align-self-center'}));
        list.append(listItem);
    });
}

function showOutcomeFormData(response, onLoaded){
    Object.assign(outcomeState.catalogs, response.data || {});
    outcomeState.outcomeTypes = outcomeState.catalogs.types || [];
    ['create', 'update'].forEach(function(prefix){
        selectCatalogs.forEach(function(catalogName){ fillCatalog(prefix, catalogName); });
        fillOutcomeTypeCatalog(prefix);
    });
    $('#create-outcome-user').val(outcomeState.catalogs.current_user_id || '').trigger('change');
    if(onLoaded) onLoaded(response);
}

export function getOutcomeFormData(onLoaded, onFailed){
    PostMethodFunction('/admin/outcomes/form-data', {}, null, function(response){ showOutcomeFormData(response, onLoaded); }, onFailed || null);
}