@extends('erp.layouts.app')
@section('component_title', 'CLIENTES')
@section('erp-app-header')
@vite('resources/js/erp/clients/clients.js')
@vite('resources/js/erp/clients/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/clients/clients.scss')
@vite('resources/sass/erp/clients/traceability.scss')
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
        <div id="client-list-container" class="scrollable">
            <div id="search-list-container" class="justify-content-center">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
            </div>
            <table id="client-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="client-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-logo text-center">Logo</th>
                        <th scope="col" class="columns-identification text-left">Identificación</th>
                        <th scope="col" class="columns-client text-left">Cliente</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-phone text-center">Teléfono</th>
                        <th scope="col" class="columns-email text-left email-col">Correo</th>
                        <th scope="col" class="columns-license text-center">Licencias</th>
                        <th scope="col" class="columns-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="client-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>

        <!-- Botón flotante de sincronización -->
        <button id="sync-siigo-btn" class="btn btn-primary rounded-circle position-fixed" style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1000;">
            <i class="fas fa-sync"></i>
        </button>

        <!-- Modal de resultados de sincronización -->
        <div class="modal fade" id="syncResultModal" tabindex="-1" aria-labelledby="syncResultModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content sync-result-modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="syncResultModalLabel">Resultado de Sincronización</h5>
                        <button type="button" class="btn-close" id="syncResultCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="syncResultMessage" role="status" aria-live="polite">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="syncResultCloseFooterBtn" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
        <div id="create-inputs-container" class="row m-0 p-0 w-100">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row justify-content-center">
                    <div class="col-3 col-md-4">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="create-client-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="create-client-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-8 col-md-5 align-self-center">
                        <div class="row">
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="clientname" class="input-title align-self-center">Verificación</label>
                                <div class="input-value align-self-center" id="create-client-verification" value="1">
                                    <i class="verification-input-icon fa-solid fa-medal enabled" value="1"></i>
                                    <i class="verification-input-icon fa-solid fa-ban disabled" value="0"></i>
                                </div>
                            </div>
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="clientname" class="input-title align-self-center">Estado</label>
                                <div class="toggle-container row" value="1" id="create-client-state">
                                    <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                        <p>Activo</p>
                                    </div>
                                    <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                        <p>Inactivo</p>
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="input-container col-12 d-flex" title="Factura electrónica">
                                <label for="client-electronic-invoice" class="input-title align-self-center">Factura E.</label>
                                <div class="toggle-container row" value="0" id="create-client-electronic-invoice">
                                    <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                        <p>Activo</p>
                                    </div>
                                    <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                        <p>Inactivo</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID del usuario">
                        <label for="clientname" class="input-title align-self-center">Cliente</label>
                        <input type="text" autofocus id="create-client-name" class="input-value form-control align-self-center" name="name" placeholder="Empresa / Nombre y apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="clientname" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="create-client-id-type" name="identification_type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="create-client-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="countries" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="create-client-country">
                                <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colombia">
                                <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                            </div>
                            <ul class="crud-list closed scrollable">
                                <li class="crud-item-add d-flex justify-content-between">
                                    <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                    <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="clientname" class="input-title align-self-center">Dirección</label>
                    <input type="text" id="create-client-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="clientname" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="create-client-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                        <label for="clientname" class="input-title align-self-center">Correo</label>
                        <input type="email" id="create-client-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="clientname" class="input-title align-self-center">Sector</label>
                        <div class="crud-input-container input-value" prefix="/admin/sector/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="create-client-sector">
                                <input type="text" class="crud-current-selected-input align-self-center" placeholder="Servicios Financieros">
                                <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                            </div>
                            <ul class="crud-list closed scrollable">
                                <li class="crud-item-add d-flex justify-content-between">
                                    <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                    <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="input-container col-12 d-flex" title="Valor por hora">
                        <label for="clientname" class="input-title align-self-center">Valor por hora</label>
                        <input type="number" id="create-client-value-per-hour" class="input-value form-control align-self-center" name="value_per_hour" placeholder="$ 0.00">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="input-container col-12">
                    <label for="clientname" class="input-title align-self-center d-block">Descripción</label>
                    <textarea id="create-client-description" name="description" rows="5" placeholder="Breve descripción de la empresa o cliente."></textarea>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="add-client-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 p-0 w-100">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row justify-content-center">
                    <div class="col-3 col-md-4 align-self-center">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="update-client-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="update-client-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-9 col-md-5 align-self-center">
                        <div class="row">
                            <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                                <label for="clientname" class="input-title align-self-center">ID Cliente</label>
                                <p id="update-client-unique-id" class="m-0 p-0 align-self-center"></p>
                            </div>
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="clientname" class="input-title align-self-center">Verificación</label>
                                <div class="input-value align-self-center" id="update-client-verification" value="1">
                                    <i class="verification-input-icon fa-solid fa-medal enabled" value="1"></i>
                                    <i class="verification-input-icon fa-solid fa-ban disabled" value="0"></i>
                                </div>
                            </div>
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="clientname" class="input-title align-self-center">Estado</label>
                                <div class="toggle-container row" value="1" id="update-client-state">
                                    <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                        <p>Activo</p>
                                    </div>
                                    <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                        <p>Inactivo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="input-container col-12 d-flex" title="Factura electrónica">
                                <label for="client-electronic-invoice" class="input-title align-self-center">Factura E.</label>
                                <div class="toggle-container row" value="0" id="update-client-electronic-invoice">
                                    <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                        <p>Activo</p>
                                    </div>
                                    <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                        <p>Inactivo</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID del usuario">
                        <label for="clientname" class="input-title align-self-center">Cliente</label>
                        <input type="text" autofocus id="update-client-name" class="input-value form-control align-self-center" name="name" placeholder="Empresa / Nombre y apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="clientname" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="update-client-id-type" name="identification_type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="clientname" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="update-client-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="countries" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-country">
                                <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colombia">
                                <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                            </div>
                            <ul class="crud-list closed scrollable">
                                <li class="crud-item-add d-flex justify-content-between">
                                    <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                    <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="clientname" class="input-title align-self-center">Dirección</label>
                    <input type="text" id="update-client-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="clientname" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="update-client-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                        <label for="clientname" class="input-title align-self-center">Correo</label>
                        <input type="email" id="update-client-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="clientname" class="input-title align-self-center">Sector</label>
                        <div class="crud-input-container input-value" prefix="/admin/sector/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-sector">
                                <input type="text" class="crud-current-selected-input align-self-center" placeholder="Servicios Financieros">
                                <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                            </div>
                            <ul class="crud-list closed scrollable">
                                <li class="crud-item-add d-flex justify-content-between">
                                    <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                    <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="input-container col-12 d-flex" title="Valor por hora">
                        <label for="clientname" class="input-title align-self-center">Valor por hora</label>
                        <input type="number" id="update-client-value-per-hour" class="input-value form-control align-self-center" name="value_per_hour" placeholder="$ 0.00">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="input-container col-12">
                    <label for="clientname" class="input-title align-self-center d-block">Descripción</label>
                    <textarea id="update-client-description" name="description" rows="5" placeholder="Breve descripción de la empresa o cliente."></textarea>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-client-button">Actualizar</button>
        <nav>
            <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
                <button class="nav-link active" id="sub-nav-users-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-users" type="button" role="tab" aria-controls="sub-nav-users" aria-selected="true">Usuarios</button>
                <button class="nav-link" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="true">Documentos</button>
                <button class="nav-link" id="sub-nav-licenses-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-licenses" type="button" role="tab" aria-controls="sub-nav-licenses" aria-selected="true">Licencias</button>
            </div>
        </nav>
        <div class="tab-content" id="sub-nav-tabContent">
            <div class="tab-pane fade show active" id="sub-nav-users" role="tabpanel" aria-labelledby="sub-nav-users-tab">
                <div id="create-client-inputs-container" class="row w-100">
                    <div class="col-12 col-md-4">
                        <div class="row w-100 p-0 m-0">
                            <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                                <label for="username" class="input-title align-self-center">Nombre/s</label>
                                <input type="text" id="create-client-user-name" class="input-value form-control align-self-center" name="name" placeholder="Pepito">
                            </div>
                            <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                                <label for="username" class="input-title align-self-center">Apellido/s</label>
                                <input type="text" id="create-client-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Perez">
                            </div>
                            <div class="input-container col-12 d-flex" title="Nickname del usuario">
                                <label for="username" class="input-title align-self-center">Usuario</label>
                                <input type="text" id="create-client-user-username" class="input-value form-control align-self-center" name="username" placeholder="pperez">
                            </div>
                            
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="row w-100 p-0 m-0">
                            <div class="input-container col-12 d-flex" title="Correo del usuario">
                                <label for="username" class="input-title align-self-center">Correo</label>
                                <input type="text" id="create-client-user-email" class="input-value form-control align-self-center" name="email" placeholder="pperez@opzio.co">
                            </div>
                            <div class="input-container col-12 d-flex" title="Teléfono del usuario">
                                <label for="username" class="input-title align-self-center">Teléfono</label>
                                <input type="text" id="create-client-user-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002536526">
                            </div>
                            <div class="input-container col-12 d-flex" title="Cargo del usuario">
                                <label for="username" class="input-title align-self-center">Cargo</label>
                                <input type="text" id="create-client-user-position" class="input-value form-control align-self-center" name="position" placeholder="Ejecutivo de cuenta">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 align-self-center">
                        <div class="d-block">
                            <div class="d-flex justify-content-center">
                                <div class="multimedia-input-container">
                                    <div id="create-client-user-color-container" class="color-container d-flex justify-content-center">
                                        <input type="color" name="color" id="create-client-user-color" class="input-color">
                                        <i class="fa-solid fa-palette align-self-center color-icon"></i>
                                    </div>
                                    <i class="fa-solid fa-plus image-plus-icon"></i>
                                </div>
                                <button class="btn btn-secondary align-self-center" id="add-client-user-button">Agregar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <table id="client-users-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="user-column-id text-left">ID</th>
                            <th scope="col" class="user-column-color text-center">Color</th>
                            <th scope="col" class="user-column-name text-left">Nombre</th>
                            <th scope="col" class="user-column-username text-left">Usuario</th>
                            <th scope="col" class="user-column-email text-left">Correo</th>
                            <th scope="col" class="user-column-phone text-left">Teléfono</th>
                            <th scope="col" class="user-column-position text-center">Cargo</th>
                            <th scope="col" class="user-column-actions text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="client-users-table-body">
                        
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-documents-tab">
                <div id="client-documents-add-container" class="row">
                    <div class="col-8 m-auto">
                        <input 
                        type="file" 
                        class="client-document-input-file form-control" 
                        name="file" 
                        accept=".pdf,.docx,.xlsx,.pptx" 
                        multiple>
                    </div>
                    <button class="col-2 btn btn-secondary" id="add-client-documens-button">
                        Agregar
                    </button>
                </div>
                <table id="client-documents-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-left">Archivo</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="client-documents-table-body">
                        
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="sub-nav-licenses" role="tabpanel" aria-labelledby="sub-nav-licenses-tab">
                <table id="client-licenses-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Servicio</th>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="client-licenses-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
