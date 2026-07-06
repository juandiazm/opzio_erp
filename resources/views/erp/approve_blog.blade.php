@extends('layouts.app')
@section('app-header')
<script>
    var unique_id = '{{ $unique_id }}';
</script>
@vite('resources/js/erp/approve_blog/approve_blog.js')
<!-- Styles -->
@vite('resources/sass/erp/approve_blog/approve_blog.scss')
@yield('home-app-header')
@endsection
@section('app-content')
<div id="approve_blog-container">
    <div id="approve_blog-centered">
        <h1 id="approve_blog-title">Proceso de aprobación de blog</h1>
        <p id="approve_blog-message">Al aprobar el siguiente blog, este será publicado en la página web.</p>
        <div class="propagation-opt-container">
            <i class="status-spinner fas fa-spinner fa-spin"></i>
            <input type="checkbox" id="propagation-opt-email" class="form-check-input" name="propagation-opt">
            <label for="propagation-opt-email" class="form-check-label">Enviar notificación por correo electrónico</label>
        </div>
        <div class="propagation-opt-container">
            <i class="status-spinner fas fa-spinner fa-spin"></i>
            <input type="checkbox" id="propagation-opt-facebook" class="form-check-input" name="propagation-opt">
            <label for="propagation-opt-facebook" class="form-check-label">Publicar en Facebook</label>
        </div>
        <div class="propagation-opt-container">
            <i class="status-spinner fas fa-spinner fa-spin"></i>
            <input type="checkbox" id="propagation-opt-linkedin" class="form-check-input" name="propagation-opt">
            <label for="propagation-opt-linkedin" class="form-check-label">Publicar en LinkedIn</label>
        </div>
        <div class="propagation-opt-container">
            <i class="status-spinner fas fa-spinner fa-spin"></i>
            <input type="checkbox" id="propagation-opt-twitter" class="form-check-input" name="propagation-opt">
            <label for="propagation-opt-twitter" class="form-check-label">Publicar en Twitter</label>
        </div>
        <button id="approve_blog-button" class="btn btn-primary">Aprobar</button>
    </div>
</div>
@endsection
