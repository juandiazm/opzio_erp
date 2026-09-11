<!-- Update tab -->
<div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
    <div id="update-income-container" class="income-form-shell">
        <section class="income-form-header">
            <div class="income-form-title-row">
                <div class="income-title-copy">
                    <span class="income-section-kicker">Documento seleccionado</span>
                    <h2>Editar ingreso</h2>
                    <span id="update-income-summary" class="income-header-summary"></span>
                </div>
                <div class="income-form-header-actions">
                    <div class="state-container">
                        <span class="state-title">Estado</span>
                        <div class="state-input-container">
                            <div class="state-0 update-state state-input selected" value="0"><span>Cotización</span></div>
                            <div class="state-1 update-state state-input" value="1"><span>Rechazada</span></div>
                            <div class="state-2 update-state state-input" value="2"><span>Aprobada</span></div>
                            <div class="state-3 state-input" value="3"><span id="pay-state-btn">Pagada</span></div>
                        </div>
                    </div>
                    <div class="income-document-actions">
                        <button type="button" id="print-income-button" class="btn btn-light" title="Imprimir" aria-label="Imprimir"><i class="fa-solid fa-print"></i></button>
                        <button type="button" id="view-income-document" class="btn btn-light" title="Ver documento" aria-label="Ver documento"><i class="fa-solid fa-eye"></i></button>
                    </div>
                    <button id="update-income-button" class="btn income-primary-action"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                                        <button type="button" id="toggle-income-header" class="btn income-header-toggle" aria-expanded="false" aria-controls="update-income-header-details" title="Mostrar datos del ingreso" aria-label="Mostrar datos del ingreso"><i class="fa-solid fa-chevron-down"></i></button>
                </div>
            </div>

                        <div id="update-income-header-details" class="income-header-details" hidden>
                            <div class="income-form-grid income-update-grid">
                <div class="income-field income-field-client">
                    <label for="update-income-client">Cliente</label>
                    <select id="update-income-client" class="input-client form-select" name="client">
                        <option value="0" selected disabled>Seleccione un cliente</option>
                    </select>
                    <div class="income-client-readiness" data-client-readiness></div>
                </div>
                <div class="income-field">
                    <label for="update-income-identification">Identificación</label>
                    <p id="update-income-identification" class="input-identification income-readonly-value">-</p>
                </div>
                <div class="income-field">
                    <label for="update-income-timely-payment">Pago oportuno</label>
                    <input id="update-income-timely-payment" type="date" class="input-timely-payment form-control" name="timely-payment">
                </div>
                <div class="income-field">
                    <label for="update-income-cutoff-date">Fecha de corte</label>
                    <input id="update-income-cutoff-date" type="date" class="input-cutoff-date form-control" name="cutoff-date">
                </div>
                <div class="income-field income-field-totalize quotation-totalize-container">
                    <label for="update-quotation-totalize">PDF</label>
                    <div class="form-check">
                        <input class="form-check-input input-quotation-totalize" type="checkbox" id="update-quotation-totalize" checked>
                        <label class="form-check-label" for="update-quotation-totalize">Totalizar</label>
                    </div>
                </div>
                <div class="income-field bill-data-container">
                    <label for="update-income-bill-name">Nombre factura</label>
                    <input id="update-income-bill-name" type="text" class="input-bill-name form-control" name="bill-name">
                </div>
                <div class="income-field bill-data-container">
                    <label for="update-income-bill-final-value">Valor pagado</label>
                    <input id="update-income-bill-final-value" type="number" class="input-bill-final-value form-control" name="bill-final-value">
                </div>
                            </div>

                            <div class="income-rich-field">
                <label for="update-income-description-editor">Descripción general</label>
                <div class="income-rich-text" data-rich-text>
                    <div class="income-rich-toolbar" data-rich-toolbar>
                        <button type="button" data-rich-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button>
                        <button type="button" data-rich-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button>
                        <button type="button" data-rich-command="underline" title="Subrayado" aria-label="Subrayado"><i class="fa-solid fa-underline"></i></button>
                        <button type="button" data-rich-command="insertUnorderedList" title="Lista" aria-label="Lista"><i class="fa-solid fa-list-ul"></i></button>
                        <button type="button" data-rich-command="insertOrderedList" title="Lista numerada" aria-label="Lista numerada"><i class="fa-solid fa-list-ol"></i></button>
                        <button type="button" data-rich-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button>
                    </div>
                    <div id="update-income-description-editor" class="income-rich-editor input-description-editor" contenteditable="true" data-rich-editor data-placeholder="Notas para la cotización"></div>
                    <textarea class="d-none input-description" data-rich-plain tabindex="-1"></textarea>
                </div>
                            </div>

                        </div>
        </section>

        @include('erp.incomes.items-table', ['mode' => 'update'])
    </div>
</div>
