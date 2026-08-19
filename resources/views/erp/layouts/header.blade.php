<header id="erp-app-header">
    <div id="erp-profile-container">
        <button id="erp-profile-toggle" type="button" aria-expanded="false" aria-controls="erp-profile-dropdown" aria-haspopup="true">
            <span id="my-profile-image-container">
                <img src="/storage/images/erp/users/{{ session('user')['photo'] }}?v={{ optional(data_get(session('user'), 'updated_at'))->timestamp ?? 0 }}" alt="Foto de {{ session('user')['name'] }}" id="my-profile-image" style="border-color:{{ session('user')['color'] }};">
            </span>
            <span id="my-profile-name-container">
                <span id="my-profile-name">{{ session('user')['name'].' '.session('user')['lastname'] }}</span>
                <span id="my-profile-role">Mi perfil</span>
            </span>
            <i class="fa-light fa-chevron-down" id="erp-profile-chevron" aria-hidden="true"></i>
        </button>
        <div id="erp-profile-dropdown" role="menu" aria-hidden="true">
            <a href="/admin/my-profile" class="erp-profile-menu-item" role="menuitem">
                <i class="fa-light fa-user" aria-hidden="true"></i>
                <span>Mi perfil</span>
            </a>
            <button type="button" class="erp-profile-menu-item" id="close-session" role="menuitem">
                <i class="fa-light fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>Cerrar sesión</span>
            </button>
        </div>
    </div>
    <div id="erp-app-header-context">
        <div id="erp-app-content-title-container">
            <h1 id="erp-app-content-title">@yield('component_title', 'Default Page Title')</h1>
            <div id="loader-container">
                <i class="fa-duotone fa-loader fa-spin-pulse d-none" id="loader-icon"></i>
            </div>
            @yield('component-title-options')
        </div>
    </div>
</header>