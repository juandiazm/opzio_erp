@extends('erp.layouts.app')
@section('component_title', 'Dashboard')
@section('erp-app-header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
@vite('resources/js/erp/dashboard/dashboard.js')
<!-- Styles -->
@vite('resources/sass/erp/dashboard/dashboard.scss')
@vite('resources/sass/erp/dashboard/dashboard-overdue.scss')
@endsection
@section('erp-app-content')
<div id="dashboard-container" class="scrollable">
    <div class="column-container">
        @include('erp.dashboard.indicators')
        @include('erp.dashboard.income-outcome-graph')
        @include('erp.dashboard.approve-incomes')
    </div>
    <div class="column-container">
        @include('erp.dashboard.tables')
        @include('erp.dashboard.incomes-by-client')
    </div>
</div>
@endsection
