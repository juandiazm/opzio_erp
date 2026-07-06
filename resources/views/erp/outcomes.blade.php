@extends('erp.layouts.app')
@section('component_title', 'EGRESOS')
@section('erp-app-header')
<script src="{{ asset('js/erp/outcomes/outcomes.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/outcomes/outcomes.css') }}" rel="stylesheet">
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="user-list-container" class="scrollable">
            <!-- filtros -->
            <div id="search-list-container" class="mb-3">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-items-center">
                    <p class="mb-0 me-2" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control" placeholder="Buscar..." autocomplete="off">
                </div>
                <div id="search-date-range" class="d-flex align-items-center ms-3">
                    <p class="mb-0 me-2" id="search-list-title">Fecha</p>
                    <input type="date" id="date-from" class="form-control date-input" placeholder="Desde">
                    <span class="mx-2" style=" font-size: 1.25rem;">/</span>
                    <input type="date" id="date-to" class="form-control date-input" placeholder="Hasta">
                </div>
            </div>

            <!-- tabla -->
            <table id="outcome-list-table" class="table table-hover table-sm align-middle w-100">
            <thead id="outcome-list-table-header">
                <tr>
                <th scope="col" class="columns-id text-center">ID</th>
                <th scope="col" class="columns-date text-center">Fecha</th>
                <th scope="col" class="columns-type text-center">Tipo</th>
                <th scope="col" class="columns-name text-center">Nombre</th>
                <th scope="col" class="columns-description text-left">Descripción</th>
                <th scope="col" class="columns-amount text-center">Monto</th>
                <th scope="col" class="columns-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="outcome-list-table-body">
               
            </tbody>
            </table>
        </div>

        <ul id="db-pagination" class="pagination pagination-sm justify-content-end"></ul>
    </div>
    <!-- Tab Create -->
    <div class="tab-pane fade show" id="nav-create" role="tabpanel" aria-labelledby="nav-profile-tab">
        <!-- Excel bulk Create -->
        <div id="import-btn-container">
            <i class="fa-solid fa-file-excel"></i>
            <span id="import-title">Importación Masiva</span>
        </div>
        <div id="import-form-container">
            <i class="fa-thin fa-file-excel import-form-icon"></i>
            <h3 id="import-form-title">¿desea iniciar la importación masiva de egresos desde el extracto bancario?</h3>
            <h5 id="import-form-subtitle">Recuerde que esta acción tendrá como consecuencia la actualización automática del balance en el sistema.</h5>
            <p id="import-form-description">Por favor, asegúrese de tener el archivo del extracto bancario listo y verificado para garantizar la precisión de los datos importados. Una vez confirmada la importación, las transacciones del extracto bancario se integrarán en el sistema y reflejarán cambios en el balance.</p>
            <input type="file" id="import-file-input" name="import-file" accept=".xlsx, .xls" class="form-control" required>
            <div id="import-btns-container">
                <button id="import-cancel-btn" class="btn">Cancelar</button>
                <button id="import-confirm-btn" class="btn">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@endsection
