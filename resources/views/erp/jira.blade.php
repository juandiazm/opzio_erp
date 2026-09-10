@extends('erp.layouts.app')
@section('component_title', 'JIRA')
@section('erp-app-header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@vite('resources/js/erp/jira/jira.js')
@vite('resources/sass/erp/jira/jira.scss')
@endsection
@section('erp-app-content')
<div id="jira-module" data-jira-module data-jira-endpoint="{{ url('/admin/jira') }}">
    <nav class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="jira-configuration-tab" data-bs-toggle="tab" data-bs-target="#jira-configuration" type="button" role="tab" aria-controls="jira-configuration" aria-selected="true">Configuracion</button>
        <button class="nav-link" id="jira-relations-tab" data-bs-toggle="tab" data-bs-target="#jira-relations" type="button" role="tab" aria-controls="jira-relations" aria-selected="false">Relaciones</button>
        <button class="nav-link" id="jira-dashboard-tab" data-bs-toggle="tab" data-bs-target="#jira-dashboard" type="button" role="tab" aria-controls="jira-dashboard" aria-selected="false">Dashboard</button>
        <button class="nav-link" id="jira-reports-tab" data-bs-toggle="tab" data-bs-target="#jira-reports" type="button" role="tab" aria-controls="jira-reports" aria-selected="false">Reportes</button>
    </nav>
    <div class="tab-content jira-tab-content" id="jira-tab-content">
        @include('erp.jira.configuration', ['connection' => $connection, 'syncedStories' => $syncedStories])
        @include('erp.jira.relations', ['projects' => $projects, 'jiraUsers' => $jiraUsers])
        @include('erp.jira.dashboard')
        @include('erp.jira.reports.index', ['reports' => $reports])
    </div>
</div>
@endsection
