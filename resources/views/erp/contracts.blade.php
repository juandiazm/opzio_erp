@extends('erp.layouts.app')
@section('component_title', 'CONTRATOS')
@section('erp-app-header')
@vite('resources/js/erp/contracts/contracts.js')
@vite('resources/sass/erp/contracts/contracts.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Contratos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear</button>
        <button class="nav-link" id="nav-types-tab" data-bs-toggle="tab" data-bs-target="#nav-types" type="button" role="tab" aria-controls="nav-types" aria-selected="false">Tipos</button>
        <button class="nav-link" id="nav-templates-tab" data-bs-toggle="tab" data-bs-target="#nav-templates" type="button" role="tab" aria-controls="nav-templates" aria-selected="false">Plantillas</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    @include('erp.contracts.list')
    @include('erp.contracts.create')
    @include('erp.contracts.types')
    @include('erp.contracts.templates')
    @include('erp.contracts.update')
</div>
@endsection