@extends('erp.layouts.app')
@section('erp-app-header')
<script src="{{ asset('js/erp/ia_assistant/ia_marketing_report.js') }}" defer></script>
<link href="{{ asset('css/erp/ia_assistant/ia_marketing_report.css') }}" rel="stylesheet">
@endsection
@section('component_title', 'Reportes de Marketing')
@section('erp-app-content')
<div id="ia-marketing-container">

    {{-- LEFT PANEL --}}
    <div id="ia-marketing-left">

        {{-- Generate New Report Card --}}
        <div class="ia-card" id="ia-generate-card">
            <div class="ia-card__toggle" id="ia-generate-toggle">
                <p class="ia-card__title">Generar Nuevo Reporte</p>
                <button type="button" class="ia-toggle-btn" aria-expanded="false" aria-controls="ia-generate-body">
                    <i class="fa-light fa-plus"></i>
                </button>
            </div>

            <div id="ia-generate-body" class="d-none">
                <div class="ia-form-group">
                    <label class="ia-label">Cliente</label>
                    <div id="ia-client-dropdown" class="ia-client-dropdown" data-value="">
                        <div id="ia-client-trigger" class="ia-client-trigger" role="combobox" tabindex="0">
                            <span id="ia-client-trigger-text" class="ia-client-trigger__text ia-client-trigger__text--placeholder">Selecciona un cliente</span>
                            <i class="fa-light fa-chevron-down ia-client-trigger__icon"></i>
                        </div>
                        <div id="ia-client-panel" class="ia-client-panel d-none">
                            <div class="ia-client-search-wrap">
                                <i class="fa-light fa-magnifying-glass ia-client-search-icon"></i>
                                <input type="text" id="ia-client-search" class="ia-client-search" placeholder="Buscar cliente...">
                            </div>
                            <div id="ia-client-options" class="ia-client-options">
                                <p class="ia-empty-text">Cargando...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ia-form-group">
                    <label class="ia-label">Período del reporte</label>
                    <input type="text" id="ia-period-input" class="ia-input" placeholder="Ej: Febrero 2026">
                </div>

                <div class="ia-form-group">
                    <label class="ia-label">Archivo de datos (Excel / CSV)</label>
                    <div id="ia-dropzone" class="ia-dropzone">
                        <i class="fa-light fa-cloud-arrow-up ia-dropzone__icon"></i>
                        <p class="ia-dropzone__text">Arrastra tu archivo aquí o <span class="ia-dropzone__link">haz clic para seleccionar</span></p>
                        <p class="ia-dropzone__hint">XLSX, XLS, CSV — máx. 20 MB</p>
                        <input type="file" id="ia-file-input" accept=".xlsx,.xls,.csv" class="ia-dropzone__input">
                    </div>
                    <p id="ia-file-name" class="ia-file-name d-none"></p>
                </div>

                <button id="ia-generate-btn" class="ia-btn ia-btn--primary" disabled>
                    <i class="fa-light fa-sparkles"></i>
                    <span>Generar Reporte</span>
                </button>
            </div>
        </div>

        {{-- Conversation History --}}
        <div class="ia-card" id="ia-history-card">
            <p class="ia-card__title">Historial</p>
            <div class="ia-history-search-wrap">
                <i class="fa-light fa-magnifying-glass ia-history-search-icon"></i>
                <input type="text" id="ia-history-search" class="ia-history-search" placeholder="Buscar por cliente, nombre o fecha...">
            </div>
            <div id="ia-history-list">
                <p class="ia-empty-text">Cargando...</p>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div id="ia-marketing-right">
        {{-- Empty state --}}
        <div id="ia-empty-state" class="ia-empty-state">
            <i class="fa-light fa-robot ia-empty-state__icon"></i>
            <p class="ia-empty-state__title">Listo para generar tu reporte</p>
            <p class="ia-empty-state__text">Selecciona un cliente, define el período y sube el archivo exportado desde Meta Ads para comenzar.</p>
        </div>

        {{-- Timeout state --}}
        <div id="ia-timeout-state" class="ia-timeout-state d-none">
            <i class="fa-light fa-clock ia-timeout-state__icon"></i>
            <p class="ia-timeout-state__title">El reporte tardó un poco más de lo esperado</p>
            <p class="ia-timeout-state__text">Una vez que esté listo lo podrás ver en el historial de conversaciones.</p>
            <button id="ia-timeout-ok-btn" class="ia-btn ia-btn--primary">
                <span>Entendido</span>
            </button>
        </div>

        {{-- Loading state --}}
        <div id="ia-loading-state" class="ia-loading-state d-none">
            <i class="fa-duotone fa-loader fa-spin-pulse ia-loading-state__icon"></i>
            <p class="ia-loading-state__text">El asistente está generando tu reporte sección por sección...</p>
            <p class="ia-loading-state__hint">Este proceso puede tomar entre 1 y 3 minutos</p>
        </div>

        {{-- Report Preview --}}
        <div id="ia-report-state" class="d-none">
            {{-- Report header actions --}}
            <div id="ia-report-actions">
                <div id="ia-report-meta">
                    <span id="ia-report-turn-badge" class="ia-turn-badge"></span>
                    <span id="ia-report-title-display" class="ia-report-title-display"></span>
                </div>
                <button id="ia-download-btn" class="ia-btn ia-btn--secondary">
                    <i class="fa-light fa-file-pdf"></i>
                    <span>Descargar PDF</span>
                </button>
                <button id="ia-send-btn" class="ia-btn ia-btn--secondary">
                    <i class="fa-light fa-paper-plane"></i>
                    <span>Enviar</span>
                </button>
            </div>

            {{-- Inline PDF preview --}}
            <iframe id="ia-report-iframe" class="ia-pdf-iframe" src=""></iframe>

            {{-- Feedback / Regenerate --}}
            <div id="ia-feedback-area">
                <p class="ia-label">¿Quieres ajustar el reporte?</p>
                <div id="ia-feedback-input-row">
                    <textarea id="ia-feedback-input" class="ia-textarea" rows="2" placeholder="Ej: Agrega más detalle en las recomendaciones. Haz más concisa la sección de conclusiones..."></textarea>
                    <button id="ia-regenerate-btn" class="ia-btn ia-btn--primary">
                        <i class="fa-light fa-arrows-rotate"></i>
                        <span>Regenerar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Send Email Modal --}}
<div id="ia-send-modal" class="ia-modal-overlay d-none">
    <div class="ia-modal">
        <div class="ia-modal__header">
            <p class="ia-modal__title">Enviar Reporte por Correo</p>
            <button type="button" id="ia-send-modal-close" class="ia-modal__close">
                <i class="fa-light fa-xmark"></i>
            </button>
        </div>
        <div class="ia-modal__body">
            <div class="ia-form-group">
                <label class="ia-label">Correo electrónico del destinatario</label>
                <input type="email" id="ia-send-email-input" class="ia-input" placeholder="correo@ejemplo.com">
            </div>
            <p class="ia-send-hint">El reporte también se enviará a tu correo registrado.</p>
        </div>
        <div class="ia-modal__footer">
            <button type="button" id="ia-send-cancel-btn" class="ia-btn ia-btn--secondary">Cancelar</button>
            <button type="button" id="ia-send-confirm-btn" class="ia-btn ia-btn--primary">
                <i class="fa-light fa-paper-plane"></i>
                <span>Enviar</span>
            </button>
        </div>
    </div>
</div>
@endsection
