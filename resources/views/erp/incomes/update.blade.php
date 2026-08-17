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