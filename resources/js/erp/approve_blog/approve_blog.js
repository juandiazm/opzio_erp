$(document).on('click', '#approve_blog-button', approveBlog);
$(document).ready(function(){});
function approveBlog(){
    $('#approve_blog-button').attr('disabled',true);
    $('.status-spinner').removeAttr('class').addClass('status-spinner fa fa-spinner fa-spin').css('visibility','visible');
    var dataSend = {
        unique_id:unique_id
        ,send_to_subscribers:$('#propagation-opt-email').is(':checked')
        ,send_to_facebook:$('#propagation-opt-facebook').is(':checked')
        ,send_to_linkedin:$('#propagation-opt-linkedin').is(':checked')
        ,send_to_twitter:$('#propagation-opt-twitter').is(':checked')
    };
    PostMethodFunction('/api/blog/approve',dataSend,null, function (response) {
        $('.status-spinner').removeAttr('class').addClass('status-spinner fa fa-check');
        $('#approve_blog-button').remove();
    }, function(){
        $('#approve_blog-button').attr('disabled',false);
        $('.status-spinner').removeAttr('class').addClass('status-spinner fa fa-exclamation-triangle');
    });
}