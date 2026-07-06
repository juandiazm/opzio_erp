@extends('layouts.app')
@section('app-header')
<script>
    var unique_id = '{{ $unique_id }}';
</script>
<script src="{{ asset('js/erp/approve_facebook_post/approve_facebook_post.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/erp/approve_facebook_post/approve_facebook_post.css') }}" rel="stylesheet">
@yield('home-app-header')
@endsection
@section('app-content')
<div id="approve-post-container">
    <div id="approve-post-centered">
        <h1 id="approve-post-title">Proceso de aprobación de post</h1>
        <p id="approve-post-message">Al aprobar el siguiente post, este será publicado en facebook.</p>
        <i id="approve-post-loading-icon" class="fas fa-spinner fa-spin"></i>
        <p id="approve-post-status-message">Aprobando...</p>
    </div>
</div>
@endsection
