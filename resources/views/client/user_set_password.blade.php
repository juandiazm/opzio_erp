@extends('layouts.app')
@section('app-header')
<script src="{{ asset('js/client/set_password/set_password.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/set_password/set_password.css') }}" rel="stylesheet">
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<section id="set-password-container" class="d-flex justify-content-center">
    <div id="set-password-centered" class="align-self-center d-flex justify-content-around">
        <div id="set-password-data-container">
            <i class="fa-solid fa-key restore-client-user-password-btn fa-bounce" id="set-password-icon"></i>
            <h1 id="set-password-title">Actualiza tu contraseña</h1>
            <input type="password" id="set-password" class="form-control input-password" placeholder="Contraseña" autofocus>
            <input type="password" id="set-confirm-password" class="form-control input-password" placeholder="Confirmar contraseña">
            <button class="btn btn-primary" id="set-password-btn">ACTUALIZAR</button>
        </div>
    </div>
</section>
@endsection
