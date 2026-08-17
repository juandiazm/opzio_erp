<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="license-list-container" class="scrollable">
        <div id="search-list-container">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
            <div id="state-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="state-list-title">Estado</p>
                <select class="form-select align-self-center" id="state-list-input" aria-label="Default select example">
                    <option value="">Todas</option>
                    <option value="1" selected>Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
        </div>
        <table id="license-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
            <thead id="license-list-table-header">
                <tr>
                    <th scope="col" class="columns-identity text-start">Licencia</th>
                    <th scope="col" class="columns-service text-start">Servicio</th>
                    <th scope="col" class="columns-value text-end">Valor</th>
                    <th scope="col" class="columns-validity text-center">Vigencia</th>
                    <th scope="col" class="columns-state text-center">Estado</th>
                    <th scope="col" class="columns-actions text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="license-list-table-body">
            </tbody>
        </table>
    </div>
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>