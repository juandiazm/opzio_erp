@extends('client.layouts.app')
@section('component_title', 'Mi perfil')
@section('client-app-header')
<script>
    var current_user = {!! json_encode(session('client_user')) !!};
    var permissions_flag = {{ collect(session('permissions'))->firstWhere('client_user_permission_id', 1)!=null?1:0 }}
    var permissions = {!! json_encode(session('permissions')) !!};
</script>
@vite('resources/js/client/profile/profile.js')
<!-- Styles -->
@vite('resources/sass/client/profile/profile.scss')
@endsection
@section('client-app-content')
<div id="my-profile-container">
    <div id="inputs-container" class="row">
        <div class="col-12 col-md-5">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID del usuario">
                    <label for="clientname" class="input-title align-self-center">ID Usuario</label>
                    <p id="client-user-id" class="input-value align-self-center" title="{{ session('client_user')['unique_id'] }}"><i class="fa-regular fa-copy copy-action" data-clipboard-text="{{ session('client_user')['unique_id'] }}"></i> {{ substr(session('client_user')['unique_id'], -10) }}</p>
                </div>
                <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                    <label for="clientname" class="input-title align-self-center">Nombre</label>
                    <input type="text" id="client-user-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre" value="{{ session('client_user')['name'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                    <label for="clientname" class="input-title align-self-center">Apellido</label>
                    <input type="text" id="client-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Apellido" value="{{ session('client_user')['lastname'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Nombre de usuario">
                    <label for="clientname" class="input-title align-self-center">Usuario</label>
                    <input type="text" id="client-user-username" class="input-value form-control align-self-center" name="username" placeholder="Usuario" value="{{ session('client_user')['username'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Correo electrónico del usuario">
                    <label for="clientname" class="input-title align-self-center">Correo</label>
                    <input type="email" id="client-user-email" class="input-value form-control align-self-center" name="email" placeholder="Correo" value="{{ session('client_user')['email'] }}">
                </div>
            </div>
            
        </div>
        <div class="col-12 col-md-5">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Teléfono del usuario">
                    <label for="clientname" class="input-title align-self-center">Teléfono</label>
                    <input type="tel" id="client-user-phone" class="input-value form-control align-self-center" name="phone" placeholder="Teléfono" value="{{ session('client_user')['phone'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Cargo del usuario">
                    <label for="clientname" class="input-title align-self-center">Cargo</label>
                    <input type="text" id="client-user-position" class="input-value form-control align-self-center" name="position" placeholder="Cargo" value="{{ session('client_user')['position'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                    <label for="clientname" class="input-title align-self-center">Contraseña</label>
                    <input type="password" id="client-user-password" class="input-password input-value form-control align-self-center" name="password" placeholder="Contraseña">
                </div>
                <div class="input-container col-12 d-flex" title="Confirmar contraseña del usuario">
                    <label for="clientname" class="input-title align-self-center">R-Contraseña</label>
                    <input type="password" id="client-user-confirm-password" class="input-password input-value form-control align-self-center" name="confirm-password" placeholder="Confirmar contraseña">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-2 d-flex flex-column justify-content-center">
            <div class="multimedia-input-container align-self-center">
                <div id="client-user-color-container" class="color-container" style="background-color: {{ session('client_user')['color'] }}; border-color: {{ session('client_user')['color'] }};">
                    <input type="color" name="color" id="client-user-color" class="input-color" value="{{ session('client_user')['color'] }}">
                </div>
                <i class="fa-solid fa-plus image-plus-icon"></i>
            </div>
        </div>
    </div>
    @if(collect(session('permissions'))->firstWhere('client_user_permission_id', 1)!=null)
    <div id="permissions-container">
        <h3 id="permissions-title">Permisos</h2>
        <div class="row permissions-list">
        </div>
    </div>
    @endif
    <button class="btn btn-secondary" id="update-client-user-button">Guardar</button>
</div>

@endsection
