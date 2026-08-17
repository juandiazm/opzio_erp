const instances = new WeakMap();
let openInstance = null;
let generatedId = 0;

function resolveSelect(target){
    if(target instanceof HTMLSelectElement) return target;
    if(target && target.jquery) return target[0];
    if(typeof target === 'string') return document.querySelector(target);
    return target && target.nodeType === 1 ? target : null;
}

function getOptions(select){
    return Array.from(select.options).filter(function(option){ return !option.disabled; });
}

function normalizeSearchText(value){
    return value.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function getSearchText(option){
    return normalizeSearchText((option.textContent+' '+option.value).trim());
}

function getBaseId(select){
    if(select.id) return select.id;
    generatedId += 1;
    return 'searchable-dropdown-'+generatedId;
}

function syncState(instance){
    const select = instance.select;
    const selectedOption = select.options[select.selectedIndex];
    const selectedText = selectedOption ? selectedOption.textContent.trim() : instance.placeholder;
    const isPlaceholder = !selectedOption || selectedOption.disabled;
    instance.triggerText.textContent = selectedText || instance.placeholder;
    instance.triggerText.classList.toggle('is-placeholder', isPlaceholder);
    instance.trigger.disabled = select.disabled;
    instance.wrapper.classList.toggle('is-disabled', select.disabled);
    instance.wrapper.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
    instance.trigger.setAttribute('aria-label', selectedText || instance.placeholder);
}

function renderOptions(instance, search = ''){
    const query = normalizeSearchText(search.trim());
    const options = getOptions(instance.select).filter(function(option){
        return query === '' || getSearchText(option).includes(query);
    });
    instance.options = options;
    instance.optionsList.innerHTML = '';
    if(options.length === 0){
        const emptyOption = document.createElement('li');
        emptyOption.className = 'searchable-dropdown__empty';
        emptyOption.textContent = 'Sin resultados';
        instance.optionsList.appendChild(emptyOption);
        return;
    }
    options.forEach(function(option, index){
        const listItem = document.createElement('li');
        const optionButton = document.createElement('button');
        optionButton.type = 'button';
        optionButton.className = 'searchable-dropdown__option';
        optionButton.dataset.value = option.value;
        optionButton.dataset.index = index;
        optionButton.id = instance.id+'-option-'+index;
        optionButton.setAttribute('role', 'option');
        optionButton.setAttribute('aria-selected', option.value === instance.select.value ? 'true' : 'false');
        optionButton.textContent = option.textContent.trim();
        optionButton.addEventListener('click', function(){ selectOption(instance, option.value); });
        optionButton.addEventListener('keydown', function(event){ handleOptionKeydown(event, instance, index); });
        listItem.appendChild(optionButton);
        instance.optionsList.appendChild(listItem);
    });
}

function selectOption(instance, value){
    instance.select.value = value;
    syncState(instance);
    instance.select.dispatchEvent(new Event('change', {bubbles: true}));
    close(instance);
    instance.trigger.focus();
}

function open(instance){
    if(instance.select.disabled) return;
    if(openInstance && openInstance !== instance) close(openInstance);
    openInstance = instance;
    instance.wrapper.classList.add('is-open');
    instance.panel.hidden = false;
    instance.trigger.setAttribute('aria-expanded', 'true');
    instance.searchInput.value = '';
    renderOptions(instance);
    window.requestAnimationFrame(function(){ instance.searchInput.focus(); });
}

function close(instance){
    if(!instance) return;
    instance.wrapper.classList.remove('is-open');
    instance.panel.hidden = true;
    instance.trigger.setAttribute('aria-expanded', 'false');
    instance.searchInput.value = '';
    if(openInstance === instance) openInstance = null;
}

function focusOption(instance, index){
    const optionButton = instance.optionsList.querySelector('[data-index="'+index+'"]');
    if(optionButton) optionButton.focus();
}

function handleTriggerKeydown(event, instance){
    if(['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)){
        event.preventDefault();
        open(instance);
    }
}

function handleSearchKeydown(event, instance){
    if(event.key === 'Escape'){
        event.preventDefault();
        close(instance);
        instance.trigger.focus();
    }else if(event.key === 'ArrowDown'){
        event.preventDefault();
        focusOption(instance, 0);
    }else if(event.key === 'Enter' && instance.options.length === 1){
        event.preventDefault();
        selectOption(instance, instance.options[0].value);
    }
}

function handleOptionKeydown(event, instance, index){
    if(event.key === 'ArrowDown'){
        event.preventDefault();
        focusOption(instance, Math.min(index + 1, instance.options.length - 1));
    }else if(event.key === 'ArrowUp'){
        event.preventDefault();
        if(index === 0) instance.searchInput.focus();
        else focusOption(instance, index - 1);
    }else if(event.key === 'Escape'){
        event.preventDefault();
        close(instance);
        instance.trigger.focus();
    }else if(event.key === 'Enter' || event.key === ' '){
        event.preventDefault();
        selectOption(instance, instance.options[index].value);
    }
}

function enhance(select){
    if(!select || select.tagName !== 'SELECT' || select.multiple) return null;
    const existing = instances.get(select);
    if(existing){
        syncState(existing);
        renderOptions(existing);
        return existing;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-dropdown';
    wrapper.dataset.searchableDropdownFor = select.id || '';
    Array.from(select.classList).forEach(function(className){
        if(className === 'form-select' || className === 'form-control' || className === 'js-searchable-dropdown') return;
        if(className === 'input-value' || className === 'input' || /^(col-|w-|p-|m-|align-self-)/.test(className)) wrapper.classList.add(className);
    });
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('searchable-dropdown__native');
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;

    const baseId = getBaseId(select);
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'searchable-dropdown__trigger';
    trigger.id = baseId+'-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const triggerText = document.createElement('span');
    triggerText.className = 'searchable-dropdown__text';
    const triggerIcon = document.createElement('i');
    triggerIcon.className = 'fa-light fa-chevron-down searchable-dropdown__icon';
    triggerIcon.setAttribute('aria-hidden', 'true');
    trigger.appendChild(triggerText);
    trigger.appendChild(triggerIcon);

    const panel = document.createElement('div');
    panel.className = 'searchable-dropdown__panel';
    panel.hidden = true;

    const searchWrapper = document.createElement('div');
    searchWrapper.className = 'searchable-dropdown__search-wrapper';
    const searchIcon = document.createElement('i');
    searchIcon.className = 'fa-light fa-magnifying-glass searchable-dropdown__search-icon';
    searchIcon.setAttribute('aria-hidden', 'true');
    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'searchable-dropdown__search';
    searchInput.placeholder = select.dataset.searchPlaceholder || 'Buscar...';
    searchInput.setAttribute('aria-label', 'Buscar opciones');
    searchWrapper.appendChild(searchIcon);
    searchWrapper.appendChild(searchInput);

    const optionsList = document.createElement('ul');
    optionsList.className = 'searchable-dropdown__options';
    optionsList.id = baseId+'-options';
    optionsList.setAttribute('role', 'listbox');
    panel.appendChild(searchWrapper);
    panel.appendChild(optionsList);
    wrapper.appendChild(trigger);
    wrapper.appendChild(panel);

    const instance = {
        id: baseId,
        select,
        wrapper,
        trigger,
        triggerText,
        panel,
        searchInput,
        optionsList,
        options: [],
        placeholder: select.dataset.placeholder || 'Seleccionar',
        originalTabIndex: select.getAttribute('tabindex'),
        originalAriaHidden: select.getAttribute('aria-hidden'),
    };
    trigger.setAttribute('aria-controls', optionsList.id);
    instances.set(select, instance);

    trigger.addEventListener('click', function(){
        if(panel.hidden) open(instance);
        else close(instance);
    });
    trigger.addEventListener('keydown', function(event){ handleTriggerKeydown(event, instance); });
    searchInput.addEventListener('input', function(){ renderOptions(instance, searchInput.value); });
    searchInput.addEventListener('keydown', function(event){ handleSearchKeydown(event, instance); });
    const syncFromChange = function(){
        syncState(instance);
        renderOptions(instance, searchInput.value);
    };
    if(window.jQuery){
        window.jQuery(select).on('change.searchableDropdown', syncFromChange);
        instance.removeChangeListener = function(){ window.jQuery(select).off('change.searchableDropdown', syncFromChange); };
    }else{
        select.addEventListener('change', syncFromChange);
        instance.removeChangeListener = function(){ select.removeEventListener('change', syncFromChange); };
    }

    const selectObserver = new MutationObserver(function(){
        syncState(instance);
        renderOptions(instance, searchInput.value);
    });
    selectObserver.observe(select, {attributes: true, childList: true, subtree: true, attributeFilter: ['class', 'disabled', 'selected']});
    instance.selectObserver = selectObserver;
    syncState(instance);
    renderOptions(instance);
    return instance;
}

function init(target = document){
    const element = resolveSelect(target);
    if(element && element.tagName === 'SELECT') return enhance(element);
    const root = element && element.querySelectorAll ? element : document;
    const scope = root && root.querySelectorAll ? root : document;
    return Array.from(scope.querySelectorAll('select:not([multiple])')).map(enhance);
}

function observeDynamicSelects(){
    if(!document.body) return;
    const observer = new MutationObserver(function(records){
        records.forEach(function(record){
            Array.from(record.addedNodes).forEach(function(node){
                if(node.nodeType !== 1) return;
                if(node.matches && node.matches('select:not([multiple])')) enhance(node);
                if(node.querySelectorAll) node.querySelectorAll('select:not([multiple])').forEach(enhance);
            });
        });
    });
    observer.observe(document.body, {childList: true, subtree: true});
}

function setOptions(target, options){
    const select = resolveSelect(target);
    if(!select) return null;
    select.innerHTML = '';
    (options || []).forEach(function(option){
        const optionElement = document.createElement('option');
        optionElement.value = option.value ?? '';
        optionElement.textContent = option.label ?? '';
        optionElement.disabled = Boolean(option.disabled);
        select.appendChild(optionElement);
    });
    return enhance(select);
}

function setValue(target, value){
    const select = resolveSelect(target);
    if(!select) return;
    select.value = value == null ? '' : value;
    select.dispatchEvent(new Event('change', {bubbles: true}));
}

function getValue(target){
    const select = resolveSelect(target);
    return select ? select.value : null;
}

function destroy(target){
    const select = resolveSelect(target);
    const instance = select ? instances.get(select) : null;
    if(!instance) return;
    close(instance);
    instance.selectObserver.disconnect();
    instance.removeChangeListener();
    instance.wrapper.parentNode.insertBefore(select, instance.wrapper);
    instance.wrapper.remove();
    select.classList.remove('searchable-dropdown__native');
    if(instance.originalAriaHidden == null) select.removeAttribute('aria-hidden');
    else select.setAttribute('aria-hidden', instance.originalAriaHidden);
    if(instance.originalTabIndex == null) select.removeAttribute('tabindex');
    else select.setAttribute('tabindex', instance.originalTabIndex);
    instances.delete(select);
}

document.addEventListener('click', function(event){
    if(openInstance && !openInstance.wrapper.contains(event.target)) close(openInstance);
});

const SearchableDropdown = {init, setOptions, setValue, getValue, destroy};
window.SearchableDropdown = SearchableDropdown;

function boot(){
    init();
    observeDynamicSelects();
}

if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();

export {destroy, getValue, init, setOptions, setValue};