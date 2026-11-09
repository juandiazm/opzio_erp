<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="income-list-container" class="scrollable">
        <div id="search-list-container">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
            <div id="state-list-input-contaner" class="dropdown align-self-center">
                <button
                    class="income-state-filter-toggle"
                    id="income-state-filter-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-controls="income-state-filter-menu"
                    aria-label="Filtrar por estado"
                >
                    <span class="income-state-filter-copy">
                        <span class="income-state-filter-title">Estado</span>
                        <span class="income-state-filter-summary" id="income-state-filter-summary">Todos los estados</span>
                    </span>
                    <span class="income-state-filter-total" id="income-state-filter-total-all">$0</span>
                    <i class="fa-solid fa-chevron-down income-state-filter-chevron" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu income-state-filter-menu" id="income-state-filter-menu" aria-labelledby="income-state-filter-toggle">
                    <div class="income-state-filter-menu-header">
                        <span>Filtrar por estado</span>
                        <button type="button" class="income-state-filter-select-all" id="income-state-filter-select-all">
                            <i class="fa-solid fa-check-double" aria-hidden="true"></i> Seleccionar todos
                        </button>
                    </div>
                    <label class="income-state-filter-option">
                        <input class="form-check-input income-state-filter" type="checkbox" value="0" checked>
                        <span class="erp-status income-state-status status-state-0">
                            <i class="fa-solid fa-file-pen erp-status-icon" aria-hidden="true"></i>
                            <span class="erp-status-label">Cotización</span>
                        </span>
                        <span class="income-state-filter-total" id="income-state-filter-total-0">$0</span>
                    </label>
                    <label class="income-state-filter-option">
                        <input class="form-check-input income-state-filter" type="checkbox" value="1" checked>
                        <span class="erp-status income-state-status status-state-1">
                            <i class="fa-solid fa-circle-xmark erp-status-icon" aria-hidden="true"></i>
                            <span class="erp-status-label">Rechazada</span>
                        </span>
                        <span class="income-state-filter-total" id="income-state-filter-total-1">$0</span>
                    </label>
                    <label class="income-state-filter-option">
                        <input class="form-check-input income-state-filter" type="checkbox" value="2" checked>
                        <span class="erp-status income-state-status status-state-2">
                            <i class="fa-solid fa-circle-check erp-status-icon" aria-hidden="true"></i>
                            <span class="erp-status-label">Aprobada</span>
                        </span>
                        <span class="income-state-filter-total" id="income-state-filter-total-2">$0</span>
                    </label>
                    <label class="income-state-filter-option">
                        <input class="form-check-input income-state-filter" type="checkbox" value="3" checked>
                        <span class="erp-status income-state-status status-state-3">
                            <i class="fa-solid fa-money-bill-wave erp-status-icon" aria-hidden="true"></i>
                            <span class="erp-status-label">Pagada</span>
                        </span>
                        <span class="income-state-filter-total" id="income-state-filter-total-3">$0</span>
                    </label>
                    <label class="income-state-filter-option">
                        <input class="form-check-input income-state-filter" type="checkbox" value="4" checked>
                        <span class="erp-status income-state-status status-state-4">
                            <i class="fa-solid fa-file-invoice-dollar erp-status-icon" aria-hidden="true"></i>
                            <span class="erp-status-label">Facturada</span>
                        </span>
                        <span class="income-state-filter-total" id="income-state-filter-total-4">$0</span>
                    </label>
                </div>
            </div>
            <div id="due-status-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="due-status-list-title">Vencimiento</p>
                <select class="form-select align-self-center" id="due-status-list-input" aria-label="Filtrar por vencimiento">
                    <option value="all" selected>Todos</option>
                    <option value="overdue">Vencidos</option>
                    <option value="due_soon">Por vencer (7 días)</option>
                </select>
            </div>
        </div>
        <table id="income-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
            <thead id="income-list-table-header">
                <tr>
                    <th scope="col" class="columns-identity text-start" data-sort-key="client_name" data-sort-label="Ingreso" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="client_name" aria-label="Ordenar por ingreso">
                            <span>Ingreso</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-cycle text-center" data-sort-key="cutoff_date" data-sort-label="Fecha de corte" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="cutoff_date" aria-label="Ordenar por fecha de corte">
                            <span>Fechas clave</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-total text-end" data-sort-key="total" data-sort-label="Valor" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="total" aria-label="Ordenar por valor">
                            <span>Valor</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-bill text-center" data-sort-key="bill_name" data-sort-label="Factura" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="bill_name" aria-label="Ordenar por factura">
                            <span>Factura</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-payment-date text-center" data-sort-key="payment_date" data-sort-label="Fecha de pago" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="payment_date" aria-label="Ordenar por fecha de pago">
                            <span>F. Pago</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-aging text-center" data-sort-key="aging" data-sort-label="Mora / corte" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="aging" aria-label="Ordenar por mora y fecha de corte">
                            <span>Mora / corte</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-created-at text-center" data-sort-key="created_at" data-sort-label="Fecha de creación" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="created_at" aria-label="Ordenar por fecha de creación">
                            <span>F. Creación</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-state text-center" data-sort-key="state" data-sort-label="Estado" aria-sort="none">
                        <button type="button" class="income-sort-button" data-sort-by="state" aria-label="Ordenar por estado">
                            <span>Estado</span><i class="fa-solid fa-sort income-sort-indicator" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th scope="col" class="columns-actions text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="income-list-table-body">
                
            </tbody>
        </table>
    </div>
    
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>