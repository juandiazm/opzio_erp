@extends('client.layouts.app')
@section('component_title', 'Trazabilidad')
@section('client-app-header')
@vite('resources/js/client/traceability.js')
<!-- Styles -->
@vite('resources/sass/client/traceability.scss')
<script>
    $(document).ready(function() {
        setTimeout(() => {
            $('#traceability-container').addClass('active');
        }, 1000);
    });
</script>
@endsection
@section('client-app-content')
<div class="traceability-container" data-url="" id="traceability-container" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
@endsection
