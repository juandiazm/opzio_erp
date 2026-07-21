@extends('erp.layouts.app')
@section('component_title', 'USUARIOS')
@section('erp-app-header')
@vite('resources/js/erp/users/users.js')
@vite('resources/js/erp/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/users/users.scss')
@vite('resources/sass/erp/traceability.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear</button>
        <button class="nav-link" id="nav-traceability-tab" data-bs-toggle="tab" data-bs-target="#nav-traceability" type="button" role="tab" aria-controls="nav-traceability" aria-selected="false">Trazabilidad</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="user-list-container" class="scrollable">
            <div id="search-list-container" class="justify-content-center">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
            </div>
            <table id="user-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="user-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-photo text-center">Foto</th>
                        <th scope="col" class="columns-name text-left">Nombre</th>
                        <th scope="col" class="columns-username text-center">Usuario</th>
                        <th scope="col" class="columns-identification text-center">Identificación</th>
                        <th scope="col" class="columns-email text-left">Correo</th>
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
        <div id="create-inputs-container" class="row m-0 p-0 w-100">
            <div class="inputs-cols col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID del usuario">
                        <label for="username" class="input-title align-self-center">ID Admin</label>
                        <p id="create-user-id" class="input-value align-self-center"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="username" class="input-title align-self-center">Identificación</label>
                        <input type="number" autofocus id="create-user-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                        <label for="username" class="input-title align-self-center">Nombre/s</label>
                        <input type="text" id="create-user-name" class="input-value form-control align-self-center" name="name" placeholder="Pepito">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="username" class="input-title align-self-center">Apellido/s</label>
                        <input type="text" id="create-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Perez">
                    </div>
                </div>
                
            </div>
            <div class="inputs-cols col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Nickname del usuario">
                        <label for="username" class="input-title align-self-center">Usuario</label>
                        <input type="text" id="create-user-username" class="input-value form-control align-self-center" name="username" placeholder="pperez">
                    </div>
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="username" class="input-title align-self-center">Correo</label>
                        <input type="text" id="create-user-email" class="input-value form-control align-self-center" name="email" placeholder="pperez@opzio.co">
                    </div>
                    <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                        <label for="username" class="input-title align-self-center">Contraseña</label>
                        <input type="password" id="create-user-password" title="Doble click para visualizar/ocultar la contrasña" class="input-password input-value form-control align-self-center" name="password" placeholder="********">
                    </div>
                    <div class="input-container col-12 d-flex" class="Confirma la contraseña del usuario">
                        <label for="username" class="input-title align-self-center">V - Contraseña</label>
                        <input type="password" id="create-user-password-confirmation" title="Doble click para visualizar/ocultar la contrasña" class="input-password input-value form-control align-self-center" name="confirm_password" placeholder="********">
                    </div>
                </div>
                
            </div>
            <div id="multimedia-container" class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-block">
                    <div class="d-flex justify-content-center">
                        <div class="multimedia-input-container">
                            <div id="create-user-img-container" class="image-container d-flex justify-content-center">
                                <input type="file" name="photo" id="create-user-img" class="d-none input_image" accept="image/*">
                                <i class="fa-regular fa-image align-self-center image-icon"></i>
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                        <div class="multimedia-input-container">
                            <div id="create-user-color-container" class="color-container d-flex justify-content-center">
                                <input type="color" name="color" id="create-user-color" class="input-color">
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
        <button class="btn btn-secondary" id="add-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
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
                <div class="d-block" id="user-sub-opt-container">
                    <div class="d-flex justify-content-center">
                        <div class="align-self-center" id="update-user-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                        <div class="align-self-center" id="update-user-delete"><i class="fa-solid fa-trash-can"></i></div>
                        <div class="align-self-center" id="update-user-restore"><i class="fa-solid fa-lightbulb"></i></div>
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
