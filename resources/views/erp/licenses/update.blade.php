<!-- Tab Update -->
<div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
    <nav>
        <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
            <button class="nav-link active" id="sub-nav-general-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-general" type="button" role="tab" aria-controls="sub-nav-general" aria-selected="true">General</button>
            <button class="nav-link" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="false">Documentos</button>
            <button class="nav-link" id="sub-nav-notifications-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-notifications" type="button" role="tab" aria-controls="sub-nav-notifications" aria-selected="false">Notificaciones</button>
        </div>
    </nav>
    <div class="tab-content" id="sub-nav-tabContent">
        <div class="tab-pane fade show active" id="sub-nav-general" role="tabpanel" aria-labelledby="sub-nav-general-tab">
    <div id="update-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID">
                    <label for="license-id" class="input-title align-self-center">ID</label>
                    <p class="input-value align-self-center" id="update-license-unique-id"></p>
                </div>
                <div class="input-container col-12 d-flex" title="Estado">
                    <label for="license-state" class="input-title align-self-center">Estado</label>
                    <div class="toggle-container row" value="1" id="update-license-state">
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
                    <select class="input-value form-select align-self-center" id="update-license-client" aria-label="Default select example">
                    </select>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="input-container col-12 d-flex" title="Servicio">
                <label for="license-service" class="input-title align-self-center">Servicio</label>
                <div class="crud-input-container input-value" prefix="/admin/service/">
                    <div class="crud-input-selected-container d-flex justify-content-between" id="update-license-service">
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
            <div class="input-container col-12 d-flex" title="Nombre">
                <label for="license-name" class="input-title align-self-center">Nombre</label>
                <input type="text" autofocus id="update-license-name" class="input-value form-control align-self-center" name="name" placeholder="Nombre de la licencia">
            </div>
            <div class="input-container col-12 d-flex" title="Empleado">
                <label for="license-employee" class="input-title align-self-center">Empleado</label>
                <select class="input-value form-select align-self-center" id="update-license-employee" aria-label="Default select example">
                </select>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="input-container col-12 d-flex" title="Valor">
                <label for="license-value" class="input-title align-self-center">Valor</label>
                <input type="number" autofocus id="update-license-value" class="input-value form-control align-self-center" name="value" placeholder="$80.000.000">
            </div>
            <div class="input-container col-12 d-flex" title="Descripcion">
                <label for="license-description" class="input-title align-self-center">Descripcion</label>
                <textarea class="input-value form-control align-self-center" id="update-license-description" placeholder="Descripcion de la licencia"></textarea>
            </div>
        </div>
    </div>
        @include('erp.licenses.details')
    <div id="license-update-actions">
        <div id="license-secondary-actions">
            <div class="d-flex justify-content-center" id="balance-button">
                <i class="fa-solid fa-scale-balanced"></i>
                <p class="align-self-center">Balance</p>
            </div>
            <div id="license-sub-opt-container">
                <div class="d-flex justify-content-between">
                    <div class="align-self-center" id="update-license-go-traceability"><i class="fa-solid fa-bars-progress"></i></div>
                    <div class="align-self-center" id="update-license-delete"><i class="fa-solid fa-trash-can"></i></div>
                    <div class="align-self-center d-none" id="update-license-restore"><i class="fa-solid fa-lightbulb"></i></div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" id="update-license-button">Guardar</button>
    </div>
        </div>
        @include('erp.licenses.documents')
        @include('erp.licenses.notifications')
    </div>
</div>