# Plan de segmentación de módulos ERP

## Objetivo

Separar la vista Blade, los estilos SCSS y la lógica JavaScript por pestaña o superficie funcional, conservando los contratos existentes: rutas, endpoints, IDs, clases CSS, permisos y comportamiento del usuario.

Cada módulo mantendrá un archivo principal como punto de composición:

- `resources/views/erp/<modulo>.blade.php`: layout, navegación y `@include` de parciales.
- `resources/views/erp/<modulo>/`: un parcial por pestaña y por sub-pestaña cuando exista.
- `resources/js/erp/<modulo>/<modulo>.js`: entrypoint y orquestación de eventos.
- `resources/js/erp/<modulo>/`: estado compartido y lógica segmentada por pestaña.
- `resources/sass/erp/<modulo>/<modulo>.scss`: imports ordenados de parciales SCSS.
- `resources/sass/erp/<modulo>/_*.scss`: estilos propios de cada pestaña o superficie.

Los módulos sin tabs se dividirán por sección funcional solo cuando eso reduzca acoplamiento real; no se crearán archivos artificiales para una vista que ya sea una unidad pequeña.

## Orden de ejecución

| Orden | Módulo | Superficie principal | Riesgo | Estado |
| ---: | --- | --- | --- | --- |
| 1 | Licenses | Lista, creación, trazabilidad, actualización, detalles, documentos y notificaciones | Alto | Completado |
| 2 | Clients | Lista, creación, trazabilidad, actualización, usuarios y documentos | Alto | Completado |
| 3 | Users | Lista, creación, trazabilidad y actualización | Alto | Completado |
| 4 | Employees | Lista, creación, trazabilidad, actualización, documentos y licencias | Alto | Completado |
| 5 | Providers | Lista, creación, trazabilidad, actualización, documentos y contactos | Alto | Completado |
| 6 | Departments | Lista, creación, trazabilidad y actualización | Medio | Completado |
| 7 | Incomes | Lista, creación, trazabilidad, actualización, orden, PDF, abonos e importación | Muy alto | Completado |
| 8 | Outcomes | Lista, creación e importación masiva | Medio | Completado |
| 9 | Reports | Gráficas, filtros, zoom y exportación | Alto | Completado |
| 10 | Dashboard | Indicadores, gráficas y tablas | Alto | Completado |
| 11 | Web pages | Lista y administración de páginas | Bajo | Completado |
| 12 | Chat | Conversaciones, mensajes y respuesta IA | Medio | Completado |
| 13 | IA Assistant | Inicio y reportes de marketing | Medio | Completado |
| 14 | Servidores | Panel y visualización de servidores | Medio | Completado |
| 15 | My profile | Perfil, credenciales y preferencias | Medio | Completado |
| 16 | Login y reset password | Autenticación y recuperación | Medio | Completado |
| 17 | Approval flows | Aprobación de blog, Facebook, Instagram, LinkedIn y Twitter | Medio | Completado |
| 18 | Layouts y tiempo real | Layout ERP, sidebar, header, Pusher y canales | Transversal | Completado |

## Método por módulo

1. Inventariar tabs, parciales, listeners, estado, endpoints y assets antes de editar.
2. Crear la carpeta del módulo y mover cada superficie sin cambiar selectores ni nombres de endpoint.
3. Dejar el archivo principal como unificador de Blade, JS y SCSS.
4. Comparar el inventario de selectores y endpoints antes y después.
5. Ejecutar `php artisan view:cache` y `npm run build`.
6. Hacer smoke test de cada tab: carga inicial, filtros/paginación, alta, edición, eliminación/restauración, navegación secundaria y carga multimedia cuando aplique.
7. Marcar el módulo como completado únicamente cuando no haya errores de compilación ni pérdida de flujo observable.

## Criterios de no regresión

- No cambiar rutas web/API, payloads ni respuestas del backend.
- No renombrar IDs o clases usados por JavaScript o SCSS.
- No duplicar listeners al importar el entrypoint.
- Mantener el orden de carga de Vite y los assets compartidos de trazabilidad/PDF.
- No modificar módulos `client` durante esta fase del ERP.
- No tocar base de datos para validar la refactorización.

## Registro de ejecución

### Licenses

- Blade dividido en `list`, `create`, `traceability`, `update`, `details`, `documents` y `notifications`.
- JS dividido en estado, utilidades, lista, creación, actualización, detalles, documentos y notificaciones; `licenses.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `licenses.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor y `git diff --check`.

