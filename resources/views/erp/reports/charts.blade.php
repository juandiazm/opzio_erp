<ul id="report-containers-list">
    <li class="report-item" value="incomes">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Ingresos</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="incomes-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra los ingresos registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-incomes">
        </div>
        <canvas class="report-item-canvas" id="incomes-report-graph"></canvas>
    </li>
    <li class="report-item" value="outcomes">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Egresos</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="outcomes-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra los egresos registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-outcomes">
        </div>
        <canvas class="report-item-canvas" id="outcomes-report-graph"></canvas>
    </li>
    <li class="report-item" value="clients">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Clientes</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="clients-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Clientes registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-clients">
        </div>
        <canvas class="report-item-canvas" id="clients-report-graph"></canvas>
    </li>
    <li class="report-item" value="licenses">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Licencias</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="licenses-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra las licencias registradas en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-licenses">
        </div>
        <canvas class="report-item-canvas" id="licenses-report-graph"></canvas>
    </li>
    <li class="report-item" value="users">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Usuarios</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="users-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Usuario registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-users">
        </div>
        <canvas class="report-item-canvas" id="users-report-graph"></canvas>
    </li>
    <li class="report-item" value="employees">
        <div class="report-item-header">
            <div class="header-left">
                <span class="report-item-title">Empleados</span>
                <input type="checkbox" class="report-item-checkbox" class="report-item-checkbox" checked id="employees-checkbox">
                <i class="tooltip-icon fa-regular fa-circle-question" title="Empleados registrados en el rango de fechas seleccionado."></i>
            </div>
            <input type="text" class="report-item-date-input" id="date-range-input-employees">
        </div>
        <canvas class="report-item-canvas" id="employees-report-graph"></canvas>
    </li>
</ul>