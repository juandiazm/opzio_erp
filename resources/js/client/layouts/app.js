$(document).on('click', '#close-session', closeSession);
//event when window size change
$(window).resize(function(){
    //get size of header menu
    var headerHeight = $('#menu-nav').height();
    var windowHeight = $(window).height();
    //set left menu height
    $('#client-app-sidebar').css('height', windowHeight - headerHeight).css('max-height', windowHeight - headerHeight);
    //set app content height
    $('#client-app-content').css('height', windowHeight - headerHeight).css('max-height', windowHeight - headerHeight);
});
$(document).ready(function(){
    $(window).resize();
});
function closeSession(){
    PostMethodFunction('/client/profile/close-session',{},null,function(){
        window.location.href = "/";
    },null);
}
//on image load error
function onImageError(image){
    image.src = '/images/no-image.png';
}
