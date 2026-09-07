@extends('erp.layouts.app')
@section('component_title', 'LICITACIONES')
@section('erp-app-header')
@vite('resources/js/erp/tenders/tenders.js')
@vite('resources/sass/erp/tenders/tenders.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="tenders-discovery-tab" data-bs-toggle="tab" data-bs-target="#tenders-discovery" type="button" role="tab" aria-controls="tenders-discovery" aria-selected="true">Discovery</button>
        <button class="nav-link" id="tenders-context-tab" data-bs-toggle="tab" data-bs-target="#tenders-context" type="button" role="tab" aria-controls="tenders-context" aria-selected="false">Contexto</button>
        <button class="nav-link" id="tenders-pipeline-tab" data-bs-toggle="tab" data-bs-target="#tenders-pipeline" type="button" role="tab" aria-controls="tenders-pipeline" aria-selected="false">Oportunidad</button>
    </div>
</nav>
<div class="tab-content" id="tenders-tab-content">
    @include('erp.tenders.discovery')
    @include('erp.tenders.context')
    @include('erp.tenders.pipeline')
</div>
@endsection