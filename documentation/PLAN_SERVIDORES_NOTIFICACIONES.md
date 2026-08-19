# Plan de notificaciones por proyecto de servidor

## Objetivo

Permitir que cada proyecto registrado por el observer tenga un cliente asociado, una bandera para encender sus notificaciones y una selección explícita de destinatarios tomados de los datos del cliente y de los notificadores activos de sus licencias.

El observer seguirá siendo ajeno a estos datos comerciales. El ERP conserva la asociación y será quien use los destinatarios cuando se implemente el motor de alertas.

## Diseño ejecutado

1. `servers_projects.client_id` relaciona el proyecto con un cliente activo.
2. `servers_projects.notifications_enabled` controla si el proyecto está habilitado para notificar.
3. `servers_project_notifications` guarda una instantánea de cada destinatario seleccionado, su canal y el origen (`client` o `license_notification`).
4. `app/traits/servers_trait.php` concentra la consulta y validación de configuración para mantener liviano el controlador del dashboard.
5. El endpoint de destinatarios solo devuelve el correo/teléfono directo del cliente y los contactos activos de sus licencias activas, deduplicados por canal y dato.
6. El guardado valida las claves seleccionadas contra el cliente recibido y no acepta contactos arbitrarios.

## Flujo de usuario

1. Desde cada fila se abre la configuración del proyecto.
2. Se selecciona un cliente.
3. Si el proyecto aún no tiene destinatarios y existen contactos disponibles, se pregunta si desea agregarlos.
4. El usuario marca o desmarca los contactos y puede encender las notificaciones.
5. Si el proyecto ya tiene destinatarios, no se muestra la pregunta de importación; se cargan los contactos configurados y los disponibles para edición explícita.

## Verificación

- Pruebas de carga de contactos, deduplicación y exclusión de licencias/notificadores inactivos.
- Pruebas de guardado, encendido y validación de contactos de otro cliente.
- Prueba de regresión para confirmar que `discovery` conserva `client_id`, `notifications_enabled` y los destinatarios.
- Validación de rutas, sintaxis PHP y build de Vite.

## Pendiente fuera de este corte

El motor que genere alertas a partir de métricas de servidor todavía no existe. Esta entrega deja preparada y administrable la configuración de destinatarios, sin enviar notificaciones automáticamente.
