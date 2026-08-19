@extends('erp.layouts.app')
@section('component_title', 'SERVIDORES')
@section('erp-app-header')
@vite('resources/js/erp/servers/servers.js')
@vite('resources/sass/erp/servers/servers.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-list-tab">
        <div id="servers-container" class="scrollable">
            <div id="servers-filter-container" class="servers-filter-container">
                <label class="servers-filter servers-search-filter" for="servers-search">
                    <span>Buscar</span>
                    <input type="search" id="servers-search" class="form-control" placeholder="Proyecto, host o ruta..." autocomplete="off">
                </label>
                <label class="servers-filter" for="servers-host-select">
                    <span>Host</span>
                    <select id="servers-host-select" class="form-select">
                        <option value="">Todos</option>
                    </select>
                </label>
                <label class="servers-filter" for="servers-environment-select">
                    <span>Entorno</span>
                    <select id="servers-environment-select" class="form-select">
                        <option value="">Todos</option>
                    </select>
                </label>
                <label class="servers-filter" for="servers-health-select">
                    <span>Estado</span>
                    <select id="servers-health-select" class="form-select">
                        <option value="">Todos</option>
                        <option value="reporting">Reportando</option>
                        <option value="stale">Sin reporte reciente</option>
                        <option value="no_data">Sin datos</option>
                    </select>
                </label>
                <label class="servers-filter" for="servers-notifications-select">
                    <span>Notificaciones</span>
                    <select id="servers-notifications-select" class="form-select">
                        <option value="">Todos</option>
                        <option value="active">Activas</option>
                        <option value="inactive">Inactivas</option>
                    </select>
                </label>
                <label class="servers-filter" for="servers-range-select">
                    <span>Rango</span>
                    <select id="servers-range-select" class="form-select">
                        <option value="15">15 min</option>
                        <option value="60">1 h</option>
                        <option value="360">6 h</option>
                        <option value="1440" selected>24 h</option>
                        <option value="10080">7 d</option>
                    </select>
                </label>
                <div class="servers-filter-actions">
                    <button type="button" id="servers-query-button" class="btn btn-primary" title="Consultar datos">
                        <i class="fa-light fa-filter"></i>
                        <span>Consultar</span>
                    </button>
                    <button type="button" id="servers-export-button" class="btn btn-secondary" title="Exportar a Excel">
                        <i class="fa-light fa-file-excel"></i>
                        <span>Excel</span>
                    </button>
                </div>
            </div>
            <div id="servers-status" class="servers-status" role="status" aria-live="polite"></div>
            <div id="servers-overview" class="servers-overview"></div>
            <div class="servers-table-wrap">
                <table id="servers-projects-table" class="table table-hover table-sm align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col" data-sort-key="name" aria-sort="descending">
                                <button type="button" class="servers-sort-button" data-sort-label="Proyecto" title="Ordenar por proyecto">
                                    <span>Proyecto</span>
                                    <i class="fa-light fa-sort-down servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="host" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Host / entorno" title="Ordenar por host y entorno">
                                    <span>Host / entorno</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="health" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Estado" title="Ordenar por estado">
                                    <span>Estado</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="requests_per_minute" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Tráfico" title="Ordenar por tráfico">
                                    <span>Tráfico</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="availability_percent" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Disponibilidad" title="Ordenar por disponibilidad">
                                    <span>Disponibilidad</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="p95_ms" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Latencia" title="Ordenar por latencia">
                                    <span>Latencia</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="status_5xx" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Errores" title="Ordenar por errores">
                                    <span>Errores</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="cpu_percent" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="CPU / RAM" title="Ordenar por CPU y RAM">
                                    <span>CPU / RAM</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="fpm_listen_queue" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="FPM" title="Ordenar por FPM">
                                    <span>FPM</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" data-sort-key="storage_total_bytes" aria-sort="none">
                                <button type="button" class="servers-sort-button" data-sort-label="Storage" title="Ordenar por storage">
                                    <span>Storage</span>
                                    <i class="fa-light fa-sort servers-sort-indicator" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="servers-projects-table-body"></tbody>
                </table>
            </div>
        </div>
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
</div>
@include('erp.servers.project-config-modal')
@endsection
