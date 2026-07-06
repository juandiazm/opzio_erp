@extends('erp.layouts.app')
@section('erp-app-header')
@vite('resources/sass/erp/ia_assistant/ia_assistant.scss')
@endsection
@section('component_title', 'IA Assistant')
@section('erp-app-content')
<div id="ia-assistant-container">
    <p class="ia-assistant-subtitle">Selecciona el asistente que necesitas</p>
    <div id="ia-assistant-cards">
        <a href="/admin/ia-assistant/marketing-report" class="ia-assistant-card ia-assistant-card--active">
            <div class="ia-assistant-card__icon-wrapper">
                <i class="fa-light fa-chart-mixed-up-circle-dollar ia-assistant-card__icon"></i>
            </div>
            <p class="ia-assistant-card__title">Reportes de Marketing</p>
            <p class="ia-assistant-card__description">Genera informes profesionales de campañas en Meta Ads basados en tus exportaciones de datos. Personalizado con la identidad del cliente.</p>
            <span class="ia-assistant-card__badge ia-assistant-card__badge--active">Disponible</span>
        </a>
        <div class="ia-assistant-card ia-assistant-card--soon">
            <div class="ia-assistant-card__icon-wrapper">
                <i class="fa-light fa-file-invoice ia-assistant-card__icon"></i>
            </div>
            <p class="ia-assistant-card__title">Análisis Financiero</p>
            <p class="ia-assistant-card__description">Análisis inteligente de ingresos, egresos y proyecciones financieras con recomendaciones estratégicas.</p>
            <span class="ia-assistant-card__badge ia-assistant-card__badge--soon">Próximamente</span>
        </div>
        <div class="ia-assistant-card ia-assistant-card--soon">
            <div class="ia-assistant-card__icon-wrapper">
                <i class="fa-light fa-users ia-assistant-card__icon"></i>
            </div>
            <p class="ia-assistant-card__title">Perfilado de Clientes</p>
            <p class="ia-assistant-card__description">Genera perfiles de cliente detallados y segmentaciones basadas en datos de comportamiento y compras.</p>
            <span class="ia-assistant-card__badge ia-assistant-card__badge--soon">Próximamente</span>
        </div>
    </div>
</div>
@endsection
