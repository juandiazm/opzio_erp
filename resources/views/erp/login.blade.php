@extends('layouts.app')
@section('app-header')
@vite('resources/js/erp/login/login.js')
<!-- Styles -->
@vite('resources/sass/erp/login/login.scss')
@yield('home-app-header')
@endsection
@section('app-content')
<section id="login-container" class="d-flex justify-content-center">
    <div id="login-centered" class="align-self-center d-flex justify-content-around">
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
