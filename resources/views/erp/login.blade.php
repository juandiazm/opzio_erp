@extends('layouts.app')
@section('app-header')
<script src="{{ asset('js/erp/login/login.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/login/login.css') }}" rel="stylesheet">
@yield('home-app-header')
@endsection
@section('app-content')
<section id="login-container" class="d-flex justify-content-center">
    <div id="login-centered" class="align-self-center d-flex justify-content-around">
        <div id="ridder-logo-container" class="align-self-center">
            <img src="/images/business_logo_white_light.webp" alt="Ridder" id="ridder-logo">
        </div>
        <div id="login-data-container">
            <img src="/images/login/avatar.svg" alt="Avatar" id="avatar-img">
            <h1 id="login-title">Iniciar Sesión</h1>
            <p id="login-message">¡Bienvenido de nuevo!</p>
            <input type="text" id="login-identification" class="form-control" placeholder="Identificación / Correo / Username" autofocus>
            <input type="password" id="login-password" class="form-control input-password" placeholder="Contraseña">
            <button class="btn btn-primary" id="login-btn">INGRESAR</button>
            <p id="forgot-password">¿Olvidaste tu contraseña?</p>
        </div>
    </div>
</section>
@endsection
