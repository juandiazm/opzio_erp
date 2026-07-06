$(document).on('click', '#burger-menu', openResponsiveEmergentMenu);
$(document).on('click', '#responsive-close-opt', closeResponsiveEmergentMenu);
$(document).on('click', '#responsive-menu .nav-list', closeResponsiveEmergentMenu);
$(document).ready(function(){
});
function closeResponsiveEmergentMenu(){
    $('#responsive-menu').animate({
        left: '-100%'
    }, 500, function(){
        $('#responsive-menu').css('display', 'none');
    });
}
function openResponsiveEmergentMenu(){
    $('#responsive-menu').css('display', 'block').css('left', '-100%');
    $('#responsive-menu').animate({
        left: '0%'
    }, 500);
}