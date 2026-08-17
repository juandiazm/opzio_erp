@extends('erp.layouts.app')
@section('erp-app-header')
@vite('resources/js/erp/ia_assistant/ia_marketing_report.js')
@vite('resources/sass/erp/ia_assistant/ia_marketing_report.scss')
@endsection
@section('component_title', 'Reportes de Marketing')
@section('erp-app-content')
<div id="ia-marketing-container">
    <div id="ia-marketing-left">
        @include('erp.ia_assistant.marketing_report.generate')
        @include('erp.ia_assistant.marketing_report.history')
    </div>

    <div id="ia-marketing-right">
        @include('erp.ia_assistant.marketing_report.report')
    </div>
</div>
@include('erp.ia_assistant.marketing_report.email')
@endsection
