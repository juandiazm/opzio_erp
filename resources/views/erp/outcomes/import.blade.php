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
                    <label class="form-label" for="import-source">Fuente</label>
                    <select id="import-source" name="source" class="form-select" required>
                        <option value="bold">Bold</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="import-file-input">Archivo CSV</label>
                    <input type="file" id="import-file-input" name="import-file" accept=".csv,text/csv" class="form-control" required>
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