<button type="button" id="import-btn-container" title="Importar egresos" aria-label="Importar egresos">
    <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
</button>
<div id="import-form-container" aria-hidden="true">
    <form id="import-form" enctype="multipart/form-data">
        @csrf
        <div class="outcome-import-modal" role="dialog" aria-modal="true" aria-labelledby="import-form-title">
            <div class="outcome-import-header">
                <h2 id="import-form-title">Importar egresos</h2>
                <button type="button" id="import-cancel-btn" class="outcome-import-close" title="Cerrar" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="outcome-import-fields">
                <div>
                    <label class="form-label" for="import-format">Formato de importación</label>
                    <select id="import-format" name="import-format" class="form-select" required>
                        <option value="csv">Bold (CSV)</option>
                        <option value="paste">Bold (copiar - pegar)</option>
                    </select>
                </div>
                <input type="hidden" name="source" value="bold">
                <div id="import-file-field">
                    <label class="form-label" for="import-file-input">Archivo CSV</label>
                    <input type="file" id="import-file-input" name="import-file" accept=".csv,text/csv" class="form-control" required>
                </div>
                <div id="import-paste-field" hidden>
                    <label class="form-label" for="import-paste-text">Datos copiados de Bold</label>
                    <textarea id="import-paste-text" class="form-control" rows="9" placeholder="Copia el listado de movimientos de Bold y pégalo aquí." aria-describedby="import-paste-help"></textarea>
                    <small id="import-paste-help" class="form-text">Se admiten los bloques de movimientos de Bold y tablas copiadas. Se incluyen compras, comisiones, impuestos, pagos y transferencias enviadas; se omiten abonos, transferencias recibidas y reembolsos.</small>
                </div>
            </div>
            <div id="import-btns-container">
                <button type="button" id="import-cancel-action" class="btn btn-light">Cancelar</button>
                <button type="button" id="import-confirm-btn" class="btn btn-primary">Importar</button>
            </div>
        </div>
    </form>
</div>
    </div>
</div>