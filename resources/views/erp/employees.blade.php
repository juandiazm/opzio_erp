@extends('erp.layouts.app')
@section('component_title', 'EMPLEADOS')
@section('erp-app-header')
@vite('resources/js/erp/employees/employees.js')
@vite('resources/js/erp/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/traceability.scss')
@vite('resources/sass/erp/employees/employees.scss')
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
        <div id="employee-list-container" class="scrollable">
            <div id="search-list-container" class="justify-content-center">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
            </div>
            <table id="employee-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="employee-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-photo text-center">Foto</th>
                        <th scope="col" class="columns-identification text-start">Identificación</th>
                        <th scope="col" class="columns-name text-start">Nombre</th>
                        <th scope="col" class="columns-department text-start">Departamento</th>
                        <th scope="col" class="columns-position text-start">Cargo</th>
                        <th scope="col" class="columns-email text-start">Correo E.</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="employee-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
        <div id="create-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="create-employee-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="create-employee-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID">
                        <label for="employeename" class="input-title align-self-center">ID Empleado</label>
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="create-employee-name" class="input-title align-self-center">Nombre/s</label>
                        <input type="text" autofocus id="create-employee-name" class="input-value form-control align-self-center" name="name" placeholder="Juan">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellidos">
                        <label for="employee-last-name" class="input-title align-self-center">Apellido/s</label>
                        <input type="text" autofocus id="create-employee-last-name" class="input-value form-control align-self-center" name="last-name" placeholder="Posada">
                    </div>
                    <div class="input-container col-12 d-flex" title="Tipo de ID">
                        <label for="create-employee-id-type" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="create-employee-id-type" name="id-type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación">
                        <label for="create-employee-identification" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="create-employee-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                </div>
                
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Estado">
                        <label for="create-employee-state" class="input-title align-self-center">Estado</label>
                        <div class="toggle-container row" value="1" id="create-employee-state">
                            <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                <p>Activo</p>
                            </div>
                            <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                <p>Inactivo</p>
                            </div>
                        </div>
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="create-employee-country" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="create-employee-country">
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
                    <div class="input-container col-12 d-flex" title="Contraseña">
                        <label for="create-employee-phone" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="create-employee-phone" class="input-phone input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="employeename" class="input-title align-self-center">Correo P.</label>
                        <input type="email" id="create-employee-personal-email" class="input-personal-email input-value form-control align-self-center" name="personal-email" placeholder="juanp@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="employeename" class="input-title align-self-center">Correo E.</label>
                        <input type="email" id="create-employee-work-email" class="input-work-email input-value form-control align-self-center" name="work-email" placeholder="juanp@opzio.co">
                    </div>
                </div>
            </div>
            <!--<div class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-center" id="balance-button">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p class="align-self-center">Balance</p>
                </div>
                <div class="d-block" id="employee-sub-opt-container">
                    <div class="d-flex justify-content-between">
                        <div class="align-self-center" id="update-employee-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                        <div class="align-self-center" id="update-employee-delete"><i class="fa-solid fa-ban"></i></div>
                        <div class="align-self-center d-none" id="update-employee-restore"><i class="fa-solid fa-lightbulb"></i></div>
                    </div>
                </div>
            </div>-->
        </div>
        <button class="btn btn-secondary" id="add-employee-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="/employees/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 w-100 justify-content-center">
            <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <div class="multimedia-input-container">
                                <div id="update-employee-img-container" class="image-container d-flex justify-content-center">
                                    <input type="file" name="photo" id="update-employee-img" class="d-none input_image" accept="image/*">
                                    <i class="fa-regular fa-image align-self-center image-icon"></i>
                                </div>
                                <i class="fa-solid fa-plus image-plus-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID">
                        <label for="employeename" class="input-title align-self-center">ID Empleado</label>
                        <p class="input-value align-self-center" id="update-employee-uid"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="update-employee-name" class="input-title align-self-center">Nombre/s</label>
                        <input type="text" autofocus id="update-employee-name" class="input-value form-control align-self-center" name="name" placeholder="Juan">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellidos">
                        <label for="employee-last-name" class="input-title align-self-center">Apellido/s</label>
                        <input type="text" autofocus id="update-employee-last-name" class="input-value form-control align-self-center" name="last-name" placeholder="Posada">
                    </div>
                    <div class="input-container col-12 d-flex" title="Tipo de ID">
                        <label for="update-employee-id-type" class="input-title align-self-center">Tipo ID</label>
                        <select class="form-select input-value align-self-center" id="update-employee-id-type" name="id-type">
                            <option value="0" selected>Nit</option>
                            <option value="1">Cédula</option>
                            <option value="2">Pasaporte</option>
                            <option value="3">Cédula extranjera</option>
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Identificación">
                        <label for="update-employee-identification" class="input-title align-self-center">Identificación</label>
                        <input type="text" id="update-employee-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                    </div>
                </div>
                
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Estado">
                        <label for="update-employee-state" class="input-title align-self-center">Estado</label>
                        <div class="toggle-container row" value="1" id="update-employee-state">
                            <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                <p>Activo</p>
                            </div>
                            <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                <p>Inactivo</p>
                            </div>
                        </div>
                    </div>
                    <div class="input-container col-12 d-flex" title="País">
                        <label for="update-employee-country" class="input-title align-self-center">País</label>
                        <div class="crud-input-container input-value" prefix="/admin/country/">
                            <div class="crud-input-selected-container d-flex justify-content-between" id="update-employee-country">
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
                    <div class="input-container col-12 d-flex" title="Contraseña">
                        <label for="update-employee-phone" class="input-title align-self-center">Teléfono</label>
                        <input type="number" id="update-employee-phone" class="input-phone input-value form-control align-self-center" name="phone" placeholder="3002583697">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="employeename" class="input-title align-self-center">Correo P.</label>
                        <input type="email" id="update-employee-personal-email" class="input-personal-email input-value form-control align-self-center" name="personal-email" placeholder="juanp@gmail.com">
                    </div>
                    <div class="input-container col-12 d-flex">
                        <label for="employeename" class="input-title align-self-center">Correo E.</label>
                        <input type="email" id="update-employee-work-email" class="input-work-email input-value form-control align-self-center" name="work-email" placeholder="juanp@opzio.co">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-center" id="balance-button">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p class="align-self-center">Balance</p>
                </div>
                <div class="d-block" id="employee-sub-opt-container">
                    <div class="d-flex justify-content-between">
                        <div class="align-self-center" id="update-employee-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                        <div class="align-self-center" id="update-employee-delete"><i class="fa-solid fa-ban"></i></div>
                        <div class="align-self-center d-none" id="update-employee-restore"><i class="fa-solid fa-lightbulb"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-employee-button">Actualizar</button>
        <nav>
            <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
                <button class="nav-link active" id="sub-nav-hiring-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-hiring" type="button" role="tab" aria-controls="sub-nav-hiring" aria-selected="true">Contratación</button>
                <button class="nav-link" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="true">Documentos</button>
                <button class="nav-link" id="sub-nav-licenses-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-licenses" type="button" role="tab" aria-controls="sub-nav-licenses" aria-selected="true">Licencias</button>
            </div>
        </nav>
        <div class="tab-content" id="sub-nav-tabContent">
            <div class="tab-pane fade show active" id="sub-nav-hiring" role="tabpanel" aria-labelledby="sub-nav-hiring-tab">
                <div id="create-employee-inputs-container" class="row w-100">
                    <div class="col-12 col-md-4">
                        <div class="row w-100 p-0 m-0">
                            <div class="input-container col-12 d-flex" title="Fecha de ingreso">
                                <label for="employeename" class="input-title align-self-center">Ingreso</label>
                                <input type="date" id="hiring-employee-entry-date" class="input-value form-control align-self-center" name="entry_date" placeholder="Juan">
                            </div>
                            <div class="input-container col-12 d-flex" title="Tipo de pago">
                                <label for="employeename" class="input-title align-self-center">Tipo de pago</label>
                                <select class="form-select input-value align-self-center" id="hiring-employee-payment-type" name="payment-type">
                                    <option value="0" selected>Quincenal</option>
                                    <option value="1">Mensual</option>
                                    <option value="2">Semanal</option>
                                    <option value="3">Diario</option>
                                </select>
                            </div>
                            <div class="input-container col-12 d-flex" title="Banco">
                                <label for="employeename" class="input-title align-self-center">Banco</label>
                                <input type="text" id="hiring-employee-bank" class="input-value form-control align-self-center" name="bank" placeholder="Bancolombia">
                            </div>
                            <div class="input-container col-12 d-flex" title="Cuenta">
                                <label for="employeename" class="input-title align-self-center">Cuenta</label>
                                <input type="text" id="hiring-employee-account-number" class="input-value form-control align-self-center" name="account-number" placeholder="1234567890">
                            </div>
                            <div class="input-container col-12 d-flex" title="Tipo de cuenta">
                                <label for="employeename" class="input-title align-self-center">Tipo de cuenta</label>
                                <select class="form-select input-value align-self-center" id="hiring-employee-account-type" name="account-type">
                                    <option value="0" selected>Ahorros</option>
                                    <option value="1">Corriente</option>
                                </select>
                            </div>
                            
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        
                        <div class="input-container col-12 d-flex" title="Salario">
                            <label for="employeename" class="input-title align-self-center">Salario</label>
                            <input type="number" id="hiring-employee-salary" class="input-value form-control align-self-center" name="salary" placeholder="1234567890">
                        </div>
                        
                        
                        <div class="input-container col-12 d-flex" title="Contrato">
                            <label for="employeename" class="input-title align-self-center">Contrato</label>
                            <input type="text" id="hiring-employee-contract" class="input-value form-control align-self-center" name="contract" placeholder="1234567890">
                        </div>
                        <div class="input-container col-12 d-flex" title="Departamento">
                            <label for="employeename" class="input-title align-self-center">Departamento</label>
                            <select class="form-select input-value align-self-center" id="hiring-employee-department" name="department">
                                
                            </select>	
                        </div>
                        <div class="input-container col-12 d-flex" title="Cargo">
                            <label for="employeename" class="input-title align-self-center">Cargo</label>
                            <input type="text" id="hiring-employee-charge" class="input-value form-control align-self-center" name="charge" placeholder="Ejecutivo de cuenta">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        
                        <div class="input-container col-12 d-flex" title="EPS">
                            <label for="employeename" class="input-title align-self-center">EPS</label>
                            <div class="crud-input-container input-value" prefix="/admin/eps/">
                                <div class="crud-input-selected-container d-flex justify-content-between" id="hiring-employee-eps">
                                    <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colsubsidio">
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
                        <div class="input-container col-12 d-flex" title="AFP">
                            <label for="employeename" class="input-title align-self-center">AFP</label>
                            <div class="crud-input-container input-value" prefix="/admin/afp/">
                                <div class="crud-input-selected-container d-flex justify-content-between" id="hiring-employee-afp">
                                    <input type="text" class="crud-current-selected-input align-self-center" placeholder="Compensar">
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
                        <div class="input-container col-12 d-flex" title="ARL">
                            <label for="employeename" class="input-title align-self-center">ARL</label>
                            <div class="crud-input-container input-value" prefix="/admin/arl/">
                                <div class="crud-input-selected-container d-flex justify-content-between" id="hiring-employee-arl">
                                    <input type="text" class="crud-current-selected-input align-self-center" placeholder="Coosalud">
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
                        <div class="input-container col-12 d-flex" title="Salida">
                            <label for="employeename" class="input-title align-self-center">Salida</label>
                            <input type="date" id="hiring-employee-retirement-date" class="input-value form-control align-self-center" name="retirement_date" placeholder="Juan">
                        </div>
                    </div>
                    <button class="btn btn-secondary align-self-center" id="update-employee-hiring-button"><i class="fa-solid fa-file-invoice"></i></button>
                </div>
            </div>
            <div class="tab-pane fade" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-documents-tab">
                <div id="employee-documents-add-container" class="row">
                    <div class="col-1 input-container d-flex">
                        <p class="employee-document-input-title align-self-end">Nombre</p>
                    </div>
                    <div class="col-3 input-container d-flex">
                        <input type="text" name="" class="employee-document-input-name align-self-end input-value form-control" placeholder="Contrato confidencialidad">
                    </div>
                    <div class="col-6">
                        <input type="file" class="employee-document-input-file form-control" name="file" placeholder="Archivo..." aria-label="Archivo" aria-describedby="basic-addon1" accept=".pdf,.docx,.xlsx,.pptx">
                    </div>
                    <button class="col-2 btn btn-secondary" id="add-employee-documens-button">Agregar</button>
                </div>
                <table id="employee-documents-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-left">Archivo</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employee-documents-table-body">
                        
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="sub-nav-licenses" role="tabpanel" aria-labelledby="sub-nav-licenses-tab">
                <table id="employee-licenses-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Servicio</th>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-left">Cliente</th>
                            <th scope="col" class="text-left">Comision</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employee-licenses-table-body"></tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>
@endsection
