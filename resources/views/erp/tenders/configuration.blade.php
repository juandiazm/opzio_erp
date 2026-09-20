<div class="tab-pane fade" id="tenders-configuration" role="tabpanel" aria-labelledby="tenders-configuration-tab">
    <div id="tenders-configuration-container" class="scrollable">
        <form id="tenders-configuration-form" class="tenders-configuration-form">
            <div class="tenders-configuration-header">
                <div>
                    <h2>Configuracion de Licitaciones</h2>
                    <p>Administra la conexion con SECOP y los filtros duros del catalogo.</p>
                </div>
                <button type="submit" class="btn btn-primary" title="Guardar configuracion">
                    <i class="fa-light fa-floppy-disk" aria-hidden="true"></i>
                    <span>Guardar</span>
                </button>
            </div>
            <div id="tenders-configuration-status" class="tenders-configuration-status" role="status" aria-live="polite"></div>
            <div class="tenders-configuration-grid">
                <section class="tenders-configuration-section">
                    <h3>Conexion SECOP</h3>
                    <label class="tenders-configuration-field">
                        <span>Nombre interno</span>
                        <input type="text" name="name" maxlength="150" value="{{ old('name', $connection?->name ?? 'SECOP principal') }}" required>
                    </label>
                    <label class="tenders-configuration-field">
                        <span>Token de aplicacion Socrata</span>
                        <input type="password" name="app_token" autocomplete="new-password" placeholder="Dejar vacio para conservarlo">
                    </label>
                    <div class="tenders-configuration-checks">
                        <label><input type="checkbox" name="secop1_enabled" value="1" @checked(data_get($settings, 'sources.secop1.enabled', true))> SECOP I</label>
                        <label><input type="checkbox" name="secop2_enabled" value="1" @checked(data_get($settings, 'sources.secop2.enabled', true))> SECOP II</label>
                    </div>
                    <div class="tenders-configuration-two-columns">
                        <label class="tenders-configuration-field"><span>Timeout (segundos)</span><input type="number" name="timeout" min="1" max="120" value="{{ data_get($settings, 'transport.timeout', 30) }}"></label>
                        <label class="tenders-configuration-field"><span>Reintentos</span><input type="number" name="retries" min="0" max="5" value="{{ data_get($settings, 'transport.retries', 2) }}"></label>
                    </div>
                    <div class="tenders-configuration-two-columns">
                        <label class="tenders-configuration-field"><span>Filas por lote</span><input type="number" name="page_size" min="1" max="250" value="{{ data_get($settings, 'transport.page_size', 250) }}"></label>
                        <label class="tenders-configuration-field"><span>Dias de ventana</span><input type="number" name="lookback_days" min="1" max="90" value="{{ data_get($settings, 'transport.lookback_days', 7) }}"></label>
                    </div>
                    <div class="tenders-configuration-status-card">
                        <span>Estado: <strong>{{ $connection?->status ?? 'draft' }}</strong></span>
                        <span>Ultima prueba: {{ $connection?->last_tested_at?->format('Y-m-d H:i') ?? 'Nunca' }}</span>
                        <span>Ultima sincronizacion: {{ $connection?->last_sync_at?->format('Y-m-d H:i') ?? 'Nunca' }}</span>
                    </div>
                    <div class="tenders-configuration-actions">
                        <button type="button" id="tenders-configuration-test" class="btn btn-outline-secondary"><i class="fa-light fa-plug-circle-check" aria-hidden="true"></i> Probar conexion</button>
                        <button type="button" id="tenders-configuration-sync" class="btn btn-outline-primary"><i class="fa-light fa-arrows-rotate" aria-hidden="true"></i> Sincronizar SECOP</button>
                    </div>
                </section>
                <section class="tenders-configuration-section">
                    <h3>Filtros duros</h3>
                    <label class="tenders-configuration-check-label"><input type="checkbox" name="only_postulable" value="1" @checked(data_get($settings, 'filters.only_postulable', true))> Excluir procesos no postulables</label>
                    <div class="tenders-configuration-two-columns">
                        <label class="tenders-configuration-field"><span>Valor minimo</span><input type="number" name="min_contract_value" min="0" step="0.01" value="{{ data_get($settings, 'filters.min_contract_value') }}"></label>
                        <label class="tenders-configuration-field"><span>Valor maximo</span><input type="number" name="max_contract_value" min="0" step="0.01" value="{{ data_get($settings, 'filters.max_contract_value') }}"></label>
                    </div>
                    <label class="tenders-configuration-field"><span>Dias minimos antes del cierre</span><input type="number" name="min_days_to_deadline" min="0" max="365" value="{{ data_get($settings, 'filters.min_days_to_deadline', 0) }}"></label>
                    @foreach ([
                        'departments' => 'Departamentos',
                        'cities' => 'Ciudades',
                        'procurement_methods' => 'Modalidades de contratacion',
                        'contract_types' => 'Tipos de contrato',
                        'category_codes' => 'Codigos de categoria',
                        'excluded_terms' => 'Terminos excluidos',
                    ] as $field => $label)
                        <label class="tenders-configuration-field"><span>{{ $label }}</span><textarea name="{{ $field }}" rows="2" maxlength="5000">{{ implode(', ', data_get($settings, 'filters.'.$field, [])) }}</textarea></label>
                    @endforeach
                    <div class="tenders-configuration-status-card">
                        <span>Version de filtros: <strong>{{ data_get($settings, 'filters_version', 1) }}</strong></span>
                        <span>El contexto comercial se administra en la pestaña Contexto.</span>
                    </div>
                </section>
            </div>
            <div id="tenders-configuration-progress" class="tenders-configuration-progress" hidden aria-live="polite"></div>
        </form>
    </div>
</div>
