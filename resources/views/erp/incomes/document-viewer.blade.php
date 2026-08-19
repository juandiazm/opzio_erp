<!-- Purchase Order Viewer -->
<div id="order-viewer-container" role="dialog" aria-modal="true" aria-labelledby="order-viewer-title" aria-hidden="true">
    <button type="button" id="close-order-viewer" class="pdf-viewer-close" title="Cerrar visualizador" aria-label="Cerrar visualizador">
        <i class="fa-solid fa-times" aria-hidden="true"></i>
    </button>
    <div id="order-viewer-sub-container">
        <h1 id="order-viewer-title">Documento</h1>
        <div id="order-viewer">
            <div id="pdf-toolbar">
                <div class="pdf-toolbar-nav">
                    <button id="pdf-prev-page" class="btn btn-sm" title="Página anterior"><i class="fa-solid fa-chevron-left"></i></button>
                    <span id="pdf-page-info"><span id="pdf-page-num">1</span> / <span id="pdf-page-count">-</span></span>
                    <button id="pdf-next-page" class="btn btn-sm" title="Página siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="pdf-toolbar-actions">
                    <button id="pdf-zoom-out" class="btn btn-sm" title="Alejar"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button id="pdf-zoom-in" class="btn btn-sm" title="Acercar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <span class="pdf-toolbar-divider"></span>
                    <button id="pdf-print" class="btn btn-sm" title="Imprimir"><i class="fa-solid fa-print"></i></button>
                    <button id="pdf-download" class="btn btn-sm" title="Descargar"><i class="fa-solid fa-download"></i></button>
                    <button id="pdf-share" class="btn btn-sm" title="Compartir"><i class="fa-solid fa-share-nodes"></i></button>
                    <button id="pdf-fullscreen" class="btn btn-sm" title="Pantalla completa"><i class="fa-solid fa-expand"></i></button>
                    <button type="button" class="btn btn-sm pdf-viewer-close-button" data-close-order-viewer title="Cerrar visualizador" aria-label="Cerrar visualizador"><i class="fa-solid fa-times" aria-hidden="true"></i></button>
                </div>
            </div>
            <div id="pdf-canvas-container">
                <div id="pdf-loading"><i class="fa-solid fa-spinner fa-spin"></i></div>
                <canvas id="pdf-canvas"></canvas>
            </div>
        </div>
        <div id="order-viewer-buttons" class="d-flex justify-content-center">
            <button id="send-order-button" class="btn">
                <i class="fa-solid fa-paper-plane"></i>
                Enviar
            </button>
        </div>
    </div>
    <!-- Purchase send container -->
    <div id="send-order-container">
        <div id="send-order-sub-container">
            <h1 id="send-order-title">Receptores</h1>
            <table id="receivers-list" class="table table-hover table-sm align-middle w-100">
                <thead id="receivers-list-header">
                    <tr>
                        <th scope="col" class="columns-send-email text-left">Correo</th>
                        <th scope="col" class="columns-send-phone text-left">Teléfono</th>
                        <th scope="col" class="columns-send-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="receivers-list-body">
                    
                </tbody>
            </table>
            <div id="send-order-buttons" class="d-flex justify-content-between">
                <button id="cancel-send-order-button" class="btn">
                    <i class="fa-solid fa-times"></i>
                    Cancelar
                </button>
                <button id="confirm-send-order-button" class="btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>