<div class="row-container">
    <div class="segment-container incomes-by-client-segment">
        <div class="segment-header">
            <h1 class="segment-title">Distribución de Ingresos por Cliente<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos la distribución de ingresos por cliente en el rango de fechas seleccionado."></i></h1>
            <div class="input-date-range-container">
                <input type="month" id="incomes-by-client-month-from-input" class="segment-month incomes-by-client-input" value="{{ \Carbon\Carbon::now()->subMonths(6)->format('Y-m') }}">
                <input type="month" id="incomes-by-client-month-to-input" class="segment-month incomes-by-client-input" value="{{  \Carbon\Carbon::now()->format('Y-m') }}">
            </div>
        </div>
        <div class="incomes-by-client-total-container">
            <p class="incomes-by-client-total">Total: $0</p>
        </div>
        <div class="incomes-by-client-content">
            <canvas id="incomes-by-client-graph"></canvas>
        </div>
    </div>
</div>