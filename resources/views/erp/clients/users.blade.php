<div class="tab-pane fade show active" id="sub-nav-users" role="tabpanel" aria-labelledby="sub-nav-users-tab">
    <div id="create-client-inputs-container" class="row w-100">
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                    <label for="username" class="input-title align-self-center">Nombre/s</label>
                    <input type="text" id="create-client-user-name" class="input-value form-control align-self-center" name="name" placeholder="Pepito">
                </div>
                <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                    <label for="username" class="input-title align-self-center">Apellido/s</label>
                    <input type="text" id="create-client-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Perez">
                </div>
                <div class="input-container col-12 d-flex" title="Nickname del usuario">
                    <label for="username" class="input-title align-self-center">Usuario</label>
                    <input type="text" id="create-client-user-username" class="input-value form-control align-self-center" name="username" placeholder="pperez">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="username" class="input-title align-self-center">Correo</label>
                    <input type="text" id="create-client-user-email" class="input-value form-control align-self-center" name="email" placeholder="pperez@opzio.co">
                </div>
                <div class="input-container col-12 d-flex" title="Teléfono del usuario">
                    <label for="username" class="input-title align-self-center">Teléfono</label>
                    <input type="text" id="create-client-user-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002536526">
                </div>
                <div class="input-container col-12 d-flex" title="Cargo del usuario">
                    <label for="username" class="input-title align-self-center">Cargo</label>
                    <input type="text" id="create-client-user-position" class="input-value form-control align-self-center" name="position" placeholder="Ejecutivo de cuenta">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 align-self-center">
            <div class="d-block">
                <div class="d-flex justify-content-center">
                    <div class="multimedia-input-container">
                        <div id="create-client-user-color-container" class="color-container d-flex justify-content-center">
                            <input type="color" name="color" id="create-client-user-color" class="input-color">
                            <i class="fa-solid fa-palette align-self-center color-icon"></i>
                        </div>
                        <i class="fa-solid fa-plus image-plus-icon"></i>
                    </div>
                    <button class="btn btn-secondary align-self-center" id="add-client-user-button">Agregar</button>
                </div>
            </div>
        </div>
    </div>
    <table id="client-users-table" class="table table-sm align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="user-column-id text-left">ID</th>
                <th scope="col" class="user-column-color text-center">Color</th>
                <th scope="col" class="user-column-name text-left">Nombre</th>
                <th scope="col" class="user-column-username text-left">Usuario</th>
                <th scope="col" class="user-column-email text-left">Correo</th>
                <th scope="col" class="user-column-phone text-left">Teléfono</th>
                <th scope="col" class="user-column-position text-center">Cargo</th>
                <th scope="col" class="user-column-actions text-end">Acciones</th>
            </tr>
        </thead>
        <tbody id="client-users-table-body">
        </tbody>
    </table>
</div>