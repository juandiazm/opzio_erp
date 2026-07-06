@extends('layouts.app')
@section('app-header')
<title>RIDDER - Respuesta de Pago</title>
<script>
    var unique_id = "{{ $unique_id }}";
</script>
<script src="{{ asset('js/client/payment_response_unlogged/payment_response_unlogged.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/payment_response_unlogged/payment_response_unlogged.css') }}" rel="stylesheet">
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<section id="pay-unlogged-container" class="d-flex justify-content-center">
    <div id="pay-result-container" class="align-self-center justify-content-around">
        <div id="pay-result-data-continer">
            <!--<img src="/images/bussines-logo-simple-blues.png" alt="Avatar" class="avatar-img">-->
            <i class="fas fa-5x" id="pay-result-icon"></i>
            <h1 id="pay-result-title"></h1>
            <p id="pay-result-description"></p>
            <button class="btn" id="pay-result-btn">CONTINUAR</button>
        </div>
    </div>
</section>
@endsection
