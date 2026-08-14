<!doctype html>
<html>
<body>
    <h2>Backup fallido: {{ $Data['project'] ?? 'Proyecto' }}</h2>
    <p>El backup de la base de datos no pudo completarse.</p>
    <p><strong>Error:</strong> {{ $Data['error'] ?? 'Error no especificado' }}</p>
</body>
</html>
