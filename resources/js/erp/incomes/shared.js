export function goToIncomesTraceability(search){
    $('#nav-traceability').attr('search', search);
    $('#nav-traceability-tab').tab('show');
    $('#nav-traceability-tab').trigger('click');
}