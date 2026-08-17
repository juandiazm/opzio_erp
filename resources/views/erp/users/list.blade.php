<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="user-list-container" class="scrollable">
        <div id="search-list-container" class="justify-content-center">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
        </div>
        <table id="user-list-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
            <thead id="user-list-table-header">
                <tr>
                    <th scope="col" class="columns-identity text-start">Nombre</th>
                    <th scope="col" class="columns-username text-center">Usuario</th>
                    <th scope="col" class="columns-identification text-center">Identificación</th>
                    <th scope="col" class="columns-email text-left">Correo</th>
                    <th scope="col" class="columns-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="user-list-table-body">
            </tbody>
        </table>
    </div>
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>