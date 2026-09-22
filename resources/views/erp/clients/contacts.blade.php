<div class="tab-pane fade notification-contacts-pane" id="sub-nav-contacts" role="tabpanel" aria-labelledby="sub-nav-contacts-tab">
    <div class="table-responsive">
        <table class="table sub-table align-middle" id="client-contacts-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Valor</th>
                    <th>Tipo</th>
                    <th>Canales</th>
                    <th>Etiquetas</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr id="add-client-contact-row" class="table-row-add">
                    <td><input type="text" class="form-control client-contact-name" placeholder="Nombre del contacto"></td>
                    <td><input type="text" class="form-control client-contact-value" placeholder="correo@ejemplo.com o 3000000000"></td>
                    <td><select class="form-select client-contact-type"><option value="email">Correo</option><option value="phone">Número</option></select></td>
                    <td><select class="form-select client-contact-channels" multiple size="3"></select></td>
                    <td class="text-start"><button type="button" class="contact-tag-add" title="Guarda el contacto para agregar etiquetas" aria-label="Guarda el contacto para agregar etiquetas" disabled><i class="fa-solid fa-plus"></i></button></td>
                    <td><div class="toggle-container row client-contact-active" value="1"><div class="toggle-value d-flex justify-content-center col-6" value="1"><p>Activo</p></div><div class="toggle-value d-flex justify-content-center col-6" value="0"><p>Inactivo</p></div></div></td>
                    <td class="text-end contact-actions-cell"><button type="button" class="btn btn-link" id="add-client-contact" title="Agregar contacto" aria-label="Agregar contacto"><i class="fa-solid fa-plus"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>