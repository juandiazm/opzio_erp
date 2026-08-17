<div class="tab-pane fade" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-details-tab">
    <div id="license-documents-add-container" class="row">
        <div class="col-1 input-container d-flex">
            <p class="license-document-input-title align-self-end">Nombre</p>
        </div>
        <div class="col-3 input-container d-flex">
            <input type="text" name="" class="license-document-input-name align-self-end input-value form-control" placeholder="Contrato confidencialidad">
        </div>
        <div class="col-6">
            <input type="file" class="license-document-input-file form-control" name="file" placeholder="Archivo..." aria-label="Archivo" aria-describedby="basic-addon1" accept=".pdf,.docx,.xlsx,.pptx">
        </div>
        <button class="col-2 btn btn-secondary" id="add-license-documens-button">Agregar</button>
    </div>
    <table id="license-documents-table" class="table table-sm align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="text-left">Nombre</th>
                <th scope="col" class="text-left">Archivo</th>
                <th scope="col" class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody id="license-documents-table-body">
        </tbody>
    </table>
</div>