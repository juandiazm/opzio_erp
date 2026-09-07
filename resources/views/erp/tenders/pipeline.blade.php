<div class="tab-pane fade" id="tenders-pipeline" role="tabpanel" aria-labelledby="tenders-pipeline-tab">
    <div id="tenders-pipeline-container" class="scrollable">
        <div class="tenders-pipeline-toolbar" role="search">
            <label class="tenders-pipeline-filter tenders-pipeline-search-filter" for="tenders-pipeline-search">
                <span>Buscar</span>
                <input type="search" id="tenders-pipeline-search" class="form-control" placeholder="Oportunidad, entidad o referencia" autocomplete="off">
            </label>
            <label class="tenders-pipeline-filter" for="tenders-pipeline-stage">
                <span>Etapa</span>
                <select id="tenders-pipeline-stage" class="form-select">
                    <option value="">Todas</option>
                    <option value="saved">Guardadas</option>
                    <option value="reviewing">En revisión</option>
                    <option value="preparing">En preparación</option>
                    <option value="submitted">Presentadas</option>
                    <option value="won">Ganadas</option>
                    <option value="lost">Perdidas</option>
                    <option value="archived">Archivadas</option>
                </select>
            </label>
            <button type="button" id="tenders-pipeline-refresh" class="tenders-icon-action" title="Actualizar oportunidades" aria-label="Actualizar oportunidades">
                <i class="fa-light fa-arrows-rotate" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tenders-pipeline-summary">
            <div id="tenders-pipeline-status" class="tenders-pipeline-status" role="status" aria-live="polite"></div>
            <div id="tenders-pipeline-overview" class="tenders-pipeline-overview"></div>
        </div>
        <div class="tenders-pipeline-table-container">
            <table id="tenders-pipeline-table" class="table table-hover table-sm align-middle w-100 erp-data-table">
                <thead>
                    <tr>
                        <th scope="col" class="tenders-pipeline-column-opportunity">Oportunidad</th>
                        <th scope="col" class="tenders-pipeline-column-entity">Entidad</th>
                        <th scope="col" class="tenders-pipeline-column-stage">Etapa</th>
                        <th scope="col" class="tenders-pipeline-column-amount text-end">Valor</th>
                        <th scope="col" class="tenders-pipeline-column-deadline text-center">F. cierre</th>
                        <th scope="col" class="tenders-pipeline-column-due text-center">Próxima tarea</th>
                        <th scope="col" class="tenders-pipeline-column-follow-up">Último seguimiento</th>
                        <th scope="col" class="tenders-pipeline-column-actions text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tenders-pipeline-list" role="rowgroup"></tbody>
            </table>
        </div>
    </div>
    <ul id="tenders-pipeline-pagination" class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>

    <div class="modal fade" id="tenders-pipeline-editor" tabindex="-1" aria-labelledby="tenders-pipeline-editor-label" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="tenders-pipeline-editor-label">Nuevo seguimiento</h2>
                        <p id="tenders-pipeline-editor-title" class="tenders-pipeline-editor-title"></p>
                        <p id="tenders-pipeline-editor-entity" class="tenders-pipeline-editor-entity"></p>
                    </div>
                    <button type="button" class="btn-close" data-tenders-pipeline-close data-bs-dismiss="modal" title="Cerrar" aria-label="Cerrar"></button>
                </div>
                <form id="tenders-pipeline-editor-form">
                    <div class="modal-body">
                        <input type="hidden" name="opportunity_id">
                        <input type="hidden" name="pipeline_entry_id">
                        <div id="tenders-pipeline-editor-status" class="tenders-pipeline-editor-status" role="status" aria-live="polite"></div>
                        <div class="tenders-pipeline-editor-grid">
                            <label class="tenders-pipeline-filter" for="tenders-pipeline-editor-stage">
                                <span>Etapa</span>
                                <select id="tenders-pipeline-editor-stage" name="stage" class="form-select">
                                    <option value="saved">Guardada</option>
                                    <option value="reviewing">En revisión</option>
                                    <option value="preparing">En preparación</option>
                                    <option value="submitted">Presentada</option>
                                    <option value="won">Ganada</option>
                                    <option value="lost">Perdida</option>
                                    <option value="archived">Archivada</option>
                                </select>
                            </label>
                            <label class="tenders-pipeline-filter" for="tenders-pipeline-editor-due-at">
                                <span>Próxima tarea</span>
                                <input type="datetime-local" id="tenders-pipeline-editor-due-at" name="due_at" class="form-control">
                            </label>
                            <label class="tenders-pipeline-filter tenders-pipeline-editor-wide" for="tenders-pipeline-editor-outcome">
                                <span>Resultado</span>
                                <input type="text" id="tenders-pipeline-editor-outcome" name="outcome" class="form-control" maxlength="100">
                            </label>
                            <label class="tenders-pipeline-filter tenders-pipeline-editor-wide" for="tenders-pipeline-editor-notes">
                                <span>Notas internas</span>
                                <textarea id="tenders-pipeline-editor-notes" name="notes" class="form-control" rows="5" maxlength="5000"></textarea>
                            </label>
                        </div>
                        <section class="tenders-pipeline-history" aria-labelledby="tenders-pipeline-history-title">
                            <div class="tenders-pipeline-history-header">
                                <h3 id="tenders-pipeline-history-title">Seguimientos de la oportunidad</h3>
                                <span id="tenders-pipeline-history-status" class="tenders-pipeline-history-status" role="status" aria-live="polite"></span>
                            </div>
                            <div id="tenders-pipeline-history-list" class="tenders-pipeline-history-list" role="list"></div>
                        </section>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-tenders-pipeline-close data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-light fa-floppy-disk" aria-hidden="true"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
