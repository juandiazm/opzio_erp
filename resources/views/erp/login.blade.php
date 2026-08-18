@extends('layouts.app')
@section('app-header')
@vite('resources/js/erp/login/login.js')
<!-- Styles -->
@vite('resources/sass/erp/login/login.scss')
@yield('home-app-header')
@endsection
@section('app-content')
<section id="login-container" class="d-flex justify-content-center">
    <div id="login-decoration" aria-hidden="true">
        <span class="login-decoration-ring login-decoration-ring-one"></span>
        <span class="login-decoration-ring login-decoration-ring-two"></span>
        <span class="login-decoration-trace login-decoration-trace-one"></span>
        <span class="login-decoration-trace login-decoration-trace-two"></span>
        <span class="login-decoration-node login-decoration-node-one"></span>
        <span class="login-decoration-node login-decoration-node-two"></span>
    </div>
    <div id="login-centered" class="align-self-center d-flex justify-content-around">
        <div id="opzio-logo-container">
            <img src="{{ asset('images/opzio-logo-wide-purple-transparent.webp') }}" id="opzio-logo" alt="Opzio" decoding="async">
        </div>
        <div id="login-data-container">
            <i class="fa-regular fa-circle-user" id="avatar-img"></i>
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
