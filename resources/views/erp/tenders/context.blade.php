@php
    $context = $context ?? null;
    $services = implode(', ', $context?->services ?? []);
    $technologies = implode(', ', $context?->technologies ?? []);
    $sectors = implode(', ', $context?->sectors ?? []);
    $geography = implode(', ', $context?->geography ?? []);
    $excludedTerms = implode(', ', $context?->excluded_terms ?? []);
@endphp
<div class="tab-pane fade" id="tenders-context" role="tabpanel" aria-labelledby="tenders-context-tab">
    <div id="tenders-context-container" class="scrollable">
        <form id="tenders-context-form" class="tenders-context-form">
            <div class="tenders-context-header">
                <h2>Contexto de la empresa</h2>
                <button type="submit" class="btn btn-primary" title="Guardar contexto">
                    <i class="fa-light fa-floppy-disk" aria-hidden="true"></i>
                    <span>Guardar</span>
                </button>
            </div>
            <div id="tenders-context-status" class="tenders-context-status" role="status" aria-live="polite"></div>
            <div class="tenders-context-grid">
                <div class="tenders-context-field">
                    <label for="tenders-context-company-name">Razón social</label>
                    <input type="text" id="tenders-context-company-name" name="company_name" class="form-control" maxlength="255" value="{{ old('company_name', $context?->company_name) }}" required>
                </div>
                <div class="tenders-context-field">
                    <label for="tenders-context-identification">NIT</label>
                    <input type="text" id="tenders-context-identification" name="identification" class="form-control" maxlength="100" value="{{ old('identification', $context?->identification) }}">
                </div>
                <div class="tenders-context-field tenders-context-field-wide">
                    <label for="tenders-context-description">Identidad, misión y visión</label>
                    <textarea id="tenders-context-description" name="description" class="form-control" rows="4" maxlength="10000">{{ old('description', $context?->description) }}</textarea>
                </div>
                <div class="tenders-context-field tenders-context-field-wide">
                    <label for="tenders-context-services">Servicios y soluciones</label>
                    <textarea id="tenders-context-services" name="services" class="form-control" rows="3" maxlength="5000" placeholder="Desarrollo de software, integraciones, analítica">{{ old('services', $services) }}</textarea>
                </div>
                <div class="tenders-context-field tenders-context-field-wide">
                    <label for="tenders-context-technologies">Pilares, capacidades y metodología</label>
                    <textarea id="tenders-context-technologies" name="technologies" class="form-control" rows="3" maxlength="5000" placeholder="Python, Laravel, cloud, inteligencia artificial">{{ old('technologies', $technologies) }}</textarea>
                </div>
                <div class="tenders-context-field">
                    <label for="tenders-context-sectors">Sectores y tipos de organización</label>
                    <textarea id="tenders-context-sectors" name="sectors" class="form-control" rows="3" maxlength="3000" placeholder="Gobierno, educación, salud">{{ old('sectors', $sectors) }}</textarea>
                </div>
                <div class="tenders-context-field">
                    <label for="tenders-context-geography">Cobertura geográfica</label>
                    <textarea id="tenders-context-geography" name="geography" class="form-control" rows="3" maxlength="3000" placeholder="Colombia, remoto, híbrido">{{ old('geography', $geography) }}</textarea>
                </div>
                <div class="tenders-context-field">
                    <label for="tenders-context-min-value">Valor mínimo objetivo</label>
                    <input type="number" id="tenders-context-min-value" name="min_contract_value" class="form-control" min="0" step="0.01" value="{{ old('min_contract_value', $context?->min_contract_value) }}">
                </div>
                <div class="tenders-context-field">
                    <label for="tenders-context-max-value">Valor máximo objetivo</label>
                    <input type="number" id="tenders-context-max-value" name="max_contract_value" class="form-control" min="0" step="0.01" value="{{ old('max_contract_value', $context?->max_contract_value) }}">
                </div>
                <div class="tenders-context-field tenders-context-field-wide">
                    <label for="tenders-context-excluded-terms">Términos o líneas excluidas</label>
                    <textarea id="tenders-context-excluded-terms" name="excluded_terms" class="form-control" rows="3" maxlength="5000" placeholder="Hardware puro, cableado, suministro">{{ old('excluded_terms', $excludedTerms) }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>
