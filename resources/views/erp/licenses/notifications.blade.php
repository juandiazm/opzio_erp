<div class="tab-pane fade" id="sub-nav-notifications" role="tabpanel" aria-labelledby="sub-nav-details-tab">
    <table class="table sub-table table-strech" id="notifications-table">
        <thead>
            <tr>
                <th scope="col" class="text-left"></th>
                <th scope="col" class="columns-notification-email text-left">Correo</th>
                <th scope="col" class="columns-notification-phone text-left">Teléfono</th>
                <th scope="col" class="columns-notification-state text-center">Estado</th>
                <th scope="col" class="columns-notification-actions text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr id="add-notification-row" class="table-row-add">
                <td class="text-center"></td>
                <td class="columns-notification-email text-center">
                    <input type="email" class="form-control align-self-center notification-email text-start" placeholder="license@gmail.com">
                </td>
                <td class="columns-notification-phone text-center">
                    <input type="number" class="form-control align-self-center notification-phone text-start" placeholder="573191425639">
                </td>
                <td class="columns-notification-state text-center">
                    <div class="toggle-container row notification-active" value="1">
                        <div class="toggle-value d-flex justify-content-center col-6" value="1">
                            <p>Activo</p>
                        </div>
                        <div class="toggle-value d-flex justify-content-center col-6" value="0">
                            <p>Inactivo</p>
                        </div>
                    </div>
                </td>
                <td class="columns-notification-actions text-center">
                    <i class="fa-solid fa-plus" id="add-notification"></i>
                </td>
            </tr>
        </tbody>
    </table>
</div>