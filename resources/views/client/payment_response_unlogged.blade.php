@extends('layouts.app')
@section('app-header')
<title>Opzio - Respuesta de Pago</title>
<script>
    var unique_id = "{{ $unique_id }}";
</script>
@vite('resources/js/client/payment_response_unlogged/payment_response_unlogged.js')
<!-- Styles -->
@vite('resources/sass/client/payment_response_unlogged/payment_response_unlogged.scss')
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<section id="pay-unlogged-container" class="d-flex justify-content-center">
    <div id="pay-result-container" class="align-self-center justify-content-around">
        <div id="pay-result-data-continer">
            <!--<img src="/images/opzio-logo-compact-purple-transparent.webp" alt="Opzio" class="avatar-img">-->
            <i class="fas fa-5x" id="pay-result-icon"></i>
            <h1 id="pay-result-title"></h1>
            <p id="pay-result-description"></p>
            <button class="btn" id="pay-result-btn">CONTINUAR</button>
        </div>
    </div>
</section>
@endsection
