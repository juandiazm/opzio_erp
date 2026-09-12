export const updateRecordQueryParameter = 'record_id';

function replaceCurrentUrl(url){
    window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
}

export function getUpdateRecordId(legacyParameters = []){
    const params = new URLSearchParams(window.location.search);
    const parameters = [updateRecordQueryParameter, ...legacyParameters];

    for(const parameter of parameters){
        const value = params.get(parameter);
        if(value !== null && value !== '') return value;
    }

    return null;
}

export function setUpdateRecordId(recordId, legacyParameters = []){
    const url = new URL(window.location.href);

    if(recordId === null || recordId === undefined || recordId === ''){
        url.searchParams.delete(updateRecordQueryParameter);
    }else{
        url.searchParams.set(updateRecordQueryParameter, String(recordId));
    }

    legacyParameters.forEach(parameter => {
        if(parameter !== updateRecordQueryParameter) url.searchParams.delete(parameter);
    });

    replaceCurrentUrl(url);
}

export function clearUpdateRecordId(legacyParameters = []){
    setUpdateRecordId(null, legacyParameters);
}