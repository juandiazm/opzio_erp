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
                <input type="date" id="date-from" class="form-control date-input" placeholder="Desde">
                <span class="mx-2" style=" font-size: 1.25rem;">/</span>
                <input type="date" id="date-to" class="form-control date-input" placeholder="Hasta">
            </div>
        </div>

        <!-- tabla -->
        <table id="outcome-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
        <thead id="outcome-list-table-header">
            <tr>
            <th scope="col" class="columns-identity text-start">Egreso</th>
            <th scope="col" class="columns-detail text-start">Detalle</th>
            <th scope="col" class="columns-associations text-start">Asociaciones</th>
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