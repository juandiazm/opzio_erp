<!-- Tab Create -->
<div class="tab-pane fade" id="nav-create" role="tabpanel" aria-labelledby="nav-profile-tab">
    <div id="create-inputs-container" class="row m-0 p-0 w-100">
        <div class="inputs-cols col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID del usuario">
                    <label for="username" class="input-title align-self-center">ID Admin</label>
                    <p id="create-user-id" class="input-value align-self-center"></p>
                </div>
                <div class="input-container col-12 d-flex" title="Identificación del usuario">
                    <label for="username" class="input-title align-self-center">Identificación</label>
                    <input type="number" autofocus id="create-user-identification" class="input-value form-control align-self-center" name="identification" placeholder="1234567890">
                </div>
                <div class="input-container col-12 d-flex" title="Nombre/s del usuario">
                    <label for="username" class="input-title align-self-center">Nombre/s</label>
                    <input type="text" id="create-user-name" class="input-value form-control align-self-center" name="name" placeholder="Pepito">
                </div>
                <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                    <label for="username" class="input-title align-self-center">Apellido/s</label>
                    <input type="text" id="create-user-lastname" class="input-value form-control align-self-center" name="lastname" placeholder="Perez">
                </div>
            </div>
        </div>
        <div class="inputs-cols col-12 col-md-4">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Nickname del usuario">
                    <label for="username" class="input-title align-self-center">Usuario</label>
                    <input type="text" id="create-user-username" class="input-value form-control align-self-center" name="username" placeholder="pperez">
                </div>
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="username" class="input-title align-self-center">Correo</label>
                    <input type="text" id="create-user-email" class="input-value form-control align-self-center" name="email" placeholder="pperez@opzio.co">
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                    <label for="username" class="input-title align-self-center">Contraseña</label>
                    <input type="password" id="create-user-password" title="Doble click para visualizar/ocultar la contrasña" class="input-password input-value form-control align-self-center" name="password" placeholder="********">
                </div>
                <div class="input-container col-12 d-flex" class="Confirma la contraseña del usuario">
                    <label for="username" class="input-title align-self-center">V - Contraseña</label>
                    <input type="password" id="create-user-password-confirmation" title="Doble click para visualizar/ocultar la contrasña" class="input-password input-value form-control align-self-center" name="confirm_password" placeholder="********">
                </div>
            </div>
        </div>
        <div id="multimedia-container" class="col-12 col-md-4 d-flex flex-column justify-content-center">
            <div class="d-block">
                <div class="d-flex justify-content-center">
                    <div class="multimedia-input-container">
                        <div id="create-user-img-container" class="image-container d-flex justify-content-center">
                            <input type="file" name="photo" id="create-user-img" class="d-none input_image" accept="image/*" data-image-crop="circle" data-image-crop-max-width="800" data-image-crop-max-height="800">
                            <img class="image_preview align-self-center" alt="Foto de perfil">
                            <i class="fa-regular fa-image align-self-center image-icon"></i>
                        </div>
                        <i class="fa-solid fa-plus image-plus-icon"></i>
                    </div>
                    <div class="multimedia-input-container">
                        <div id="create-user-color-container" class="color-container d-flex justify-content-center">
                            <input type="color" name="color" id="create-user-color" class="input-color">
                            <i class="fa-solid fa-palette align-self-center color-icon"></i>
                        </div>
                        <i class="fa-solid fa-plus image-plus-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="permissions-container">
        <h3 id="permissions-title">Permisos</h2>
        <div class="row permissions-list">
        </div>
    </div>
    <button class="btn btn-secondary" id="add-button">Guardar</button>
</div>