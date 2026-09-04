<div class="tab-pane fade show active" id="tenders-discovery" role="tabpanel" aria-labelledby="tenders-discovery-tab">
    <div id="licitaciones-container" class="scrollable">
        <div id="licitaciones-filter-container" class="licitaciones-filter-container" role="search">
            <label class="licitaciones-filter licitaciones-search-filter" for="licitaciones-search">
                <span>Buscar</span>
                <input type="search" id="licitaciones-search" class="form-control" placeholder="Proceso, entidad o palabra clave" autocomplete="off">
            </label>
            <label class="licitaciones-filter" for="licitaciones-status-filter">
                <span>Estado</span>
                <select id="licitaciones-status-filter" class="form-select">
                    <option value="">Todos</option>
                    <option value="open">Abiertas</option>
                    <option value="updated">Actualizadas</option>
                    <option value="closed">Cerradas</option>
                </select>
            </label>
            <label class="licitaciones-filter" for="licitaciones-eligibility-filter">
                <span>Aptitud</span>
                <select id="licitaciones-eligibility-filter" class="form-select">
                    <option value="">Todas</option>
                    <option value="probably_fit">Probablemente aptas</option>
                    <option value="requires_validation">Requieren validacion</option>
                    <option value="high_risk">Riesgo alto</option>
                    <option value="unknown">Sin informacion</option>
                </select>
            </label>
            <label class="licitaciones-filter" for="licitaciones-confidence-filter">
                <span>Confianza</span>
                <select id="licitaciones-confidence-filter" class="form-select">
                    <option value="">Todas</option>
                    <option value="high">Alta</option>
                    <option value="medium">Media</option>
                    <option value="low">Baja</option>
                </select>
            </label>
            <div class="licitaciones-filter-actions">
                <button type="button" id="licitaciones-query-button" class="btn btn-primary" title="Consultar resultados">
                    <i class="fa-light fa-filter" aria-hidden="true"></i>
                    <span>Consultar</span>
                </button>
                <button type="button" id="licitaciones-sync-button" class="btn btn-outline-primary" title="Actualizar datos desde SECOP">
                    <i class="fa-light fa-arrows-rotate" aria-hidden="true"></i>
                    <span>Actualizar SECOP</span>
                </button>
            </div>
        </div>
        <div class="licitaciones-summary" aria-live="polite">
            <div id="licitaciones-status" class="licitaciones-status" role="status"></div>
            <div id="licitaciones-sync-status" class="licitaciones-sync-status"></div>
            <div id="licitaciones-overview" class="licitaciones-overview"></div>
        </div>
        <div id="licitaciones-list" class="licitaciones-list" role="list"></div>
    </div>
    <ul id="licitaciones-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>
