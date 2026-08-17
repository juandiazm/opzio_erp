<section id="zoom-in-super-container" class="d-none">
    <div id="zoom-in-container">
        <div id="zoom-in-header-container">
            <div class="left-container">
                <h1 id="zoom-in-title"></h1>
                <i class="tooltip-icon fa-regular fa-circle-question" title="Muestra el detalle de un usuario en el rango de fechas seleccionado."></i>
                <input type="text" class="zoom-in-report-item-date-input" id="date-range-input-zoom-in">
                <div id="zoom-in-export-report-excel-container">
                    <i class="fas fa-file-excel" id="zoom-in-export-report-excel-icon"></i>
                </div>
            </div>
            <i class="fas fa-times" id="zoom-in-close-icon"></i>
        </div>
        <div id="zoom-in-data-container">
            <div id="graphic-container">
                <canvas id="zoom-in-report-graph"></canvas>
                <div id="zoom-in-graph-info-container">
                    <div class="zoom-in-label">
                        <span class="label-text">Total</span>
                        <span class="label-value" id="zoom-in-total-value">$0</span>
                    </div>
                    <div class="zoom-in-label">
                        <span class="label-text">Partición</span>
                        <span class="label-value" id="zoom-in-partition-value">0</span>
                    </div>
                    <div class="zoom-in-label">
                        <span class="label-text">Promedio</span>
                        <span class="label-value" id="zoom-in-average-value">$0</span>
                    </div>
                </div>
            </div>
            <table id="zoom-in-table" class="table table-striped"></table>
        </div>
    </div>
</section>