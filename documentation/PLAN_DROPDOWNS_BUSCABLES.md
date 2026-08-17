# Plan de dropdowns buscables

## Objetivo

Reemplazar progresivamente los `<select>` nativos del ERP y del portal cliente por un control buscable, accesible y reutilizable, sin romper los contratos actuales de formularios, IDs, nombres, eventos `change` ni endpoints.

## Estado actual

- Existe `resources/js/crud-input.js`, orientado a catálogos CRUD con endpoints propios.
- Existe un dropdown específico para clientes en IA Marketing Report.
- Se añadió `resources/js/searchable-dropdown.js`, un adaptador progresivo para `<select>` simples.
- Se añadió `resources/sass/searchable-dropdown.scss` y se carga desde el layout base.
- El piloto está activo en `resources/views/erp/outcomes/form.blade.php`.
- El `<select>` original permanece en el DOM, conserva `id`, `name` y `value`, y recibe los eventos `change` para mantener compatibilidad.

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
- Outcomes: `form` (piloto)
- Providers: `create`, `update`
- Servers: vista principal

### Portal cliente

- Companies
- Incomes
- Licenses
- Register

### Generación dinámica desde JavaScript

Hay superficies que reconstruyen opciones desde AJAX en clientes, empleados, departamentos, ingresos, licencias, outcomes, proveedores, usuarios, servidores y trazabilidad. Deben migrarse junto con sus listeners y no solo cambiando el markup Blade.

## Fases de trabajo

### Fase 0: contrato y piloto

- Completado con `searchable-dropdown.js` y `searchable-dropdown.scss`.
- Validar búsqueda, selección, teclado, click fuera, opciones inyectadas por AJAX, `change`, estados invalid/disabled y responsive en Outcomes.

### Fase 1: filtros y listados de bajo acoplamiento

- Migrar filtros de estado y selects de listados en Licenses e Incomes.
- Migrar filtros de Servers después de identificar si el valor se usa para ordenar, paginar o pedir datos.
- Mantener el mismo `id` y disparar `change` en el select nativo.

### Fase 2: formularios ERP con catálogos

- Migrar Clients, Providers, Employees y Departments.
- Migrar Clients, Employees y Services dentro de Licenses.
- Migrar Clients, Employees y asociaciones de Incomes.
- Revisar selects CRUD y mantener `crud-input` cuando exista alta/edición/borrado inline; el nuevo control cubre selección, no administración del catálogo.

### Fase 3: portal cliente

- Migrar Companies, Incomes, Licenses y Register.
- Verificar permisos, sesiones de cliente y nombres de payload separados del panel administrativo.

### Fase 4: superficies dinámicas

- Sustituir los `.html('<option...')`, `.append('<option...')` y selects creados desde JS por `SearchableDropdown.setOptions` o por `init` después de insertar el select.
- Revisar respuestas vacías, catálogos grandes y búsqueda remota cuando un catálogo no deba cargarse completo.

### Fase 5: limpieza y retirada

- Confirmar que no quedan `<select>` nativos sin justificar.
- Retirar estilos y listeners duplicados solo después de cerrar todos los módulos.
- Mantener `crud-input` y el dropdown específico de IA hasta que sus responsabilidades estén cubiertas por componentes equivalentes.

## Criterios de aceptación por módulo

- El usuario puede abrir el control sin ver el dropdown nativo del navegador.
- La búsqueda filtra dinámicamente y muestra estado vacío.
- La selección actual se refleja en la interfaz y en el `<select>` original.
- Los listeners existentes reciben `change` una sola vez.
- Funcionan teclado, `Escape`, click fuera, disabled, invalid y formularios enviados.
- Los catálogos cargados por AJAX actualizan el control sin reinicializar toda la página.
- No hay regresiones en paginación, filtros, tabs ni modales.
- El control conserva legibilidad y desplazamiento usable en móvil.

## Validación global

Por cada fase:

1. `php artisan view:cache`
2. `npm run build`
3. `php artisan route:list --path=<modulo>` cuando aplique
4. Diagnósticos del editor y `git diff --check`
5. Smoke test de alta, edición, filtros, carga AJAX y envío del formulario