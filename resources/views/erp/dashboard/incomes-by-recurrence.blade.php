<div class="row-container dashboard-recurrence-row">
    <section class="segment-container incomes-by-recurrence-segment" aria-labelledby="incomes-by-recurrence-title">
        <div class="incomes-by-recurrence-header">
            <div>
                <h1 class="segment-title" id="incomes-by-recurrence-title">Ingresos recurrentes<i class="tooltip-icon fa-regular fa-circle-question" title="Comparamos la proyección de licencias recurrentes con los ingresos pagados en el rango seleccionado."></i></h1>
                <p class="incomes-by-recurrence-subtitle">Licencias activas, ciclos proyectados y pagos conseguidos</p>
            </div>
            <div class="input-date-range-container">
                <input type="month" id="incomes-by-recurrence-month-from-input" class="segment-month incomes-by-recurrence-input" value="{{ \Carbon\Carbon::now()->subMonths(5)->format('Y-m') }}" aria-label="Inicio del rango de ingresos recurrentes">
                <input type="month" id="incomes-by-recurrence-month-to-input" class="segment-month incomes-by-recurrence-input" value="{{ \Carbon\Carbon::now()->format('Y-m') }}" aria-label="Fin del rango de ingresos recurrentes">
            </div>
        </div>
        <div class="incomes-by-recurrence-summary">
            <div class="incomes-by-recurrence-summary-item">
                <span>Proyección del rango</span>
                <strong class="incomes-by-recurrence-projected-total">$0</strong>
            </div>
            <div class="incomes-by-recurrence-summary-item">
                <span>Pagado en el rango</span>
                <strong class="incomes-by-recurrence-paid-total">$0</strong>
            </div>
        </div>
        <div class="incomes-by-recurrence-grid" aria-live="polite">
            <div class="incomes-by-recurrence-state">
                <i class="fa-duotone fa-spinner-third fa-spin" aria-hidden="true"></i>
                <span>Cargando recurrencias</span>
            </div>
        </div>
    </section>
</div>