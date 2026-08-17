@extends('erp.layouts.app')
@section('component_title', 'MI PERFIL')
@section('erp-app-header')
<script>
    var current_user = {!! json_encode(session('user')) !!};
    var permissions = {!! json_encode(session('permissions')) !!};
</script>
@vite('resources/js/erp/my_profile/my_profile.js')
<!-- Styles -->
@vite('resources/sass/erp/my_profile/my_profile.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    @include('erp.my_profile.update')
</div>
@endsection
