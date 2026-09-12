<!-- Create Tab -->
<div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
    <div id="create-income-container" class="income-form-shell">
        <div class="income-editor-column">
        <section class="income-form-header">
            <div class="income-form-title-row">
                <div>
                    <span class="income-section-kicker">Nuevo documento</span>
                    <h2>Crear ingreso</h2>
                </div>
                <div class="state-container">
                    <span class="state-title">Estado</span>
                    <div class="state-input-container">
                        <div class="state-0 state-input selected" value="0"><span>Cotización</span></div>
                        <div class="state-1 state-input" value="1"><span>Rechazada</span></div>
                        <div class="state-2 state-input" value="2"><span>Aprobada</span></div>
                        <div class="state-3 state-input" value="3"><span>Pagada</span></div>
                    </div>
                </div>
            </div>

            <div class="income-form-grid">
                <div class="income-field income-field-client">
                    <label for="create-income-client">Cliente</label>
                    <select id="create-income-client" class="input-client form-select" name="client">
                        <option value="0" selected disabled>Seleccione un cliente</option>
                    </select>
                    <div class="income-client-readiness" data-client-readiness></div>
                </div>
                <div class="income-field">
                    <label for="create-income-identification">Identificación</label>
                    <p id="create-income-identification" class="input-identification income-readonly-value">-</p>
                </div>
                <div class="income-field">
                    <label for="create-income-timely-payment">Pago oportuno</label>
                    <input id="create-income-timely-payment" type="date" class="input-timely-payment form-control" name="timely-payment">
                </div>
                <div class="income-field">
                    <label for="create-income-cutoff-date">Fecha de corte</label>
                    <input id="create-income-cutoff-date" type="date" class="input-cutoff-date form-control" name="cutoff-date">
                </div>
                <div class="income-field income-field-totalize quotation-totalize-container">
                    <label for="create-quotation-totalize">PDF</label>
                    <div class="form-check">
                        <input class="form-check-input input-quotation-totalize" type="checkbox" id="create-quotation-totalize" checked>
                        <label class="form-check-label" for="create-quotation-totalize">Totalizar</label>
                    </div>
                </div>
            </div>

            <div class="income-rich-field">
                <label for="create-income-description-editor">Descripción general</label>
                <div class="income-rich-text" data-rich-text>
                    <div class="income-rich-toolbar" data-rich-toolbar>
                        <button type="button" data-rich-command="bold" title="Negrita" aria-label="Negrita"><i class="fa-solid fa-bold"></i></button>
                        <button type="button" data-rich-command="italic" title="Cursiva" aria-label="Cursiva"><i class="fa-solid fa-italic"></i></button>
                        <button type="button" data-rich-command="underline" title="Subrayado" aria-label="Subrayado"><i class="fa-solid fa-underline"></i></button>
                        <button type="button" data-rich-command="insertUnorderedList" title="Lista" aria-label="Lista"><i class="fa-solid fa-list-ul"></i></button>
                        <button type="button" data-rich-command="insertOrderedList" title="Lista numerada" aria-label="Lista numerada"><i class="fa-solid fa-list-ol"></i></button>
                        <button type="button" data-rich-command="removeFormat" title="Limpiar formato" aria-label="Limpiar formato"><i class="fa-solid fa-eraser"></i></button>
                    </div>
                    <div id="create-income-description-editor" class="income-rich-editor input-description-editor" contenteditable="true" data-rich-editor data-placeholder="Notas para la cotización"></div>
                    <textarea class="d-none input-description" data-rich-plain tabindex="-1"></textarea>
                </div>
            </div>
        </section>

        @include('erp.incomes.items-table', ['mode' => 'create'])
        </div>
        @include('erp.incomes.preview')
    </div>
</div>
