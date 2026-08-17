export function safeJson(response){
    const contentType = response.headers.get('content-type') || '';
    if(!contentType.includes('application/json')){
        return response.text().then(text => {
            const preview = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300);
            const error = new Error('El servidor devolvió un error (' + response.status + '): ' + preview);
            error.httpStatus = response.status;
            throw error;
        });
    }
    return response.json();
}

export function getCsrfToken(){
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

export function escapeHtml(value){
    if(value === null || value === undefined) return '';
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}