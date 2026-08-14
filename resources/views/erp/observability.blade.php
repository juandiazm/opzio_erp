@extends('erp.layouts.app')
@section('component_title', 'Observabilidad')
@section('erp-app-header')
@vite('resources/js/erp/observability/observability.js')
@vite('resources/sass/erp/observability/observability.scss')
@endsection
@section('erp-app-content')
<div id="observability-container" class="scrollable">
    <div class="observability-toolbar">
        <div>
            <p class="observability-eyebrow">Infraestructura</p>
            <h2>Estado del servidor y proyectos</h2>
        </div>
        <label class="observability-range">
            <span>Rango</span>
            <select id="observability-range-select">
                <option value="15">15 min</option>
                <option value="60">1 h</option>
                <option value="360">6 h</option>
                <option value="1440" selected>24 h</option>
                <option value="10080">7 d</option>
            </select>
        </label>
    </div>
    <div id="observability-status" class="observability-status" role="status"></div>
    <div id="observability-hosts" class="observability-hosts"></div>
</div>
@endsection
