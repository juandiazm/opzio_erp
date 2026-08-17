<!-- Tab Update -->
<div class="tab-pane fade" id="nav-update" role="tabpanel" aria-labelledby="nav-update-tab">
    <div id="update-inputs-container" class="row m-0 p-0 w-100">
        <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
            <div class="row justify-content-center">
                <div class="col-3 col-md-4 align-self-center">
                    <div class="d-flex justify-content-center">
                        <div class="multimedia-input-container">
                            <div id="update-client-img-container" class="image-container d-flex justify-content-center">
                                <input type="file" name="photo" id="update-client-img" class="d-none input_image" accept="image/*">
                                <i class="fa-regular fa-image align-self-center image-icon"></i>
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-9 col-md-5 align-self-center">
                    <div class="row">
                        <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                            <label for="clientname" class="input-title align-self-center">ID Cliente</label>
                            <p id="update-client-unique-id" class="m-0 p-0 align-self-center"></p>
                        </div>
                        <div class="input-container col-12 d-flex" title="ID del usuario">
                            <label for="clientname" class="input-title align-self-center">Verificación</label>
                            <div class="input-value align-self-center" id="update-client-verification" value="1">
                                <i class="verification-input-icon fa-solid fa-medal enabled" value="1"></i>
                                <i class="verification-input-icon fa-solid fa-ban disabled" value="0"></i>
                            </div>
                        </div>
                        <div class="input-container col-12 d-flex" title="ID del usuario">
                            <label for="clientname" class="input-title align-self-center">Estado</label>
                            <div class="toggle-container row" value="1" id="update-client-state">
                                <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                    <p>Activo</p>
                                </div>
                                <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                    <p>Inactivo</p>
                                </div>
                            </div>
                        </div>
                        <div class="input-container col-12 d-flex" title="Factura electrónica">
                            <label for="client-electronic-invoice" class="input-title align-self-center">Factura E.</label>
                            <div class="toggle-container row" value="0" id="update-client-electronic-invoice">
                                <div class="toggle-value d-flex justify-content-center col-6" value="1">
                                    <p>Activo</p>
                                </div>
                                <div class="toggle-value d-flex justify-content-center col-6" value="0">
                                    <p>Inactivo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID del usuario">
                    <label for="clientname" class="input-title align-self-center">Cliente</label>
                    <input type="text" autofocus id="update-client-name" class="input-value form-control align-self-center" name="name" placeholder="Empresa / Nombre y apellido">
                </div>
                <div class="input-container col-12 d-flex" title="Identificación del usuario">
                    <label for="clientname" class="input-title align-self-center">Tipo ID</label>
                    <select class="form-select input-value align-self-center" id="update-client-id-type" name="identification_type">
                        <option value="0" selected>Nit</option>
                        <option value="1">Cédula</option>
                        <option value="2">Pasaporte</option>
                        <option value="3">Cédula extranjera</option>
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                    <label for="clientname" class="input-title align-self-center">Identificación</label>
                    <input type="text" id="update-client-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                </div>
                <div class="input-container col-12 d-flex" title="País">
                    <label for="countries" class="input-title align-self-center">País</label>
                    <div class="crud-input-container input-value" prefix="/admin/country/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-country">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colombia">
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
            <div class="input-container col-12 d-flex" title="Correo del usuario">
                <label for="clientname" class="input-title align-self-center">Dirección</label>
                <input type="text" id="update-client-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52">
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="clientname" class="input-title align-self-center">Teléfono</label>
                    <input type="number" id="update-client-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697">
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                    <label for="clientname" class="input-title align-self-center">Correo</label>
                    <input type="email" id="update-client-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com">
                </div>
                <div class="input-container col-12 d-flex">
                    <label for="clientname" class="input-title align-self-center">Sector</label>
                    <div class="crud-input-container input-value" prefix="/admin/sector/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-sector">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Servicios Financieros">
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
                <div class="input-container col-12 d-flex" title="Valor por hora">
                    <label for="clientname" class="input-title align-self-center">Valor por hora</label>
                    <input type="number" id="update-client-value-per-hour" class="input-value form-control align-self-center" name="value_per_hour" placeholder="$ 0.00">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="input-container col-12">
                <label for="clientname" class="input-title align-self-center d-block">Descripción</label>
                <textarea id="update-client-description" name="description" rows="5" placeholder="Breve descripción de la empresa o cliente."></textarea>
            </div>
        </div>
    </div>
    <button class="btn btn-secondary" id="update-client-button">Actualizar</button>
    <nav>
        <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
            <button class="nav-link active" id="sub-nav-users-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-users" type="button" role="tab" aria-controls="sub-nav-users" aria-selected="true">Usuarios</button>
            <button class="nav-link" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="true">Documentos</button>
            <button class="nav-link" id="sub-nav-licenses-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-licenses" type="button" role="tab" aria-controls="sub-nav-licenses" aria-selected="true">Licencias</button>
        </div>
    </nav>
    <div class="tab-content" id="sub-nav-tabContent">
        @include('erp.clients.users')
        @include('erp.clients.documents')
        @include('erp.clients.licenses')
    </div>
</div>