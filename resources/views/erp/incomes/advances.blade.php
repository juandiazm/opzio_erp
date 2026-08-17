<!-- Advances Modal -->
<div id="advances-modal">
    <div id="advances-modal-container">
        <i class="fa-solid fa-times" id="close-advances-modal"></i>
        <div id="advances-modal-header">
            <h1 id="advances-modal-title">Gestión de Abonos</h1>
            <div id="advances-modal-income-info">
                <p><strong>Ingreso:</strong> <span id="advances-modal-income-id"></span></p>
                <p><strong>Cliente:</strong> <span id="advances-modal-income-client"></span></p>
            </div>
            <div id="advances-modal-summary">
                <div class="summary-item">
                    <span class="summary-label">Total Ingreso:</span>
                    <span class="summary-value" id="advances-modal-income-total">$0</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Abonos:</span>
                    <span class="summary-value total-advances" id="advances-modal-total-advances">$0</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Saldo Pendiente:</span>
                    <span class="summary-value balance-pending" id="advances-modal-balance-pending">$0</span>
                </div>
            </div>
        </div>
        
        <div id="advances-modal-content">
            <!-- Advance Form -->
            <div id="advance-form-container" style="display: none;">
                <h2 id="advance-form-title">Agregar Abono</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="advance-form-amount">Monto *</label>
                            <input type="number" class="form-control" id="advance-form-amount" placeholder="Ingrese el monto" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="advance-form-date">Fecha de Pago *</label>
                            <input type="date" class="form-control" id="advance-form-date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="advance-form-method">Método de Pago</label>
                            <select class="form-control" id="advance-form-method">
                                <option value="">Seleccione...</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="advance-form-reference">Referencia</label>
                            <input type="text" class="form-control" id="advance-form-reference" placeholder="Número de referencia">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="advance-form-notes">Notas</label>
                            <textarea class="form-control" id="advance-form-notes" rows="3" placeholder="Notas adicionales"></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn btn-secondary" id="cancel-advance-button">Cancelar</button>
                    <button class="btn btn-primary" id="save-advance-button">Guardar</button>
                </div>
            </div>
            
            <!-- Advances List -->
            <div id="advances-list-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Listado de Abonos</h2>
                    <button class="btn btn-success" id="create-advance-button">
                        <i class="fa-solid fa-plus"></i> Agregar Abono
                    </button>
                </div>
                <table class="table table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">Fecha</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Método</th>
                            <th class="text-center">Referencia</th>
                            <th class="text-center">Creado por</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="advances-list-body">
                        <tr><td colspan="6" class="text-center">No hay abonos registrados</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>