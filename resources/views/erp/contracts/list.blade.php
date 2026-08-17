<div class="tab-pane fade show active contracts-pane" id="nav-list" role="tabpanel" aria-labelledby="nav-list-tab">
    <div id="contracts-list-container" class="scrollable">
        <div class="contracts-filter-bar">
            <input type="search" id="contract-search" class="form-control" placeholder="Buscar por ID, nombre o asunto" aria-label="Buscar contratos">
            <select id="contract-type-filter" class="form-select" aria-label="Filtrar por tipo">
                <option value="">Todos los tipos</option>
            </select>
            <select id="contract-status-filter" class="form-select" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="draft">Borrador</option>
                <option value="generated">Generado</option>
                <option value="sent">Enviado</option>
                <option value="signed">Firmado</option>
                <option value="expired">Vencido</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
        <table id="contract-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Titular</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="contract-list-table-body"></tbody>
        </table>
    </div>
    <ul id="contract-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>