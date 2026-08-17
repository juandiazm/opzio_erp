@extends('erp.layouts.app')
@section('component_title', 'REPORTES')
@section('erp-app-header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@vite('resources/js/erp/reports/reports.js')
<!-- Styles -->
@vite('resources/sass/erp/reports/reports.scss')
@endsection
@section('component-title-options')
    @include('erp.reports.export')
@endsection
@section('erp-app-content')
@include('erp.reports.charts')
@include('erp.reports.zoom')
@endsection
