@php($association = $association ?? '')
<div class="tab-pane fade associated-outcomes-pane" id="sub-nav-outcomes" role="tabpanel" aria-labelledby="sub-nav-outcomes-tab" data-outcome-association="{{ $association }}" data-outcome-association-id="">
    <div id="associated-outcomes-container" class="associated-outcomes-list">
        <div class="associated-outcomes-toolbar">
            <label for="associated-outcomes-search" class="mb-0">Buscar</label>
            <input type="text" id="associated-outcomes-search" class="form-control" placeholder="Buscar..." autocomplete="off">
        </div>

        <div class="outcome-total-segment associated-outcomes-total" aria-live="polite">
            <div class="outcome-total-copy">
                <span class="outcome-total-label">Total de egresos</span>
                <strong id="associated-outcomes-total-amount">$0</strong>
            </div>
            <span id="associated-outcomes-total-records" class="outcome-total-records">0 registros</span>
        </div>

        <div class="table-responsive">
            <table id="associated-outcomes-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
                <thead>
                    <tr>
                        <th scope="col" class="columns-identity text-start">Egreso</th>
                        <th scope="col" class="columns-provider text-start">Proveedor</th>
                        <th scope="col" class="columns-employee text-start">Empleado</th>
                        <th scope="col" class="columns-department text-start">Departamento</th>
                        <th scope="col" class="columns-user text-start">Usuario</th>
                        <th scope="col" class="columns-client text-start">Cliente</th>
                        <th scope="col" class="columns-amount text-end">Monto</th>
                        <th scope="col" class="columns-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="associated-outcomes-table-body"></tbody>
            </table>
        </div>
    </div>

    <ul id="associated-outcomes-pagination" class="pagination pagination-sm justify-content-end"></ul>
</div>