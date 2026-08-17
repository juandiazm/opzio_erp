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