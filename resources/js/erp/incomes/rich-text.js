let eventsInitialized = false;

export function getRichTextPlainText(value){
    const container = document.createElement('div');
    container.innerHTML = value || '';
    return (container.textContent || '').replace(/\s+/g, ' ').trim();
}

function syncEditor(editor){
    const wrapper = $(editor).closest('[data-rich-text]');
    wrapper.find('[data-rich-plain]').val(getRichTextPlainText(editor.innerHTML));
}

export function initRichTextEditors(root = document){
    const container = root instanceof HTMLElement ? root : root?.[0] || document;
    container.querySelectorAll('[data-rich-editor]').forEach(function(editor){
        if(editor.dataset.richInitialized === 'true') return;
        editor.dataset.richInitialized = 'true';
        editor.addEventListener('input', function(){ syncEditor(editor); });
        syncEditor(editor);
    });
}

export function getRichTextHtml(scope, selector){
    const editor = $(scope).find(selector).first();
    return editor.length ? editor.html().trim() : '';
}

export function setRichTextContent(scope, selector, content){
    const editor = $(scope).find(selector).first();
    if(!editor.length) return;
    editor.html(content || '');
    syncEditor(editor[0]);
}

export function initializeRichTextEvents(){
    if(eventsInitialized) return;
    eventsInitialized = true;
    $(document).on('mousedown.incomeRichText', '[data-rich-command]', function(event){ event.preventDefault(); });
    $(document).on('click.incomeRichText', '[data-rich-command]', function(){
        const editor = $(this).closest('[data-rich-text]').find('[data-rich-editor]').first()[0];
        if(!editor) return;
        editor.focus();
        document.execCommand($(this).data('rich-command'), false, $(this).data('rich-value') || null);
        syncEditor(editor);
    });
}