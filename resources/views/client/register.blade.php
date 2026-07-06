@extends('layouts.app')
@section('app-header')
@vite('resources/js/client/register/register.js')
<!-- Styles -->
@vite('resources/sass/client/register/register.scss')
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<section id="register-container" class="d-flex justify-content-center">
    <div id="register-centered" class="align-self-center d-flex justify-content-around">
        <div id="register-data-container">
            <img src="/images/bussines-logo-simple-blues.png" alt="Avatar" id="avatar-img">
            <h1 id="register-title">Crear una cuenta</h1>
            <p id="register-message">App Clientes</p>
            <div id="inputs-container">
                <div class="input-container row">
                    <label for="name" class="col-4 align-self-center label">Cliente</label>
                    <input type="text" id="name" class="col-8 input align-self-center" placeholder="Empresa / Nombre y apellido" autofocus>
                </div>
                <div class="input-container row">
                    <label for="identification_type" class="col-4 align-self-center label">Tipo de ID</label>
                    <select class="col-8 input align-self-center input" id="identification-type" name="identification_type">
                        <option value="0" selected>Nit</option>
                        <option value="1">Cédula</option>
                        <option value="2">Pasaporte</option>
                        <option value="3">Cédula extranjera</option>
                    </select>
                </div>
                <div class="input-container row">
                    <label for="identification" class="col-4 align-self-center label">Identificación</label>
                    <input type="text" id="identification" class="col-8 input align-self-center" placeholder="123456789">
                </div>
                <div class="input-container row">
                    <label for="email" class="col-4 align-self-center label">Correo</label>
                    <input type="text" id="email" class="col-8 input align-self-center" placeholder="info@empresa.com">
                </div>
                <div class="input-container row">
                    <label for="country" class="col-4 align-self-center label">País</label>
                    <select class="col-8 input align-self-center" id="country" name="country">
                    </select>
                </div>
            </div>
            <button class="btn" id="register-btn">REGISTRARME</button>
            <div id="login-container" class="d-flex justify-content-center" onclick="location.href='/client'">
                <p id="login-message">¿Ya tienes una cuenta?</p>
                <p id="login-link">Inicia Sesión</p>
            </div>
        </div>
    </div>
</section>
@endsection
