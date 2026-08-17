<div class="row-container">
    <div class="segment-container income-outcome-segment">
        <div class="segment-header">
            <h1 class="segment-title">Ingresos y Egresos<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos los ingresos y egresos de la empresa en el mes seleccionado."></i></h1>
            <input type="month" id="income-outcome-month-input" class="segment-month" value="{{ \Carbon\Carbon::now()->format('Y-m') }}">
        </div>
        <div class="income-number-container">
            <div class="value-container">
                <p class="income-number"><i class="income-number-icon fa-solid fa-arrow-up"></i><span></span></p>
                <div class="income-title">Ingresos</div>
            </div>
            <p class="last-month-comparison-message"><i class="last-month-comparison-icon fa-solid fa-arrow-down"></i></p>
        </div>
        <div class="separate-line"></div>
        <div class="outcome-number-container">
            <div class="value-container">
                <p class="outcome-number"><i class="outcome-number-icon fa-solid fa-arrow-down"></i><span></span></p>
                <div class="outcome-title">Egresos</div>
            </div>
            <p class="last-month-comparison-message"><i class="last-month-comparison-icon fa-solid fa-arrow-up"></i></p>
        </div>
    </div>
    <div class="segment-container collect-active-container">
        <div class="collect-active-sub-container collect-container">
            <div class="segment-header">
                <h1 class="segment-title">Cartera por Recoger<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos la cartera por recoger de la empresa."></i></h1>
            </div>
            <p class="receivable-value"></p>
        </div>
        <div class="collect-active-sub-container active-clients-container">
            <div class="segment-header">
                <h1 class="segment-title">Clientes Activos<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos la cantidad y licencias activos."></i></h1>
            </div>
            <p class="active-clients-value"></p>
            <p class="active-clients-value-licenses"></p>
        </div>
    </div>
</div>