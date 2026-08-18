<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $Data['contract']['name'] ?? 'Contrato' }}</title>
    <style>
        @page { margin: 104px 48px 56px; }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            color: #222222;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 1;
        }
        .watermark img {
            display: block;
            width: 100%;
        }
        .document-content {
            position: relative;
            z-index: 2;
        }
        .contract-content {
            color: #222222;
            font-size: 11px;
        }
        .contract-content p {
            margin: 0 0 10px;
        }
        .contract-content table {
            border-collapse: collapse;
            margin: 10px 0;
            width: 100%;
        }
        .contract-content th,
        .contract-content td {
            border: 1px solid #cccccc;
            padding: 5px;
        }
        .contract-content ul,
        .contract-content ol {
            margin: 6px 0 10px 20px;
        }
    </style>
</head>
<body>
    <div class="watermark" aria-hidden="true">
        <img src="{{ public_path('images/opzio-monogram-purple-transparent.png') }}" alt="">
    </div>

    <main class="document-content">
        <div class="contract-content">{!! $Data['contract']['content'] ?? '' !!}</div>
    </main>
</body>
</html>
