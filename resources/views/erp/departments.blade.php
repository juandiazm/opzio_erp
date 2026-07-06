@extends('erp.layouts.app')
@section('component_title', 'DEPARTAMENTOS')
@section('erp-app-header')
@vite('resources/js/erp/departments/departments.js')
@vite('resources/js/erp/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/departments/departments.scss')
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
        <div id="department-list-container" class="scrollable">
            <div id="search-list-container" class="justify-content-center">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
            </div>
            <table id="department-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="department-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-name text-left">Nombre</th>
                        <th scope="col" class="columns-budget text-end">Presupuesto</th>
                        <th scope="col" class="columns-employees-number text-center">No.Empleados</th>
                        <th scope="col" class="columns-director text-center">Director</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="department-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
        <div id="create-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
            <div class="col-12 col-md-6">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="create-department-name" class="input-title align-self-center">Nombre</label>
                        <input type="text" autofocus id="create-department-name" class="input-value form-control align-self-center" name="name" placeholder="Tecnología">
                    </div>
                    <div class="input-container col-12 d-flex" title="Apellidos">
                        <label for="department-budget" class="input-title align-self-center">Presupuesto Mensual</label>
                        <input type="number" autofocus id="create-department-budget" class="input-value form-control align-self-center" name="budget" placeholder="$80.000.000">
                    </div>
                    <div class="input-container col-12 d-flex" title="Director">
                        <label for="department-director" class="input-title align-self-center">Director</label>
                        <select class="input-value form-select align-self-center" id="add-department-director" aria-label="Default select example">
                            <option selected>Seleccionar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="add-department-button">Guardar</button>
    </div>
    <!-- Tab Traceability -->
    <div class="tab-pane fade traceability-container" data-url="/departments/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
    <!-- Tab Update -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
        <div id="update-inputs-container" class="row m-0 w-100 justify-content-center">
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    <div class="input-container col-12 d-flex" title="ID">
                        <label for="departmentname" class="input-title align-self-center">ID Departamento</label>
                        <p class="input-value align-self-center" id="update-department-uid"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Nombre">
                        <label for="update-department-name" class="input-title align-self-center">Nombre</label>
                        <input type="text" autofocus id="update-department-name" class="input-value form-control align-self-center" name="name" placeholder="Tecnología">
                    </div>
                    <div class="input-container col-12 d-flex" title="Presupuesto Mensual">
                        <label for="department-budget" class="input-title align-self-center">Presupuesto Mensual</label>
                        <input type="number" autofocus id="update-department-budget" class="input-value form-control align-self-center" name="budget" placeholder="$80.000.000">
                    </div>
                    
                    
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="row w-100 p-0 m-0">
                    
                    <div class="input-container col-12 d-flex" title="Número de empleados">
                        <label for="department-employees" class="input-title align-self-center">Número de empleados</label>
                        <p class="input-value align-self-center" id="update-department-employees"></p>
                    </div>
                    <div class="input-container col-12 d-flex" title="Director">
                        <label for="department-director" class="input-title align-self-center">Director</label>
                        <select class="input-value form-select align-self-center" id="update-department-director" aria-label="Default select example">
                        </select>
                    </div>
                    <div class="input-container col-12 d-flex" title="Presupuesto Utilizado">
                        <label for="department-budget-used" class="input-title align-self-center">Presupuesto Utilizado</label>
                        <p class="input-value align-self-center" id="update-department-budget-used">0</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-center" id="balance-button">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p class="align-self-center">Balance</p>
                </div>
                <div class="d-block" id="department-sub-opt-container">
                    <div class="d-flex justify-content-between">
                        <div class="align-self-center" id="update-department-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                        <div class="align-self-center" id="update-department-delete"><i class="fa-solid fa-trash-can"></i></div>
                        <div class="align-self-center d-none" id="update-department-restore"><i class="fa-solid fa-lightbulb"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-department-button">Actualizar</button>
        <nav>
            <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
                <button class="nav-link active" id="sub-nav-employee-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-employee" type="button" role="tab" aria-controls="sub-nav-employee" aria-selected="true">Empleados</button>
            </div>
        </nav>
        <div class="tab-content" id="sub-nav-tabContent">
            <div class="tab-pane fade show active" id="sub-nav-employee" role="tabpanel" aria-labelledby="sub-nav-employee-tab">
                <table id="department-employee-table" class="table table-hover table-sm align-middle w-100">
                    <thead id="department-employee-table-header">
                        <tr>
                            <th scope="col" class="columns-employees-name text-center">Nombre</th>
                            <th scope="col" class="columns-employees-position text-center">Cargo</th>
                            <th scope="col" class="columns-employees-identification text-center">Identificación</th>
                            <th scope="col" class="columns-employees-email text-center">Correo</th>
                            <th scope="col" class="columns-employees-phone text-center">Teléfono</th>
                            <th scope="col" class="columns-employees-salary text-end">Salario</th>
                            <th scope="col" class="columns-employees-entry-date text-center">Fecha de Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
