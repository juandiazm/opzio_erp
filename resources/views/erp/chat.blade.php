@extends('erp.layouts.app')
@section('component_title', 'CHAT')
@section('erp-app-header')

<!-- Styles -->
@vite('resources/sass/erp/chat/chat.scss')
@endsection
@section('erp-app-content')
<div class="chat-container">
    
    <div class="chat-messages-container">
        <div class="empty-view-chat">
            <div class="empty-view-icon">
                <i class="fas fa-comments"></i>
            </div>
            <p class="empty-view-title">Selecciona un chat para empezar</p>
        </div>
        <div class="loading-view-chat">
            <div class="loading-view-icon">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p class="loading-view-title">Cargando mensajes...</p>
        </div>
        <div class="chat-message-sub-container">
            <ul class="chat-messages-list scrollable"></ul>
            <textarea id="chat-message-input" placeholder="Escribe un mensaje..."></textarea>
        </div>
    </div>
    <ul id="chat-conversations-list" class="chat-conversations-list scrollable">

    </ul>
</div>
@vite('resources/js/erp/chat/chat.js')
@endsection
