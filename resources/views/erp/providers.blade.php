@extends('erp.layouts.app')
@section('component_title', 'PROVEEDORES')
@section('erp-app-header')
<script src="{{ asset('js/erp/providers/providers.js') }}" defer></script>
<script src="{{ asset('js/erp/traceability.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/providers/providers.css') }}" rel="stylesheet">
<link href="{{ asset('css/erp/traceability.css') }}" rel="stylesheet">
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
        <div id="provider-list-container" class="scrollable">
            <div id="search-list-container" class="justify-content-center">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
            </div>
            <table id="provider-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="provider-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-photo text-center">Proveedor</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-identification text-left">Identificación</th>
                        <th scope="col" class="columns-name text-left">Nombre</th>
                        <th scope="col" class="columns-phone text-center">Teléfono</th>
                        <th scope="col" class="columns-email text-left email-col">Correo</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="provider-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
        <div id="create-inputs-container" class="row m-0 p-0 w-100">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row justify-content-center">
                    <div class="col-3 col-md-4">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="create-provider-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="create-provider-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-9 col-md-5 align-self-center">
                        <div class="row">
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="providername" class="input-title align-self-center">Estado</label>
                                <div class="toggle-container row" value="1" id="create-provider-state">
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
                        <label for="providername" class="input-title align-self-center">Proveedor</label>
                        <input type="text" autofocus id="create-provider-name" class="input-value form-control align-self-center" name="name" placeholder="Empresa / Nombre y apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="providername" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="create-provider-id-type" name="identification_type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="providername" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="create-provider-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="countries" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="create-provider-country">
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
                
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="providername" class="input-title align-self-center">Dirección</label>
                        <input type="text" id="create-provider-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52">
                    </div>
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="providername" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="create-provider-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                        <label for="providername" class="input-title align-self-center">Correo</label>
                        <input type="email" id="create-provider-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="providername" class="input-title align-self-center">Sector</label>
                        <div class="crud-input-container input-value" prefix="/admin/sector/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="create-provider-sector">
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
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="input-container col-12">
                    <label for="providername" class="input-title align-self-center d-block">Descripción</label>
                    <textarea id="create-provider-description" name="description" rows="5" placeholder="Breve descripción de la empresa o providere."></textarea>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="add-provider-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="/providers/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 p-0 w-100">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row d-flex justify-content-center">
                    <div class="col-3 col-md-4 align-self-center">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="update-provider-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="update-provider-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-9 col-md-5 align-self-center">
                        <div class="row">
                            <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                                <label for="providername" class="input-title align-self-center">ID Proveedor</label>
                                <p id="update-provider-unique-id" class="m-0 p-0 align-self-center"></p>
                            </div>
                            <div class="input-container col-12 d-flex" title="ID del usuario">
                                <label for="providername" class="input-title align-self-center">Estado</label>
                                <div class="toggle-container row" value="1" id="update-provider-state">
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
                    <div class="col-3 align-self-center">
                        <div class="d-block" id="provider-sub-opt-container">
                            <div class="d-flex justify-content-center">
                                <div class="align-self-center" id="update-provider-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                                <div class="align-self-center" id="update-provider-delete"><i class="fa-solid fa-trash-can"></i></div>
                                <div class="align-self-center" id="update-provider-restore"><i class="fa-solid fa-lightbulb"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID del usuario">
                        <label for="providername" class="input-title align-self-center">Proveedor</label>
                        <input type="text" autofocus id="update-provider-name" class="input-value form-control align-self-center" name="name" placeholder="Empresa / Nombre y apellido">
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación del usuario">
                        <label for="providername" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="update-provider-id-type" name="identification_type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                        <label for="providername" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="update-provider-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="countries" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="update-provider-country">
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
                
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="providername" class="input-title align-self-center">Dirección</label>
                        <input type="text" id="update-provider-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52">
                    </div>
                    <div class="input-container col-12 d-flex" title="Correo del usuario">
                        <label for="providername" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="update-provider-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                        <label for="providername" class="input-title align-self-center">Correo</label>
                        <input type="email" id="update-provider-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="providername" class="input-title align-self-center">Sector</label>
                        <div class="crud-input-container input-value" prefix="/admin/sector/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="update-provider-sector">
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
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="input-container col-12">
                    <label for="providername" class="input-title align-self-center d-block">Descripción</label>
                    <textarea id="update-provider-description" name="description" rows="5" placeholder="Breve descripción de la empresa o providere."></textarea>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-provider-button">Actualizar</button>
        <nav>
            <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
                <button class="nav-link" id="sub-nav-payments-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-payments" type="button" role="tab" aria-controls="sub-nav-payments" aria-selected="true">Pagos</button>
                <button class="nav-link active" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="true">Documentos</button>
                <button class="nav-link" id="sub-nav-contacts-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-contacts" type="button" role="tab" aria-controls="sub-nav-contacts" aria-selected="true">Contactos</button>
            </div>
        </nav>
        <div class="tab-content" id="sub-nav-tabContent">
            <div class="tab-pane fade" id="sub-nav-payments" role="tabpanel" aria-labelledby="sub-nav-payments-tab">
                Pagos...
            </div>
            <div class="tab-pane fade show active" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-documents-tab">
                <div id="provider-documents-add-container" class="row">
                    <div class="col-1 input-container d-flex">
                        <p class="provider-document-input-title align-self-end">Nombre</p>
                    </div>
                    <div class="col-3 input-container d-flex">
                        <input type="text" name="" class="provider-document-input-name align-self-end input-value form-control" placeholder="Contrato confidencialidad">
                    </div>
                    <div class="col-6">
                        <input type="file" class="provider-document-input-file form-control" name="file" placeholder="Archivo..." aria-label="Archivo" aria-describedby="basic-addon1" accept=".pdf,.docx,.xlsx,.pptx">
                    </div>
                    <button class="col-2 btn btn-secondary" id="add-provider-documens-button">Agregar</button>
                </div>
                <table id="provider-documents-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-left">Archivo</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="provider-documents-table-body">
                        
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="sub-nav-contacts" role="tabpanel" aria-labelledby="sub-nav-contacts-tab">
                <table class="table sub-table table-strech" id="contacts-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">Nombre</th>
                            <th scope="col" class="text-center">Correo</th>
                            <th scope="col" class="text-center">Teléfono</th>
                            <th scope="col" class="text-center">Cargo</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="add-contact-row" class="table-row-add">
                            <td class="text-center">
                                <input type="text" class="form-control text-center align-self-center contact-name" placeholder="Pepito Perez Aristizabal">
                            </td>
                            <td class="text-center">
                                <input type="email" class="form-control text-center align-self-center contact-email" placeholder="ppari@gmail.com">
                            </td>
                            <td class="text-center">
                                <input type="number" class="form-control text-center align-self-center contact-phone" placeholder="573192536985">
                            </td>
                            <td class="text-center">
                                <input type="text" class="form-control text-center align-self-center contact-position" placeholder="Gerente General">
                            </td>
                            <td class="text-center">
                                <i class="fa-solid fa-plus" id="add-contact"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
