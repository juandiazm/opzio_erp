@extends('layouts.app')
@section('app-header')
<title>Opzio - Realiza tu pago</title>
<!-- Scripts de pasarelas de pago -->
<script type="text/javascript" src="https://checkout.wompi.co/widget.js"></script>
<script type="text/javascript" src="https://checkout.bold.co/library/boldPaymentButton.js"></script>
<script>
    var income_unique_id = "{{ $income_unique_id }}";
</script>
@vite('resources/js/client/pay_unlogged/pay_unlogged.js')
<!-- Styles -->
@vite('resources/sass/client/pay_unlogged/pay_unlogged.scss')
@yield('home-app-header')
@endsection
@section('app-content')
{!! $header_menu_view !!}
<section id="pay-unlogged-container" class="d-flex justify-content-center">
    <div id="pay-unlogged-centered" class="align-self-center justify-content-around">
        <div id="pay-unlogged-data-container">
            <div class="payment-header">
                <div class="payment-header-left">
                    <img src="/images/opzio-logo-compact-purple-transparent.webp" alt="Opzio" class="avatar-img">
                    <h1 id="pay-unlogged-title">Realiza tu pago</h1>
                </div>
                <div class="payment-header-right">
                    <div class="header-info-row">
                        <span class="info-label">Cliente:</span>
                        <span class="info-value" id="client_name"></span>
                    </div>
                    <div class="header-info-row">
                        <span class="info-label">ID de Orden:</span>
                        <span class="info-value" id="unique_id_header"></span>
                    </div>
                    <div class="header-info-row">
                        <span class="info-label">Fecha de Corte:</span>
                        <span class="info-value" id="cutoff_date_header"></span>
                    </div>
                </div>
            </div>

            <div class="payment-content-wrapper">
                <!-- Columna izquierda: Lista de artículos -->
                <div class="payment-items-section">
                    <div class="section-header">
                        <h2 class="section-title">Resumen de Licencias</h2>
                    </div>
                    <div id="items-list-container" class="items-list-expanded">
                        <ul id="licences-list" class="items-list"></ul>
                    </div>
                </div>

                <!-- Columna derecha: Método de pago y total -->
                <div class="payment-method-section">
                    <div id="payment-summary-container">
                        <h2 class="section-title">Resumen de Pago</h2>
                        
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value" id="total"></span>
                        </div>

                        <div id="advances-container" class="summary-row advances-row" style="display: none;">
                            <span class="summary-label">
                                <i class="fas fa-check-circle"></i> Abonos realizados:
                            </span>
                            <span class="summary-value negative" id="total_advances"></span>
                        </div>

                        <div class="summary-divider"></div>

                        <div id="balance-container" class="summary-row total-row" style="display: none;">
                            <span class="summary-label"><strong>Saldo pendiente:</strong></span>
                            <span class="summary-value total" id="balance_pending"></span>
                        </div>

                        <div id="no-advances-total" class="summary-row total-row">
                            <span class="summary-label"><strong>Total a pagar:</strong></span>
                            <span class="summary-value total" id="total_to_pay"></span>
                        </div>
                    </div>

                    <!-- Selector de pasarela de pago -->
                    <div id="payment-gateway-selector">
                        <h3 class="gateway-selector-title">Selecciona tu método de pago</h3>
                        <div class="gateway-options">
                            <label class="gateway-option" data-gateway="bold">
                                <input type="radio" name="payment_gateway" value="bold" checked>
                                <div class="gateway-option-content">
                                    <img src="/images/payment-gateways/logo-bold.webp" alt="Bold" class="gateway-logo">
                                </div>
                            </label>
                            <label class="gateway-option" data-gateway="wompi">
                                <input type="radio" name="payment_gateway" value="wompi">
                                <div class="gateway-option-content">
                                    <img src="/images/payment-gateways/logo-wompi.webp" alt="Wompi" class="gateway-logo">
                                </div>
                            </label>
                        </div>
                    </div>

                    <button class="btn" id="pay-unlogged-btn">
                        <i class="fas fa-lock"></i> Pagar de forma segura
                    </button>

                    <div class="payment-security-info">
                        <i class="fas fa-shield-alt"></i>
                        <span>Pago procesado de forma segura</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="pay-result-container" class="align-self-center justify-content-around">
        <div id="pay-result-data-continer">
            <!--<img src="/images/opzio-logo-compact-purple-transparent.webp" alt="Opzio" class="avatar-img">-->
            <i class="fas fa-5x" id="pay-result-icon"></i>
            <h1 id="pay-result-title"></h1>
            <p id="pay-result-description"></p>
            <button class="btn" id="pay-result-btn">CONTINUAR</button>
        </div>
    </div>
</section>
@endsection
