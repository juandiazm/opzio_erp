import { outcomeState } from './state.js';

const catalogs = ['providers', 'employees', 'departments', 'users', 'clients'];

function fillCatalog(prefix, catalogName){
    const select = $('#'+prefix+'-outcome-'+catalogName.slice(0, -1));
    if(select.length === 0) return;
    const emptyLabel = select.find('option').first().text();
    select.empty().append($('<option>', {value: '', text: emptyLabel}));
    $.each(outcomeState.catalogs[catalogName] || [], function(index, item){
        select.append($('<option>', {value: item.id, text: item.label}));
    });
}

function showOutcomeFormData(response){
    outcomeState.catalogs = response.data;
    ['create', 'update'].forEach(function(prefix){ catalogs.forEach(function(catalogName){ fillCatalog(prefix, catalogName); }); });
    $('#create-outcome-user').val(outcomeState.catalogs.current_user_id || '').trigger('change');
}

export function getOutcomeFormData(){
    PostMethodFunction('/admin/outcomes/form-data', {}, null, showOutcomeFormData, null);
}