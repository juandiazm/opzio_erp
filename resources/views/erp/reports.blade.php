@extends('erp.layouts.app')
@section('component_title', 'REPORTES')
@section('erp-app-header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="{{ asset('js/erp/reports/reports.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/reports/reports.css') }}" rel="stylesheet">
@endsection
@section('component-title-options')
    <div id="export-report-excel-container">
        <i class="fas fa-file-excel" id="export-report-excel-icon"></i>
        <span id="export-report-excel-text">Exportar</span>
    </div>
@endsection
@section('erp-app-content')
<ul id="report-containers-list">
    <li class="report-item" value="incomes">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Ingresos</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="incomes-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra los ingresos registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-incomes">
        </div>
        <canvas class="report-item-canvas" id="incomes-report-graph"></canvas>
    </li>
    <li class="report-item" value="outcomes">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Egresos</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="outcomes-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra los egresos registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-outcomes">
        </div>
        <canvas class="report-item-canvas" id="outcomes-report-graph"></canvas>
    </li>
    <li class="report-item" value="clients">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Clientes</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="clients-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Clientes registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-clients">
        </div>
        <canvas class="report-item-canvas" id="clients-report-graph"></canvas>
    </li>
    <li class="report-item" value="licenses">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Licencias</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="licenses-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra las licencias registradas en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-licenses">
        </div>
        <canvas class="report-item-canvas" id="licenses-report-graph"></canvas>
    </li>
    <li class="report-item" value="users">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Usuarios</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="users-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Usuario registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-users">
        </div>
        <canvas class="report-item-canvas" id="users-report-graph"></canvas>
    </li>
    <li class="report-item" value="employees">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Empleados</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="employees-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Empleados registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-employees">
        </div>
        <canvas class="report-item-canvas" id="employees-report-graph"></canvas>
    </li>
</ul>
<section id="zoom-in-super-container" class="d-none">
    <div id="zoom-in-container">
        <div id="zoom-in-header-container">
            <div class="left-container">
                <h1 id="zoom-in-title"></h1>
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra el detalle de un usuario en el rango de fechas seleccionado."></i>
                <input type="text" class="zoom-in-report-item-date-input" id="date-range-input-zoom-in">
                <div id="zoom-in-export-report-excel-container">
                    <i class="fas fa-file-excel" id="zoom-in-export-report-excel-icon"></i>
                </div>
            </div>
            <i class="fas fa-times" id="zoom-in-close-icon"></i>
        </div>
        <div id="zoom-in-data-container">
            <div id="graphic-container">
                <canvas id="zoom-in-report-graph"></canvas>
                <div id="zoom-in-graph-info-container">
                    <div class="zoom-in-label">
                        <span class="label-text">Total</span>
                        <span class="label-value" id="zoom-in-total-value">$0</span>
                    </div>
                    <div class="zoom-in-label">
                        <span class="label-text">Partición</span>
                        <span class="label-value" id="zoom-in-partition-value">0</span>
                    </div>
                    <div class="zoom-in-label">
                        <span class="label-text">Promedio</span>
                        <span class="label-value" id="zoom-in-average-value">$0</span>
                    </div>
                </div>
            </div>
            <table id="zoom-in-table" class="table table-striped"></table>
        </div>
    </div>
</section>
@endsection
