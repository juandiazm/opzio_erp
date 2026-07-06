@extends('client.layouts.app')
@section('component_title', 'Inicio')
@section('client-app-header')
<script src="{{ asset('js/client/dashboard/dashboard.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/dashboard/dashboard.css') }}" rel="stylesheet">
@endsection
@section('client-app-content')
<div id="dashboard-container" class="d-flex justify-content-center">
    <img src="{{ asset('images/business_blues.webp') }}" alt="dashboard" class="align-self-center" id="dashboard-logo">
</div>
@endsection
