# Plan de implementacion: modulo de contratos ERP

## Objetivo

Agregar al ERP un modulo para administrar contratos con clientes, empleados y proveedores. El modulo debe permitir crear, actualizar, consultar, eliminar/restaurar y generar contratos a partir de tipos y plantillas, manteniendo la estructura existente de Laravel, Blade, Vite, traits, permisos y scheduler.

## Hipotesis tecnica

Un contrato necesita un titular variable y no debe duplicar `client_id`, `employee_id` y `provider_id`. Se usara una relacion polimorfica `contractable` limitada en la capa de dominio a `client`, `employee` y `provider`. El contenido generado se guardara en el contrato para conservar la version que fue creada, mientras la plantilla conserva el formato reutilizable.

La automatizacion puede usar el scheduler existente de Laravel y `SendMail`, que ya registra y encola correos mediante `mail_log`. No se agregara un worker paralelo ni se enviaran correos desde una peticion web.

## Alcance funcional

1. **Contratos**
   - Listado paginado con busqueda, filtros por tipo, titular y estado.
   - Alta y actualizacion de contratos manuales o generados desde plantilla.
   - Estados: generado, en espera de firma, firmado, vencido, finalizado y cancelado; el envio mantiene estado separado.
   - Fechas de inicio y vencimiento, asunto, contenido y notas.
   - Eliminacion logica y restauracion.
   - Accion para generar/regenerar contenido desde una plantilla y accion para enviar al titular.
   - Pestaña de contratos asociados en las fichas de clientes, empleados y proveedores.

2. **Tipos de contrato**
   - CRUD de tipos con nombre, descripcion y estado activo.
   - Un tipo puede tener varias plantillas versionadas.

3. **Plantillas**
   - CRUD de plantillas asociadas a un tipo.
   - Asunto y contenido HTML/texto con variables seguras como `{{contractable.name}}`, `{{client.name}}`, `{{employee.name}}`, `{{provider.name}}`, `{{department.name}}`, `{{licenses.count}}` y `{{incomes.total}}`.
   - Editor enriquecido con alineacion, listas, formato de parrafo, insercion de variables y vista previa con datos de ejemplo.
   - Variables personalizadas definidas en la plantilla y valores concretos congelados por contrato en `generation_data.custom_variables`.
   - La generacion reemplaza solo variables permitidas y conserva el resultado en `contracts.content`.

4. **Recurrencia por contrato**
   - Frecuencia diaria, semanal, mensual o anual, intervalo, proxima creacion, fecha final opcional y envio automatico por contrato.
   - Un job diario crea un nuevo contrato con las mismas fuentes, variables y duracion desplazada.
   - La recurrencia activa se transfiere al nuevo contrato y se apaga en el contrato anterior.
   - El procesamiento usa bloqueo transaccional para evitar duplicados.

5. **Correo**
   - El destinatario se obtiene del titular: `email` para clientes/proveedores y `work_email` con fallback a `personal_email` para empleados.
   - Se utiliza la vista de correo propia del modulo y `SendMail` para respetar la cola/log existente.
   - No se guardan tokens ni solicitudes completas en logs.

6. **Firma publica**
   - Cada envio incluye una URL publica con token individual para cargar el PDF firmado.
   - Estados del PDF: `pendiente`, `cargado` y `aceptado`.
   - El PDF firmado se almacena en disco privado; la URL se bloquea cuando el estado pasa a `aceptado`.
   - El equipo administrativo puede cambiar el estado desde el listado de contratos; aceptar el PDF marca el contrato como firmado.

## Modelo de datos propuesto

- `contract_types`: `id`, `name`, `description`, `active`, timestamps y soft deletes.
- `contract_templates`: `id`, `contract_type_id`, `name`, `subject`, `content`, `variables` como JSON con `key`, `label`, `type`, `default_value` y `required`, `version`, `active`, timestamps y soft deletes.
- `contracts`: `id`, `unique_id`, `contract_type_id`, `contract_template_id`, `contractable_type`, `contractable_id`, `sources` como JSON de filas seleccionables (`client`, `employee`, `provider` o `license`), `license_id` opcional, `name`, `subject`, `content`, estado, estado de envio, fechas, recurrencia, `generated_at`, `pdf_generated_at`, `sent_at`, `signed_at`, `notes`, `generation_data`, timestamps y soft deletes.
- `contracts`: incluye tambien `recurrence_enabled`, `recurrence_frequency`, `recurrence_interval`, `recurrence_next_at`, `recurrence_ends_at`, `recurrence_send_automatically`, `recurrence_last_at`, `recurrence_error`, `recurrence_parent_id`, `send_status` y `pdf_generated_at`.
- `contracts.generation_data.custom_variables`: snapshot de valores personalizados usado en regeneraciones, sin guardar valores escapados.
- Indices en tipo, titular polimorfico, estado y proxima ejecucion de recurrencia. La unicidad de ejecucion se controla con bloqueo transaccional por contrato.