### Clients

- Blade dividido en `list`, `create`, `traceability`, `update`, `users`, `documents` y `licenses`.
- JS dividido en estado, utilidades, lista, creación, actualización, usuarios, documentos, licencias y sincronización Siigo; `clients.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `clients.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, inventario de endpoints, `php artisan route:list --path=admin/clients` y `git diff --check`.

### Users
- Blade dividido en `list`, `create`, `traceability` y `update`.
- JS dividido en estado/permisos, lista, creación y actualización; `users.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `users.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, inventario de endpoints, `php artisan route:list --path=admin/users` y `git diff --check`.

### Employees

- Blade dividido en `list`, `create`, `traceability`, `update`, `hiring`, `documents` y `licenses`.
- JS dividido en estado, lista, creación, actualización/contratación, documentos, licencias y departamentos; `employees.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `employees.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, inventario de endpoints, `php artisan route:list --path=admin/employees` y `git diff --check`.

### Providers

- Blade dividido en `list`, `create`, `traceability`, `update`, `payments`, `documents` y `contacts`.
- JS dividido en estado, utilidades, lista, creación, actualización, documentos y contactos; `providers.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `providers.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, inventario de endpoints, `php artisan route:list --path=admin/providers` y `git diff --check`.

### Departments

- Blade dividido en `list`, `create`, `traceability`, `update` y `employee`.
- JS dividido en estado, utilidades/directores, lista, creación y actualización; `departments.js` coordina imports y listeners.
- SCSS dividido en `_list.scss`, `_create.scss` y `_update.scss`; `departments.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, inventario de endpoints, `php artisan route:list --path=admin/departments` y `git diff --check`.

### Incomes

- Blade dividido en `list`, `create`, `traceability`, `update`, `document-viewer`, `import` y `advances`, preservando IDs y markup de tabs, PDF, receptores y abonos.
- JS dividido en estado compartido, lista, creación, actualización, documento/receptores, importación y abonos; `incomes.js` conserva únicamente la orquestación de listeners y tabs.
- SCSS dividido en `_list.scss`, `_create.scss`, `_update.scss`, `_document.scss`, `_import.scss` y `_advances.scss`; `incomes.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/incomes` y `git diff --check`.

### Próximo módulo: Outcomes

Separar la lista, el formulario de creación y la importación masiva; conservar contratos de rutas, IDs, clases y listeners antes de avanzar a Reports.

### Outcomes

- Blade dividido en `list`, `create`, `form`, `update` e `import`, preservando los filtros de fecha, paginación, acciones de recuperación y modal de importación.
- JS dividido en estado, lista, catálogos, creación, actualización e importación; `outcomes.js` conserva la orquestación de tabs y listeners sin duplicarlos.
- SCSS dividido en `_list.scss`, `_form.scss` y `_import.scss`; `outcomes.scss` conserva el orden de composición.
- Outcomes permite asociar cada egreso, de forma opcional, a proveedor, empleado, departamento, usuario y cliente mediante columnas nullable y sus relaciones Eloquent.
- Añadidos endpoints de catálogo, creación y actualización, con validación de claves foráneas y compatibilidad con la importación masiva existente.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/outcomes` y `git diff --check`.

### Próximo módulo: Reports

Separar gráficas, filtros, zoom y exportación por superficie; inventariar primero los contratos compartidos con Dashboard antes de mover listeners.

### Reports

- Blade dividido en `charts`, `zoom` y `export`, conservando los seis canvas, el modal de detalle y los botones de exportación.
- JS dividido en estado, gráficas, zoom/tablas y exportación; `reports.js` conserva la inicialización de `daterangepicker` y los listeners únicos.
- SCSS dividido en `_layout.scss`, `_export.scss`, `_charts.scss` y `_zoom.scss`; `reports.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/reports` y `git diff --check`.

### Próximo módulo: Dashboard

Separar indicadores, gráficas y tablas; verificar primero qué funciones/selectores de Outcomes y Reports consume para no romper el panel transversal.

### Dashboard

- Blade dividido en `indicators`, `income-outcome-graph`, `approve-incomes`, `tables` e `incomes-by-client`, conservando las dos columnas y los IDs de navegación.
- JS dividido en estado, indicadores, tablas y gráficas; `dashboard.js` conserva debounce, listeners y las ocho cargas iniciales.
- SCSS dividido en `_layout.scss`, `_indicators.scss`, `_graphs.scss` y `_tables.scss`; `dashboard.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/dashboard` y `git diff --check`.

