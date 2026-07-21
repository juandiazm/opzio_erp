<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Configuration -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1E0045">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- iOS PWA Configuration -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Opzio ERP">
    <link rel="apple-touch-startup-image" href="/images/pwa/icon-512x512.png">
    
    {!! SEO::generate() !!}
    <meta property="og:image" content="{{ asset('images/bussines-logo-rounded-white.webp') }}">
    <meta property="og:image:width" content="801">
    <meta property="og:image:height" content="801">
    <!-- Scripts -->
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/popper.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    @vite('resources/js/app.js')
    <script src="{{ asset('js/general.js')}}" defer></script>
    @vite('resources/js/crud-input.js')
    <script src="{{ asset('js/pwa.js')}}" defer></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet" async>
    <!-- Styles -->
    @vite('resources/sass/app.scss')
    @vite('resources/sass/crud-input.scss')
    @yield('app-header')
</head>
<body>
    {{ csrf_field() }}
    <div id="layout-app" class="mx-0 px-0 w-100">
        @yield('app-content')
    </div>
    <a class="d-block w-100 px-0 mx-0 text-center" href="https://opzio.co" id="opzio-feet" target="_blank">® Powered by Opzio - {{ \Carbon\Carbon::now()->format('Y') }}</a>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAF9AFSoPJMHWh_8EmeOGTFLVRTQcFCm-M">
    </script>
</body>
</html>
