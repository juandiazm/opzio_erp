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
<div id="dashboard-container">
    <div class="dashboard-first-view">
        <div class="dashboard-first-view-main">
            <div class="dashboard-kpi-section">
                @include('erp.dashboard.indicators')
            </div>
            <div class="dashboard-first-goals">
                @include('erp.dashboard.income-goals')
            </div>
            <div class="dashboard-first-recurrence">
                @include('erp.dashboard.incomes-by-recurrence')
            </div>
        </div>
        <div class="dashboard-first-view-right">
            @include('erp.dashboard.income-outcome-graph')
            @include('erp.dashboard.incomes-by-client')
        </div>
    </div>
    <div class="dashboard-columns">
        <div class="column-container">
            @include('erp.dashboard.approve-incomes')
        </div>
        <div class="column-container">
            @include('erp.dashboard.tables')
        </div>
    </div>
</div>
@endsection
