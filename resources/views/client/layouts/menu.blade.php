<!doctype html>
<head>
    <script src="{{ asset('js/client/layouts/menu.js') }}" defer></script>
    <!-- Styles -->
    <link href="{{ asset('css/client/layouts/menu.css') }}" rel="stylesheet">    
</head>
<body>
    <header id="menu-nav" class="w-100">
        <section id="first-header" class="general-padding d-none d-md-flex">
            <ul id="header-social-media-list" class="d-none d-md-flex justify-content-end">
                <li class="header-social-media-item">
                    <a target="_blank" href="mailto:comunicaciones@ridder.com.co">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                </li>
                <li class="header-social-media-item">
                    <a target="_blank" href="https://www.facebook.com/riddersyh">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </li>
                <li class="header-social-media-item">
                    <a target="_blank" href="https://www.instagram.com/riddersyh/">
                        <i class="fab fa-instagram"></i>
                    </a>
                </li>
                <li class="header-social-media-item">
                    <a target="_blank" href="https://wa.me/573197536472">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </li>
            </ul>
        </section>
        <section id="secondary-header" class="general-padding d-flex justify-content-between">
            <section class="d-block d-md-none my-auto">
                <i class="fa-solid fa-bars" id="burger-menu"></i>
            </section>
            <img src="/images/business_logo_white_light.webp" alt="Ridder" id="header-business-logo" onclick="location.href='/'">
            <ul id="header-navigation-list" class="d-none d-md-flex justify-content-end align-self-center">
                <li class="header-navigation-list-item header-navigation-normal">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}">{{ __('landing_page.header.navigation.web_page') }}</a>
                </li>
                @if(session()->has('client_user'))
                <li class="header-navigation-list-item header-navigation-normal">
                    <i class="fa-regular fa-bell" id="header-menu-notification-icon"></i>
                </li>
                <li class="header-navigation-list-item header-navigation-normal" id="header-menu-client-user-profile-container">
                    <a href="/client/profile" class="d-flex justify-content-center w-100 p-0 m-0">
                        <div class="align-self-center" id="header-menu-client-user-initials" style="background-color: {{ session('client_user')['color'] }}; color: white;">
                            <p class="m-0 p-0">{{ session('client_user')['name'][0].(session('client_user')['lastname']==null?'':session('client_user')['lastname'][0]) }}</p>
                        </div>
                        <p class="align-self-center" id="header-menu-client-user-name">{{ session('client_user')['name'] }}</p>
                    </a>
                </li>
                @endif
            </ul>
            <!--<section class="d-block d-md-none my-auto">
                <i class="fa-regular fa-user" id="user-responsive-menu"></i>
            </section>-->
        </section>
        <section id="responsive-menu" class="d-block d-md-none">
            <button id="responsive-close-opt">X</button>
            <img id="responsive-header-logo" src="/images/business_logo_blue_light.webp" alt="RIDDER S.A.S" onclick="location.href='/'">
            <ul id="responsive-ul-nav" class="scrollable-transparent">
                 <li class="custom-space nav-list">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}#about-us">{{ __('landing_page.header.navigation.about_us') }}</a>
                </li>
                <li class="custom-space nav-list">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}#what-we-do">{{ __('landing_page.header.navigation.services') }}</a>
                </li>
                <li class="custom-space nav-list">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}/briefcase">{{ __('landing_page.header.navigation.mallette') }}</a>
                </li>
                <li class="custom-space nav-list">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}#contact">{{ __('landing_page.header.navigation.contact') }}</a>
                </li>
                <li class="header-navigation-list-item">
                    <a href="{{ env('APP_HOME_PAGE_URL') }}" class="header-pay-button">{{ __('landing_page.header.navigation.pay') }}</a>
                </li>
            </ul>
        </section>
    </header>
</body>
</html>
