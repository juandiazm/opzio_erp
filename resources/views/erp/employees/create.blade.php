<!-- Tab Create -->
<div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-create-tab">
    <div id="create-inputs-container" class="row m-0 p-0 w-100 justify-content-center">
        <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <div class="multimedia-input-container">
                            <div id="create-employee-img-container" class="image-container d-flex justify-content-center">
                                <input type="file" name="photo" id="create-employee-img" class="d-none input_image" accept="image/*" data-image-crop="circle" data-image-crop-max-width="800" data-image-crop-max-height="800">
                                <i class="fa-regular fa-image align-self-center image-icon"></i>
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID">
                    <label for="employeename" class="input-title align-self-center">ID Empleado</label>
                </div>
                <div class="input-container col-12 d-flex" title="Nombre">
                    <label for="create-employee-name" class="input-title align-self-center">Nombre/s</label>
                    <input type="text" autofocus id="create-employee-name" class="input-value form-control align-self-center" name="name" placeholder="Juan">
                </div>
                <div class="input-container col-12 d-flex" title="Apellidos">
                    <label for="employee-last-name" class="input-title align-self-center">Apellido/s</label>
                    <input type="text" autofocus id="create-employee-last-name" class="input-value form-control align-self-center" name="last-name" placeholder="Posada">
                </div>
                <div class="input-container col-12 d-flex" title="Tipo de ID">
                    <label for="create-employee-id-type" class="input-title align-self-center">Tipo ID</label>
                    <select class="form-select input-value align-self-center" id="create-employee-id-type" name="id-type">
                        <option value="0" selected>Nit</option>
                        <option value="1">Cédula</option>
                        <option value="2">Pasaporte</option>
                        <option value="3">Cédula extranjera</option>
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Identificación">
                    <label for="create-employee-identification" class="input-title align-self-center">Identificación</label>
                    <input type="text" id="create-employee-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Estado">
                    <label for="create-employee-state" class="input-title align-self-center">Estado</label>
                    <div class="toggle-container row" value="1" id="create-employee-state">
                        <div class="toggle-value d-flex justify-content-center col-6" value="1"><p>Activo</p></div>
                        <div class="toggle-value d-flex justify-content-center col-6" value="0"><p>Inactivo</p></div>
                    </div>
                </div>
                <div class="input-container col-12 d-flex" title="País">
                    <label for="create-employee-country" class="input-title align-self-center">País</label>
                    <div class="crud-input-container input-value" prefix="/admin/country/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="create-employee-country">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colombia">
                            <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                        </div>
                        <ul class="crud-list closed scrollable"><li class="crud-item-add d-flex justify-content-between"><input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar"><i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i></li></ul>
                    </div>
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña">
                    <label for="create-employee-phone" class="input-title align-self-center">Teléfono</label>
                    <input type="number" id="create-employee-phone" class="input-phone input-value form-control align-self-center" name="phone" placeholder="3002583697">
                </div>
                <div class="input-container col-12 d-flex"><label for="employeename" class="input-title align-self-center">Correo P.</label><input type="email" id="create-employee-personal-email" class="input-personal-email input-value form-control align-self-center" name="personal-email" placeholder="juanp@gmail.com"></div>
                <div class="input-container col-12 d-flex"><label for="employeename" class="input-title align-self-center">Correo E.</label><input type="email" id="create-employee-work-email" class="input-work-email input-value form-control align-self-center" name="work-email" placeholder="juanp@opzio.co"></div>
            </div>
        </div>
    </div>
    <button class="btn btn-secondary" id="add-employee-button">Guardar</button>
</div>