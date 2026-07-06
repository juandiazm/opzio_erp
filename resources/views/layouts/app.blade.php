<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="shortcut icon" href="/images/mini_icon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Configuration -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#00057B">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- iOS PWA Configuration -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RIDDER ERP">
    <link rel="apple-touch-icon" href="/images/pwa/icon-192x192.png">
    <link rel="apple-touch-startup-image" href="/images/pwa/icon-512x512.png">
    
    {!! SEO::generate() !!}
    <meta property="og:image" content="{{ asset('images/bussines-logo-rounded-white.webp') }}">
    <meta property="og:image:width" content="801">
    <meta property="og:image:height" content="801">
    <!-- Scripts -->
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/popper.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/general.js')}}" defer></script>
    <script src="{{ asset('js/crud-input.js')}}" defer></script>
    <script src="{{ asset('js/pwa.js')}}" defer></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet" async>
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/crud-input.css') }}" rel="stylesheet">
    @yield('app-header')
</head>
<body>
    {{ csrf_field() }}
    <div id="layout-app" class="mx-0 px-0 w-100">
        @yield('app-content')
    </div>
    <a class="d-block w-100 px-0 mx-0 text-center" href="https://ridder.com.co" id="ridder-feet" target="_blank">® Powered by RIDDER - {{ \Carbon\Carbon::now()->format('Y') }}</a>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAF9AFSoPJMHWh_8EmeOGTFLVRTQcFCm-M">
    </script>
</body>
</html>
