@extends('layouts.app')
@section('app-header')
@vite('resources/js/erp/pusher/pusher.js')
@vite('resources/js/erp/pusher/pusher_channels_chat.js')
@vite('resources/js/erp/layouts/app.js')
<!-- Styles -->
@vite('resources/sass/erp/layouts/app.scss')
@yield('erp-app-header')
@endsection
@section('app-content')
<div id="erp-app-container" class="row w-100 px-0 mx-0">
    <div id="erp-app-sidebar" class="col-12 col-md-2 d-flex flex-column">
        <button id="sidebar-toggle-btn" class="sidebar-toggle-btn d-none d-md-flex" aria-label="Toggle menu">
            <i class="fa-light fa-chevron-left"></i>
        </button>
        <div id="my-profile-container" class="d-flex justify-content-start">
            <div id="my-profile-image-container">
                <img src="/images/erp/users/{{ session('user')['photo'] }}" alt="My Profile" id="my-profile-image" style="border-color:{{ session('user')['color'] }};" onclick="location.href='/admin/my-profile'">
            </div>
            <div id="my-profile-name-container" class="align-self-center">
                <p id="my-profile-name">{{ session('user')['name'].' '.session('user')['lastname'] }}</p>
                <!--<p id="my-profile-role">Administrador</p>-->
                <a id="my-profile-link" href="/admin/my-profile">Mi perfíl</a>
            </div>
        </div>
        <ul id="sidebar-menu" class="scrollable">
            <li class="sidebar-menu-item{{ request()->is('admin') ? ' selected' : '' }}">
                <a href="/admin" class="sidebar-menu-item-link">
                    <i class="fa-light fa-gauge-high align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Dashboard</p>
                </a>
            </li>
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 1)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/users')?' selected':'' }}">
                <a href="/admin/users" class="sidebar-menu-item-link">
                    <i class="fa-light fa-users align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Usuarios</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 2)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/clients')?' selected':'' }}">
                <a href="/admin/clients" class="sidebar-menu-item-link">
                    <i class="fa-light fa-address-card align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Clientes</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 3)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/employees')?' selected':'' }}">
                <a href="/admin/employees" class="sidebar-menu-item-link">
                    <i class="fa-light fa-user-tie align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Empleados</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 4)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/providers')?' selected':'' }}">
                <a href="/admin/providers" class="sidebar-menu-item-link">
                    <i class="fa-light fa-truck align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Proveedores</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 5)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/departments')?' selected':'' }}">
                <a href="/admin/departments" class="sidebar-menu-item-link">
                    <i class="fa-light fa-sitemap align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Departamentos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 6)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/licenses')?' selected':'' }}">
                <a href="/admin/licenses" class="sidebar-menu-item-link">
                    <i class="fa-light fa-file-certificate align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Licencias</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 7)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/incomes')?' selected':'' }}">
                <a href="/admin/incomes" class="sidebar-menu-item-link">
                    <i class="fa-light fa-arrow-trend-up align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Ingresos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 8)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/outcomes')?' selected':'' }}">
                <a href="/admin/outcomes" class="sidebar-menu-item-link">
                    <i class="fa-light fa-arrow-trend-down align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Egresos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 9)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/reports')?' selected':'' }}">
                <a href="/admin/reports" class="sidebar-menu-item-link">
                    <i class="fa-light fa-chart-bar align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Reportes</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 10)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/web-pages')?' selected':'' }}">
                <a href="/admin/web-pages" class="sidebar-menu-item-link">
                    <i class="fa-light fa-globe align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Página Web</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 18)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/chat')?' selected':'' }}">
                <a href="/admin/chat" class="sidebar-menu-item-link">
                    <i class="fa-light fa-comments comments align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">Chats</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 19)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/ia-assistant')?' selected':'' }}">
                <a href="/admin/ia-assistant" class="sidebar-menu-item-link">
                    <i class="fa-light fa-microchip-ai align-self-center sidebar-menu-item-icon"></i>
                    <p class="align-self-center sidebar-menu-item-text">IA Assistant</p>
                </a>
            </li>
            @endif
        </ul>
        <button class="align-self-center mt-auto mb-0 d-flex justify-content-center" id="close-session">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27.244 29.009" id="close-session-img">
                <path  d="M11.021,27.924,2.177,25.848A2.812,2.812,0,0,1,0,23.1V4.789A2.806,2.806,0,0,1,2.21,2.033L11.054.068a2.819,2.819,0,0,1,3.432,2.7h4.408A2.82,2.82,0,0,1,21.71,5.586V7.571a.384.384,0,1,1-.768,0V5.586a2.05,2.05,0,0,0-2.048-2.048h-4.4V24.511h4.4a2.051,2.051,0,0,0,2.048-2.048V20.726a.384.384,0,0,1,.768,0v1.738a2.82,2.82,0,0,1-2.816,2.816H14.483A2.818,2.818,0,0,1,11.672,28,2.862,2.862,0,0,1,11.021,27.924Zm.2-27.107L2.377,2.783A2.043,2.043,0,0,0,.768,4.789V23.1a2.047,2.047,0,0,0,1.585,2L11.2,27.176a2.054,2.054,0,0,0,2.524-2V2.823A2.056,2.056,0,0,0,11.67.768,2.088,2.088,0,0,0,11.221.818Zm10.63,17.218a.384.384,0,0,1,0-.543l3.083-3.083H16.791a.384.384,0,0,1,0-.768h8.12l-2.927-2.829a.384.384,0,1,1,.534-.552l3.611,3.49,0,0,0,0a.388.388,0,0,1,.059.078l.011.021a.387.387,0,0,1,.032.085s0,.007,0,.01a.387.387,0,0,1,.006.1c0,.008,0,.016,0,.024a.382.382,0,0,1-.075.186l-.017.021c-.006.006-.01.014-.016.02l-3.739,3.739a.384.384,0,0,1-.543,0ZM9.228,16.022V12.28a.384.384,0,1,1,.768,0v3.743a.384.384,0,1,1-.768,0Z"/>
            </svg>
            <p id="close-session-text" class="align-self-center">Cerrar Sesión</p>
        </button>
    </div>
    <div id="erp-app-content" class="col-11 col-md-10 scrollable">
        <div id="erp-app-content-title-container">
            <h1 id="erp-app-content-title">@yield('component_title', 'Default Page Title')</h1>
            <div id="loader-container">
                <i class="fa-duotone fa-loader fa-spin-pulse d-none" id="loader-icon"></i>
            </div>
            @yield('component-title-options')
        </div>
        @yield('erp-app-content')
    </div>
    <img src="/images/opzio-logo-wide-purple-transparent.webp" id="principal-logo-opzio" alt="opzio" onclick="location.href='/admin'">
</div>
@endsection
