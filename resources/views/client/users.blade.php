@extends('client.layouts.app')
@section('component_title', 'Usuarios')
@section('client-app-header')
<script>
    var current_logged_user = @json(session('client_user'));
</script>
<script src="{{ asset('js/client/users/users.js') }}" defer></script>
<script src="{{ asset('js/client/traceability.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/users/users.css') }}" rel="stylesheet">
<link href="{{ asset('css/client/traceability.css') }}" rel="stylesheet">
@endsection
@section('client-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
        <button class="nav-link" id="nav-traceability-tab" data-bs-toggle="tab" data-bs-target="#nav-traceability" type="button" role="tab" aria-controls="nav-traceability" aria-selected="false">Trazabilidad</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="user-list-container" class="scrollable">
            <table id="user-list-table" class="table table-sm align-middle w-100">
                <thead id="user-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-color text-center">Color</th>
                        <th scope="col" class="columns-name text-left">Nombre</th>
                        <th scope="col" class="columns-username text-left">Usuario</th>
                        <th scope="col" class="columns-email text-left">Correo</th>
                        <th scope="col" class="columns-phone text-left">Teléfono</th>
                        <th scope="col" class="columns-position text-center">Cargo</th>
                        <th scope="col" class="columns-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="user-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-profile-tab">
        <div id="create-inputs-container" class="row">
            <div class="normal-inputs-container col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Nombre</label>
                        <input type="text" id="create-user-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Apellido</label>
                        <input type="text" id="create-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre de usuario">
                        <label for="clientname" class="input-title align-self-center">Usuario</label>
                        <input type="text" id="create-user-username" class="input-value form-control align-self-center" name="username" placeholder="Usuario">
                    </div>
                </div>
            </div>
            <div class="normal-inputs-container col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo electrónico del usuario">
                        <label for="clientname" class="input-title align-self-center">Correo</label>
                        <input type="email" id="create-user-email" class="input-value form-control align-self-center" name="email" placeholder="Correo">
                    </div>
                    <div class="input-container col-12 d-flex" title="Teléfono del usuario">
                        <label for="clientname" class="input-title align-self-center">Teléfono</label>
                        <input type="tel" id="create-user-phone" class="input-value form-control align-self-center" name="phone" placeholder="Teléfono">
                    </div>
                    <div class="input-container col-12 d-flex" title="Cargo del usuario">
                        <label for="clientname" class="input-title align-self-center">Cargo</label>
                        <input type="text" id="create-user-position" class="input-value form-control align-self-center" name="position" placeholder="Cargo">
                    </div>
                </div>
            </div>
            <div class="multimedia-super-container col-12 col-md-2 d-flex flex-column justify-content-center">
                <div class="multimedia-input-container align-self-center">
                    <div id="create-user-color-container" class="color-container">
                        <input type="color" name="color" id="create-user-color" class="input-color">
                        <i class="fa-solid fa-palette align-self-center color-icon"></i>
                    </div>
                    <i class="fa-solid fa-plus image-plus-icon"></i>
                </div>
            </div>
        </div>
        <div id="permissions-container">
            <h3 id="permissions-title">Permisos</h2>
            <div class="row permissions-list">
            </div>
        </div>
        <button class="btn btn-secondary" id="create-user-button">Guardar</button>
    </div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row">
            <div class="normal-inputs-container col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Nombre</label>
                        <input type="text" id="update-user-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Apellido</label>
                        <input type="text" id="update-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre de usuario">
                        <label for="clientname" class="input-title align-self-center">Usuario</label>
                        <input type="text" id="update-user-username" class="input-value form-control align-self-center" name="username" placeholder="Usuario">
                    </div>
                </div>
            </div>
            <div class="normal-inputs-container col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo electrónico del usuario">
                        <label for="clientname" class="input-title align-self-center">Correo</label>
                        <input type="email" id="update-user-email" class="input-value form-control align-self-center" name="email" placeholder="Correo">
                    </div>
                    <div class="input-container col-12 d-flex" title="Teléfono del usuario">
                        <label for="clientname" class="input-title align-self-center">Teléfono</label>
                        <input type="tel" id="update-user-phone" class="input-value form-control align-self-center" name="phone" placeholder="Teléfono">
                    </div>
                    <div class="input-container col-12 d-flex" title="Cargo del usuario">
                        <label for="clientname" class="input-title align-self-center">Cargo</label>
                        <input type="text" id="update-user-position" class="input-value form-control align-self-center" name="position" placeholder="Cargo">
                    </div>
                </div>
            </div>
            <div class="multimedia-super-container col-12 col-md-2 d-flex flex-column justify-content-center">
                <div class="multimedia-input-container align-self-center">
                    <div id="update-user-color-container" class="color-container">
                        <input type="color" name="color" id="update-user-color" class="input-color">
                        <i class="fa-solid fa-palette align-self-center color-icon"></i>
                    </div>
                    <i class="fa-solid fa-plus image-plus-icon"></i>
                </div>
            </div>
        </div>
        <div id="permissions-container">
            <h3 id="permissions-title">Permisos</h2>
            <div class="row permissions-list">
            </div>
        </div>
        <button class="btn btn-secondary" id="update-user-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
</div>
@endsection
