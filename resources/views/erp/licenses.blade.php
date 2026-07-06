@extends('erp.layouts.app')
@section('component_title', 'LICENCIAS')
@section('erp-app-header')
@vite('resources/js/erp/licenses/licenses.js')
@vite('resources/js/erp/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/licenses/licenses.scss')
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
        <div id="license-list-container" class="scrollable">
            <div id="search-list-container">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
                <div id="state-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="state-list-title">Estado</p>
                    <select class="form-select align-self-center" id="state-list-input" aria-label="Default select example">
                        <option value="">Todas</option>
                        <option value="1" selected>Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </div>
            <table id="license-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="license-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-client text-left">Cliente</th>
                        <th scope="col" class="columns-name text-left">Nombre</th>
                        <th scope="col" class="columns-service text-left">Servicio</th>
                        <th scope="col" class="columns-type text-left">Tipo L.</th>
                        <th scope="col" class="columns-value text-end">Valor</th>
                        <th scope="col" class="columns-last-billing-date text-center">Última factura</th>
                        <th scope="col" class="columns-last-payed_date text-center">Último pago</th>
                        <th scope="col" class="columns-remaining-days text-center">Dias restantes</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="license-list-table-body">
                    
                </tbody>
            </table>
        </div>
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
        <div id="create-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
            <div class="col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Estado">
                        <label for="license-state" class="input-title align-self-center">Estado</label>
                        <div class="toggle-container row" value="1" id="create-license-state">
                            <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                <p>Activo</p>
                            </div>
                            <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                <p>Inactivo</p>
                            </div>
                        </div>
                    </div>
                    <div class="input-container col-12 d-flex" title="Cliente">
                        <label for="license-client" class="input-title align-self-center">Cliente</label>
                        <select class="input-value form-select align-self-center" id="create-license-client" aria-label="Default select example">
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="license-name" class="input-title align-self-center">Nombre</label>
                        <input type="text" autofocus id="create-license-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre de la licencia">
                    </div>
                    
                </div>
                <div class="input-container col-12 d-flex" title="Servicio">
                    <label for="license-service" class="input-title align-self-center">Servicio</label>
                    <div class="crud-input-container input-value" prefix="/admin/service/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="create-license-service">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Selecciona un servicio">
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
            <div class="col-12 col-md-5">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Empleado">
                        <label for="license-employee" class="input-title align-self-center">Empleado</label>
                        <select class="input-value form-select align-self-center" id="create-license-employee" aria-label="Default select example">
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Valor">
                        <label for="license-value" class="input-title align-self-center">Valor</label>
                        <input type="number" autofocus id="create-license-value" class="input-value form-control align-self-center" name="value" placeholder="$80.000.000">
                    </div>
                    <div class="input-container col-12 d-flex" title="Descripcion">
                        <label for="license-description" class="input-title align-self-center">Descripcion</label>
                        <textarea class="input-value form-control align-self-center" id="create-license-description" placeholder="Descripcion de la licencia"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="add-license-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="/licenses/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID">
                        <label for="license-id" class="input-title align-self-center">ID</label>
                        <p class="input-value align-self-center" id="update-license-unique-id"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Estado">
                        <label for="license-state" class="input-title align-self-center">Estado</label>
                        <div class="toggle-container row" value="1" id="update-license-state">
                            <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                <p>Activo</p>
                            </div>
                            <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                <p>Inactivo</p>
                            </div>
                        </div>
                    </div>
                    <div class="input-container col-12 d-flex" title="Cliente">
                        <label for="license-client" class="input-title align-self-center">Cliente</label>
                        <select class="input-value form-select align-self-center" id="update-license-client" aria-label="Default select example">
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="license-name" class="input-title align-self-center">Nombre</label>
                        <input type="text" autofocus id="update-license-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre de la licencia">
                    </div>
                </div>
                
            </div>
            <div class="col-12 col-md-4">
                <div class="input-container col-12 d-flex" title="Servicio">
                    <label for="license-service" class="input-title align-self-center">Servicio</label>
                    <div class="crud-input-container input-value" prefix="/admin/service/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="update-license-service">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Selecciona un servicio">
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
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Empleado">
                        <label for="license-employee" class="input-title align-self-center">Empleado</label>
                        <select class="input-value form-select align-self-center" id="update-license-employee" aria-label="Default select example">
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Valor">
                        <label for="license-value" class="input-title align-self-center">Valor</label>
                        <input type="number" autofocus id="update-license-value" class="input-value form-control align-self-center" name="value" placeholder="$80.000.000">
                    </div>
                    <div class="input-container col-12 d-flex" title="Descripcion">
                        <label for="license-description" class="input-title align-self-center">Descripcion</label>
                        <textarea class="input-value form-control align-self-center" id="update-license-description" placeholder="Descripcion de la licencia"></textarea>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-center" id="balance-button">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p class="align-self-center">Balance</p>
                </div>
                <div class="d-block" id="license-sub-opt-container">
                    <div class="d-flex justify-content-between">
                        <div class="align-self-center" id="update-license-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                        <div class="align-self-center" id="update-license-delete"><i class="fa-solid fa-trash-can"></i></div>
                        <div class="align-self-center d-none" id="update-license-restore"><i class="fa-solid fa-lightbulb"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-license-button">Guardar</button>
        <nav>
            <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
                <button class="nav-link active" id="sub-nav-details-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-details" type="button" role="tab" aria-controls="sub-nav-details" aria-selected="true">Detalles</button>
                <button class="nav-link" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="false">Documentos</button>
                <button class="nav-link" id="sub-nav-notifications-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-notifications" type="button" role="tab" aria-controls="sub-nav-notifications" aria-selected="false">Notificaciones</button>
            </div>
        </nav>
        <div class="tab-content" id="sub-nav-tabContent">
            <div class="tab-pane fade show active" id="sub-nav-details" role="tabpanel" aria-labelledby="sub-nav-details-tab">
                <div id="license-details-inputs-container" class="row w-100">
                    <div class="col-12 col-md-6">
                        <div class="row w-100 p-0 m-0">
                            <div class="input-container col-12 d-flex" title="Tipo de licencia">
                                <label for="license-type" class="input-title align-self-center">Tipo de licencia</label>
                                <select class="input-value form-select align-self-center" id="update-license-type">
                                    <option value="1">Recurrente</option>
                                    <option value="2">Stática</option>
                                </select>
                            </div>
                            <div class="input-container col-12 d-flex" title="Frecuencia Mensual">
                                <label for="license-recurrence-months" class="input-title align-self-center">Frecuencia en meses</label>
                                <input type="number" autofocus id="update-license-recurrence-months" class="input-value form-control align-self-center" name="monthly-frequency" placeholder="1">
                            </div>
                            <div class="input-container col-12 d-flex" title="Dia de facturación">
                                <label for="license-billing-day" class="input-title align-self-center">Día de facturación</label>
                                <input type="number" autofocus id="update-license-billing-day" class="input-value form-control align-self-center" name="billing-day" placeholder="1">
                            </div>
                            <div class="input-container col-12 d-flex" title="Días de gracia">
                                <label for="license-days-to-expire" class="input-title align-self-center">Días de gracia</label>
                                <input type="number" autofocus id="update-license-days-to-expire" class="input-value form-control align-self-center" name="grace-days" placeholder="1">
                            </div>
                            <div class="input-container col-12 d-flex" title="Último pago">
                                <label for="license-last-payed" class="input-title align-self-center">Último pago</label>
                                <input type="date" autofocus id="update-license-last-payed-date" class="input-value form-control align-self-center" name="last-payed" placeholder="2021-12-31">
                            </div>
                            <div class="input-container col-12 d-flex" title="Próxima facturación">
                                <label for="license-next-billing" class="input-title align-self-center">Próxima facturación</label>
                                <input type="date" autofocus id="update-license-next-billing-date" class="input-value form-control align-self-center" name="next-billing" placeholder="2021-12-31">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row w-100 p-0 m-0">
                            <div class="input-container col-12 d-flex" title="Llave de usuario">
                                <label for="license-user-key" class="input-title align-self-center"><i class="fa-regular fa-copy copy-action" id="copy-update-license-user-key" data-clipboard-text=""></i>Llave de usuario</label>
                                <p class="input-value align-self-center" id="update-license-user-key"></p>
                            </div>
                            <div class="input-container col-12 d-flex" title="Contraseña de usuario">
                                <label for="license-password-key" class="input-title align-self-center"><i class="fa-regular fa-copy copy-action" id="copy-update-license-password-key" data-clipboard-text=""></i>Contraseña de usuario</label>
                                <p class="input-value align-self-center" id="update-license-password-key"></p>
                            </div>
                            <div class="input-container col-12 d-flex" title="Ultima facturación">
                                <label for="license-last-billing" class="input-title align-self-center">Última facturación</label>
                                <p class="input-value align-self-center" id="update-license-last-billing-date"></p>
                            </div>
                            <div class="input-container col-12 d-flex" title="Dias restantes">
                                <label for="license-remaining-days" class="input-title align-self-center">Dias restantes</label>
                                <p class="input-value align-self-center" id="update-license-remaining-days"></p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary align-self-center" id="update-license-details-button"><i class="fa-solid fa-file-invoice"></i></button>
                </div>        
            </div>
            <div class="tab-pane fade" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-details-tab">
                <div id="license-documents-add-container" class="row">
                    <div class="col-1 input-container d-flex">
                        <p class="license-document-input-title align-self-end">Nombre</p>
                    </div>
                    <div class="col-3 input-container d-flex">
                        <input type="text" name="" class="license-document-input-name align-self-end input-value form-control" placeholder="Contrato confidencialidad">
                    </div>
                    <div class="col-6">
                        <input type="file" class="license-document-input-file form-control" name="file" placeholder="Archivo..." aria-label="Archivo" aria-describedby="basic-addon1" accept=".pdf,.docx,.xlsx,.pptx">
                    </div>
                    <button class="col-2 btn btn-secondary" id="add-license-documens-button">Agregar</button>
                </div>
                <table id="license-documents-table" class="table table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Nombre</th>
                            <th scope="col" class="text-left">Archivo</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="license-documents-table-body">
                        
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="sub-nav-notifications" role="tabpanel" aria-labelledby="sub-nav-details-tab">
                <table class="table sub-table table-strech" id="notifications-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left"></th>
                            <th scope="col" class="columns-notification-email text-left">Correo</th>
                            <th scope="col" class="columns-notification-phone text-left">Teléfono</th>
                            <th scope="col" class="columns-notification-state text-center">Estado</th>
                            <th scope="col" class="columns-notification-actions text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="add-notification-row" class="table-row-add">
                            <td class="text-center"></td>
                            <td class="columns-notification-email text-center">
                                <input type="email" class="form-control align-self-center notification-email text-start" placeholder="license@gmail.com">
                            </td>
                            <td class="columns-notification-phone text-center">
                                <input type="number" class="form-control align-self-center notification-phone text-start" placeholder="573191425639">
                            </td>
                            <td class="columns-notification-state text-center">
                                <div class="toggle-container row notification-active" value="1">
                                    <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                        <p>Activo</p>
                                    </div>
                                    <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                        <p>Inactivo</p>
                                    </div>
                                </div>
                            </td>
                            <td class="columns-notification-actions text-center">
                                <i class="fa-solid fa-plus" id="add-notification"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
