<div class="tab-pane fade" id="jira-reports" role="tabpanel" aria-labelledby="jira-reports-tab">
    <section class="jira-panel jira-report-list-panel" data-jira-report-list-view>
        <div class="jira-panel-title jira-report-list-header">
            <div>
                <span class="jira-panel-kicker">Analisis y seguimiento</span>
                <h2>Reportes generados</h2>
            </div>
            <div class="jira-report-list-actions">
                <span class="jira-report-count" data-jira-report-count>{{ $reports->count() }}</span>
                <button class="btn btn-primary jira-report-add-button" type="button" data-jira-report-open aria-label="Generar nuevo reporte" title="Generar nuevo reporte">
                    <i class="fa-light fa-plus" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="jira-report-filters" data-jira-report-filters>
            <label class="jira-report-filter jira-report-filter-search">
                <span>Buscar</span>
                <input type="search" data-jira-report-search placeholder="Titulo, proyecto o epica" autocomplete="off">
            </label>
            <label class="jira-report-filter">
                <span>Proyecto</span>
                <select data-jira-report-project-filter aria-label="Filtrar por proyecto">
                    <option value="">Todos los proyectos</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_key }} · {{ $project->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="jira-report-filter">
                <span>Estado</span>
                <select data-jira-report-status-filter aria-label="Filtrar por estado">
                    <option value="">Todos los estados</option>
                    <option value="generated">Generado</option>
                    <option value="generating">En proceso</option>
                    <option value="failed">Con error</option>
                </select>
            </label>
            <label class="jira-report-filter">
                <span>Intencion</span>
                <select data-jira-report-intention-filter aria-label="Filtrar por intencion">
                    <option value="">Todas las intenciones</option>
                    @foreach([
                        'internal_improvement' => 'Mejora interna',
                        'client_report' => 'Resultados para cliente',
                        'executive_summary' => 'Resumen ejecutivo',
                        'team_capacity' => 'Capacidad y distribucion del equipo',
                        'project_progress' => 'Avance por proyecto',
                        'delivery_risks' => 'Riesgos de entrega',
                        'unplanned_work' => 'Calidad y trabajo no planificado',
                    ] as $intention => $label)
                        <option value="{{ $intention }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="jira-report-filter">
                <span>Recurrencia</span>
                <select data-jira-report-recurrence-filter aria-label="Filtrar por recurrencia">
                    <option value="">Todos los reportes</option>
                    <option value="recurring">Recurrentes</option>
                    <option value="non_recurring">No recurrentes</option>
                </select>
            </label>
            <button class="btn btn-light jira-report-clear-button" type="button" data-jira-report-clear-filters>
                <i class="fa-light fa-filter-circle-xmark" aria-hidden="true"></i>
                <span>Limpiar</span>
            </button>
        </div>
        <p class="jira-status" data-jira-report-list-status role="status" aria-live="polite"></p>
        <div class="jira-table-wrap jira-report-table-wrap">
            <table id="jira-report-table" class="table table-hover table-sm align-middle w-100 erp-data-table jira-report-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-start">Reporte</th>
                        <th scope="col" class="text-start">Proyecto</th>
                        <th scope="col" class="text-start">Intencion</th>
                        <th scope="col" class="text-center">Periodo</th>
                        <th scope="col" class="text-center">Actualizado</th>
                        <th scope="col" class="text-center">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody data-jira-report-list>
                    @include('erp.jira.reports.rows', ['reports' => $reports])
                </tbody>
            </table>
        </div>
        <ul class="pagination pagination-sm justify-content-end px-0 mx-0 d-flex jira-table-pagination" data-jira-report-pagination role="navigation" aria-label="Paginacion de reportes"></ul>
    </section>

    <section class="jira-panel jira-report-detail" data-jira-report-detail hidden aria-live="polite"></section>

    <div class="jira-report-modal" data-jira-report-modal hidden aria-hidden="true">
        <div class="jira-report-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="jira-report-modal-title">
            <div class="jira-report-modal-header">
                <div>
                    <span class="jira-panel-kicker">Nuevo reporte</span>
                    <h2 id="jira-report-modal-title">Generar reporte</h2>
                </div>
                <button class="jira-modal-icon-button" type="button" data-jira-report-close aria-label="Cerrar formulario" title="Cerrar">
                    <i class="fa-light fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form class="jira-report-form" data-jira-report-form>
                <div class="jira-form-grid">
                    <label class="jira-field jira-field-wide"><span>Titulo</span><input name="title" maxlength="200" placeholder="Resumen de avance del equipo"></label>
                    <label class="jira-field"><span>Desde</span><input name="from_date" type="date" value="{{ now()->startOfMonth()->toDateString() }}" required></label>
                    <label class="jira-field"><span>Hasta</span><input name="to_date" type="date" value="{{ now()->toDateString() }}" required></label>
                    <div class="jira-field jira-field-wide jira-recurrence-field">
                        <label class="jira-recurrence-toggle"><input type="checkbox" name="recurrence_enabled" value="1" data-jira-recurrence-toggle><span>Hacer este reporte recurrente</span></label>
                        <div class="jira-recurrence-options" data-jira-recurrence-options hidden>
                            <label class="jira-recurrence-option"><span>Generar cada</span><div><input type="number" name="frequency_value" value="1" min="1" max="365" data-jira-recurrence-input><select name="frequency_unit" data-jira-recurrence-input><option value="days">Dias</option><option value="months">Meses</option></select></div></label>
                            <label class="jira-recurrence-option jira-monthly-execution-day" data-jira-monthly-day-field hidden><span>Dia de ejecucion mensual</span><select name="execution_day" data-jira-recurrence-input aria-label="Dia de ejecucion mensual">@for($day = 1; $day <= 31; $day++)<option value="{{ $day }}">{{ $day }}</option>@endfor</select></label>
                            <label class="jira-recurrence-option"><span>Rango de fechas por ejecucion</span><div><input type="number" name="range_value" value="1" min="1" max="365" data-jira-recurrence-input><select name="range_unit" data-jira-recurrence-input><option value="days">Dias</option><option value="months">Meses</option></select></div></label>
                            <small data-jira-recurrence-help>Se generara un nuevo reporte con los filtros seleccionados y la fecha de generacion en el titulo.</small>
                        </div>
                    </div>
                    <label class="jira-field" data-jira-report-project-filter><span>Proyectos</span><select name="project_ids[]" multiple data-jira-report-project-native aria-label="Filtrar por proyectos">@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->project_key }} · {{ $project->name }}</option>@endforeach</select></label>
                    <label class="jira-field" data-jira-report-epic-filter><span>Epicas</span><select name="epic_ids[]" multiple data-jira-report-epic-native aria-label="Filtrar por epicas"></select></label>
                    <label class="jira-field" data-jira-report-user-filter><span>Usuarios</span><select name="user_ids[]" multiple data-jira-report-user-native aria-label="Filtrar por usuarios">@foreach($jiraUsers as $jiraUser)<option value="{{ $jiraUser->id }}">{{ $jiraUser->display_name }}</option>@endforeach</select></label>
                    <label class="jira-field" data-jira-report-status-filter><span>Estados</span><select name="statuses[]" multiple data-jira-report-status-native aria-label="Filtrar por estados">@foreach($jiraStatuses as $jiraStatus)<option value="{{ $jiraStatus }}">{{ $jiraStatus }}</option>@endforeach</select></label>
                    <label class="jira-field jira-field-wide"><span>Intencion</span><select name="intention" required><option value="internal_improvement">Mejora interna</option><option value="client_report">Resultados para cliente</option><option value="executive_summary">Resumen ejecutivo</option><option value="team_capacity">Capacidad y distribucion del equipo</option><option value="project_progress">Avance por proyecto</option><option value="delivery_risks">Riesgos de entrega</option><option value="unplanned_work">Calidad y trabajo no planificado</option></select></label>
                    <div class="jira-field jira-field-wide"><span>Selector de datos</span><div class="jira-check-grid">@foreach(['story_points' => 'Story Points', 'worklogs' => 'Horas de worklog', 'projects' => 'Proyectos', 'epics' => 'Epicas', 'users' => 'Usuarios', 'statuses' => 'Estados', 'erp_relations' => 'Relaciones ERP', 'quality' => 'Calidad de datos'] as $source => $label)<label><input type="checkbox" name="data_sources[]" value="{{ $source }}" checked><span>{{ $label }}</span></label>@endforeach</div></div>
                    <label class="jira-field jira-field-wide"><span>Contexto para la IA</span><textarea name="context_prompt" rows="5" maxlength="5000" placeholder="Ejemplo: destaca bloqueos y distribucion de carga por licencia."></textarea></label>
                </div>
                <div class="jira-report-modal-actions">
                    <button class="btn btn-light" type="button" data-jira-report-close>Cancelar</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-light fa-sparkles" aria-hidden="true"></i> Generar reporte</button>
                </div>
                <p class="jira-status" data-jira-report-status role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>

    <div class="jira-report-pdf-viewer" data-jira-report-pdf-viewer hidden aria-hidden="true">
        <button type="button" class="jira-report-pdf-close" data-jira-report-pdf-close title="Cerrar visualizador" aria-label="Cerrar visualizador">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
        <div class="jira-report-pdf-dialog" role="dialog" aria-modal="true" aria-labelledby="jira-report-pdf-title">
            <div class="jira-report-pdf-heading">
                <div>
                    <span class="jira-panel-kicker">Documento generado</span>
                    <h2 id="jira-report-pdf-title">Vista previa</h2>
                </div>
                <span class="jira-report-pdf-period" data-jira-report-pdf-period></span>
            </div>
            <div id="order-viewer" class="jira-pdf-document" tabindex="-1" aria-label="Vista previa del reporte">
                <div id="pdf-toolbar">
                    <div class="pdf-toolbar-nav">
                        <button id="pdf-prev-page" class="btn btn-sm" type="button" data-jira-pdf-action="previous" title="Pagina anterior" aria-label="Pagina anterior"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                        <span id="pdf-page-info"><span id="pdf-page-num">1</span> / <span id="pdf-page-count">-</span></span>
                        <button id="pdf-next-page" class="btn btn-sm" type="button" data-jira-pdf-action="next" title="Pagina siguiente" aria-label="Pagina siguiente"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                    </div>
                    <div class="pdf-toolbar-actions">
                        <button id="pdf-zoom-out" class="btn btn-sm" type="button" data-jira-pdf-action="zoom-out" title="Alejar" aria-label="Alejar"><i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i></button>
                        <button id="pdf-zoom-in" class="btn btn-sm" type="button" data-jira-pdf-action="zoom-in" title="Acercar" aria-label="Acercar"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></button>
                        <span class="pdf-toolbar-divider" aria-hidden="true"></span>
                        <button id="pdf-print" class="btn btn-sm" type="button" data-jira-pdf-action="print" title="Imprimir" aria-label="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i></button>
                        <button id="pdf-download" class="btn btn-sm" type="button" data-jira-pdf-action="download" title="Descargar" aria-label="Descargar"><i class="fa-solid fa-download" aria-hidden="true"></i></button>
                        <button id="pdf-share" class="btn btn-sm" type="button" data-jira-pdf-action="share" title="Compartir" aria-label="Compartir"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button>
                        <button id="pdf-fullscreen" class="btn btn-sm" type="button" data-jira-pdf-action="fullscreen" title="Pantalla completa" aria-label="Pantalla completa"><i class="fa-solid fa-expand" aria-hidden="true"></i></button>
                        <button class="btn btn-sm jira-report-pdf-send-button" type="button" data-jira-report-pdf-email-toggle title="Enviar por correo"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Enviar</span></button>
                    </div>
                </div>
                <div id="pdf-canvas-container">
                    <div id="pdf-loading"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i></div>
                    <canvas id="pdf-canvas"></canvas>
                </div>
            </div>
            <form class="jira-report-pdf-email" data-jira-report-pdf-email hidden>
                <label class="jira-field"><span>Correo destinatario</span><input type="email" name="recipients" required maxlength="2000" placeholder="correo@empresa.com"></label>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar PDF</button>
                <p class="jira-status" data-jira-report-pdf-email-status role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>
</div>
