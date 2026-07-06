@extends('erp.layouts.app')
@section('component_title', 'INGRESOS')
@section('erp-app-header')
@vite('resources/js/erp/incomes/incomes.js')
@vite('resources/js/erp/traceability.js')
<!-- Styles -->
@vite('resources/sass/erp/incomes/incomes.scss')
@vite('resources/sass/erp/traceability.scss')
@endsection
@section('erp-app-content')
<nav>
    <div class="nav nav-tabs principal-nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-list-tab" data-bs-toggle="tab" data-bs-target="#nav-list" type="button" role="tab" aria-controls="nav-list" aria-selected="true">Base de Datos</button>
        <button class="nav-link" id="nav-create-tab" data-bs-toggle="tab" data-bs-target="#nav-create" type="button" role="tab" aria-controls="nav-create" aria-selected="false">Crear Documento</button>
        <button class="nav-link" id="nav-traceability-tab" data-bs-toggle="tab" data-bs-target="#nav-traceability" type="button" role="tab" aria-controls="nav-traceability" aria-selected="false">Trazabilidad</button>
        <button class="nav-link d-none" id="nav-update-tab" data-bs-toggle="tab" data-bs-target="#nav-update" type="button" role="tab" aria-controls="nav-update" aria-selected="false">Actualizar</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent">
    <!-- Tab List -->
    <div class="tab-pane fade show active" id="nav-list" role="tabpanel" aria-labelledby="nav-home-tab">
        <div id="income-list-container" class="scrollable">
            <div id="search-list-container">
                <div id="search-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="search-list-title">Buscar</p>
                    <input type="text" id="search-list-input" class="form-control align-self-center" autofocus placeholder="Buscar..." autofocus>
                </div>
                <div id="state-list-input-contaner" class="d-flex justify-content-center align-self-center">
                    <p class="align-self-center" id="state-list-title">Estado</p>
                    <select class="form-select align-self-center" id="state-list-input" aria-label="Default select example" value="-1">
                        <option value="-1" selected>Todas</option>
                        <option value="0">Cotizaciones</option>
                        <option value="1">Rechazadas</option>
                        <option value="2">Aprobadas</option>
                        <option value="3">Pagadas</option>
                        <option value="4">Facturadas</option>
                    </select>
                </div>
            </div>
            <table id="income-list-table" class="table table-hover table-sm align-middle w-100">
                <thead id="income-list-table-header">
                    <tr>
                        <th scope="col" class="columns-id text-left">ID</th>
                        <th scope="col" class="columns-client text-left">Cliente</th>
                        <th scope="col" class="columns-timely-payment text-center">Fecha Pago O.</th>
                        <th scope="col" class="columns-cutoff-date text-center">Fecha Corte</th>
                        <th scope="col" class="columns-total text-end">Valor</th>
                        <th scope="col" class="columns-bill text-center">Factura</th>
                        <th scope="col" class="columns-created-at text-center">F. Creación</th>
                        <th scope="col" class="columns-bill text-center">Factura E.</th>
                        <th scope="col" class="columns-state text-center">Estado</th>
                        <th scope="col" class="columns-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="income-list-table-body">
                    
                </tbody>
            </table>
        </div>
        
        <ul id="db-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>
    </div>
    <!-- Create Tab -->
    <div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-profile-tab">
        <div id="create-income-container">
            <div class="row header-income-container">
                <div class="col-12 d-flex justify-content-center state-container" >
                    <label class="form-check-label align-self-center state-title"  for="state">Estado</label>
                    <div  class="state-input-container d-flex justify-content-start align-self-center">
                        <div class="state-0 state-input selected" value="0">
                            <label class="state-input-label" for="state-0">Cotización</label>
                        </div>
                        <div class="state-1 state-input" value="1">
                            <label class="state-input-label" for="state-1">Rechazada</label>
                        </div>
                        <div class="state-2 state-input" value="2">
                            <label class="state-input-label" for="state-2">Aprobada</label>
                        </div>
                        <div class="state-3 state-input" value="3">
                            <label class="state-input-label" for="state-3">Pagada</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-client">Cliente</span>
                        <select class="input-client form-select align-self-center input-value"  name="client">
                            <option value="0" selected disabled>Seleccione un cliente</option>
                        </select>
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-identification">Identificación</span>
                        <p class="input-identification form-control input-value" ></p>
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-timely-payment">Pago oportuno</span>
                        <input type="date" class="input-timely-payment form-control input-value"  name="timely-payment">
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-cutoff-date">Fecha de corte</span>
                        <input type="date" class="input-cutoff-date form-control input-value"  name="cutoff-date">
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-total-value">Valor total</span>
                        <p class="input-total-value input-value" ><strong>$0</strong></p>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                    <div class="input-container d-flex flex-column justify-content-center description-container">
                        <span class="input-title align-self-start" for="input-description">Descripción</span>
                        <textarea class="input-description form-control input-value"  name="description"></textarea>
                    </div>
                </div>
            </div>
            <ul class="income-licenses-list">
                <li class="add-row order-licenses-list-item-create order-licenses-list-item row">
                    <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-license">Licencia</span>
                            <select class="form-select align-self-center input-value input-item-license" name="item-license">
                                <option value="0" selected disabled>Seleccione una licencia</option>
                            </select>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-service">Servicio</span>
                            <p class="form-control input-value input-item-service"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-recurrence">Recurrencia</span>
                            <p class="form-control input-value input-item-recurrence"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-value">Valor</span>
                            <input type="number" class="form-control input-value input-item-value" name="input-item-value">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-hours">Horas</span>
                            <input type="number" min="0" class="form-control input-value input-item-hours" name="input-item-value" value="0">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-employee">Empleado</span>
                            <p class="form-control input-value input-item-employee"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-comission">Comisión(%)</span>
                            <input type="number" class="form-control input-value input-item-comission" name="input-item-value">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-comission"></span>
                            <p class="input-value input-item-total-comission">$0</p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-tax">Impuesto</span>
                            <p class="input-value align-self-center input-item-tax" name="item-tax">0%</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                        <div class="input-container d-flex flex-column justify-content-center description-container">
                            <span class="input-title align-self-start" for="input-item-description">Descripción</span>
                            <textarea class="form-control input-value input-item-description" name="description"></textarea>
                        </div>
                    
                    </div>
                    <i class="fas fa-plus-circle add-license-button"></i>
                </li>
            </ul>
            <button id="create-income-button" class="btn">Agregar</button>
        </div>
        
        
    </div>
    <!-- Update tab -->
    <div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-profile-tab">
        <div id="update-income-container">
            <div class="row header-income-container">
                <div class="col-12 d-flex justify-content-around state-container" >
                    <div class="d-flex justify-content-center">
                        <label class="form-check-label align-self-center state-title"  for="state">Estado</label>
                        <div  class="state-input-container d-flex justify-content-start align-self-center">
                            <div class="state-0 update-state state-input selected" value="0">
                                <label class="state-input-label" for="state-0">Cotización</label>
                            </div>
                            <div class="state-1 update-state state-input" value="1">
                                <label class="state-input-label" for="state-1">Rechazada</label>
                            </div>
                            <div class="state-2 update-state state-input" value="2">
                                <label class="state-input-label" for="state-2">Aprobada</label>
                            </div>
                            <div class="state-3 state-input" value="3">
                                <label class="state-input-label" id="pay-state-btn" for="state-3">Pagada</label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fa-solid align-self-center fa-print" id="print-income-button"></i>
                        <i class="fa-solid align-self-center fa-eye" id="view-income-document"></i>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-client">Cliente</span>
                        <select class="input-client form-select align-self-center input-value"  name="client">
                            <option value="0" selected disabled>Seleccione un cliente</option>
                        </select>
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-identification">Identificación</span>
                        <p class="input-identification form-control input-value" ></p>
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-timely-payment">Pago oportuno</span>
                        <input type="date" class="input-timely-payment form-control input-value"  name="timely-payment">
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-cutoff-date">Fecha de corte</span>
                        <input type="date" class="input-cutoff-date form-control input-value"  name="cutoff-date">
                    </div>
                    <div class="input-container d-flex justify-content-start">
                        <span class="input-title align-self-center" for="input-total-value">Valor total</span>
                        <p class="input-total-value input-value" ><strong>$0</strong></p>
                    </div>
                    <div class="input-container d-flex justify-content-start bill-data-container">
                        <span class="input-title align-self-center" for="input-bill-name">Nombre factura</span>
                        <input type="text" class="input-bill-name form-control input-value"  name="bill-name">
                    </div>
                    <div class="input-container d-flex justify-content-start bill-data-container">
                        <span class="input-title align-self-center" for="input-bill-final-value">Valor pagado</span>
                        <input type="number" class="input-bill-final-value form-control input-value"  name="bill-final-value">
                    </div>

                </div>
                <div class="col-12 col-md-6 d-flex flex-column justify-content-center">
                    <div class="input-container d-flex flex-column justify-content-center description-container">
                        <span class="input-title align-self-start" for="input-description">Descripción</span>
                        <textarea class="input-description form-control input-value"  name="description"></textarea>
                    </div>
                </div>  
                <div class="col-12 d-flex justify-content-end">
                    <button id="update-income-button" class="btn align-self-center">Actualizar ingreso</button>
                </div>          
            </div>
            <ul class="income-licenses-list">
                <li class="add-row order-licenses-list-item-update order-licenses-list-item row">
                    <div class="col-6 d-flex flex-column justify-content-center">
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-license">Licencia</span>
                            <select class="form-select align-self-center input-value input-item-license" name="item-license">
                                <option value="0" selected disabled>Seleccione una licencia</option>
                            </select>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-service">Servicio</span>
                            <p class="form-control input-value input-item-service"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-recurrence">Recurrencia</span>
                            <p class="form-control input-value input-item-recurrence"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-value">Valor</span>
                            <input type="number" class="form-control input-value input-item-value" name="input-item-value">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-hours">Horas</span>
                            <input type="number" min="0" class="form-control input-value input-item-hours" name="input-item-value" value="0">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-employee">Empleado</span>
                            <p class="form-control input-value input-item-employee"></p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-comission">Comisión(%)</span>
                            <input type="number" class="form-control input-value input-item-comission" name="input-item-value">
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-comission"></span>
                            <p class="input-value input-item-total-comission">$0</p>
                        </div>
                        <div class="input-container d-flex justify-content-start">
                            <span class="input-title align-self-center" for="input-item-tax">Impuesto</span>
                            <p class="input-value align-self-center input-item-tax" name="item-tax">0%</p>
                        </div>
                    </div>
                    <div class="col-6 d-flex flex-column justify-content-center">
                        <div class="input-container d-flex flex-column justify-content-center description-container">
                            <span class="input-title align-self-start" for="input-item-description">Descripción</span>
                            <textarea class="form-control input-value input-item-description" name="description"></textarea>
                        </div>
                    
                    </div>
                    <i class="fas fa-plus-circle add-license-button"></i>
                </li>
            </ul>
            
        </div>
        
        
    </div>
    <!-- Traceability Tab -->
    <div class="tab-pane fade traceability-container" data-url="/incomes/" id="nav-traceability" role="tabpanel" aria-labelledby="nav-traceability-tab"></div>
