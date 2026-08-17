$(document).on('click', '#close-session', closeSession);
$(document).on('click', '#erp-profile-toggle', toggleProfileMenu);
$(document).on('click', function(event){
    if (!$(event.target).closest('#erp-profile-container').length) {
        closeProfileMenu();
    }
});
$(document).on('keydown', function(event){
    if (event.key === 'Escape') {
        closeProfileMenu();
    }
});

$(document).ready(function(){
    if (window.innerWidth <= 768) {
        initMobileHeaderInteractions();
    }
});

function closeSession(){
    PostMethodFunction('/admin/users/close-session',{},null,function(){
        window.location.href = "/admin";
    },null);
}

function toggleProfileMenu(event){
    event.preventDefault();

    const toggle = $('#erp-profile-toggle');
    const dropdown = $('#erp-profile-dropdown');
    const isOpen = dropdown.hasClass('is-open');

    closeProfileMenu();

    if (!isOpen) {
        toggle.attr('aria-expanded', 'true');
        dropdown.addClass('is-open').attr('aria-hidden', 'false');
    }
}

function closeProfileMenu(){
    $('#erp-profile-toggle').attr('aria-expanded', 'false');
    $('#erp-profile-dropdown').removeClass('is-open').attr('aria-hidden', 'true');
}

function initMobileHeaderInteractions(){
    const hapticFeedback = () => {
        if ('vibrate' in navigator) {
            navigator.vibrate(10);
        }
    };

    $('#close-session').on('touchstart', function(){
        $(this).addClass('pressed');
        hapticFeedback();
    });

    $('#close-session').on('touchend touchcancel', function(){
        $(this).removeClass('pressed');
    });

    $('#my-profile-image').on('touchstart', function(){
        $(this).addClass('pressed');
        hapticFeedback();
    });

    $('#my-profile-image').on('touchend touchcancel', function(){
        $(this).removeClass('pressed');
    });
}