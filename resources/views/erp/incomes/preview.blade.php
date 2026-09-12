<aside class="income-live-preview" data-income-preview aria-label="Vista previa del ingreso">
    <div class="income-preview-panel-header">
        <div>
            <span class="income-section-kicker">Documento en vivo</span>
            <h3>Vista previa</h3>
        </div>
        <span class="income-preview-live"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> En vivo</span>
    </div>

    <div class="income-preview-status-row">
        <span class="income-preview-label">Estado</span>
        <span class="income-pdf-preview-status status-state-0" data-preview-state>
            <i class="income-preview-status-icon fa-solid fa-file-pen" aria-hidden="true"></i>
            <span data-preview-state-label>Cotización</span>
        </span>
        <span class="income-preview-draft-label" data-preview-save-status>Sin guardar</span>
    </div>

    <article class="income-pdf-preview-document">
        <header class="income-pdf-preview-header">
            <div class="income-pdf-preview-company">
                <img src="{{ asset('images/opzio-logo-wide-purple-transparent.webp') }}" alt="Opzio S.A.S">
                <div>
                    <strong>Opzio S.A.S</strong>
                    <span>NIT: 902.086.745-1</span>
                    <span>Bogotá D.C., Colombia</span>
                    <a href="https://opzio.co/" target="_blank" rel="noreferrer">opzio.co</a>
                </div>
            </div>
            <div class="income-pdf-preview-document-info">
                <strong data-preview-document-title>COTIZACIÓN</strong>
                <span><b>ID:</b> <span data-preview-id>BORRADOR</span></span>
                <span><b>Fecha:</b> <span data-preview-created-at>Borrador</span></span>
                <span data-preview-payment-line hidden><b>Fecha pago oportuno:</b> <span data-preview-payment-date>-</span></span>
                <span data-preview-cutoff-line hidden><b>Fecha de vencimiento:</b> <span data-preview-cutoff-date>-</span></span>
            </div>
        </header>

        <section class="income-pdf-preview-client">
            <div class="income-pdf-preview-client-data">
                <strong class="income-pdf-preview-title">Empresa</strong>
                <p><b>Nombre:</b> <span data-preview-client>Cliente por seleccionar</span></p>
                <p><b>NIT:</b> <span data-preview-identification>-</span></p>
                <p><b>Dir:</b> <span data-preview-address>-</span></p>
                <p><b>Tel:</b> <span data-preview-phone>-</span></p>
                <p><b>Email:</b> <span data-preview-email>-</span></p>
                <p><span data-preview-country>-</span></p>
            </div>
            <div class="income-pdf-preview-payment" data-preview-payment-tools hidden>
                <div class="income-pdf-preview-qr">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    <small>QR de pago al guardar</small>
                </div>
                <div class="income-pdf-preview-pay-method">
                    <span>Realiza tu pago con</span>
                    <img src="{{ asset('images/logobold.png') }}" alt="BOLD">
                </div>
            </div>
        </section>

        <section class="income-pdf-preview-lines">
            <div class="income-pdf-preview-lines-heading">
                <strong>Detalle</strong>
                <span data-preview-item-count>0 ítems</span>
            </div>
            <table>
                <thead><tr data-preview-column-headers></tr></thead>
                <tbody data-preview-items></tbody>
            </table>
            <p class="income-pdf-preview-empty" data-preview-empty>Agrega una licencia para verla aquí.</p>
        </section>

        <section class="income-pdf-preview-totals" data-preview-totals>
            <div><span>SubTotal</span><strong data-preview-subtotal>$0 COP</strong></div>
            <div><span>Impuestos</span><strong data-preview-taxes>$0 COP</strong></div>
            <div class="income-pdf-preview-total-row"><span>Total documento</span><strong data-preview-total>$0 COP</strong></div>
        </section>

        <section class="income-pdf-preview-description" data-preview-description-section hidden>
            <div data-preview-description></div>
        </section>

        <footer class="income-pdf-preview-footer">
            <div class="income-pdf-preview-footer-contact">
                <img src="{{ asset('images/opzio-monogram-circle-purple-bg.webp') }}" alt="Opzio S.A.S">
                <div>
                    <strong data-preview-department>Departamento Comercial</strong>
                    <span data-preview-commercial-email>{{ session('user') == null ? '' : session('user')['email'] }}</span>
                    <span data-preview-accounting-email hidden>contabilidad@opzio.co</span>
                    <a href="https://opzio.co/" target="_blank" rel="noreferrer">Opzio S.A.S</a>
                </div>
            </div>
            <div class="income-pdf-preview-bank" data-preview-bank hidden>
                <strong>Información Bancaria</strong>
                <span><b>Razón Social:</b> OPZIO S.A.S</span>
                <span><b>Banco:</b> BOLD C.F</span>
                <span><b>Cuenta de Ahorros:</b> 1700-1363-1382</span>
                <span><b>Llave:</b> contabilidad@opzio.co</span>
                <span><b>NIT:</b> 902.086.745-1</span>
            </div>
        </footer>
    </article>
</aside>