<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Certificado</title>
        <!-- Styles -->
        <style>
            html { margin: 0px; }
            @page { margin: 0px; }
            body { margin: 0px; }
            body{
                font-family: sans-serif;
                margin: 0;
                padding: 0;
                display: block;
                background-color: #ffffff;
                width: 100%;
            }
            #pdf-container{
                width: 100%;
                height: 100%;
                background-image: url('https://erp.opzio.com.co/images/api/clients/ensamble/diploma_fondo.jpg');
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                margin: 0 auto;
                padding: 0;
                display: block; 
            }
            #content-container{
                width: 70%;
                margin: 0 auto;
                padding: 1.5cm 0 0 0;
                display: block;
                text-align: center;
            }
            p{
                margin: 0;
                padding: 0;
                display: block;
            }
            #house-img{
                width: 200px;
                margin: 0 auto;
                padding: 0;
                display: block;
            }
            #emprendamos{
                font-weight: bold;
                font-size: 18px;  
                color: #000;  
            }
            #juntos{
                font-weight: bold;
                font-size: 18px;
                color: #666;    
            }
            #certificate-to{
                font-weight: bold;
                color: #DA2029;
                margin-top: 1cm;
                font-size: 18px;
            }
            #certified-name{
                font-size: 35px;
                margin-top: 3mm;
                color: #3A4149;
            }
            #certified-id{
                font-size: 20px;
                margin-top: 1mm;
                color: #3A4149;
            }
            #why{
                margin-top: 10mm;
                font-size: 20px;
                color: #3A4149;
            }
            #sign-img{
                width: 100%;
                margin-top: 10mm;
            }
        </style>
    </head>
    <body>
        <div id="pdf-container">
            <div id="content-container">
                <img id="house-img" src="https://erp.opzio.com.co/images/api/clients/ensamble/diploma_logo.jpg" alt="">
                <img src="" id="sponsors-img" alt="">
                <p id="certificate-to">OTORGA EL SIGUIENTE CERTIFICADO A:</p>
                <p id="certified-name">{{ $Data['name'] }}</p>
                <p id="certified-id">Bajo la identificación N°{{ $Data['identification'] }}</p>
                <p id="why">
                    Por su participación y aprobación de los 12 módulos del programa de formación<br>
                    <strong>Emprendamos junt@s de Sistema Coca-Cola</strong>
                    <br>
                    <br>
                    Diciembre de 2023
                </p>
                <img src="https://erp.opzio.com.co/images/api/clients/ensamble/diploma_firma completa.jpg" id="sign-img" alt="">
            </div>
        </div>
    </body>
</html>
