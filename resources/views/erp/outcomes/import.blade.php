<div id="import-btn-container">
    <i class="fa-solid fa-file-excel"></i>
    <span id="import-title">Importación Masiva</span>
</div>
<div id="import-form-container">
    <i class="fa-thin fa-file-excel import-form-icon"></i>
    <h3 id="import-form-title">¿desea iniciar la importación masiva de egresos desde el extracto bancario?</h3>
    <h5 id="import-form-subtitle">Recuerde que esta acción tendrá como consecuencia la actualización automática del balance en el sistema.</h5>
    <p id="import-form-description">Por favor, asegúrese de tener el archivo del extracto bancario listo y verificado para garantizar la precisión de los datos importados. Una vez confirmada la importación, las transacciones del extracto bancario se integrarán en el sistema y reflejarán cambios en el balance.</p>
    <input type="file" id="import-file-input" name="import-file" accept=".xlsx, .xls" class="form-control" required>
    <div id="import-btns-container">
        <button id="import-cancel-btn" class="btn">Cancelar</button>
        <button id="import-confirm-btn" class="btn">Confirmar</button>
    </div>
</div>