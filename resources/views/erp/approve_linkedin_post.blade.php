@extends('layouts.app')
@section('app-header')
<script>
    var unique_id = '{{ $unique_id }}';
</script>
@vite('resources/js/erp/approve_linkedin_post/approve_linkedin_post.js')
<!-- Styles -->
@vite('resources/sass/erp/approve_linkedin_post/approve_linkedin_post.scss')
@yield('home-app-header')
@endsection
@section('app-content')
@include('erp.approval.post', ['platform' => 'linkedin'])
@endsection
