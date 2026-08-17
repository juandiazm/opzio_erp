@extends('layouts.app')
@section('app-header')
<script>
    var unique_id = '{{ $unique_id }}';
</script>
@vite('resources/js/erp/approve_instagram_post/approve_instagram_post.js')
<!-- Styles -->
@vite('resources/sass/erp/approve_instagram_post/approve_instagram_post.scss')
@yield('home-app-header')
@endsection
@section('app-content')
@include('erp.approval.post', ['platform' => 'instagram'])
@endsection
