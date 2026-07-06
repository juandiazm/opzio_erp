@extends('client.layouts.app')
@section('component_title', 'Inicio')
@section('client-app-header')
@vite('resources/js/client/dashboard/dashboard.js')
<!-- Styles -->
@vite('resources/sass/client/dashboard/dashboard.scss')
@endsection
@section('client-app-content')
<div id="dashboard-container" class="d-flex justify-content-center">
    <img src="{{ asset('images/business_blues.webp') }}" alt="dashboard" class="align-self-center" id="dashboard-logo">
</div>
@endsection
