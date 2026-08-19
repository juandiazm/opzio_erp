# Plan de reportes mensuales de servidores

## Objetivo

El primer día de cada mes, enviar a los correos activos de cada proyecto con notificaciones activas un resumen ejecutivo del mes calendario anterior y un PDF con el detalle técnico del servidor.

## Decisiones

1. El scheduler ejecutará `servers:send-monthly-report` a las 07:00 del día 1.
2. El período por defecto será el mes anterior completo; el comando acepta `--period=YYYY-MM` para reprocesos controlados.
3. Se enviará un correo por proyecto, no uno por cliente, porque cada proyecto tiene su propia configuración y métricas.
4. Los destinatarios se tomarán únicamente de `servers_project_notifications` con canal email, proyecto habilitado y `notifications_enabled = true`.
5. El correo se encolará mediante `mail_logs` para reutilizar `command:send_queued_mails`, no se enviará directamente desde el scheduler.
6. El remitente será siempre `soporte@opzio.co` con nombre `Soporte Opzio`, independientemente del remitente global.
7. `notification_batch` será `servers-monthly-report:YYYY-MM:project_id` para evitar duplicados si el scheduler se ejecuta más de una vez.
8. El PDF se almacenará en `storage/app/servers/monthly-reports/YYYY-MM/` y se conservará junto con el log del correo.
9. El texto usa interpretaciones deterministas de disponibilidad, latencia, errores, CPU, FPM y almacenamiento. No se usa IA porque el contenido debe ser trazable y no depender de un servicio externo.
10. El nombre visible del proyecto puede configurarse en el modal de servidores; si está vacío, asunto, correo y PDF usan el nombre técnico registrado.
11. Si la disponibilidad medida es inferior al 90%, el reporte no se envía al cliente: se dirige únicamente a `soporte@opzio.co` con asunto de alerta y conserva el PDF completo para análisis.

## Métricas del reporte

- Disponibilidad estimada y distribución de respuestas HTTP.
- Solicitudes totales, cobertura observada y volumen de bytes.
- Latencia promedio, p95 promedio y p95 máximo.
- CPU promedio y pico, memoria RSS/PSS máxima.
- Cola PHP-FPM promedio, pico y máxima observada.
- Almacenamiento actual, crecimiento, archivos y directorios.
- Muestras recolectadas, eventos por severidad y última observación.

El PDF usa un encabezado y pie de página compatibles con los contratos, incluye el logo Opzio y paginación. El cuerpo del PDF muestra únicamente métricas, interpretación y recomendaciones; no expone host, ruta, entorno ni detalles de alojamiento del producto.

## Operación manual

```text
php artisan servers:send-monthly-report --dry-run
php artisan servers:send-monthly-report --period=2026-08 --project=36
php artisan servers:send-monthly-report --date=2026-08 --project=36
php artisan servers:send-monthly-report --date=2026-08-19 --project=36
php artisan servers:send-monthly-report --date=2026-08 --project=36 --force
```

`--date` acepta `YYYY-MM` o `YYYY-MM-DD` y siempre evalúa el mes indicado. `--period=YYYY-MM` sigue siendo válido por compatibilidad. Si ambos están vacíos, el comando conserva el comportamiento actual y reporta el mes anterior. No se deben combinar `--period` y `--date`.

`--force` reconstruye el PDF y encola un nuevo correo aunque el proyecto y período ya tengan un reporte registrado. Si existía un correo pendiente del mismo lote, queda marcado como reemplazado para evitar un envío duplicado.

Cada correo mensual se programa con una hora aleatoria entre las 07:00 y las 12:00 del día en que se ejecuta el comando. En la ejecución automática ese día es el primero del mes; el worker de correos lo entrega cuando alcanza su `send_at`.

El `--dry-run` calcula los proyectos y destinatarios sin generar PDF ni crear correos. El comando normal es idempotente para el mismo proyecto y período.
