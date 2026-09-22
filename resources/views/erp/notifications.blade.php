@extends('erp.layouts.app')
@section('component_title', 'NOTIFICACIONES')
@section('erp-app-header')
@vite('resources/js/erp/notifications/notifications.js')
@vite('resources/sass/erp/notifications/notifications.scss')
@endsection
@section('erp-app-content')
<div class="notifications-page">
    <nav>
        <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="notifications-whatsapp-tab" data-bs-toggle="tab" data-bs-target="#notifications-whatsapp-pane" type="button" role="tab" aria-controls="notifications-whatsapp-pane" aria-selected="true"><i class="fa-brands fa-whatsapp"></i> WhatsApp <span class="notifications-tab-unread d-none" id="notifications-whatsapp-unread-tab-badge">0</span></button>
            <button class="nav-link" id="notifications-email-tab" data-bs-toggle="tab" data-bs-target="#notifications-email-pane" type="button" role="tab" aria-controls="notifications-email-pane" aria-selected="false"><i class="fa-light fa-envelope"></i> Email</button>
            <button class="nav-link" id="notifications-sms-tab" data-bs-toggle="tab" data-bs-target="#notifications-sms-pane" type="button" role="tab" aria-controls="notifications-sms-pane" aria-selected="false"><i class="fa-light fa-comment-sms"></i> SMS</button>
        </div>
    </nav>

    <div class="tab-content" id="notifications-tab-content">
        @include('erp.notifications.email')
        @include('erp.notifications.sms')
        @include('erp.notifications.whatsapp')
    </div>
</div>
@include('erp.notifications.compose')
@include('erp.notifications.email-view')
@endsection