## Integracion con la estructura actual

- Vista compositora: `resources/views/erp/contracts.blade.php`.
- Parciales: `list`, `create`, `update`, `types` y `templates`.
- JS: `resources/js/erp/contracts/contracts.js` como orquestador y modulos por superficie con estado separado.
- SCSS: `resources/sass/erp/contracts/contracts.scss` y parciales de listado/formulario.
- Backend: `contracts_controller.php`, `contracts_trait.php` y modelos Eloquent en singular minuscula, siguiendo el estilo actual.
- Rutas bajo `/admin/contracts`, protegidas por `admin_middleware`, mas `get-associated` para las fichas de titulares.
- Permiso `admin/contracts/` agregado por migracion y mostrado en el sidebar usando el mismo mecanismo existente.
- Relaciones `contracts()` en `client`, `employee` y `provider`, sin alterar sus claves actuales.

## Orden de implementacion

### Fase 0 - Contrato y esquema

- [x] Documentar decisiones, endpoints y criterios de terminado.
- [x] Crear migraciones de tipos, plantillas y contratos.
- [x] Crear modelos, relaciones, casts y estados permitidos.
- [x] Agregar permiso y entrada de sidebar.

### Fase 1 - Dominio y API

- [x] Implementar trait/controlador para catalogos, CRUD, generacion, envio y contratos asociados.
- [x] Implementar render seguro de variables y resolucion de titulares.
- [x] Implementar comando diario `contracts:process-recurrences` idempotente.
- [x] Registrar el comando diario en el scheduler con bloqueo de concurrencia.
- [x] Agregar vista de correo de contratos.

### Fase 2 - Superficie principal

- [x] Crear Blade, JS y SCSS segmentados para listado, alta, actualizacion, tipos y plantillas.
- [x] Mantener IDs, clases y convenciones de paginacion compatibles con el ERP.
- [x] Agregar catalogos buscables para titulares y tipos.

### Fase 3 - Asociaciones

- [x] Agregar la subpestana Contratos a clientes, empleados y proveedores.
- [x] Cargar contratos asociados mediante un script compartido sin duplicar listeners.
- [x] Permitir abrir un contrato asociado en el modulo principal.

### Fase 4 - Verificacion y cierre

- [x] Ejecutar `php artisan view:cache`.
- [x] Ejecutar `php artisan route:list --path=admin/contracts`.
- [x] Ejecutar `npm run build`.
- [x] Ejecutar pruebas PHPUnit del dominio y del comando diario de recurrencias.
- [x] Revisar diagnosticos del editor y `git diff --check`.
- [x] Hacer smoke test de navegacion, formularios y asociaciones.
- [x] Actualizar esta bitacora con resultados y pendientes residuales.

### Fase 5 - Editor enriquecido y variables dinamicas

- [x] Agregar `contract_templates.variables` y normalizar nombres, tipos, valores por defecto y obligatoriedad.
- [x] Publicar catalogo seguro de variables de contrato, titular, cliente, empleado, proveedor, departamento, licencias e ingresos.
- [x] Implementar editor `contenteditable` con formato, alineacion, listas, insercion de variables y preview.
- [x] Capturar valores propios al crear o actualizar contratos y reutilizarlos al regenerar.
- [x] Sanitizar HTML conservando estilos de presentacion permitidos y eliminando scripts, eventos y URLs peligrosas.
- [x] Cubrir renderer, fuentes de cliente/licencias/ingresos y normalizacion con pruebas focalizadas.

## Criterios de terminado

- El modulo carga con el permiso correcto y aparece en el sidebar.
- Se pueden crear y editar contratos para los tres tipos de titular.
- Una plantilla genera contenido reproducible con variables permitidas.
- Los contratos asociados aparecen en las tres fichas fuente y enlazan al registro completo.
- Una recurrencia activa no duplica contratos al procesarse dos veces para la misma fecha.
- El envio usa la infraestructura de correo existente, reutiliza el PDF almacenado y no lo regenera.
- Migraciones, vistas, rutas, JS y pruebas pasan sin errores nuevos.

## Riesgos y decisiones

- No se generara DOCX en esta primera entrega: el documento generado queda en HTML y puede enviarse por correo. El campo `content` permite incorporar una salida PDF en una iteracion posterior sin perder el contrato historico.
- No se hard-deletearan titulares desde el modulo; los contratos conservaran su contenido aunque el titular se elimine logicamente.
- Las plantillas no ejecutan PHP ni Blade arbitrario. Solo se reemplaza una lista blanca de variables.
- La recurrencia automatica requiere que el scheduler de Laravel este activo en el entorno de despliegue.

