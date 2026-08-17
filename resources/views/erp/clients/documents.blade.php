<div class="tab-pane fade" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-documents-tab">
    <div id="client-documents-add-container" class="row">
        <div class="col-8 m-auto">
            <input type="file" class="client-document-input-file form-control" name="file" accept=".pdf,.docx,.xlsx,.pptx" multiple>
        </div>
        <button class="col-2 btn btn-secondary" id="add-client-documens-button">Agregar</button>
    </div>
    <table id="client-documents-table" class="table table-sm align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="text-left">Nombre</th>
                <th scope="col" class="text-left">Archivo</th>
                <th scope="col" class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody id="client-documents-table-body">
        </tbody>
    </table>
</div>