### Próximo módulo: Web pages

Separar lista y administración de páginas; conservar editor, filtros, acciones y endpoints antes de continuar con Chat.

### Web pages

- Blade dividido en `list`, preservando el shell existente, sus IDs de tabla y paginación.
- JS y SCSS no tenían lógica propia ni superficie adicional que segmentar; no se inventaron endpoints, modelo ni migraciones.
- Validado con `php artisan view:cache`, `npm run build`, `php artisan route:list --path=admin/web-pages` y `git diff --check`.
- Pendiente funcional: el repositorio solo expone la vista `admin/web-pages`; no existe API ni entidad de páginas para administrar datos.

### Chat

- Blade conservó la composición de conversaciones y mensajes en `erp.chat.messages` y `erp.chat.conversations`.
- JS quedó dividido en estado, conversaciones, mensajes y respuesta IA; `chat.js` conserva la orquestación única y Pusher sigue importando `appendChatMesssages`.
- SCSS quedó dividido en `_layout.scss`, `_conversations.scss` y `_messages.scss`; `chat.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/chat` y `git diff --check`.

### IA Assistant

- Blade del reporte de marketing quedó dividido en `generate`, `history`, `report` y `email`; la pantalla inicial se mantuvo como unidad pequeña.
- JS quedó dividido en estado, utilidades compartidas, clientes/archivo, historial, generación, reporte y correo; `ia_marketing_report.js` conserva únicamente la inicialización.
- SCSS quedó dividido en `_layout.scss`, `_forms.scss`, `_history.scss`, `_report.scss` y `_modal.scss`; `ia_marketing_report.scss` conserva el orden de composición.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/ia-assistant` y `git diff --check`.

### Servidores

- Blade conserva la única pestaña del panel, sin crear parciales artificiales.
- JS quedó dividido en estado, formateadores, vista y datos; `servers.js` conserva los listeners de filtros, orden, detalle, paginación y exportación.
- SCSS quedó dividido en `_layout.scss`, `_filters.scss`, `_overview.scss`, `_table.scss` y `_responsive.scss`; `servers.scss` conserva el orden de composición.
- Validado con `npm run build`, diagnósticos del editor, `php artisan route:list --path=admin/servers` y `git diff --check`.

### My profile

- Blade quedó dividido en el parcial `update`, conservando la única pestaña y sus IDs.
- JS quedó dividido en estado, perfil, permisos y actualización; se conservaron `window.current_user`, `window.permissions` y el payload multimedia.
- SCSS quedó dividido en `_form.scss` y `_permissions.scss`; `my_profile.scss` conserva el orden de composición.
- Validado con `npm run build`, `php artisan view:cache`, diagnósticos del editor, `php artisan route:list --path=admin/my-profile` y `git diff --check`.

### Login y reset password

- Login quedó dividido en autenticación y recuperación; `login.js` conserva listeners y precarga de `restore-email`/`restore-code`.
- Reset password se mantuvo como unidad pequeña, sin archivos artificiales.
- SCSS de Login quedó dividido en `_layout.scss`, `_form.scss` y `_responsive.scss`; reset password conserva su entrypoint único.
- Validado con `npm run build`, `php artisan view:cache`, diagnósticos del editor, `php artisan route:list --path=admin/login` y `git diff --check`.

### Approval flows

- Blog conserva su vista, JS y SCSS específicos por sus cuatro opciones de propagación.
- Facebook, Instagram, LinkedIn y Twitter comparten el parcial Blade `erp.approval.post`, el helper JS `approval/post.js` y `_post.scss`, manteniendo sus entrypoints y endpoints de plataforma.
- Validado con `php artisan view:cache`, `npm run build`, diagnósticos del editor, rutas `api/blog`, `api/facebook`, `api/instagram`, `api/linkedin` y `api/twitter`, y `git diff --check`.

### Layouts y tiempo real

- `erp.layouts.app` carga `layouts/app.js` como único coordinador de sidebar y header; Pusher y el canal Chat se mantienen separados.
- Se conservaron los parciales Blade, estilos segmentados de layout y el contrato de `appendChatMesssages` para eventos en tiempo real.
- Validado con `npm run build`, `php artisan view:cache`, diagnósticos del editor y `git diff --check`.