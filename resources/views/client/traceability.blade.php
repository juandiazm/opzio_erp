@extends('client.layouts.app')
@section('component_title', 'Trazabilidad')
@section('client-app-header')
<script src="{{ asset('js/client/traceability.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/traceability.css') }}" rel="stylesheet">
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
