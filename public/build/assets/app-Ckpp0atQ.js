$(document).on("click","#close-session",l);$(document).on("click","#sidebar-toggle-btn",r);let s=null;const n=500;$(document).ready(function(){localStorage.getItem("sidebarCollapsed")==="true"&&($("#erp-app-sidebar").addClass("collapsed"),$("#erp-app-content").addClass("expanded")),i(),window.innerWidth<=768&&d()});function l(){PostMethodFunction("/admin/users/close-session",{},null,function(){window.location.href="/admin"},null)}function r(){const t=$("#erp-app-sidebar"),e=$("#erp-app-content");t.hasClass("collapsed")?(t.removeClass("collapsed"),e.removeClass("expanded"),localStorage.setItem("sidebarCollapsed","false")):(t.addClass("collapsed"),e.addClass("expanded"),localStorage.setItem("sidebarCollapsed","true")),setTimeout(i,300)}function i(){$("#erp-app-sidebar").hasClass("collapsed")?($(".sidebar-menu-item-link").each(function(){const o=$(this).find(".sidebar-menu-item-text").text().trim();o&&($(this).attr("title",o),$(this).attr("data-toggle","tooltip"),$(this).attr("data-placement","right"))}),$("#my-profile-image").attr("title","Mi perfil"),$("#my-profile-image").attr("data-toggle","tooltip"),$("#my-profile-image").attr("data-placement","right"),$("#close-session").attr("title","Cerrar sesión"),$("#close-session").attr("data-toggle","tooltip"),$("#close-session").attr("data-placement","right"),$('[data-toggle="tooltip"]').tooltip()):($('[data-toggle="tooltip"]').tooltip("dispose"),$(".sidebar-menu-item-link, #my-profile-image, #close-session").removeAttr("title data-toggle data-placement"))}function d(){const t=()=>{"vibrate"in navigator&&navigator.vibrate(10)};$(".sidebar-menu-item").on("touchstart",function(e){const o=$(this);o.addClass("pressed"),t(),s=setTimeout(()=>{const a=o.find(".sidebar-menu-item-text").text();a&&window.innerWidth<=768&&(c(a),t())},n)}),$(".sidebar-menu-item").on("touchend touchcancel",function(e){$(this).removeClass("pressed"),s&&(clearTimeout(s),s=null)}),$("#close-session").on("touchstart",function(){$(this).addClass("pressed"),t()}),$("#close-session").on("touchend touchcancel",function(){$(this).removeClass("pressed")}),$("#my-profile-image").on("touchstart",function(){$(this).addClass("pressed"),t()}),$("#my-profile-image").on("touchend touchcancel",function(){$(this).removeClass("pressed")}),$("#erp-app-sidebar").on("touchstart",function(e){e.touches[0].clientY}),$("#erp-app-sidebar").on("touchmove",function(e){e.touches[0].clientY})}function c(t){$(".mobile-toast").remove();const e=$('<div class="mobile-toast"></div>');e.text(t),e.css({position:"fixed",bottom:"20px",left:"50%",transform:"translateX(-50%)","background-color":"rgba(0, 0, 0, 0.85)",color:"#fff",padding:"12px 24px","border-radius":"24px","font-size":"0.9em","font-weight":"500","z-index":"9999","box-shadow":"0 4px 12px rgba(0,0,0,0.3)",animation:"fadeInUp 0.3s ease","max-width":"80%","text-align":"center","white-space":"nowrap",overflow:"hidden","text-overflow":"ellipsis"}),$("body").append(e),setTimeout(()=>{e.css("animation","fadeOutDown 0.3s ease"),setTimeout(()=>e.remove(),300)},1500)}$("#mobile-toast-animations").length||$("head").append(`
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
