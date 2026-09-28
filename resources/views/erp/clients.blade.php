@extends('erp.layouts.app')
@section('component_title', 'CLIENTES')
@section('erp-app-header')
@vite('resources/js/erp/clients/clients.js')
@vite('resources/js/erp/outcomes/associated.js')
@vite('resources/js/erp/contracts/associations.js')
@vite('resources/js/erp/clients/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/clients/clients.scss')
@vite('resources/sass/erp/outcomes/associated.scss')
@vite('resources/sass/erp/clients/traceability.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true"><i class="fa-light fa-database" aria-hidden="true"></i> Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false"><i class="fa-light fa-plus" aria-hidden="true"></i> Crear</button>
        <button class="nav-link" id="nav-traceability-tab" data-bs-toggle="tab" data-bs-target="#nav-traceability" type="button" role="tab" aria-controls="nav-traceability" aria-selected="false"><i class="fa-light fa-route" aria-hidden="true"></i> Trazabilidad</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false"><i class="fa-light fa-pen-to-square" aria-hidden="true"></i> Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    @include('erp.clients.list')
    @include('erp.clients.create')
    @include('erp.clients.traceability')
    @include('erp.clients.update')
</div>
@endsection
