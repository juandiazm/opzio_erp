@extends('layouts.app')
@section('app-header')
<script src="{{ asset('js/erp/reset_password/reset_password.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/reset_password/reset_password.css') }}" rel="stylesheet">
@yield('admin-app-header')
@endsection
@section('app-content')
<section id="reset-password-container" class="d-flex justify-content-center">
    <div id="reset-password-centered" class="align-self-center d-flex justify-content-around">
        <div id="reset-password-data-container">
            <i class="fa-solid fa-key restore-admin-user-password-btn fa-bounce" id="reset-password-icon"></i>
            <h1 id="reset-password-title">Actualiza tu contraseña</h1>
            <input type="password" id="reset-password" class="form-control input-password" placeholder="Contraseña" autofocus>
            <input type="password" id="set-confirm-password" class="form-control input-password" placeholder="Confirmar contraseña">
            <button class="btn btn-primary" id="reset-password-btn">ACTUALIZAR</button>
        </div>
    </div>
</section>
@endsection
