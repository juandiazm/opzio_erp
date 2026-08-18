<div class="tab-pane fade show active contracts-pane" id="nav-list" role="tabpanel" aria-labelledby="nav-list-tab">
    <div id="contracts-list-container" class="scrollable">
        <div class="contracts-filter-bar" role="search">
            <label class="contracts-filter contracts-search-filter" for="contract-search">
                <span>Buscar</span>
                <input type="search" id="contract-search" class="form-control" placeholder="ID, nombre o asunto" autocomplete="off">
            </label>
            <label class="contracts-filter" for="contract-type-filter">
                <span>Tipo</span>
                <select id="contract-type-filter" class="form-select">
                    <option value="">Todos los tipos</option>
                </select>
            </label>
            <label class="contracts-filter" for="contract-status-filter">
                <span>Estado</span>
                <select id="contract-status-filter" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="generated">Generado</option>
                    <option value="pending_signature">En espera de firma</option>
                    <option value="signed">Firmado</option>
                    <option value="expired">Vencido</option>
                    <option value="completed">Finalizado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </label>
        </div>
        <div class="contracts-list-table-scroll">
            <table id="contract-list-table" class="table table-hover table-sm align-middle erp-data-table">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Titular</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th>Envío</th>
                        <th class="contract-signature-column">PDF firmado</th>
                        <th class="text-end contract-actions-column">Acciones</th>
                    </tr>
                </thead>
                <tbody id="contract-list-table-body"></tbody>
            </table>
        </div>
    </div>
    <ul id="contract-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>