@extends('erp.layouts.app')
@section('component_title', 'Dashboard')
@section('erp-app-header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
@vite('resources/js/erp/dashboard/dashboard.js')
<!-- Styles -->
@vite('resources/sass/erp/dashboard/dashboard.scss')
@vite('resources/sass/erp/dashboard/dashboard-overdue.scss')
@endsection
@section('erp-app-content')
<div id="dashboard-container" class="scrollable">
    <div class="column-container">
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
                        <h1 class="segment-title">Clientes Activos<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos la cantidad y licencias activos."></i>
                    </div>
                    <p class="active-clients-value"></p>
                    <p class="active-clients-value-licenses"></p>
                </div>
            </div>
            
        </div>
        <div class="row-container">
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
        <div class="row-container">
            <div class="segment-container approve-incomes-segment">
                <div class="segment-header">
                    <h1 class="segment-title">Cartera<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos los ingresos pendientes por aprobar."></i></h1>
                    <p class="approve-incomes-value"></p>
                    <div class="approve-incomes-quantity"><span></span></div>
                </div>
                <div class="approve-incomes-table-container">
                    <table class="table approve-incomes-table">
                        <thead>
                            <tr>
                                <th class="approve-incomes-link">Link</th>
                                <th class="approve-incomes-client">Cliente</th>
                                <th class="approve-incomes-amount">Valor</th>
                                <th class="approve-incomes-cutoff">Fecha Corte</th>
                                <th class="approve-incomes-overdue">Días Vencido</th>
                                <th class="approve-incomes-action">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="column-container">
        <div class="row-container">
            <div class="segment-container quotation-segment">
                <div class="segment-header">
                    <h1 class="segment-title">Cotizaciones<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos las cotizaciones pendientes."></i></h1>
                    <p class="quotation-value"></p>
                    <div class="quotation-quantity"><span></span></div>
                </div>
                <div class="quotation-table-container">
                    <table class="table quotation-table">
                        <thead>
                            <tr>
                                <th class="quotation-client">Cliente</th>
                                <th class="quotation-amount">Valor</th>
                                <th class="quotation-action">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="segment-container due-clients-segment">
                <div class="segment-header">
                    <h1 class="segment-title">Clientes en mora<i class="tooltip-icon fa-regular fa-circle-question" title="Te mostramos los clientes con ingresos pendientes por pagar."></i></h1>
                </div>
                <div class="due-clients-table-container">
                    <table class="table due-clients-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="client-name">Servicio</th>
                                <th class="client-amount">Valor</th>
                                <th class="client-days">Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
    </div>
</div>
@endsection
