@extends('erp.layouts.app')
@section('component_title', 'GITHUB')
@section('erp-app-header')
@vite('resources/js/erp/github/github.js')
@vite('resources/sass/erp/github/github.scss')
@endsection
@section('erp-app-content')
<div id="github-module" data-github-module data-github-endpoint="{{ url('/admin/github') }}">
    <nav class="nav nav-tabs github-tabs" id="github-module-tabs" role="tablist" aria-label="Secciones GitHub">
        <button class="nav-link active" id="github-overview-tab" data-bs-toggle="tab" data-bs-target="#github-overview" type="button" role="tab" aria-controls="github-overview" aria-selected="true"><i class="fa-light fa-chart-simple"></i><span>Resumen</span></button>
        <button class="nav-link" id="github-connection-tab" data-bs-toggle="tab" data-bs-target="#github-connection" type="button" role="tab" aria-controls="github-connection" aria-selected="false"><i class="fa-light fa-plug"></i><span>Conexion</span></button>
        <button class="nav-link" id="github-projects-tab" data-bs-toggle="tab" data-bs-target="#github-projects" type="button" role="tab" aria-controls="github-projects" aria-selected="false"><i class="fa-light fa-diagram-project"></i><span>Proyectos</span></button>
        <button class="nav-link" id="github-agents-tab" data-bs-toggle="tab" data-bs-target="#github-agents" type="button" role="tab" aria-controls="github-agents" aria-selected="false"><i class="fa-light fa-sparkles"></i><span>Agentes</span></button>
        <button class="nav-link" id="github-supervisors-tab" data-bs-toggle="tab" data-bs-target="#github-supervisors" type="button" role="tab" aria-controls="github-supervisors" aria-selected="false"><i class="fa-light fa-users"></i><span>Supervisores</span></button>
        <button class="nav-link" id="github-approvals-tab" data-bs-toggle="tab" data-bs-target="#github-approvals" type="button" role="tab" aria-controls="github-approvals" aria-selected="false"><i class="fa-light fa-shield-check"></i><span>Aprobaciones</span></button>
        <button class="nav-link" id="github-executions-tab" data-bs-toggle="tab" data-bs-target="#github-executions" type="button" role="tab" aria-controls="github-executions" aria-selected="false"><i class="fa-light fa-bars-progress"></i><span>Ejecuciones</span></button>
    </nav>
    <div class="tab-content github-tab-content" id="github-module-content">
        @include('erp.github.overview')
        @include('erp.github.connection')
        @include('erp.github.projects')
        @include('erp.github.agents')
        @include('erp.github.supervisors')
        @include('erp.github.approvals')
        @include('erp.github.executions')
    </div>
</div>
@endsection