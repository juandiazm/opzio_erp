export function initializePostApproval(endpoint){
    $(document).ready(function(){
        const dataSend = { unique_id: window.unique_id };
        PostMethodFunction(endpoint, dataSend, null, function(){
            $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-check');
            $('#approve-post-status-message').text('Post aprobado exitosamente');
        }, function(){
            $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-exclamation-triangle');
            $('#approve-post-status-message').text('Error al aprobar el post');
        });
    });
}