@extends('erp.layouts.app')
@section('component_title', 'PÁGINA WEB')
@section('erp-app-header')
<script src="{{ asset('js/erp/web-pages/web-pages.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/web-pages/web-pages.css') }}" rel="stylesheet">
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="user-list-container" class="scrollable">
            <table id="user-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="user-list-table-header">
                    <tr>
                        <th scope="col" class="text-center">ID</th>
                        <th scope="col" class="text-center">Foto</th>
                        <th scope="col" class="text-left">Nombre</th>
                        <th scope="col" class="text-center">Usuario</th>
                        <th scope="col" class="text-center">Identificación</th>
                        <th scope="col" class="text-left">Correo</th>
                        <th scope="col" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="user-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
</div>
@endsection
