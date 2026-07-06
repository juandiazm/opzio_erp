@extends('layouts.app')
@section('app-header')
<script>
    var unique_id = "{{ $unique_id }}";
    var home_url = "{{ config('app.APP_HOME_PAGE_URL') }}";
</script>
@vite('resources/js/client/blog/email/unsubscribe.js')
<!-- Styles -->
@vite('resources/sass/client/blog/email/unsubscribe.scss')
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<div class="unsubscribe-container">
    <div class="unsubscribe-sub-container">
        <i class="fa-solid fa-newspaper icon"></i>
        <h1 class="title">Ayudanos a mejorar</h1>
        <p class="description">Nuestro sistema de noticias tiene como objetivo brindarte la mejor experiencia posible, por favor dinos la razón por la cual deseas darte de baja de nuestras noticias.</p>
        <textarea id="unsubscribe_reason" class="reason" placeholder="Escribe aquí tu razón" autofocus></textarea>
        <dib class="buttons-container">
            <button id="unsubscribe" class="unsubscribe btn">Darme de baja</button>
            <button id="cancel" class="cancel btn">Seguir recibiendo noticias</button>
        </dib>
    </div>
</div>
@endsection
