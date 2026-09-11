<section class="income-items-panel" data-income-items-panel>
    <div class="income-items-panel-header">
        <div>
            <span class="income-section-kicker">Detalle</span>
            <h3>Ítems del ingreso</h3>
        </div>
        <div class="income-items-header-actions">
            <div class="income-items-total">
                <span>Total</span>
                <strong class="input-total-value">$0</strong>
            </div>
            @if($mode === 'create')
                <button id="create-income-button" class="btn income-primary-action income-items-submit"><i class="fa-solid fa-plus"></i> Agregar ingreso</button>
            @endif
        </div>
    </div>
    <div class="table-responsive income-items-table-wrap">
        <table class="table align-middle income-items-table">
            <thead>
                <tr>
                    <th>Licencia / servicio</th>
                    <th class="income-column-number">Valor</th>
                    <th class="income-column-small">Horas</th>
                    <th class="income-column-small">Comisión</th>
                    <th class="income-column-small">Impuesto</th>
                    <th>Descripción</th>
                    <th class="income-column-actions">Acciones</th>
                </tr>
            </thead>
            <tbody class="income-items-body">
                <tr class="income-item-add-row add-row order-licenses-list-item-{{ $mode }} order-licenses-list-item">
                    <td data-label="Licencia / servicio">
                        <select class="form-select input-item-license" name="item-license">
                            <option value="0" selected disabled>Seleccione una licencia</option>
                        </select>
                        <small class="income-item-service input-item-service"></small>
                        <small class="income-item-recurrence input-item-recurrence"></small>
                    </td>
                    <td data-label="Valor"><input type="number" min="0" step="0.01" class="form-control input-item-value" name="input-item-value" value="0"></td>
                    <td data-label="Horas"><input type="number" min="0" class="form-control input-item-hours" name="input-item-hours" value="0"></td>
                    <td data-label="Comisión"><input type="number" min="0" step="0.01" class="form-control input-item-comission" name="input-item-comission" value="0"></td>
                    <td data-label="Impuesto"><span class="income-item-tax input-item-tax">0%</span></td>
                    <td data-label="Descripción">
                        <div class="income-rich-text" data-rich-text>
                            <div class="income-rich-toolbar" data-rich-toolbar><button type="button" data-rich-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button><button type="button" data-rich-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button><button type="button" data-rich-command="insertUnorderedList" title="Lista" aria-label="Lista"><i class="fa-solid fa-list-ul"></i></button><button type="button" data-rich-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button></div>
                            <div class="income-rich-editor input-item-description-editor" contenteditable="true" data-rich-editor data-placeholder="Descripción del ítem"></div>
                            <textarea class="d-none input-item-description" data-rich-plain tabindex="-1"></textarea>
                        </div>
                    </td>
                    <td class="income-item-actions" data-label="Acciones"><button type="button" class="btn income-add-item-button add-license-button" title="Agregar ítem" aria-label="Agregar ítem"><i class="fa-solid fa-plus"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="income-items-empty-state">Aún no hay ítems agregados.</div>
</section>