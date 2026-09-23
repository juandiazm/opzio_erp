# Plan de implementacion: modulo de notificaciones ERP

## Diagnostico del estado actual

### Correo

- La tabla `mail_logs` es el registro historico existente. Guarda asunto, vista,
  remitente, destinatarios JSON, datos JSON, intentos, estado y `sent_at`.
- `mail_log_attachments` existe, pero `mail_log_trait` tiene deshabilitado el
  guardado de adjuntos mediante un bloque comentado. Por eso los adjuntos de
  algunos envios solo sobreviven mientras el proceso actual los tiene en
  memoria.
- `SendMail` y `SendMail_attach_array` construyen `CustomMail` y llaman a
  `queue()`. La respuesta positiva se registra como `status = 1` al aceptar la
  cola, no al confirmar la entrega SMTP.
- `send_queued_mails` busca `status = 0`, reintenta hasta tres veces y vuelve a
  encolar el correo. Actualmente no filtra por fecha de envio.
- El scheduler ejecuta los procesadores de email, SMS y WhatsApp cada diez
  minutos. La cobranza automatica se genera a las 07:00 hora de Bogota y
  programa por cliente el envio del canal correspondiente en una hora aleatoria
  entre las 08:00 y las 15:00. La conexion de cola por defecto es `sync`, aunque
  produccion puede cambiarla por base de datos, Redis u otro driver.
- La infraestructura actual soporta `Reply-To`, remitente y archivos en
  `CustomMail`, pero el log no tiene una columna para programacion ni un
  formulario para editar/reutilizar un envio.

### SMS

 Twilio, antepone el texto de marca y en entorno local redirige todos los SMS
   al numero de pruebas `+573145433746` antes de enviarlos.
  notificaciones operativas actuales; el modulo nuevo usara Twilio para
  mantener una sola politica de entrega.

### Destinatarios y permisos

- `clients` contiene correo, telefono, estado y nombre. Las notificaciones
  activas de las licencias de un cliente tambien pueden aportar correo y
  telefono, por lo que se deduplicaran al construir destinatarios.
- El middleware `admin_middleware` autoriza por una fila de `user_permissions`
  y su asociacion con el usuario. El sidebar ya aplica el mismo patron para
  permisos agregados por migracion.
- No se agregara autorizacion solo en la vista: las rutas y cada operacion del
  controlador quedaran bajo el middleware existente.

### Editor y archivos

- Contratos ya tiene un editor `contenteditable` con formato, alineacion,
  listas, vista previa y sanitizacion. El modulo de notificaciones reutilizara
  el mismo comportamiento visual, pero sanitizara tambien en backend antes de
  guardar y antes de renderizar el correo.
- Los archivos se almacenaran en el disco local de Laravel y se registraran en
  `mail_log_attachments`, para que los reenvios y el worker puedan recuperar la
  ruta persistente.

## Hipotesis tecnica verificable

La solucion de menor riesgo es conservar `mail_logs` como fuente de verdad para
email, agregarle `send_at` nullable y crear `sms_logs` para SMS. Las
notificaciones nuevas se guardaran con estado pendiente; el worker solo las
procesara si `send_at` es null o ya vencio. Los envios legacy continuaran usando
su flujo actual y los registros con `send_at = null` seguiran siendo elegibles,
por lo que no se rompe compatibilidad.

La comprobacion discriminante sera ejecutar el procesador con un correo futuro,
uno vencido y uno con fecha null: solo los dos ultimos deben cambiar de estado.
Para SMS se repetira la comprobacion en local, verificando que el destinatario
efectivo sea `+573145433746`.

## Modelo de datos

### Cambio de `mail_logs`

- Agregar `send_at` nullable e indexado.
- Agregar `notification_batch` nullable para agrupar una creacion masiva y
  distinguir reenvios sin afectar registros existentes.
- Mantener `status`, `attemps` y `sent_at` por compatibilidad.
- Guardar en `mail_data` el contenido HTML sanitizado, `reply_to`, modo de
  destinatarios, canal, usuario creador y referencia de reenvio.

