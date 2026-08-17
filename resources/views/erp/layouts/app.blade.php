@extends('layouts.app')
@section('app-header')
@vite('resources/js/erp/pusher/pusher.js')
@vite('resources/js/erp/pusher/pusher_channels_chat.js')
@vite('resources/js/erp/layouts/app.js')
<!-- Styles -->
@vite('resources/sass/erp/layouts/app.scss')
@vite('resources/sass/erp/layouts/sidebar.scss')
@vite('resources/sass/erp/layouts/header.scss')
@yield('erp-app-header')
@endsection
@section('app-content')
<div id="erp-app-container">
    @include('erp.layouts.sidebar')
    <main id="erp-app-main">
        @include('erp.layouts.header')
        <section id="erp-app-content" class="scrollable">
            @yield('erp-app-content')
        </section>
    </main>
</div>
@endsection
