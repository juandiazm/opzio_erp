@extends('erp.layouts.app')
@section('component_title', 'MI PERFIL')
@section('erp-app-header')
<script>
    var current_user = {!! json_encode(session('user')) !!};
    var permissions = {!! json_encode(session('permissions')) !!};
</script>
@vite('resources/js/erp/my_profile/my_profile.js')
<!-- Styles -->
@vite('resources/sass/erp/my_profile/my_profile.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab Update -->
    <div class="tab-pane fade show active" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 p-0 w-100">
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID del usuario">
                        <label for="username" class="input-title align-self-center">ID Admin</label>
                        <input type="hidden" autofocus id="update-user-id-input" class="input-value form-control align-self-center" name="id" placeholder="id">
                        <p id="update-user-id" class="input-value align-self-center"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="username" class="input-title align-self-center">Identificación</label>
                        <input type="number" autofocus id="update-user-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                        <label for="username" class="input-title align-self-center">Nombre/s</label>
                        <input type="text" id="update-user-name" class="input-value form-control align-self-center" name="name" placeholder="Pepito">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="username" class="input-title align-self-center">Apellido/s</label>
                        <input type="text" id="update-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Perez">
                    </div>
                    <div class="input-container col-12 d-flex" title="Nickname del usuario">
                        <label for="username" class="input-title align-self-center">Usuario</label>
                        <input type="text" id="update-user-username" class="input-value form-control align-self-center" name="username" placeholder="pperez">
                    </div>
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="username" class="input-title align-self-center">Correo</label>
                        <input type="text" id="update-user-email" class="input-value form-control align-self-center" name="email" placeholder="pperez@opzio.co">
                    </div>
                </div>
                
            </div>
            <div class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-block">
                    <div class="d-flex justify-content-center">
                        <div class="multimedia-input-container">
                            <div id="update-user-img-container" class="image-container d-flex justify-content-center">
                                <input type="file" name="photo" id="update-user-img" class="d-none input_image" accept="image/*">
                                <img class="image_preview align-self-center" alt="Foto de perfil">
                                <i class="fa-regular fa-image align-self-center image-icon"></i>
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                        <div class="multimedia-input-container">
                            <div id="update-user-color-container" class="color-container d-flex justify-content-center">
                                <input type="color" name="color" id="update-user-color" class="input-color">
                                <i class="fa-solid fa-palette align-self-center color-icon"></i>
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="permissions-container">
            <h3 id="permissions-title">Permisos</h2>
            <div class="row permissions-list">
            </div>
        </div>
        <button class="btn btn-secondary" id="update-button">Actualizar</button>
    </div>
</div>
@endsection