## Bitacora de iteraciones

### 2026-08-17 - Fase 0

- Se revisaron los modulos de licencias, proveedores, clientes y empleados.
- Se confirmo el patron Blade/JS/SCSS segmentado y el uso de traits/controladores.
- Se confirmo que `SendMail` y `mail_log` son la infraestructura de envio existente.
- Se creo este plan antes de iniciar los cambios de codigo.

### 2026-08-17 - Fases 1 a 4

- Se crearon inicialmente las tablas de contratos y programaciones; la migracion `000014` traslado la recurrencia a `contracts` y elimino `contract_schedules` y sus columnas asociadas.
- Se agregaron los modelos, el trait `contracts_trait`, el controlador, las rutas bajo `/admin/contracts`, el permiso, el sidebar y el comando diario `contracts:process-recurrences`.
- Se implementaron CRUD, restauracion, generacion con lista blanca de variables, envio mediante `SendMail` y recurrencia encadenada por contrato.
- Se implementaron Blade, JS y SCSS segmentados; Vite se actualizo sin el modulo de programaciones.
- Clientes, empleados y proveedores incluyen la subpestana `Contratos`, con loader comun y enlace al contrato completo.
- Las seis migraciones se ejecutaron correctamente en la base local.
- `php artisan view:cache`, `php artisan route:list --path=admin/contracts`, `npm run build`, diagnosticos del editor y las pruebas focalizadas de contratos pasan.
- `php artisan test tests/Feature/contracts_test.php` pasa con 10 assertions. La suite completa pasa 14 tests y 69 assertions, pero conserva el fallo preexistente de `tests/Feature/ExampleTest.php`, que espera 200 en `/` aunque la aplicacion redirige a `/admin` y devuelve 302.
- El smoke test autenticado comprobo carga del modulo, sidebar, filtros, Crear, Tipos, Plantillas y la pestana asociada de clientes y proveedores. No se crearon registros funcionales desde el navegador.

### 2026-08-17 - Fase 5

- Se incorporo el editor enriquecido de plantillas con vista previa, alineacion, listas, formato de parrafo y paleta de variables.
- Se agregaron variables de cliente, empleado, proveedor, departamento, licencias e ingresos, con resolucion mediante lista blanca y valores escapados al renderizar.
- Se agregaron variables propias por plantilla; sus valores se guardan sin escapar en `generation_data` y se vuelven a aplicar al regenerar.
- Se agrego sanitizacion HTML para conservar formato permitido sin ejecutar scripts, eventos ni enlaces peligrosos.
- La suite focalizada de contratos pasa 5 tests y 19 assertions; `npm run build` compila el editor y los diagnosticos del editor no reportan errores.

### 2026-08-17 - Fuentes y recurrencia de licencias

- El alta de contratos exige plantilla y siempre genera el contenido; ya no permite contenido directo.
- Las variables de la plantilla determinan las fuentes requeridas. El contrato conserva la fuente principal compatible con el modelo anterior y un JSON con fuentes adicionales.
- La licencia se selecciona como una fuente mas dentro de una lista repetible. Su cliente se incorpora y valida automaticamente como fuente; las variables de licencia requieren una licencia seleccionada.
- La recurrencia del contrato puede tomar la vigencia por defecto de la ultima facturacion/pago y la proxima facturacion de la licencia, pero su configuracion y cadena viven en `contracts`.
- Se agregaron migraciones, controles de alta/edicion y pruebas de fuentes multiples y recurrencia.

### Fase 6 - Destinatarios de contratos

- [x] Agregar `POST /admin/contracts/send-options` para resolver destinatarios sugeridos.
- [x] Priorizar notificaciones activas de la licencia, usar el correo directo del cliente como fallback y dejar la lista vacia cuando no exista un correo valido.
- [x] Permitir editar uno o varios destinatarios antes de enviar el contrato desde el dialogo de confirmacion.
- [x] Validar destinatarios en backend y aplicar `Reply-To: legal@opzio.co` a envios manuales y programados.
- [x] Cubrir prioridad, fallback, envio personalizado y `Reply-To` con pruebas focalizadas.

### 2026-08-17 - Destinatarios y Reply-To

- El envio manual consulta las opciones del contrato y precarga las notificaciones activas de su licencia; si no existen, precarga el correo del cliente.
- El campo de destinatarios acepta correos separados por coma, punto y coma o salto de linea y permite redactarlos cuando no hay un valor por defecto.
- `SendMail` conserva la compatibilidad con los envios existentes y permite definir un `Reply-To`; los contratos usan `legal@opzio.co`.
