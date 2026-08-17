$(document).on('click', '#sidebar-toggle-btn', toggleSidebar);

let touchStartY = 0;
let longPressTimer = null;
const LONG_PRESS_DURATION = 500;

$(document).ready(function(){
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    setSidebarState(sidebarCollapsed);
    initializeTooltips();

    if (window.innerWidth <= 768) {
        initMobileSidebarInteractions();
    }
});

function toggleSidebar(){
    const sidebar = $('#erp-app-sidebar');
    const isCollapsed = !sidebar.hasClass('collapsed');

    setSidebarState(isCollapsed);
    localStorage.setItem('sidebarCollapsed', String(isCollapsed));
    initializeTooltips();
}

function setSidebarState(isCollapsed){
    const sidebar = $('#erp-app-sidebar');
    const toggle = $('#sidebar-toggle-btn');

    sidebar.toggleClass('collapsed', isCollapsed);
    toggle.attr('aria-expanded', String(!isCollapsed));
    toggle.attr('aria-label', isCollapsed ? 'Expandir menú' : 'Colapsar menú');
    toggle.attr('title', isCollapsed ? 'Expandir menú' : 'Colapsar menú');
}

function initializeTooltips(){
    const sidebar = $('#erp-app-sidebar');
    const isCollapsed = sidebar.hasClass('collapsed');

    if (isCollapsed) {
        $('.sidebar-menu-item-link').each(function(){
            const text = $(this).find('.sidebar-menu-item-text').text().trim();
            if (text) {
                $(this).attr('title', text);
            }
        });
        $('#sidebar-brand-link').attr('title', 'Dashboard');
    } else {
        $('.sidebar-menu-item-link, #sidebar-brand-link').removeAttr('title');
    }
}

function initMobileSidebarInteractions(){
    const hapticFeedback = () => {
        if ('vibrate' in navigator) {
            navigator.vibrate(10);
        }
    };

    $('.sidebar-menu-item').on('touchstart', function(){
        const $item = $(this);

        $item.addClass('pressed');
        hapticFeedback();

        longPressTimer = setTimeout(() => {
            const itemText = $item.find('.sidebar-menu-item-text').text();
            if (itemText && window.innerWidth <= 768) {
                showMobileToast(itemText);
                hapticFeedback();
            }
        }, LONG_PRESS_DURATION);
    });

    $('.sidebar-menu-item').on('touchend touchcancel', function(){
        const $item = $(this);
        $item.removeClass('pressed');

        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
    });

    let isScrolling = false;
    $('#erp-app-sidebar').on('touchstart', function(event){
        touchStartY = event.touches[0].clientY;
        isScrolling = false;
    });

    $('#erp-app-sidebar').on('touchmove', function(event){
        const touchY = event.touches[0].clientY;
        const deltaY = Math.abs(touchY - touchStartY);

        if (deltaY > 10) {
            isScrolling = true;
        }
    });
}

function showMobileToast(message){
    $('.mobile-toast').remove();

    const $toast = $('<div class="mobile-toast"></div>');
    $toast.text(message);
    $toast.css({
        'position': 'fixed',
        'bottom': '20px',
        'left': '50%',
        'transform': 'translateX(-50%)',
        'background-color': 'rgba(0, 0, 0, 0.85)',
        'color': '#fff',
        'padding': '12px 24px',
        'border-radius': '24px',
        'font-size': '0.9em',
        'font-weight': '500',
        'z-index': '9999',
        'box-shadow': '0 4px 12px rgba(0,0,0,0.3)',
        'animation': 'fadeInUp 0.3s ease',
        'max-width': '80%',
        'text-align': 'center',
        'white-space': 'nowrap',
        'overflow': 'hidden',
        'text-overflow': 'ellipsis'
    });

    $('body').append($toast);

    setTimeout(() => {
        $toast.css('animation', 'fadeOutDown 0.3s ease');
        setTimeout(() => $toast.remove(), 300);
    }, 1500);
}

if (!$('#mobile-toast-animations').length) {
    $('head').append(`
        <style id="mobile-toast-animations">
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translate(-50%, 20px);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, 0);
                }
            }

            @keyframes fadeOutDown {
                from {
                    opacity: 1;
                    transform: translate(-50%, 0);
                }
                to {
                    opacity: 0;
                    transform: translate(-50%, 20px);
                }
            }

            .pressed {
                opacity: 0.7 !important;
            }
        </style>
    `);
}