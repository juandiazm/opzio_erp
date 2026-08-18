<div class="row-container dashboard-graph-row">
    <div class="segment-container income-outcome-graph-segment">
        <div class="segment-header">
            <h1 class="segment-title">Ingresos y Egresos<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos los ingresos y egresos de la empresa en el mes seleccionado."></i></h1>
            <div class="input-date-range-container">
                <input type="month" id="income-outcome-graph-month-form-input" class="segment-month income-outcome-by-month-input" value="{{ \Carbon\Carbon::now()->subMonths(6)->format('Y-m') }}">
                <input type="month" id="income-outcome-graph-month-to-input" class="segment-month income-outcome-by-month-input" value="{{  \Carbon\Carbon::now()->format('Y-m') }}">
            </div>
        </div>
        <div class="income-outcome-balance-container">
            <p class="income-total">$0</p>
            <p class="outcome-total">$0</p>
            <p class="balance-total">$0</p>
        </div>
        <canvas id="income-outcome-graph"></canvas>
        <div class="income-outcome-average-container">
            <div class="average-item">
                <span class="average-label">Promedio Ingresos:</span>
                <span class="average-income-value">$0</span>
            </div>
            <div class="average-item">
                <span class="average-label">Promedio Egresos:</span>
                <span class="average-outcome-value">$0</span>
            </div>
        </div>
    </div>
</div>