@extends('client.layouts.app')
@section('component_title', 'Pagos')
@section('client-app-header')
<script src="{{ asset('js/client/incomes/incomes.js') }}" defer></script>
<script src="{{ asset('js/client/traceability.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/incomes/incomes.css') }}" rel="stylesheet">
<link href="{{ asset('css/client/traceability.css') }}" rel="stylesheet">
@endsection
@section('client-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-traceability-tab" data-bs-toggle="tab" data-bs-target="#nav-traceability" type="button" role="tab" aria-controls="nav-traceability" aria-selected="false">Trazabilidad</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="income-list-container" class="scrollable">
            <div id="search-list-container">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
                <div id="state-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="state-list-title">Estado</p>
                    <select class="form-select align-self-center" id="state-list-input" aria-label="Default select example">
                        <option value="" selected>Todas</option>
                        <option value="0">Pendiente</option>
                        <option value="1">Rechazada</option>
                        <option value="2">Aprobada</option>
                        <option value="3">Pagada</option>
                        <option value="4">Facturada</option>
                    </select>
                </div>
            </div>
            <table id="income-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="income-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-client text-left">Cliente</th>
                        <th scope="col" class="columns-timely-payment text-center">Fecha Pago O.</th>
                        <th scope="col" class="columns-cutoff-date text-center">Fecha Corte</th>
                        <th scope="col" class="columns-total text-end">Valor</th>
                        <th scope="col" class="columns-bill text-center">Factura</th>
                        <th scope="col" class="columns-created-at text-center">F. Creación</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="income-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    
    <!-- Traceability Tab -->
    <div class="tab-pane fade traceability-container" data-url="/payments/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
</div>
<!-- Purchase Order Viewer -->
<div id="order-viewer-container">
    <i class="fa-solid fa-times" id="close-order-viewer"></i>
    <div id="order-viewer-sub-container">
        <h1 id="order-viewer-title">Orden de compra</h1>
        <iframe id="order-viewer" src="" frameborder="0"></iframe>
        <div id="order-viewer-buttons" class="d-flex justify-content-center">
            <button id="send-order-button" class="btn">
                <i class="fa-solid fa-paper-plane"></i>
                Enviar
            </button>
        </div>
    </div>
    <!-- Purchase send container -->
    <div id="send-order-container">
        <div id="send-order-sub-container">
            <h1 id="send-order-title">Receptores</h1>
            <table id="receivers-list" class="table table-hover table-sm align-middle w-100">
                <thead id="receivers-list-header">
                    <tr>
                        <th scope="col" class="columns-send-email text-left">Correo</th>
                        <th scope="col" class="columns-send-phone text-left">Teléfono</th>
                        <th scope="col" class="columns-send-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="receivers-list-body">
                    
                </tbody>
            </table>
            <div id="send-order-buttons" class="d-flex justify-content-between">
                <button id="cancel-send-order-button" class="btn">
                    <i class="fa-solid fa-times"></i>
                    Cancelar
                </button>
                <button id="confirm-send-order-button" class="btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
