# Plan de dropdowns buscables

## Objetivo

Reemplazar progresivamente los `<select>` nativos del ERP y del portal cliente por un control buscable, accesible y reutilizable, sin romper los contratos actuales de formularios, IDs, nombres, eventos `change` ni endpoints.

## Estado actual

- Existe `resources/js/crud-input.js`, orientado a catálogos CRUD con endpoints propios.
- Existe un dropdown específico para clientes en IA Marketing Report.
- Se añadió `resources/js/searchable-dropdown.js`, un adaptador progresivo para `<select>` simples.
- Se añadió `resources/sass/searchable-dropdown.scss` y se carga desde el layout base.
- La migración global está activa desde `resources/views/layouts/app.blade.php`.
- Todos los `<select>` simples del proyecto se inicializan automáticamente; no se encontraron controles `<select multiple>`.
- El `<select>` original permanece oculto en el DOM, conserva `id`, `name` y `value`, y recibe los eventos `change` para mantener compatibilidad.
- Los selects insertados posteriormente por AJAX, paginación o renderizado dinámico también se convierten mediante un `MutationObserver`.
- Los catálogos dinámicos de Servers, Incomes, Licenses, Departments, Employees y Traceability ya usan explícitamente `SearchableDropdown.setOptions`.

## API del componente

```javascript
SearchableDropdown.init('#mi-select');
SearchableDropdown.setOptions('#mi-select', [
    {value: '1', label: 'Primera opción'},
    {value: '2', label: 'Segunda opción'},
]);
SearchableDropdown.setValue('#mi-select', '2');
const value = SearchableDropdown.getValue('#mi-select');
SearchableDropdown.destroy('#mi-select');
```

Uso declarativo:

```html
<select id="mi-select" name="mi_id" class="js-searchable-dropdown" data-placeholder="Seleccionar">
    <option value="" selected disabled>Seleccionar</option>
    <option value="1">Primera opción</option>
</select>
```

El componente observa cambios en las opciones, filtra por texto y valor, soporta `Escape`, flechas, `Enter`, `Space`, click fuera y estados disabled/invalid.

## Inventario

Actualmente se localizaron 23 vistas con markup `<select>` y 38 líneas que lo contienen:

### ERP

- Clients: `create`, `update`
- Departments: `create`, `update`
- Employees: `create`, `hiring`, `update`
- Incomes: `advances`, `create`, `list`, `update`
- Licenses: `create`, `details`, `list`, `update`
- Outcomes: `form`
- Providers: `create`, `update`
- Servers: vista principal

### Portal cliente

- Companies
- Incomes
- Licenses
- Register

### Generación dinámica desde JavaScript

Hay superficies que reconstruyen opciones desde AJAX en clientes, empleados, departamentos, ingresos, licencias, outcomes, proveedores, usuarios, servidores y trazabilidad. Las superficies cubiertas usan la API directa; las restantes quedan protegidas por la inicialización automática y el observador de nodos.

## Fases de trabajo

### Fase 0: contrato y piloto - completada

- Completada con `searchable-dropdown.js` y `searchable-dropdown.scss`.
- La prueba inicial se realizó en Outcomes y se extendió al inicializador global.

### Fase 1: filtros y listados de bajo acoplamiento - completada

- Cubiertos filtros de estado, paginación y servidores.
- Se conservan los mismos `id`, clases, valores y eventos `change`.

### Fase 2: formularios ERP con catálogos - completada

- Cubiertos Clients, Providers, Employees, Departments, Licenses, Incomes y Outcomes.
- Los controles `crud-input` ya eran dropdowns personalizados y se mantienen porque administran catálogos inline; no son dropdowns nativos.

### Fase 3: portal cliente - completada

- Cubiertos Companies, Incomes, Licenses y Register.
- Los permisos, sesiones y payloads no cambian porque el select nativo subyacente se conserva.

### Fase 4: superficies dinámicas - completada

- Los selects creados por `.html()`, `.append()`, `innerHTML` y paginación se detectan automáticamente después de insertarse.
- Las opciones modificadas dentro de un select existente se sincronizan mediante `MutationObserver`.
- Servers, Incomes, Licenses, Departments, Employees y Traceability cargan sus catálogos con `SearchableDropdown.setOptions` y conservan sus selecciones sin disparar consultas duplicadas.
- La búsqueda actual es local sobre las opciones cargadas; los catálogos que requieran búsqueda remota quedan como evolución futura.

### Fase 5: limpieza y retirada - completada

- Confirmado: 38 líneas con `<select>` en 23 vistas, sin `multiple`, quedan cubiertas por el inicializador global.
- No se retiraron listeners existentes porque continúan operando sobre el select oculto compatible.
- `crud-input` y el dropdown específico de IA se mantienen por tener responsabilidades distintas y no usar el dropdown nativo del navegador.

## Criterios de aceptación por módulo

- El usuario puede abrir el control sin ver el dropdown nativo del navegador.
- La búsqueda filtra dinámicamente y muestra estado vacío.
- La selección actual se refleja en la interfaz y en el `<select>` original.
- Los listeners existentes reciben `change` una sola vez.
- Funcionan teclado, `Escape`, click fuera, disabled, invalid y formularios enviados.
- Los catálogos cargados por AJAX actualizan el control sin reinicializar toda la página.
- No hay regresiones en paginación, filtros, tabs ni modales.
- El control conserva legibilidad y desplazamiento usable en móvil.

## Resultado de la migración

- Estado: completada para el inventario actual del proyecto; continúa la consolidación de renderizadores dinámicos mediante la API explícita.
- Cobertura: 100% de los selects simples estáticos y dinámicos detectados.
- Compatibilidad: `id`, `name`, `.val()`, eventos `change`, estados disabled/invalid y opciones AJAX preservados.
- Exclusión explícita: los controles múltiples requerirán una variante multiselección si se incorporan en el futuro.

## Validación global

Para la migración ejecutada:

1. `php artisan view:cache`
2. `npm run build`
3. `php artisan route:list --path=<modulo>` cuando aplique
4. Diagnósticos del editor y `git diff --check`
5. Smoke test de alta, edición, filtros, carga AJAX y envío del formulario

Para cambios futuros:

- Todo nuevo `<select>` simple queda cubierto automáticamente por `SearchableDropdown`.
- Los nuevos `<select multiple>` deben diseñarse con un componente específico antes de incorporarse.