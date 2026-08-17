@extends('erp.layouts.app')
@section('component_title', 'CHAT')
@section('erp-app-header')

<!-- Styles -->
@vite('resources/sass/erp/chat/chat.scss')
@endsection
@section('erp-app-content')
<div class="chat-container">
    @include('erp.chat.messages')
    @include('erp.chat.conversations')
</div>
@vite('resources/js/erp/chat/chat.js')
@endsection
