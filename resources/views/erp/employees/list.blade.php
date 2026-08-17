<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="employee-list-container" class="scrollable">
        <div id="search-list-container" class="justify-content-center">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
        </div>
        <table id="employee-list-table" class="table table-hover table-sm align-middle w-100">
            <thead id="employee-list-table-header">
                <tr>
                    <th scope="col" class="columns-id text-left">ID</th>
                    <th scope="col" class="columns-photo text-center">Foto</th>
                    <th scope="col" class="columns-identification text-start">Identificación</th>
                    <th scope="col" class="columns-name text-start">Nombre</th>
                    <th scope="col" class="columns-department text-start">Departamento</th>
                    <th scope="col" class="columns-position text-start">Cargo</th>
                    <th scope="col" class="columns-email text-start">Correo E.</th>
                    <th scope="col" class="columns-state text-center">Estado</th>
                    <th scope="col" class="columns-actions text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="employee-list-table-body">
            </tbody>
        </table>
    </div>
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
</div>