### Nueva tabla `sms_logs`

- `unique_id`, `client_id` nullable, nombre del destinatario, telefono,
  contenido, estado, intentos, error, `send_at`, `sent_at`, `notification_batch`,
  `resend_of_id`, `created_by`, SID/estado/error de Twilio, fecha de ultima
  consulta y timestamps.
- Estado `0` pendiente, `1` enviado y `2` agotado/fallido.
- No se guardaran tokens ni respuestas completas del proveedor.

### Nuevas tablas `whatsapp_conversations` y `whatsapp_messages`

- Las conversaciones agrupan por telefono y sender de negocio, con nombre,
  cliente asociado, ventana de atencion de 24 horas y contador de no leidos.
- Los mensajes conservan direccion, SID, estado, errores, media, plantillas,
  variables y payload original del webhook.
- El SID de Twilio es unico para soportar reintentos idempotentes.

## Superficie funcional

1. Ruta `/admin/notifications`, visible solo con `admin/notifications/`.
2. Tres pestañas: `Email`, `SMS` y `WhatsApp`. Email y SMS conservan sus
  listados, filtros y compositores existentes; WhatsApp lista conversaciones
  por telefono y abre un chat bidireccional.
3. Modal de alta con destinatarios por clientes seleccionados, opcion de todos
   los clientes activos y destinatarios manuales.
4. Email: modo masivo (un correo a todos) o individual (un registro por
   destinatario), asunto, remitente, reply-to, editor enriquecido, adjuntos y
   fecha/hora de envio opcional.
5. SMS: mensaje, clientes/destinatarios manuales y fecha/hora opcional. Cada
   telefono se procesa como un envio independiente.
6. Reenvio de cada registro enviado o fallido, abriendo el mismo modal con
   destinatarios, asunto, reply-to, contenido y adjuntos editables. El reenvio
   crea nuevos registros y conserva el historial original.
7. Estados visibles: pendiente, enviado y fallido/ag agotado, junto con fecha
  programada y fecha efectiva. Cada SMS con SID tiene una accion para consultar
  en Twilio su estado de entrega y actualizar el estado local.
8. WhatsApp: recepcion por webhook, envio libre dentro de la ventana de
  atencion, envio templated fuera de ella, estados por callback, media entrante,
  lectura y contador global de mensajes pendientes.
9. Plantillas: listado, alta, edicion, eliminacion y solicitud de aprobacion
  usando Content API, sin duplicar la fuente de verdad de Twilio.

### Cadencia automatica de cobranza

El comando diario considera ingresos aprobados, no pagados, con fecha limite
igual o anterior al dia actual y clientes activos. Solo envia en los dias
definidos por la cadencia; si un cliente tiene varias deudas, sus avisos del
dia comparten una misma hora aleatoria entre las 08:00 y las 15:00.
`command:send_pay_remaining` es el orquestador unico de la cobranza: agrupa por
cliente, separa las ordenes del dia por canal y genera el reporte administrativo.
Los comandos de email, SMS y WhatsApp que corren cada diez minutos solo entregan
las colas ya definidas por este orquestador.
El reporte resume por cliente la cartera vencida completa, los canales y montos
programados para el dia, la hora comun de envio y el detalle de cada orden con
su canal correspondiente.

| Dias vencidos | Canal |
| --- | --- |
| 0 | Email |
| 1, 3 | WhatsApp |
| 5, 12, 21, 36, 48 | SMS |
| 7, 15, 25, 30, 42, 52, 60 | Email |
| 10, 18, 28, 33, 39, 45, 56 | WhatsApp |
| Mayor a 60 | WhatsApp diario |

El dia 60 conserva el correo y el reporte interno para escalamiento manual.

## Orden de trabajo

### Fase 0 - Contrato y persistencia

- [x] Documentar el diagnostico, las decisiones y los criterios de terminado.
- [x] Agregar migracion de `send_at` y `notification_batch` a `mail_logs`.
- [x] Persistir adjuntos de correo sin romper llamadas existentes.
- [x] Crear migracion, modelo y relaciones de `sms_logs`.
- [x] Crear migraciones, modelos y relaciones de conversaciones/mensajes WhatsApp.
- [x] Crear permiso idempotente y entrada protegida del sidebar.

