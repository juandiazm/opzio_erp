<!-- Tab List -->
<div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
    <div id="client-list-container" class="scrollable">
        <div id="search-list-container" class="justify-content-center">
            <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                <p class="align-self-center" id="search-list-title">Buscar</p>
                <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
            </div>
        </div>
        <table id="client-list-table" class="table table-hover table-sm align-middle w-100">
            <thead id="client-list-table-header">
                <tr>
                    <th scope="col" class="columns-id text-left">ID</th>
                    <th scope="col" class="columns-logo text-center">Logo</th>
                    <th scope="col" class="columns-identification text-left">Identificación</th>
                    <th scope="col" class="columns-client text-left">Cliente</th>
                    <th scope="col" class="columns-state text-center">Estado</th>
                    <th scope="col" class="columns-phone text-center">Teléfono</th>
                    <th scope="col" class="columns-email text-left email-col">Correo</th>
                    <th scope="col" class="columns-license text-center">Licencias</th>
                    <th scope="col" class="columns-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="client-list-table-body">
            </tbody>
        </table>
    </div>
    <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>

    <!-- Botón flotante de sincronización -->
    <button id="sync-siigo-btn" class="btn btn-primary rounded-circle position-fixed" style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1000;">
        <i class="fas fa-sync"></i>
    </button>

    <!-- Modal de resultados de sincronización -->
    <div class="modal fade" id="syncResultModal" tabindex="-1" aria-labelledby="syncResultModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content sync-result-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncResultModalLabel">Resultado de Sincronización</h5>
                    <button type="button" class="btn-close" id="syncResultCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="syncResultMessage" role="status" aria-live="polite">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="syncResultCloseFooterBtn" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>