</div>
<!-- Purchase Order Viewer -->
<div id="order-viewer-container">
    <i class="fa-solid fa-times" id="close-order-viewer"></i>
    <div id="order-viewer-sub-container">
        <h1 id="order-viewer-title">Documento</h1>
        <iframe id="order-viewer" src="/storage/incomes/pdfs/eb5646cf-bf6e-4517-9b77-a378b5ca076a.pdf" frameborder="0"></iframe>
        <div id="order-viewer-buttons" class="d-flex justify-content-center">
            <button id="send-order-button" class="btn">
                <i class="fa-solid fa-paper-plane"></i>
                Enviar
            </button>
        </div>
    </div>
    <!-- Purchase send container -->
    <div id="send-order-container">
        <div id="send-order-sub-container">
            <h1 id="send-order-title">Receptores</h1>
            <table id="receivers-list" class="table table-hover table-sm align-middle w-100">
                <thead id="receivers-list-header">
                    <tr>
                        <th scope="col" class="columns-send-email text-left">Correo</th>
                        <th scope="col" class="columns-send-phone text-left">Teléfono</th>
                        <th scope="col" class="columns-send-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="receivers-list-body">
                    
                </tbody>
            </table>
            <div id="send-order-buttons" class="d-flex justify-content-between">
                <button id="cancel-send-order-button" class="btn">
                    <i class="fa-solid fa-times"></i>
                    Cancelar
                </button>
                <button id="confirm-send-order-button" class="btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="import-report-excel-container" title="Importar productos para generar orden de compra">
	<i class="fas fa-file-arrow-up" id="import-report-excel-icon"></i>
	<span id="import-report-excel-text">Importar</span>
	<input type="file" id="import-report-excel-input" accept=".xlsx" class="hidden" name="import_file_input">
</div>

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

@endsection
