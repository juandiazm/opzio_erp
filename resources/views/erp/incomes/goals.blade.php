<!-- Income Goals Tab -->
<div class="tab-pane fade income-goals-pane" id="nav-goals" role="tabpanel" aria-labelledby="nav-goals-tab">
    <div class="income-goal-form-section income-goal-collapsible-form">
        <div class="income-goal-form-header">
            <h3>Nueva meta de ingreso</h3>
            <button type="button" id="income-goal-toggle" class="income-goal-toggle" aria-label="Agregar meta de ingreso" title="Agregar meta de ingreso" aria-expanded="false" aria-controls="income-goal-form-body">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
        </div>
        <div id="income-goal-form-body" class="income-goal-form-body" hidden>
            <div class="income-goal-inline-form">
                <div class="income-goal-field">
                    <label for="income-goal-target-amount">Monto objetivo</label>
                    <input type="number" id="income-goal-target-amount" class="form-control" min="0.01" step="0.01" placeholder="Ingrese el monto">
                </div>
                <div class="income-goal-field">
                    <label for="income-goal-frequency-months">Frecuencia de recaudo (cada X meses)</label>
                    <input type="number" id="income-goal-frequency-months" class="form-control" min="1" step="1" placeholder="Ej. 1 mensual, 2 bimestral, 12 anual">
                </div>
                <div class="income-goal-field">
                    <label for="income-goal-start-date">Inicio del rango</label>
                    <input type="date" id="income-goal-start-date" class="form-control">
                </div>
                <div class="income-goal-field">
                    <label for="income-goal-end-date">Fin del rango</label>
                    <input type="date" id="income-goal-end-date" class="form-control">
                </div>
                <div class="income-goal-form-actions">
                    <button type="button" class="btn btn-secondary" id="income-goal-save"><i class="fa-solid fa-plus"></i> Agregar</button>
                    <button type="button" class="btn btn-link d-none" id="income-goal-cancel">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="income-goals-table" class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th class="text-start">Monto objetivo</th>
                    <th class="text-center">Frecuencia</th>
                    <th class="text-center">Rango de comparación</th>
                    <th class="text-center">Creada</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="income-goals-table-body">
                <tr><td colspan="5" class="text-center">No hay metas registradas</td></tr>
            </tbody>
        </table>
    </div>
</div>