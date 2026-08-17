<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="income-list-container" class="scrollable">
        <div id="search-list-container">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
            <div id="state-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="state-list-title">Estado</p>
                <select class="form-select align-self-center" id="state-list-input" aria-label="Default select example" value="-1">
                    <option value="-1" selected>Todas</option>
                    <option value="0">Cotizaciones</option>
                    <option value="1">Rechazadas</option>
                    <option value="2">Aprobadas</option>
                    <option value="3">Pagadas</option>
                    <option value="4">Facturadas</option>
                </select>
            </div>
        </div>
        <table id="income-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
            <thead id="income-list-table-header">
                <tr>
                    <th scope="col" class="columns-identity text-start">Ingreso</th>
                    <th scope="col" class="columns-cycle text-center">Ciclo</th>
                    <th scope="col" class="columns-total text-end">Valor</th>
                    <th scope="col" class="columns-bill text-center">Factura</th>
                    <th scope="col" class="columns-created-at text-center">F. Creación</th>
                    <th scope="col" class="columns-state text-center">Estado</th>
                    <th scope="col" class="columns-actions text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="income-list-table-body">
                
            </tbody>
        </table>
    </div>
    
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>