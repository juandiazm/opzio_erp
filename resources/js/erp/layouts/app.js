$(document).on('click', '#close-session', closeSession);
$(document).on('click', '#sidebar-toggle-btn', toggleSidebar);

// Mejorar interacción táctil en mobile
let touchStartY = 0;
let touchStartTime = 0;
let longPressTimer = null;
const LONG_PRESS_DURATION = 500; // ms

$(document).ready(function(){
    // Cargar el estado del sidebar desde localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        $('#erp-app-sidebar').addClass('collapsed');
        $('#erp-app-content').addClass('expanded');
    }
    
    // Inicializar tooltips cuando el sidebar está colapsado
    initializeTooltips();
    
    // Detectar dispositivos móviles
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        initMobileInteractions();
    }
});

function closeSession(){
    PostMethodFunction('/admin/users/close-session',{},null,function(){
        window.location.href = "/admin";
    },null);
}

function toggleSidebar(){
    const sidebar = $('#erp-app-sidebar');
    const content = $('#erp-app-content');
    
    const isCollapsed = sidebar.hasClass('collapsed');
    
    if (isCollapsed) {
        // Expandir
        sidebar.removeClass('collapsed');
        content.removeClass('expanded');
        localStorage.setItem('sidebarCollapsed', 'false');
    } else {
        // Colapsar
        sidebar.addClass('collapsed');
        content.addClass('expanded');
        localStorage.setItem('sidebarCollapsed', 'true');
    }
    
    // Actualizar tooltips después del toggle
    setTimeout(initializeTooltips, 300);
}

function initializeTooltips(){
    const sidebar = $('#erp-app-sidebar');
    const isCollapsed = sidebar.hasClass('collapsed');
    
    if (isCollapsed) {
        // Agregar tooltips cuando está colapsado
        $('.sidebar-menu-item-link').each(function(){
            const text = $(this).find('.sidebar-menu-item-text').text().trim();
            if (text) {
                $(this).attr('title', text);
                $(this).attr('data-toggle', 'tooltip');
                $(this).attr('data-placement', 'right');
            }
        });
        
        $('#my-profile-image').attr('title', 'Mi perfil');
        $('#my-profile-image').attr('data-toggle', 'tooltip');
        $('#my-profile-image').attr('data-placement', 'right');
        
        $('#close-session').attr('title', 'Cerrar sesión');
        $('#close-session').attr('data-toggle', 'tooltip');
        $('#close-session').attr('data-placement', 'right');
        
        // Inicializar tooltips de Bootstrap
        $('[data-toggle="tooltip"]').tooltip();
    } else {
        // Remover tooltips cuando está expandido
        $('[data-toggle="tooltip"]').tooltip('dispose');
        $('.sidebar-menu-item-link, #my-profile-image, #close-session').removeAttr('title data-toggle data-placement');
    }
}

function initMobileInteractions(){
    // Feedback háptico si está disponible
    const hapticFeedback = () => {
        if ('vibrate' in navigator) {
            navigator.vibrate(10); // Vibración corta
        }
    };
    
    // Mejorar interacción con items del menú
    $('.sidebar-menu-item').on('touchstart', function(e){
        const $item = $(this);
        touchStartTime = Date.now();
        
        // Añadir clase de pressed para feedback visual
        $item.addClass('pressed');
        hapticFeedback();
        
        // Detectar long press para mostrar nombre completo
        longPressTimer = setTimeout(() => {
            const itemText = $item.find('.sidebar-menu-item-text').text();
            if (itemText && window.innerWidth <= 768) {
                showMobileToast(itemText);
                hapticFeedback();
            }
        }, LONG_PRESS_DURATION);
    });
    
    $('.sidebar-menu-item').on('touchend touchcancel', function(e){
        const $item = $(this);
        $item.removeClass('pressed');
        
        // Cancelar long press
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
    });
    
    // Mejorar botón de cerrar sesión
    $('#close-session').on('touchstart', function(){
        $(this).addClass('pressed');
        hapticFeedback();
    });
    
    $('#close-session').on('touchend touchcancel', function(){
        $(this).removeClass('pressed');
    });
    
    // Mejorar imagen de perfil
    $('#my-profile-image').on('touchstart', function(){
        $(this).addClass('pressed');
        hapticFeedback();
    });
    
    $('#my-profile-image').on('touchend touchcancel', function(){
        $(this).removeClass('pressed');
    });
    
    // Prevenir scroll en sidebar mientras se hace scroll
    let isScrolling = false;
    $('#erp-app-sidebar').on('touchstart', function(e){
        touchStartY = e.touches[0].clientY;
        isScrolling = false;
    });
    
    $('#erp-app-sidebar').on('touchmove', function(e){
        const touchY = e.touches[0].clientY;
        const deltaY = Math.abs(touchY - touchStartY);
        
        if (deltaY > 10) {
            isScrolling = true;
        }
    });
}

function showMobileToast(message){
    // Remover toast anterior si existe
    $('.mobile-toast').remove();
    
    // Crear nuevo toast
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
    
    // Auto-remover después de 1.5s
    setTimeout(() => {
        $toast.css('animation', 'fadeOutDown 0.3s ease');
        setTimeout(() => $toast.remove(), 300);
    }, 1500);
}

// Añadir animaciones CSS para el toast
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