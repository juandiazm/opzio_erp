<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Orden de compra</title>
        <!-- Styles -->
        <style>
            @page { margin: 0px; }
            html{margin: 0px;}
            /*general clases*/
            .title{
                font-weight: bold;
            }
            .container{
                width: 100%;
                padding: 0 0 15px 0;
                margin: 0 0 15px 0;
                
            }
            .border-botton{
                border-bottom: 1px solid #707070;
            }
            .text-left{
                text-align: left;
            }
            .text-right{
                text-align: right;
            }
            .text-center{
                text-align: center;
            }
            /*--------------*/
            html{
                margin: 0;
                padding: 0;
            }
            body{
                font-family: sans-serif;
                margin: 0;
                padding: 0;
                display: block;
                background-color: #ffffff;
                width: 100%;
            }
            #general-container{
                width: 90%;
                display: block;
                padding: 0 0 50px 0;
                margin: 50px auto 0 auto;
            }
            #header-container{
                justify-content: space-between;
                align-items: center;
            }
            #header-container #opzio-logo,
            #header-container #opzio-data-container,
            #header-container #order-main-information{
                display: inline-block;
                vertical-align: middle;
            }
            #header-container #opzio-logo{
                width: 25%;
                position: relative;
                left: -2.3vw;
            }
            #header-container #opzio-data-container{
                width: 35%;
            }
            #header-container #opzio-data-container p,
            #header-container #opzio-data-container a{
                margin: 0%;
                padding: 0%;
                display: block;
                font-size: 13px;
                color: #222;
            }
            #order-main-information{
                width: 38%;
            }
            #order-main-information p{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
                text-align: right;
            }
            #timely_payment{
                background: #220245 0% 0% no-repeat padding-box;
                border-radius: 6px;
                color: #fff !important;
                padding: 7px 10px !important;
                width: fit-content;
                margin: 0 0 0 auto !important;
            }
            #client-container{}
            #client-container #client-data-container,
            #client-container #qr-container,
            #client-container #paymethods-container{
                display: inline-block;
                vertical-align: top;
            }
            #client-container #paymethods-container a{
                text-decoration: none;
            }
            #client-container #client-data-container{
                width: 35%;
                padding-left: 10px;
            }
            #client-container #client-data-container p{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
            }
            #qr-container{
                width: 29%;
                text-align: center;
            }
            #qr-container .title{
                margin: 0 0 5px 0;
                padding: 0;
                font-size: 13px;
                color: #222;
                text-align: center;
            }
            #qr-container #qr{
                width: 50%;
            }
            #paymethods-container{
                width: 33%;
                text-align: center;
            }
            #paymethods-container .title{
                margin: 0 0 5px 0;
                padding: 0;
                font-size: 13px;
                color: #222;
                text-align: center;
            }
            #paymethods-container #pay-methods-image{
                width: 100%;
            }
            #licenses-container table{
                width: 100%;
                border-collapse: collapse;
            }
            #licenses-container table thead{
                background-color: #C4C4C4;
                color: #222;
            }
            #licenses-container table thead th{
                padding: 5px 10px;
                font-size: 13px;
                font-weight: bold;
            }
            #licenses-container table tbody tr{
                border-bottom: 1px solid #707070;
            }
            .description-row{
                min-width:50%;
            }
            #licenses-container table tbody tr td{
                padding: 5px 10px;
                font-size: 13px;
                color: #222;
            }
            #licenses-container table tbody tr td p{
                padding: 0;
                margin: 0;
                font-size: 13px;
                color: #222;
            }
            #total-container{
                display: block;
                width: 100%;
                padding: 15px 0 0 0;
                text-align: right;
            }
            #total-container .total-sub-container{
                margin: 0 0 10px auto;
                display: block;
                width: 50%;
            }
            #total-container .total-sub-container p{
                font-size: 16px;
                color: #222;
                display: inline-block;
                margin: 0;
                padding: 0;
            }
            #total-container .total-sub-container .title{
                width: 40%;
                text-align: left;
            }
            #total-container .total-sub-container .total-value{
                width: 58%;
            }
            #total-page-container{
                border-top: 1px solid #707070;
                padding-top: 10px
            }
            #order-description{
                margin: 0;
                padding: 0;
                font-size: 13px;
                color: #222;
            }
            #order-description p{
                margin: 0 0 0 auto;
                padding: 0;
                display: block;
                text-align: right;
                width: 70%;
            }
            #feed-container{
                align-items: center;
                margin: 0;
                padding: 20px 0 0 0;
                width: 90%;
                position: absolute; 
                bottom: 25px; 
                left: 5%;   
                border-top: 1px solid #707070;
            }
            .feed-sub-contianer{
                display: inline-block;
                vertical-align: top;
                width: 49.5%;
                margin: 0;
                padding: 0;
            }
            .feed-sub-contianer span{
                font-weight: bold;
            }
            #feed-container #opzio-logo-feed,
            #feed-container #feed-text-container{
                display: inline-block;
                vertical-align: middle;
            }
            #feed-container #opzio-logo-feed{
                width: 100px;
                align-self: center;
                box-shadow: 0px 3px 6px #00000029;
                border-radius: 50%;
                margin-right: 10px;
                padding: 3px
            }
            #feed-container #feed-text-container{
                align-self: center;
            }
            #feed-container #feed-text-container p{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
            }
            #feed-container #feed-department{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
            }
            #feed-container #feed-link{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #220245;
                text-decoration: none;
            }
            #feed-container #feed-email{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #220245;
            }
            #bank-data-container p{
                text-align: right;
            }
            #bank-data-title{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
                font-weight: bold;
            }
            #bank-data-bank-name,
            #bank-data-bank,
            #bank-data-account,
            #bank-data-id,
            #bank-key{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
            }
            #bank-key span{
                font-weight: bold;
                color: #220245;
            }
            .service-row{
            }
            .cop{
                font-size: 8px;
            }
            .money-col{
                /*all content in one line*/
                white-space: nowrap;
            }
        </style>
        <style>
            body { color: #0F172A; }
            #general-container { width: 88%; padding-bottom: 60px; margin-top: 36px; }
            #header-container { padding: 0 0 14px 0; margin-bottom: 18px; border-bottom: 2px solid #220245; }
            #header-container #opzio-logo { width: 20%; left: 0; }
            #header-container #opzio-data-container { width: 39%; }
            #header-container #opzio-data-container p,
            #header-container #opzio-data-container a { font-size: 10px; line-height: 1.45; color: #475569; }
            #header-container #opzio-data-container .title { color: #220245; font-size: 13px; }
            #order-main-information { width: 34%; padding: 10px 12px; background-color: #220245; box-sizing: border-box; }
            #order-main-information p { color: #FFFFFF; font-size: 10px; line-height: 1.45; }
            #order-main-information p.title { font-size: 14px; }
            #timely_payment { background: #FFFFFF; color: #220245 !important; border-radius: 0; padding: 4px 0 !important; margin: 4px 0 0 0 !important; font-weight: bold; }
            #client-container { padding: 13px 15px 11px 15px; margin-bottom: 20px; background-color: #F2F2E8; border-bottom: 0; box-sizing: border-box; }
            #client-container #client-data-container { width: 39%; padding-left: 0; }
            #client-container #client-data-container p { padding: 1px 0; font-size: 10px; line-height: 1.45; color: #475569; }
            #client-container #client-data-container .title,
            #qr-container .title,
            #paymethods-container .title { margin-bottom: 5px; color: #220245; font-size: 11px; text-transform: uppercase; }
            #qr-container { width: 25%; }
            #qr-container #qr { width: 62%; }
            #paymethods-container { width: 32%; }
            #paymethods-container #pay-methods-image { width: 50%; margin-top: 20px; }
            #licenses-container table { border: 1px solid #D4D4C8; }
            #licenses-container table thead { background-color: #220245; color: #FFFFFF; }
            #licenses-container table thead th { padding: 8px 9px; color: #FFFFFF; font-size: 10px; text-transform: uppercase; }
            #licenses-container table tbody tr { border-bottom: 1px solid #D4D4C8; }
            #licenses-container table tbody tr td,
            #licenses-container table tbody tr td p { padding: 7px 9px; color: #334155; font-size: 10px; line-height: 1.4; }
            #licenses-container .service-row td { padding-top: 6px; padding-bottom: 6px; background-color: #F2F2E8; color: #220245; font-size: 10px; }
            #total-container { padding-top: 18px; }
            #total-container .total-sub-container { width: 42%; margin-bottom: 5px; padding: 3px 0; border-bottom: 1px solid #D4D4C8; }
            #total-container .total-sub-container p { color: #475569; font-size: 11px; }
            #total-container .total-sub-container:last-child { padding: 8px 10px; background-color: #220245; border-bottom: 0; box-sizing: border-box; }
            #total-container .total-sub-container:last-child p { color: #FFFFFF; font-size: 13px; }
            #order-description p { width: 100%; margin-top: 12px; padding: 10px 12px; background-color: #F2F2E8; box-sizing: border-box; color: #475569; font-size: 10px; text-align: left; }
            #feed-container { padding-top: 12px; border-top: 1px solid #D4D4C8; }
            #feed-container #opzio-logo-feed { width: 52px; margin-right: 7px; padding: 0; box-shadow: none; }
            #feed-container #feed-text-container p,
            #bank-data-container p,
            #bank-data-title,
            #bank-data-bank-name,
            #bank-data-bank,
            #bank-data-account,
            #bank-data-id,
            #bank-key { padding: 1px 0; font-size: 9px; line-height: 1.35; }
            #bank-data-title { color: #220245; text-transform: uppercase; }
            #bank-key { color: #220245; font-weight: bold; }

            /* White-only minimalist treatment. */
            #header-container { border-bottom: 1px solid #D4D4C8; }
            #order-main-information { background-color: #FFFFFF; border: 1px solid #220245; }
            #order-main-information p { color: #334155; }
            #order-main-information p.title { color: #220245; }
            #client-container { background-color: #FFFFFF; border: 1px solid #D4D4C8; }
            #licenses-container table thead { background-color: #FFFFFF; color: #220245; border-bottom: 2px solid #220245; }
            #licenses-container table thead th { color: #220245; }
            #licenses-container .service-row td { background-color: #FFFFFF; }
            #total-container .total-sub-container:last-child { background-color: #FFFFFF; border: 1px solid #220245; }
            #total-container .total-sub-container:last-child p { color: #220245; }
            #order-description p { background-color: #FFFFFF; border: 1px solid #D4D4C8; }
            .feed-sub-contianer { vertical-align: middle; }
            #feed-container {
                display: table;
                width: 88%;
                height: 92px;
                padding-top: 0;
                bottom: 18px;
                left: 6%;
                box-sizing: border-box;
            }
            #feed-container .feed-sub-contianer { display: table-cell; vertical-align: middle; }
        </style>
    </head>
    <body>
        @php
            $number_of_licenses = count($Data['income']['licenses']);
            $subtotal = 0;
            $taxes = 0;
            $total = 0;
            
            // Función para estimar la altura de una fila en cm
            if (!function_exists('estimateLicenseRowHeight')) {
                function estimateLicenseRowHeight($license, $tax_flag, $hours_flag) {
                    $baseHeight = 1.0; // Altura base en cm por fila (incluyendo padding y borders)
                    $lineHeight = 0.45; // Altura por línea adicional en cm
                    
                    // Calcular líneas necesarias para la descripción (columna más variable)
                    $maxLines = 1;
                    
                    // La descripción es el campo que más puede crecer
                    if (!empty($license['description'])) {
                        // Considerando que la descripción tiene un ancho aproximado de ~400-500px
                        // y el font-size es 13px, estimamos ~60-70 caracteres por línea
                        $descriptionText = strip_tags($license['description']);
                        $descLines = ceil(strlen($descriptionText) / 65);
                        $maxLines = max($maxLines, $descLines);
                    }
                    
                    // Si tiene recurrence_months, agrega una línea extra
                    if (!empty($license['recurrence_months'])) {
                        $maxLines += 1;
                    }
                    
                    return $baseHeight + (($maxLines - 1) * $lineHeight);
                }
            }
            
            // Verificar flags antes de calcular páginas
            $tax_flag = $Data['income']['licenses']->contains(function($license) {
                return $license['tax_value'] > 0;
            });
            $hours_flag = collect($Data['income']['licenses'])->contains(function($license) {
                return $license['hours'] > 0;
            });
            
            // Agrupar licencias por páginas dinámicamente
            $availableHeight = 16; // Altura disponible para la tabla de productos en cm
            $headerHeight = 0.8; // Altura del header de la tabla
            $serviceRowHeight = 0.7; // Altura de las filas de servicio
            $remainingHeight = $availableHeight - $headerHeight;
            
            $pages = [];
            $currentPage = [];
            $currentPageHeight = 0;
            $current_service = '';
            
            foreach ($Data['income']['licenses'] as $index => $license) {
                $rowHeight = estimateLicenseRowHeight($license, $tax_flag, $hours_flag);
                
                // Verificar si necesitamos agregar una fila de servicio
                $needsServiceRow = ($current_service != $license['service_name'].' - '. $license['license_name']);
                $totalRowHeight = $rowHeight + ($needsServiceRow ? $serviceRowHeight : 0);
                
                // Si agregar esta licencia excede el espacio, crear nueva página
                if ($currentPageHeight + $totalRowHeight > $remainingHeight && count($currentPage) > 0) {
                    $pages[] = $currentPage;
                    $currentPage = [];
                    $currentPageHeight = 0;
                    $current_service = ''; // Reset service tracking for new page
                }
                
                $currentPage[] = $license;
                $currentPageHeight += $totalRowHeight;
                
                if ($needsServiceRow) {
                    $current_service = $license['service_name'].' - '. $license['license_name'];
                }
            }
            
            // Agregar última página si tiene licencias
            if (count($currentPage) > 0) {
                $pages[] = $currentPage;
            }
            
            $totalPages = count($pages);
        @endphp
        @foreach($pages as $pageIndex => $pageLicenses)
            @if($pageIndex > 0)
                <div style="page-break-before: always;"></div>
            @endif
            <div id="general-container">
                <div id="header-container" class="container border-botton">
                    <img src="{{ $Data['public_path'].'images/opzio-logo-wide-purple.jpg' }}" alt="Opzio S.A.S" id="opzio-logo"/>
                    <div id="opzio-data-container">
                        <p class="title">Opzio S.A.S</p>
                        <p>NIT: 902.086.745-1</p>
                        <p>Bogotá D.C., Colombia</p>
                        <a href="https://opzio.co/" target="_blank">opzio.co</a>
                    </div>
                    <div id="order-main-information">
                        <p class="title">ORDEN DE COMPRA</p>
                        <p><strong>ID: {{ substr($Data['income']['unique_id'], -10) }}</strong></p>
                        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($Data['income']['created_at'])->format('Y-m-d') }}</p>
                        <p id="timely_payment">Fecha pago oportuno: {{ \Carbon\Carbon::parse($Data['income']['timely_payment'])->format('Y-m-d') }}</p>
                        <p>Fecha de vencimiento: {{ \Carbon\Carbon::parse($Data['income']['cutoff_date'])->format('Y-m-d') }}</p>
                    </div>
                </div>
                <div id="client-container" class="container border-botton">
                    <div id="client-data-container">
                        <p class="title">Empresa</p>
                        <p>Nombre: {{ $Data['client']['name'].($Data['client']['lastname'] != null ? ' '.$Data['client']['lastname'] : '') }}</p>
                        <p>NIT: {{ $Data['client']['identification'] }}</p>
                        <p>Dir: {{ $Data['client']['address'] }}</p>
                        <p>Tel: {{ $Data['client']['phone'] }}</p>
                        <p>Email: {{ $Data['client']['email'] }}</p>
                        <p>{{ $Data['client']['country']['name'] }}</p>
                    </div>
                    <div id="qr-container">
                        <a href="{{ $Data['income']['url'] }}">
                            <p class="title">Link de pago</p>
                            <img src="{{ $Data['storage_path'].'incomes/qr/'.$Data['income']['unique_id'].'.png'}}" alt="QR" id="qr"/>
                        </a>
                    </div>
                    <div id="paymethods-container">
                        <a href="{{ $Data['income']['url'] }}">
                            <p class="title">Realiza tu pago con</p>
                            <img src="{{ $Data['public_path'].'images/logobold.png'}}" alt="BOLD" id="pay-methods-image"/>
                        </a>
                    </div>
                </div>
                <div class="container">
                    <div id="licenses-container">
                        @php
                            $current_service = '';
                        @endphp
                        <table>
                            <thead>
                                <tr>
                                    <th class="text-left">Descripción</th>
                                    @if($tax_flag)<th class="text-center">Impuestos</th>@endif
                                    @if($hours_flag)<th class="text-center">Horas</th>@endif
                                    <th class="text-right">Valor Und</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pageLicenses as $license)
                                    @if($current_service != $license['service_name'].' - '. $license['license_name'])
                                        @php
                                            $current_service = $license['service_name'].' - '. $license['license_name'];
                                        @endphp
                                        <tr class="service-row">
                                            <td class="text-left description-row">
                                                <strong>{{ $current_service }}</strong>
                                            </td>
                                            @if($tax_flag)<td></td>@endif
                                            @if($hours_flag)<td></td>@endif
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="text-left description-row">
                                            @if($license['recurrence_months'])
                                            {{ $license['recurrence_months'].' '.($license['recurrence_months']==1?'Mes':'Meses') }}
                                            <br>
                                            @endif
                                            <p>
                                            {!! $license['description'] !!}
                                            </p>
                                        </td>
                                        @if($tax_flag)<td class="text-center">{{ ($license['tax_value']==0?'':($license['tax_name'].'('.($license['tax_value']*100).'%)')) }}</td>@endif
                                        @if($hours_flag)<td class="text-center">{{ $license['hours'] }}</td>@endif
                                        <td class="text-right money-col">${{ number_format($license['value'],0,',','.') }} <span class="cop">COP</span></td>
                                        <td class="text-right money-col">${{ number_format($license['total'],0,',','.') }} <span class="cop">COP</span></td>
                                    </tr>
                                    @php
                                        $subtotal += $license['value'];
                                        $taxes += $license['total'] - $license['value'];
                                        $total += $license['total'];
                                    @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($pageIndex == $totalPages - 1)
                    <div id="total-container">
                        <div class="total-sub-container">
                            <p class="title">SubTotal</p>
                            <p class="text-right total-value">${{ number_format($subtotal, 0 ,',' , '.') }} <span class="cop">COP</span></p>
                        </div>
                        <div class="total-sub-container">
                            <p class="title">Impuestos</p>
                            <p class="text-right total-value">${{ number_format($taxes, 0 ,',' , '.') }} <span class="cop">COP</span></p>
                        </div>
                        <div class="total-sub-container">
                            <p class="title">Total documento</p>
                            <p class="text-right total-value"><strong>${{ number_format($Data['income']['total'], 0 ,',' , '.') }} <span class="cop">COP</span></strong></p>
                        </div>
                    </div>
                    @endif
                </div>
                @if( $Data['income']['description'] != null && $Data['income']['description'] != '')
                <div id="order-description">
                    <p>{!! $Data['income']['description'] !!}
                </div>
                @endif
                <div id="feed-container">
                    <div class="feed-sub-contianer">
                        <img src="{{ $Data['public_path'].'images/opzio-monogram-circle-purple-bg.jpg'}}" alt="Opzio S.A.S" id="opzio-logo-feed"/>
                        <div id="feed-text-container">
                            <p id="feed-department"><strong>Departamento Contable</strong></p>
                            <p id="feed-email">Correo: contabilidad@opzio.co</p>
                            <a id="feed-link" href="https://opzio.co/" target="_blank">Opzio S.A.S</a>
                        </div>
                    </div>
                    <div class="feed-sub-contianer" id="bank-data-container">
                        <p id="bank-data-title">Información Bancaria</p>
                        <p id="bank-data-bank-name"><span>Razón Social:</span> OPZIO S.A.S</p>
                        <p id="bank-data-bank"><span>Banco:</span> BOLD C.F</p>
                        <p id="bank-data-account"><span>Cuenta de Ahorros:</span> 1700-1363-1382</p>
                        <p id="bank-key"><span>Llave:</span> contabilidad@opzio.co</p>
                        <p id="bank-data-id"><span>NIT:</span> 902.086.745-1</p>
                    </div>
                </div>
            </div>
        @endforeach
    </body>
</html>
