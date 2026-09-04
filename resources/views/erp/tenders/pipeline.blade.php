<div class="tab-pane fade" id="tenders-pipeline" role="tabpanel" aria-labelledby="tenders-pipeline-tab">
    <div id="tenders-pipeline-container" class="scrollable">
        <div class="tenders-pipeline-toolbar">
            <label class="licitaciones-filter" for="tenders-pipeline-stage">
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
        </div>
        <div id="tenders-pipeline-status" class="tenders-pipeline-status" role="status" aria-live="polite"></div>
        <div id="tenders-pipeline-list" class="tenders-pipeline-list" role="list"></div>
    </div>
</div>