### Fase 1 - Dominio y procesamiento

- [x] Crear trait/controlador de notificaciones con validacion, deduplicacion y
  resolucion de contactos por cliente.
- [x] Sanitizar HTML con lista blanca y rechazar contenido vacio.
- [x] Implementar alta email/SMS, consulta paginada y reenvio editable.
- [x] Implementar worker de email programado y worker SMS con aislamiento de
  errores y maximo de tres intentos.
- [x] Implementar envio/recepcion WhatsApp, ventana de 24 horas, idempotencia,
  callbacks de estado y Content API.
- [x] Registrar el comando en scheduler sin cambiar el comportamiento de
  comandos legacy salvo el filtro de fecha requerido.

### Fase 2 - Superficie web

- [x] Crear Blade con tres pestañas, chat WhatsApp y modal de composicion.
- [x] Crear JS para listado, modal, editor enriquecido, adjuntos y reenvio.
- [x] Crear JS para conversaciones, chat, plantillas y badge de no leidos.
- [x] Crear SCSS responsive siguiendo el patron ERP existente.
- [x] Agregar endpoints de catalogo de clientes y estados.

### Fase 3 - Verificacion y cierre

- [x] Agregar pruebas focalizadas de persistencia, programacion, sanitizacion,
  masivo/individual, SMS, reenvio y permisos.
- [x] Ejecutar lint PHP, pruebas PHPUnit, cache de vistas, rutas y build Vite.
- [x] Revisar `git diff --check` y diagnosticos del editor.
- [x] Actualizar esta bitacora con resultados y pendientes residuales.

## Criterios de terminado

- Un usuario sin `admin/notifications/` no puede abrir la pagina ni ejecutar
  endpoints del modulo.
- Email, SMS y WhatsApp aparecen en pestañas separadas; WhatsApp lista contactos
  y mantiene historial bidireccional.
- Se puede crear un envio por cliente, para todos los clientes o con
  destinatarios manuales; email distingue masivo de individual.
- El cuerpo HTML se conserva con formato permitido, sin scripts, eventos ni
  URLs peligrosas, y los adjuntos sobreviven al worker y al reenvio.
- Un registro con fecha futura no se procesa; uno con fecha null o vencida si
  es elegible. Los reintentos quedan limitados.
- El reenvio permite cambiar destinatarios, reply-to, asunto, cuerpo, telefono,
  fecha y adjuntos sin alterar el registro original.
- Las migraciones, vistas, rutas, pruebas y build pasan sin errores nuevos.

## Riesgos y decisiones

- `status = 1` en el correo existente significa que la cola acepto el mensaje,
  no que el servidor SMTP confirmo entrega. Se conserva esa semantica para no
  romper contratos, ingresos y correos operativos.
- La fecha se interpreta en la zona horaria de Laravel y se envia como
  `datetime-local`; no se almacenan fechas en horario del navegador.
- El envio masivo de email se mantiene en un solo `mail_log`; el individual
  crea un log por destinatario para que cada reenvio y fallo sean independientes.
- Los adjuntos se limitan por cantidad, tamano y tipo permitido en el endpoint;
  las rutas siempre se generan en almacenamiento controlado por la aplicacion.
- WhatsApp exige firma Twilio en webhooks, opt-in del contacto y plantilla
  aprobada fuera de la ventana de atencion. Content API permanece como fuente
  de verdad de plantillas; el ERP solo presenta sus operaciones permitidas.

## Bitacora de implementacion

### 2026-08-17 - Analisis y persistencia

- Se confirmo que `mail_logs` era el registro existente y que `mail_log_trait`
  filtraba unicamente por `status = 0`, sin fecha de envio ni adjuntos durables.
- Se agregaron `send_at` nullable y `notification_batch` a `mail_logs` para
  conservar compatibilidad: `NULL` y fechas vencidas son elegibles; fechas
  futuras quedan pendientes.
