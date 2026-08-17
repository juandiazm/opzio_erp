<div id="ia-empty-state" class="ia-empty-state">
    <i class="fa-light fa-robot ia-empty-state__icon"></i>
    <p class="ia-empty-state__title">Listo para generar tu reporte</p>
    <p class="ia-empty-state__text">Selecciona un cliente, define el período y sube el archivo exportado desde Meta Ads para comenzar.</p>
</div>

<div id="ia-timeout-state" class="ia-timeout-state d-none">
    <i class="fa-light fa-clock ia-timeout-state__icon"></i>
    <p class="ia-timeout-state__title">El reporte tardó un poco más de lo esperado</p>
    <p class="ia-timeout-state__text">Una vez que esté listo lo podrás ver en el historial de conversaciones.</p>
    <button id="ia-timeout-ok-btn" class="ia-btn ia-btn--primary">
        <span>Entendido</span>
    </button>
</div>

<div id="ia-loading-state" class="ia-loading-state d-none">
    <i class="fa-duotone fa-loader fa-spin-pulse ia-loading-state__icon"></i>
    <p class="ia-loading-state__text">El asistente está generando tu reporte sección por sección...</p>
    <p class="ia-loading-state__hint">Este proceso puede tomar entre 1 y 3 minutos</p>
</div>

<div id="ia-report-state" class="d-none">
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

    <iframe id="ia-report-iframe" class="ia-pdf-iframe" src=""></iframe>

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