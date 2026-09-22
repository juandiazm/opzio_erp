@extends('erp.layouts.app')
@section('component_title', 'CONTACTOS')
@section('erp-app-header')
@vite('resources/js/erp/contacts/contacts.js')
@vite('resources/sass/erp/contacts/contacts.scss')
@endsection
@section('erp-app-content')
<div class="contacts-directory" data-contact-directory>
    <form class="contacts-filter-bar" data-contact-directory-form>
        <label class="contacts-filter-field contacts-filter-search">
            <span>Buscar</span>
            <input type="search" name="search" data-contact-search placeholder="Nombre, correo, número o licencia" autocomplete="off">
        </label>
        <label class="contacts-filter-field">
            <span>Origen</span>
            <select name="owner" data-contact-owner>
                <option value="">Todos</option>
                <option value="client">Cliente</option>
                <option value="license">Licencia</option>
            </select>
        </label>
        <label class="contacts-filter-field" data-contact-client-field>
            <span>Clientes</span>
            <select name="client_ids[]" multiple data-contact-client-filter aria-label="Filtrar por clientes"></select>
        </label>
        <label class="contacts-filter-field" data-contact-license-field>
            <span>Licencias</span>
            <select name="license_ids[]" multiple data-contact-license-filter aria-label="Filtrar por licencias"></select>
        </label>
        <label class="contacts-filter-field" data-contact-type-field>
            <span>Tipo</span>
            <select name="types[]" multiple data-contact-type-filter aria-label="Filtrar por tipo"></select>
        </label>
        <label class="contacts-filter-field" data-contact-channel-field>
            <span>Canales</span>
            <select name="channels[]" multiple data-contact-channel-filter aria-label="Filtrar por canales"></select>
        </label>
        <label class="contacts-filter-field" data-contact-tag-field>
            <span>Etiquetas</span>
            <select name="tag_ids[]" multiple data-contact-tag-filter aria-label="Filtrar por etiquetas"></select>
        </label>
        <div class="contacts-filter-actions">
            <button type="button" class="btn btn-light contacts-filter-icon-button" data-contact-reset title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fa-light fa-rotate-left"></i></button>
            <button type="submit" class="btn btn-primary contacts-filter-icon-button" title="Consultar contactos" aria-label="Consultar contactos"><i class="fa-light fa-filter"></i></button>
            <div class="contacts-filter-menu" data-contact-tools>
                <button type="button" class="btn btn-light contacts-filter-icon-button contacts-filter-more" data-contact-tools-toggle aria-expanded="false" aria-controls="contacts-filter-tools-menu" title="Más acciones" aria-label="Más acciones"><i class="fa-light fa-ellipsis-vertical"></i></button>
                <div class="contacts-filter-tools-menu" id="contacts-filter-tools-menu" data-contact-tools-menu hidden>
                    <button type="button" class="contacts-filter-tool" data-contact-create title="Agregar nuevo contacto"><i class="fa-light fa-user-plus"></i><span>Nuevo contacto</span></button>
                    <button type="button" class="contacts-filter-tool" data-contact-import title="Importar contactos"><i class="fa-light fa-file-arrow-up"></i><span>Importar</span></button>
                    <button type="button" class="contacts-filter-tool" data-contact-export title="Exportar contactos"><i class="fa-light fa-file-excel"></i><span>Exportar</span></button>
                </div>
            </div>
        </div>
    </form>
    <form class="d-none" data-contact-import-form enctype="multipart/form-data">
        @csrf
        <input type="file" name="import-file" data-contact-import-file accept=".xlsx,.xls,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
    </form>
    <p class="contacts-directory-status" data-contact-status role="status"></p>
    <section class="contacts-table-panel">
        <div class="contacts-table-wrap">
            <table class="table table-sm align-middle erp-data-table contacts-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Canales</th>
                        <th>Cliente</th>
                        <th>Licencia</th>
                        <th>Etiquetas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody data-contact-table-body>
                    <tr><td colspan="9" class="contacts-empty">Cargando contactos...</td></tr>
                </tbody>
            </table>
        </div>
        <ul class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex contacts-pagination" data-contact-pagination role="navigation" aria-label="Paginación de contactos"></ul>
    </section>
</div>
<div class="contacts-edit-modal d-none" data-contact-edit-modal role="dialog" aria-modal="true" aria-labelledby="contacts-edit-title">
    <div class="contacts-edit-dialog">
        <header class="contacts-edit-header">
            <div><span class="contacts-edit-kicker">CONTACTO</span><h2 id="contacts-edit-title" data-contact-edit-title>Editar contacto</h2></div>
            <button type="button" class="contacts-edit-close" data-contact-edit-close title="Cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </header>
        <form class="contacts-edit-form" data-contact-edit-form>
            <div class="contacts-edit-grid">
                <label class="contacts-edit-field"><span>Nombre</span><input type="text" data-contact-edit-name required></label>
                <label class="contacts-edit-field"><span>Valor</span><input type="text" data-contact-edit-value required></label>
                <label class="contacts-edit-field"><span>Tipo</span><select data-contact-edit-type><option value="email">Correo</option><option value="phone">Número</option></select></label>
                <label class="contacts-edit-field contacts-edit-field-wide" data-contact-edit-channels-field><span>Canales</span><select multiple data-contact-edit-channels></select></label>
                <label class="contacts-edit-field"><span>Cliente</span><select data-contact-edit-client></select></label>
                <label class="contacts-edit-field"><span>Licencia</span><select data-contact-edit-license></select></label>
                <div class="contacts-edit-field contacts-edit-field-wide" data-contact-edit-tags-field><span>Etiquetas</span><div class="contacts-edit-tags" data-contact-edit-tags></div></div>
                <div class="contacts-edit-field"><span>Estado</span><div class="toggle-container row contacts-edit-active" data-contact-edit-active value="1"><div class="toggle-value d-flex justify-content-center col-6" value="1"><p>Activo</p></div><div class="toggle-value d-flex justify-content-center col-6" value="0"><p>Inactivo</p></div></div></div>
            </div>
            <footer class="contacts-edit-footer"><button type="button" class="btn btn-light" data-contact-edit-close>Cancelar</button><button type="submit" class="btn btn-primary"><i data-contact-edit-submit-icon class="fa-solid fa-floppy-disk"></i> <span data-contact-edit-submit-label>Guardar</span></button></footer>
        </form>
    </div>
</div>
@endsection