- Se habilito el guardado real en `mail_log_attachments` y la resolucion de
  rutas relativas al ejecutar el worker.
- Se creo `sms_logs`, su modelo, historial de intentos, fecha, estado y
  relacion con clientes y reenvios.

### 2026-08-17 - Dominio, permisos y superficie web

- Se implementaron altas individuales y masivas de email, altas SMS por
  destinatario, destinatarios manuales y por clientes activos, deduplicacion,
  sanitizacion HTML, adjuntos y reenvios editables.
- Se agrego el permiso `admin/notifications/`, sidebar protegido, pagina,
  endpoints, scheduler de email/SMS cada diez minutos y comando
  `notifications:process-sms`.
- Se creo el editor enriquecido con vista previa y el modal responsive de dos
  canales. El smoke test autenticado comprobo acceso protegido, tabs, modal,
  cierre, alta SMS, listado, reenvio y alta email; los datos temporales fueron
  eliminados de la base.

### 2026-08-17 - Verificacion

- Migraciones reales aplicadas correctamente en MySQL local.
- `tests/Feature/notifications_test.php`: 5 tests y 28 assertions pasan.
- `tests/Feature/contracts_test.php`: 17 tests pasan; junto con notificaciones
  fueron 21 tests y 138 assertions.
- Suite completa: 34 tests pasan y 1 falla por el test preexistente
  `tests/Feature/ExampleTest.php`, que espera 200 en `/` mientras la aplicacion
  redirige a `/admin` con 302 (202 assertions totales).
- `php artisan view:cache`, `php artisan route:list --path=admin/notifications`,
  `php artisan notifications:process-sms`, `npm run build`, diagnosticos del
  editor y `git diff --check` fueron ejecutados. El ultimo solo reporta
  advertencias LF/CRLF de Git en assets generados.

### 2026-08-19 - Homologacion de superficies y detalle de email

- `notifications.blade.php` quedo como vista principal compositora, con parciales
  separados para las pestañas Email y SMS, composicion, formularios y detalle.
- JavaScript quedo dividido en estado, utilidades compartidas, email y SMS;
  SCSS quedo dividido por pestaña y superficie, conservando IDs, clases,
  endpoints y payloads existentes.
- El historial de email ahora ofrece visualizar y reenviar como acciones
  independientes. La visualizacion muestra el cuerpo sanitizado, destinatarios,
  estado, fechas y adjuntos; el reenvio permite editar destinatarios y el resto
  de los datos antes de crear nuevos registros.
- El detalle renderiza el cuerpo de historicos legacy desde la plantilla guardada
  y permite reenviar cualquier correo cuya vista pueda renderizarse; el reenvio
  crea un nuevo registro editable y conserva el original.
- Email y SMS incorporan filtros inclusivos por `created_at` con fechas `Desde`
  y `Hasta`; ambos valores se inicializan con el día actual y se adaptan al
  layout responsive de Outcomes.
- Verificado con `php artisan view:cache`, `npm run build`, `php artisan
  route:list --path=admin/notifications`, `notifications_test.php` (11 tests) y
  diagnosticos del editor.

### 2026-09-20 - WhatsApp bidireccional y Content API

- Se agregaron `whatsapp_conversations` y `whatsapp_messages`, con asociacion
  opcional a clientes, ventana de atencion, media y deduplicacion por SID.
- Se agregaron los webhooks firmados `/api/webhooks/twilio/whatsapp/incoming`
  y `/api/webhooks/twilio/whatsapp/status`.
- La pestaña lista telefonos, abre chat responsive, permite texto libre o
  `ContentSid`, marca conversaciones leidas y actualiza el badge del sidebar.
- Se agrego CRUD de Content Templates y solicitud de aprobacion sobre la API
  oficial de Twilio.
- Verificado con `php artisan test tests/Feature/notifications_test.php` (22
  tests, 122 assertions), `php artisan migrate --pretend`, `php artisan
  view:cache`, ambos `route:list` y `npm run build`.