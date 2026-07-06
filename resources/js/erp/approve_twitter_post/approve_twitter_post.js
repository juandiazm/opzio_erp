$(document).ready(function(){
    approvePost();
});
function approvePost(){
    var dataSend = {
        unique_id:unique_id
    };
    PostMethodFunction('/api/twitter/approve',dataSend,null, function (response) {
        $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-check');
        $('#approve-post-status-message').text('Post aprobado exitosamente');
    }, function(){
        $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-exclamation-triangle');
        $('#approve-post-status-message').text('Error al aprobar el post');
    });
}