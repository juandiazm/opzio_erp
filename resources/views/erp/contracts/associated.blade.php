<div class="tab-pane fade" id="sub-nav-contracts" role="tabpanel" aria-labelledby="sub-nav-contracts-tab" data-contractable-type="{{ $contractableType }}">
    <div class="contracts-associated-header d-flex justify-content-between align-items-center">
        <h3>Contratos asociados</h3>
        <a class="btn btn-sm btn-secondary" href="/admin/contracts"><i class="fa-solid fa-file-signature"></i> Administrar contratos</a>
    </div>
    <div class="table-responsive">
        <table id="associated-contracts-table" class="table table-hover table-sm align-middle w-100">
            <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="associated-contracts-table-body"></tbody>
        </table>
    </div>
</div>