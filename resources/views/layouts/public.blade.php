<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Documento Opzio')</title>
    <style>
        :root {
            --opzio-purple: #220245;
            --opzio-ink: #202124;
            --opzio-muted: #6f7075;
            --opzio-line: #e7e5eb;
            --opzio-surface: #ffffff;
            --opzio-background: #f6f5f8;
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            color: var(--opzio-ink);
            background: var(--opzio-background);
            font-family: Arial, Helvetica, sans-serif;
        }
        .public-shell {
            min-height: 100vh;
            padding: 32px 16px;
        }
        .public-container {
            width: min(680px, 100%);
            margin: 0 auto;
        }
        .public-header {
            padding: 24px 28px 18px;
            background: var(--opzio-surface);
            border-bottom: 3px solid var(--opzio-purple);
            text-align: center;
        }
        .public-header img {
            display: inline-block;
            width: min(210px, 70%);
            height: auto;
        }
        .public-content {
            padding: 32px;
            background: var(--opzio-surface);
        }
        .public-footer {
            padding: 18px 28px;
            color: var(--opzio-muted);
            background: var(--opzio-surface);
            border-top: 1px solid var(--opzio-line);
            font-size: 12px;
            text-align: center;
        }
        @media (max-width: 560px) {
            .public-shell { padding: 0; }
            .public-content { padding: 24px 20px; }
            .public-header { padding: 22px 20px 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <main class="public-shell">
        <div class="public-container">
            <header class="public-header">
                <img src="{{ asset('images/opzio-logo-wide-purple-transparent.webp') }}" alt="Opzio">
            </header>
            <section class="public-content">
                @yield('content')
            </section>
            <footer class="public-footer">legal@opzio.co · opzio.co</footer>
        </div>
    </main>
</body>
</html>
