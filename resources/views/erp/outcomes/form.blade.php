@php($prefix = $prefix ?? 'create')
<div class="outcome-form-container" id="{{ $prefix }}-outcome-form">
    <div class="outcome-form-header">
        <div>
            <span class="outcome-form-kicker">{{ $prefix === 'create' ? 'Nuevo registro' : 'Registro seleccionado' }}</span>
            <h2>{{ $prefix === 'create' ? 'Crear egreso' : 'Editar egreso' }}</h2>
        </div>
        <i class="fa-solid fa-arrow-down-wide-short outcome-form-icon" aria-hidden="true"></i>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-date">Fecha del egreso</label>
            <input type="date" class="form-control" id="{{ $prefix }}-outcome-date" value="{{ $prefix === 'create' ? now()->format('Y-m-d') : '' }}">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-type">Tipo</label>
            <select class="form-select js-searchable-dropdown" id="{{ $prefix }}-outcome-type" data-placeholder="Seleccionar tipo">
                <option value="-1">Otro</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-name">Nombre</label>
            <input type="text" class="form-control" id="{{ $prefix }}-outcome-name" maxlength="100" placeholder="Nombre del egreso">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-amount">Monto</label>
            <input type="number" class="form-control" id="{{ $prefix }}-outcome-amount" min="0.01" step="0.01" placeholder="0.00">
        </div>
        <div class="col-12">
            <div class="outcome-associations-heading">
                <span>Asociaciones</span>
                <small>Opcionales</small>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-provider">Proveedor</label>
            <select class="form-select outcome-association js-searchable-dropdown" id="{{ $prefix }}-outcome-provider" data-catalog="providers" data-placeholder="Sin proveedor">
                <option value="">Sin proveedor</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-employee">Empleado</label>
            <select class="form-select outcome-association js-searchable-dropdown" id="{{ $prefix }}-outcome-employee" data-catalog="employees" data-placeholder="Sin empleado">
                <option value="">Sin empleado</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-department">Departamento</label>
            <select class="form-select outcome-association js-searchable-dropdown" id="{{ $prefix }}-outcome-department" data-catalog="departments" data-placeholder="Sin departamento">
                <option value="">Sin departamento</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-user">Usuario</label>
            <select class="form-select outcome-association js-searchable-dropdown" id="{{ $prefix }}-outcome-user" data-catalog="users" data-placeholder="Sin usuario">
                <option value="">Sin usuario</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}-outcome-client">Cliente</label>
            <select class="form-select outcome-association js-searchable-dropdown" id="{{ $prefix }}-outcome-client" data-catalog="clients" data-placeholder="Sin cliente">
                <option value="">Sin cliente</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" for="{{ $prefix }}-outcome-description">Descripción</label>
            <textarea class="form-control" id="{{ $prefix }}-outcome-description" rows="4" placeholder="Detalle del egreso"></textarea>
        </div>
    </div>
    <div class="outcome-form-actions">
        <button type="button" class="btn btn-primary" id="{{ $prefix }}-outcome-button">{{ $prefix === 'create' ? 'Crear egreso' : 'Guardar cambios' }}</button>
    </div>
</div>