@extends('erp.layouts.app')
@section('component_title', 'PÁGINA WEB')
@section('erp-app-header')
@vite('resources/js/erp/web-pages/web-pages.js')
<!-- Styles -->
@vite('resources/sass/erp/web-pages/web-pages.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    @include('erp.web-pages.list')
</div>
@endsection
