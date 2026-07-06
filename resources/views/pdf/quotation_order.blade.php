<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cotización</title>
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
            #header-container #ridder-logo,
            #header-container #ridder-data-container,
            #header-container #order-main-information{
                display: inline-block;
                vertical-align: middle;
            }
            #header-container #ridder-logo{
                width: 25%;
                position: relative;
                left: -2.3vw;
            }
            #header-container #ridder-data-container{
                width: 35%;
            }
            #header-container #ridder-data-container p,
            #header-container #ridder-data-container a{
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
                background: #0153FF 0% 0% no-repeat padding-box;
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
            #client-container #client-data-container{
                width: 100%;
                padding-left: 10px;
            }
            #client-container #client-data-container p{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
            }
            #licenses-container{
                margin: 0;
                padding: 0;
                width: 100%;
            }
            #licenses-container table{
                width: 100%;
                min-width: 100%;
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
                margin: 20px 0 0 0;
                padding: 0;
                width: 90%;
                position: absolute; 
                bottom: 25px; 
                left: 5%; 
                right: 0px;  
            }
            .feed-sub-contianer{
                display: inline-block;
                vertical-align: bottom;
                width: 49.5%;
                margin: 0;
                padding: 0;
            }
            .feed-sub-contianer span{
                font-weight: bold;
            }
            #feed-container #ridder-logo-feed,
            #feed-container #feed-text-container{
                display: inline-block;
                vertical-align: middle;
            }
            #feed-container #ridder-logo-feed{
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
                color: #0153FF;
                text-decoration: none;
            }
            #feed-container #feed-email{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #0153FF;
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
            #bank-data-id{
                margin: 0;
                padding: 2px 0;
                font-size: 13px;
                color: #222;
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
    </head>
    <body>
        @php
            $number_of_licenses = count($Data['income']['licenses']);
            $subtotal = 0;
            $taxes = 0;
            $total = 0;
            
            // Función para estimar la altura de una fila en cm
            function estimateLicenseRowHeightQuotation($license, $tax_flag, $hours_flag) {
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
            
            // Verificar flags antes de calcular páginas
            $tax_flag = collect($Data['income']['licenses'])->contains(function($license) {
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
                $rowHeight = estimateLicenseRowHeightQuotation($license, $tax_flag, $hours_flag);
                
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
                    <img src="{{ $Data['public_path'].'images/business_blues.webp' }}" alt="RIDDER S.A.S" id="ridder-logo"/>
                    <div id="ridder-data-container">
                        <p class="title">RIDDER S.A.S</p>
                        <br>
                        <p>NIT: 901.721.687-1</p>
                        <p>Dir: CARRERA 76 80 20 P 4</p>
                        <p>Tel: (601) 4051307</p>
                        <p>Bogotá D.C., Colombia</p>
                        <a href="https://ridder.com.co/" target="_blank">www.ridder.com.co</a>
                    </div>
                    <div id="order-main-information">
                        <p class="title">COTIZACIÓN</p>
                        <p><strong>ID: {{ substr($Data['income']['unique_id'], -10) }}</strong></p>
                        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($Data['income']['created_at'])->format('Y-m-d') }}</p>
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
                                            {!! $license['description'] !!}
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
                    <img src="{{ $Data['public_path'].'images/bussines-logo-rounded.webp'}}" alt="RIDDER S.A.S" id="ridder-logo-feed"/>
                        <div id="feed-text-container">
                            <p id="feed-department"><strong>Departamento Comercial</strong></p>
                            <p id="feed-email">{{  session('user')==null?'':session('user')['email'] }}</p>
                            <a id="feed-link" href="https://ridder.com.co/" target="_blank">Ridder S.A.S</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </body>
</html>
