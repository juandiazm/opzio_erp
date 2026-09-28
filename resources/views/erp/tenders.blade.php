@extends('erp.layouts.app')
@section('component_title', 'LICITACIONES')
@section('erp-app-header')
@vite('resources/js/erp/tenders/tenders.js')
@vite('resources/sass/erp/tenders/tenders.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="tenders-discovery-tab" data-bs-toggle="tab" data-bs-target="#tenders-discovery" type="button" role="tab" aria-controls="tenders-discovery" aria-selected="true"><i class="fa-light fa-magnifying-glass" aria-hidden="true"></i> Discovery</button>
        <button class="nav-link" id="tenders-context-tab" data-bs-toggle="tab" data-bs-target="#tenders-context" type="button" role="tab" aria-controls="tenders-context" aria-selected="false"><i class="fa-light fa-book-open" aria-hidden="true"></i> Contexto</button>
        <button class="nav-link" id="tenders-pipeline-tab" data-bs-toggle="tab" data-bs-target="#tenders-pipeline" type="button" role="tab" aria-controls="tenders-pipeline" aria-selected="false"><i class="fa-light fa-filter" aria-hidden="true"></i> Oportunidad</button>
        <button class="nav-link" id="tenders-configuration-tab" data-bs-toggle="tab" data-bs-target="#tenders-configuration" type="button" role="tab" aria-controls="tenders-configuration" aria-selected="false"><i class="fa-light fa-gear" aria-hidden="true"></i> Configuracion</button>
    </div>
</nav>
<div class="tab-content" id="tenders-tab-content">
    @include('erp.tenders.discovery')
    @include('erp.tenders.context')
    @include('erp.tenders.pipeline')
    @include('erp.tenders.configuration', ['connection' => $connection, 'settings' => $settings])
</div>
@endsection