@extends('erp.layouts.app')
@section('component_title', 'EGRESOS')
@section('erp-app-header')
@vite('resources/js/erp/outcomes/outcomes.js')
<!-- Styles -->
@vite('resources/sass/erp/outcomes/outcomes.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Editar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    @include('erp.outcomes.list')
    @include('erp.outcomes.create')
    @include('erp.outcomes.update')
</div>
@include('erp.outcomes.import')
@endsection
