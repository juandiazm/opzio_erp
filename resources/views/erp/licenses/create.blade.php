<!-- Tab Create -->
<div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
    <div id="create-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
        <div class="col-12 col-md-5">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Estado">
                    <label for="license-state" class="input-title align-self-center">Estado</label>
                    <div class="toggle-container row" value="1" id="create-license-state">
                        <div class="toggle-value d-flex justify-content-center col-6" value="1">
                            <p>Activo</p>
                        </div>
                        <div class="toggle-value d-flex justify-content-center col-6" value="0">
                            <p>Inactivo</p>
                        </div>
                    </div>
                </div>
                <div class="input-container col-12 d-flex" title="Cliente">
                    <label for="license-client" class="input-title align-self-center">Cliente</label>
                    <select class="input-value form-select align-self-center" id="create-license-client" aria-label="Default select example">
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Nombre">
                    <label for="license-name" class="input-title align-self-center">Nombre</label>
                    <input type="text" autofocus id="create-license-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre de la licencia">
                </div>
            </div>
            <div class="input-container col-12 d-flex" title="Servicio">
                <label for="license-service" class="input-title align-self-center">Servicio</label>
                <div class="crud-input-container input-value" prefix="/admin/service/">
                    <div class="crud-input-selected-container d-flex justify-content-between" id="create-license-service">
                        <input type="text" class="crud-current-selected-input align-self-center" placeholder="Selecciona un servicio">
                        <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                    </div>
                    <ul class="crud-list closed scrollable">
                        <li class="crud-item-add d-flex justify-content-between">
                            <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                            <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Empleado">
                    <label for="license-employee" class="input-title align-self-center">Empleado</label>
                    <select class="input-value form-select align-self-center" id="create-license-employee" aria-label="Default select example">
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Valor">
                    <label for="license-value" class="input-title align-self-center">Valor</label>
                    <input type="number" autofocus id="create-license-value" class="input-value form-control align-self-center" name="value" placeholder="$80.000.000">
                </div>
                <div class="input-container col-12 d-flex" title="Descripcion">
                    <label for="license-description" class="input-title align-self-center">Descripcion</label>
                    <textarea class="input-value form-control align-self-center" id="create-license-description" placeholder="Descripcion de la licencia"></textarea>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-secondary" id="add-license-button">Guardar</button>
</div>