# WhatsApp en Notificaciones ERP

## Analisis de la plataforma

La integracion usa Twilio Programmable Messaging con WhatsApp Business Platform.
La direccion de cada participante es `whatsapp:+E164`; el sender debe estar
habilitado para WhatsApp en Twilio y la empresa debe conservar el opt-in del
contacto. No se debe usar este canal para mensajes sin consentimiento.

La documentacion oficial consultada fue:

- [WhatsApp API de Twilio](https://www.twilio.com/docs/whatsapp/api)
- [Plantillas de notificacion de WhatsApp](https://www.twilio.com/docs/whatsapp/tutorial/send-whatsapp-notification-messages-templates)
- [Parametros de webhooks de Messaging](https://www.twilio.com/docs/messaging/guides/webhook-request)
- [Validacion de solicitudes Twilio](https://www.twilio.com/docs/usage/security#validating-requests)
- [Content API](https://www.twilio.com/docs/content/content-api-resources)

Decisiones derivadas de esas fuentes:

1. Un mensaje libre solo se permite dentro de la ventana de atencion de 24
   horas contada desde el ultimo mensaje entrante del cliente.
2. Fuera de esa ventana se exige una plantilla aprobada mediante `ContentSid`
   y, cuando aplique, `ContentVariables`.
3. Los webhooks se validan con `Twilio\\Security\\RequestValidator`, usando la
   URL publica exacta y todos los parametros recibidos. El cuerpo crudo se
   usa para validar formularios `application/x-www-form-urlencoded`, porque
   Laravel puede recortar espacios antes de llegar al controlador.
4. Un webhook entrante puede traer texto, media, nombre de perfil, `WaId` y
   datos de mensajes interactivos. Se guarda el payload original para no perder
   informacion cuando Twilio agregue campos nuevos.

### Respuestas asistidas por IA

El webhook entrante puede responder automaticamente solo cuando se cumplen
todas estas condiciones:

1. El texto menciona un tema integrado: licencia, factura, cartera, orden de compra, valores o medios de pago.
2. El numero coincide con un contacto activo que tenga canal WhatsApp, o esta
   incluido en la lista explicita de administradores.
3. El servidor resuelve el scope antes de llamar a OpenAI: el contacto obtiene
   los clientes asociados y, desde esos clientes, todas sus licencias e
   ingresos.
4. La consulta estructurada devuelta por la IA solo puede usar IDs que el
   servidor incluyo en ese scope. Un ID externo detiene el flujo y deja el
   mensaje para atencion humana.

El flujo usa una conversacion de OpenAI por telefono y scope. Si cambia la
lista de clientes, licencias o ingresos autorizados, se crea un hilo nuevo y
no se reutiliza el contexto anterior. La respuesta final recibe unicamente
los registros obtenidos por consultas Eloquent limitadas por esos IDs. Los
temas no integrados, contactos no encontrados, fallos de IA y respuestas que
no se puedan enviar quedan registrados como `handoff` y no generan un mensaje
automatico.

La funcion esta controlada por estas variables:

```dotenv
WHATSAPP_AI_ENABLED=true
WHATSAPP_AI_ADMIN_NUMBERS=+573XXXXXXXXX,+573YYYYYYYYY
WHATSAPP_AI_MODEL=${OPENAI_MODEL_CHAT}
WHATSAPP_AI_PLANNER_MAX_OUTPUT_TOKENS=700
WHATSAPP_AI_ANSWER_MAX_OUTPUT_TOKENS=900
WHATSAPP_AI_CATALOG_LIMIT=50
```

`WHATSAPP_AI_ADMIN_NUMBERS` es la unica excepcion de contacto: sus numeros
normalizados pueden consultar cualquier cliente, licencia o ingreso, pero
siguen sujetos a los temas integrados. Debe mantenerse vacia hasta que
los numeros hayan sido revisados y aprobados por el responsable del ambiente.

#### Matriz de consultas del chat

La decision ocurre en este orden: tema permitido, contacto y scope, plan
estructurado, validacion de IDs, consulta ERP y redaccion. La IA nunca decide
si un registro pertenece al cliente; solo propone la forma de la consulta.

| Pregunta | Tema/intencion | Condicion server-side | Respuesta entregada a la IA |
| --- | --- | --- | --- |
| `Cual es mi cartera?` | `portfolio/balance` | Estados 2, 3 o 4; se excluyen rechazados y pagos aprobados. | `balance_pending = total - abonos`, total pendiente y vencido. |
| `Dame mi ultima factura` | `invoice/latest` | `state = 4`, `bill_name` o `siigo_invoice_id`; orden descendente por `created_at`; máximo un registro. | Factura más reciente, valor, estado, fechas y enlace si aplica. |
| `Por donde puedo pagar?` | `payment/payment_methods` | Solo ingresos autorizados con saldo, `payment_state != 1` y estados 0 o 2. | Bold y únicamente los enlaces de pago válidos. |
| `Facturas generadas en los ultimos 3 meses` | `invoice/history` | Facturas por `created_at` dentro del período; si no se indica período, se usan los últimos 12 meses. | Conteo, total y resumen agrupado por `YYYY-MM`. |
| `Dame mis ordenes de compra` | `purchase_order/list` | Estados 2, 3 o 4 dentro del scope. | Orden, estado, valor, vencimiento y enlace si está habilitado. |
| `Cuales son los valores de mis licencias?` | `license/values` | Licencias cuyo `id` está en el scope. | Valor por licencia y suma de valores autorizados. |

Si el mensaje mezcla palabras como `factura` y `ultimos 3 meses`, la regla
de histórico tiene prioridad sobre `latest`. Si el plan de OpenAI propone
otra intención, el servidor la normaliza con estas reglas antes de consultar.
Un período inválido, un ID fuera del scope, un resultado que requiera datos
no disponibles o un tema distinto deriva la conversación a una persona.
5. Los estados de salida llegan por `StatusCallback`; no se debe interpretar
   la respuesta `queued` de la API como entrega final.
6. Content API es la fuente de verdad de las plantillas. Una plantilla enviada
   a aprobacion por WhatsApp puede no ser editable y su eliminacion debe
   respetar las restricciones de WhatsApp.

## Arquitectura implementada

### Persistencia

- `whatsapp_conversations`: un registro por telefono y sender de negocio,
  nombre visible, cliente asociado, ultimo mensaje, ventana de atencion y
  contador de no leidos.
- `whatsapp_messages`: historial entrante y saliente, `MessageSid`, direccion,
  estado, errores, media, plantilla, variables, fechas y payload original.
- `twilio_sid` es unico para que los reintentos de Twilio no dupliquen mensajes.
- No se almacenan credenciales ni respuestas completas del SDK; solo los datos
  necesarios para operar el chat y auditar estados.

### Backend

- `twilio_whatsapp_trait` encapsula normalizacion E.164, envio libre/templated,
  estados del proveedor, firma de webhook y Content API.
- `whatsapp_notifications_trait` mantiene el caso de uso ERP: listado de
  contactos, historial, lectura, alta de conversacion, envio, recepcion y
  plantillas.
- Webhooks publicos:
  - `POST /api/webhooks/twilio/whatsapp/incoming`
  - `POST /api/webhooks/twilio/whatsapp/status`
- Endpoints autenticados bajo `/admin/notifications/whatsapp/` para el chat,
  badge de no leidos y CRUD de plantillas.

### Frontend

- La pestaña WhatsApp lista conversaciones, no filas de mensajes.
- El panel de chat muestra burbujas entrantes/salientes, estados, media,
  ventana de atencion y refresco periodico.
- Abrir una conversacion marca su contador como leido.
- El sidebar consulta el total cada 30 segundos y muestra el badge global.
- El modal de plantillas permite listar, crear, editar, eliminar y solicitar
  aprobacion a traves de Content API. La aplicacion solo expone inicialmente
  el tipo `twilio/text` en el formulario, pero conserva los tipos recibidos de
  Twilio para no destruir contenido rico al consultar la API.

### Actualizacion en tiempo real

- Se reutiliza la conexion Pusher global ya cargada por el layout ERP.
- La pestaña WhatsApp se suscribe solo mientras esta activa al canal publico
   `opzio-channel-whatsapp`.
- Los mensajes entrantes publican `opzio-event-message`; los cambios de estado
   de mensajes salientes publican `opzio-event-status`.
- El payload publicado solo contiene IDs, direccion, estado y contador de no
   leidos; no incluye cuerpo, telefono ni media.
- Al recibir un evento, la interfaz refresca conversaciones, contador y el chat
   abierto correspondiente. Al cambiar a Email/SMS, la suscripcion se desactiva.
- Como el canal sigue el patron publico existente del chat ERP, no se deben
   publicar datos sensibles en el payload.

## Activacion por ambiente

1. Habilitar el sender WhatsApp y su WABA en Twilio. Para produccion, usar un
   numero aprobado; el Sandbox sirve para pruebas y requiere el flujo de alta
   del Sandbox.
2. Configurar el webhook entrante del sender o Messaging Service como:
   `https://erp.opzio.co/api/webhooks/twilio/whatsapp/incoming` con metodo
   `POST`.
3. Configurar el callback de estados como:
   `https://erp.opzio.co/api/webhooks/twilio/whatsapp/status` con metodo
   `POST`.
4. Usar HTTPS con certificado publico. Las URLs configuradas en las variables
   deben coincidir con la URL que Twilio firma, incluidos esquema, host, puerto
   y ruta.
5. Definir en `.env`:

```dotenv
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+57XXXXXXXXXX
TWILIO_WHATSAPP_MESSAGING_SERVICE_SID=MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_WHATSAPP_WEBHOOK_URL=https://erp.opzio.co/api/webhooks/twilio/whatsapp/incoming
TWILIO_WHATSAPP_STATUS_CALLBACK_URL=https://erp.opzio.co/api/webhooks/twilio/whatsapp/status
TWILIO_WHATSAPP_VALIDATE_WEBHOOKS=true
TWILIO_WHATSAPP_DEFAULT_COUNTRY_CODE=+57
TWILIO_WHATSAPP_TEMPLATE_LIMIT=100
```

Se puede usar `TWILIO_WHATSAPP_FROM` para mensajes libres. Para plantillas,
Twilio recomienda un Messaging Service con sender WhatsApp; si se define,
la implementacion lo prioriza al enviar.

6. Ejecutar las migraciones y reconstruir assets:

```powershell
php artisan migrate
npm run build
```

7. Comprobar la firma y el flujo con un mensaje real del Sandbox antes de
   cambiar a produccion. No desactivar `TWILIO_WHATSAPP_VALIDATE_WEBHOOKS` en
   un ambiente publico.

## Plan de operacion

- Revisar logs de `whatsapp_incoming_webhook` y `whatsapp_status_webhook` ante
  respuestas 4xx/5xx. Twilio reintenta webhooks fallidos; la idempotencia por
  SID evita duplicados.
- Vigilar estados `failed`, `undelivered`, `canceled` y errores de plantilla.
- Registrar el opt-in y los opt-out en el dominio de clientes antes de enviar
  campañas. La pantalla no reemplaza esa obligación de cumplimiento.
- Mantener plantillas aprobadas en Twilio. El modulo muestra su estado, pero
  WhatsApp puede rechazar, pausar o desactivar una plantilla fuera del ERP.
- Para volumen alto, mover `Notification_SendWhatsappMessage` a una cola y
  conservar la misma transicion de estados. El flujo actual envia de forma
  sincrona para que el chat tenga respuesta inmediata.
- Mantener `WHATSAPP_AI_ADMIN_NUMBERS` fuera de repositorios publicos y
   reconstruir la configuracion (`php artisan config:cache`) despues de
   cambiarla.
- Las URLs de media entrante se conservan como referencias de Twilio. Si se
  requiere retencion local, hay que agregar un job de descarga autenticada,
  control de tamano, antivirus y politica de expiracion.

## Verificacion ejecutada

- `php artisan test tests/Feature/notifications_test.php`
- La prueba `test_whatsapp_ai_rejects_a_query_for_an_income_outside_contact_scope`
   verifica que un ID de otro cliente se bloquee antes de la respuesta y que no
   se envie ningun mensaje automatico.
- `php artisan route:list --path=notifications`
- `php artisan route:list --path=webhooks`
- `php artisan view:cache`
- `npm run build`

Las pruebas focalizadas cubren recepcion idempotente, contador de lectura,
ventana de 24 horas, envio libre y envio templated con `ContentVariables`.

La integracion Pusher queda cubierta por una prueba adicional que valida el
canal y evento emitidos al recibir un mensaje WhatsApp.