<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="user-list-container" class="scrollable">
        <!-- filtros -->
        <div id="search-list-container" class="mb-3">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-items-center">
                <p class="mb-0 me-2" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control" placeholder="Buscar..." autocomplete="off">
            </div>
            <div id="search-date-range" class="d-flex align-items-center ms-3">
                <p class="mb-0 me-2" id="search-list-title">Fecha</p>
                <input type="date" id="date-from" class="form-control date-input" value="{{ now()->startOfMonth()->format('Y-m-d') }}" placeholder="Desde">
                <span class="mx-2" style=" font-size: 1.25rem;">/</span>
                <input type="date" id="date-to" class="form-control date-input" value="{{ now()->format('Y-m-d') }}" placeholder="Hasta">
            </div>
            <div id="search-outcome-type" class="outcome-filter-field">
                <label for="outcome-type-filter">Tipo</label>
                <select id="outcome-type-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los tipos">
                    <option value="">Todos los tipos</option>
                </select>
            </div>
            <button type="button" id="toggle-outcome-filters" class="outcome-filter-toggle" aria-expanded="false" aria-controls="outcome-association-filters" title="Mostrar filtros adicionales">
                <i class="fa-solid fa-sliders outcome-filter-toggle-icon" aria-hidden="true"></i>
                <span class="outcome-filter-toggle-label">Más filtros</span>
            </button>
        </div>

        <div id="outcome-association-filters" class="outcome-association-filters" hidden>
            <div class="outcome-filter-field">
                <label for="outcome-provider-filter">Proveedor</label>
                <select id="outcome-provider-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los proveedores">
                    <option value="">Todos los proveedores</option>
                </select>
            </div>
            <div class="outcome-filter-field">
                <label for="outcome-employee-filter">Empleado</label>
                <select id="outcome-employee-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los empleados">
                    <option value="">Todos los empleados</option>
                </select>
            </div>
            <div class="outcome-filter-field">
                <label for="outcome-department-filter">Departamento</label>
                <select id="outcome-department-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los departamentos">
                    <option value="">Todos los departamentos</option>
                </select>
            </div>
            <div class="outcome-filter-field">
                <label for="outcome-user-filter">Usuario</label>
                <select id="outcome-user-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los usuarios">
                    <option value="">Todos los usuarios</option>
                </select>
            </div>
            <div class="outcome-filter-field">
                <label for="outcome-client-filter">Cliente</label>
                <select id="outcome-client-filter" class="form-select outcome-filter js-searchable-dropdown" data-placeholder="Todos los clientes">
                    <option value="">Todos los clientes</option>
                </select>
            </div>
        </div>

        <div id="outcome-total-segment" class="outcome-total-segment" aria-live="polite">
            <div class="outcome-total-copy">
                <span class="outcome-total-label">Total de egresos</span>
                <strong id="outcome-total-amount">$0</strong>
            </div>
            <span id="outcome-total-records" class="outcome-total-records">0 registros</span>
        </div>

        <!-- tabla -->
        <table id="outcome-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
        <thead id="outcome-list-table-header">
            <tr>
            <th scope="col" class="columns-identity text-start">Egreso</th>
            <th scope="col" class="columns-provider text-start">Proveedor</th>
            <th scope="col" class="columns-employee text-start">Empleado</th>
            <th scope="col" class="columns-department text-start">Departamento</th>
            <th scope="col" class="columns-user text-start">Usuario</th>
            <th scope="col" class="columns-client text-start">Cliente</th>
            <th scope="col" class="columns-amount text-end">Monto</th>
            <th scope="col" class="columns-actions text-center">Acciones</th>
            </tr>
        </thead>
        <tbody id="outcome-list-table-body">
           
        </tbody>
        </table>
    </div>

    <ul id="db-pagination" class="pagination pagination-sm justify-content-end"></ul>
</div>