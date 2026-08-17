<div class="tab-pane fade show active" id="sub-nav-details" role="tabpanel" aria-labelledby="sub-nav-details-tab">
    <div id="license-details-inputs-container" class="row w-100">
        <div class="col-12 col-md-6">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Tipo de licencia">
                    <label for="license-type" class="input-title align-self-center">Tipo de licencia</label>
                    <select class="input-value form-select align-self-center" id="update-license-type">
                        <option value="1">Recurrente</option>
                        <option value="2">Stática</option>
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Frecuencia Mensual">
                    <label for="license-recurrence-months" class="input-title align-self-center">Frecuencia en meses</label>
                    <input type="number" autofocus id="update-license-recurrence-months" class="input-value form-control align-self-center" name="monthly-frequency" placeholder="1">
                </div>
                <div class="input-container col-12 d-flex" title="Dia de facturación">
                    <label for="license-billing-day" class="input-title align-self-center">Día de facturación</label>
                    <input type="number" autofocus id="update-license-billing-day" class="input-value form-control align-self-center" name="billing-day" placeholder="1">
                </div>
                <div class="input-container col-12 d-flex" title="Días de gracia">
                    <label for="license-days-to-expire" class="input-title align-self-center">Días de gracia</label>
                    <input type="number" autofocus id="update-license-days-to-expire" class="input-value form-control align-self-center" name="grace-days" placeholder="1">
                </div>
                <div class="input-container col-12 d-flex" title="Último pago">
                    <label for="license-last-payed" class="input-title align-self-center">Último pago</label>
                    <input type="date" autofocus id="update-license-last-payed-date" class="input-value form-control align-self-center" name="last-payed" placeholder="2021-12-31">
                </div>
                <div class="input-container col-12 d-flex" title="Próxima facturación">
                    <label for="license-next-billing" class="input-title align-self-center">Próxima facturación</label>
                    <input type="date" autofocus id="update-license-next-billing-date" class="input-value form-control align-self-center" name="next-billing" placeholder="2021-12-31">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Llave de usuario">
                    <label for="license-user-key" class="input-title align-self-center"><i class="fa-regular fa-copy copy-action" id="copy-update-license-user-key" data-clipboard-text=""></i>Llave de usuario</label>
                    <p class="input-value align-self-center" id="update-license-user-key"></p>
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña de usuario">
                    <label for="license-password-key" class="input-title align-self-center"><i class="fa-regular fa-copy copy-action" id="copy-update-license-password-key" data-clipboard-text=""></i>Contraseña de usuario</label>
                    <p class="input-value align-self-center" id="update-license-password-key"></p>
                </div>
                <div class="input-container col-12 d-flex" title="Ultima facturación">
                    <label for="license-last-billing" class="input-title align-self-center">Última facturación</label>
                    <p class="input-value align-self-center" id="update-license-last-billing-date"></p>
                </div>
                <div class="input-container col-12 d-flex" title="Dias restantes">
                    <label for="license-remaining-days" class="input-title align-self-center">Dias restantes</label>
                    <p class="input-value align-self-center" id="update-license-remaining-days"></p>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary align-self-center" id="update-license-details-button"><i class="fa-solid fa-file-invoice"></i></button>
    </div>
</div>