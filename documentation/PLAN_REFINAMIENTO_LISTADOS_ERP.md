# Plan de refinamiento de listados ERP

## Regla visual comun

- La identidad principal ocupa una sola celda: avatar o logo, nombre y copia del ID.
- El ID completo permanece disponible en el tooltip y el icono de copiar conserva la clase `copy-action`.
- Los estados usan un indicador compacto con punto y etiqueta breve, sin sombras internas ni ancho completo.
- Los datos relacionados se agrupan en pilas de metadatos de dos niveles; no se eliminan datos del modelo.
- Las acciones siguen siendo iconos y conservan sus clases y atributos de fila existentes.

## Estado de implementacion

### Usuarios

Aplicado. Se fusionaron ID, foto y nombre en `Nombre`; se mantienen usuario, identificacion, correo, trazabilidad y acciones.

### Empleados

Aplicado. Se fusionaron ID, foto y nombre en `Empleado`; se mantienen identificacion, departamento, cargo, correo y estado compacto.

### Clientes

Aplicado. Se fusionaron ID, logo y nombre en `Cliente`; se mantienen identificacion, estado, telefono, correo y contador de licencias.

### Proveedores

Aplicado. Se fusionaron ID, logo y nombre en `Proveedor`; se mantienen estado, identificacion, telefono, correo y acciones.

### Departamentos

Aplicado. Se fusionaron ID y nombre en `Departamento`; se mantienen presupuesto, numero de empleados y director.

### Licencias

Aplicado. Se fusionaron ID, nombre y cliente en `Licencia`; servicio y tipo quedan apilados, y las tres fechas operativas se agrupan en `Vigencia`.

### Ingresos

Aplicado. Se fusionaron ID y cliente en `Ingreso`; pago oportuno y fecha de corte forman `Ciclo`, y las dos referencias de factura quedan en una sola celda.

### Egresos

Aplicado. Se fusionaron ID, nombre y fecha en `Egreso`; tipo y descripcion forman `Detalle`, y proveedor, empleado, departamento, usuario y cliente forman `Asociaciones`.

### Contratos

Aplicado. Se fusionaron ID, nombre, tipo y asunto en `Contrato`; inicio y vencimiento forman `Vigencia`, con estado compacto.

## Segunda fase

### Servidores

No forzar la identidad ERP: es un listado de observabilidad y sus metricas son el dato principal. Mantener la fila expandible de detalle, compactar `servers-pill` con el mismo lenguaje de estados y revisar si trafico, disponibilidad y latencia pueden presentarse como metricas apiladas en anchos menores.

### Paginas web

El listado actual es una estructura vacia sin renderizador de datos. Primero completar endpoint y contrato de fila; despues aplicar identidad, URL y estado cuando exista informacion real para no diseñar columnas ficticias.

### Catalogos de contratos

Tipos, plantillas, programaciones y contratos asociados deben compartir cabecera compacta, estado tipo chip y acciones alineadas. No necesitan avatar ni columna de ID si el nombre ya identifica el registro.

### Sublistas de documentos, licencias, usuarios y contactos

Mantenerlas como tablas secundarias. Aplicar solo espaciado, cabecera y acciones compactas; no reutilizar la celda de identidad cuando el contexto padre ya identifica la entidad.

### Dashboard, reportes y trazabilidad

No aplicar la reduccion de columnas de CRUD. Son superficies analiticas o historicas: priorizar lectura, fechas, totales y filtros sobre la unificacion de identidad.

## Criterios de aceptacion

- Buscar, paginar, editar, eliminar, restaurar, copiar ID y abrir trazabilidad conservan su comportamiento.
- Cada tabla conserva la informacion operativa que permite decidir o ejecutar una accion.
- En escritorio las filas se leen sin badges gigantes; en movil la tabla puede desplazarse horizontalmente sin romper el layout.
- No se agregan cambios de backend ni nuevos endpoints para este refinamiento.