# Plan de acción V2 - Observabilidad de infraestructura integrada con `opzio_erp`

**Estado:** Documento vivo / iterativo  
**Arquitectura:** tres frentes independientes  
**Servidor:** Debian + NGINX + PHP-FPM en GCP  
**Aplicación existente:** `opzio_erp` (Laravel, con CI/CD existente)  
**Proyecto nuevo:** `opzio_observer_agent` (Go)

---

# 1. Decisión arquitectónica

A partir de este punto el proyecto se divide en tres responsabilidades estrictas:

```text
┌──────────────────────────────────────────────────────────────┐
│                    SERVIDOR DE PRODUCCIÓN                    │
│                                                              │
│  NGINX logs        PHP-FPM status        /proc               │
│  filesystem        cgroup v2             systemd             │
│       │                  │                  │                 │
│       └──────────────────┼──────────────────┘                 │
│                          ▼                                    │
│               opzio_observer_agent                           │
│                    (Go service)                              │
│                          │                                    │
│                    HTTP localhost                            │
│                   127.0.0.1:9080                            │
│                          │                                    │
│                          ▼                                    │
│                     opzio_erp                                │
│                       Laravel                                │
│                          │                                    │
│             ┌────────────┼────────────┐                      │
│             ▼            ▼            ▼                      │
│          Database     Dashboard     Alerts                    │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## 1.1 Responsabilidad del servidor

El servidor prepara las **fuentes de observabilidad** y los permisos necesarios:

- NGINX genera logs JSON por proyecto;
- PHP-FPM expone status local por pool;
- Linux expone `/proc`, filesystem y cgroup v2;
- systemd mantiene ejecutándose el agente;
- el usuario del agente tiene acceso de solo lectura a las fuentes necesarias;
- ningún endpoint de observabilidad del agente o del ERP queda expuesto públicamente.

Los cambios de esta sección se ejecutan directamente mediante VS Code Remote SSH / terminal del servidor.

## 1.2 Responsabilidad de `opzio_erp`

`opzio_erp` es el **control plane y propietario de los datos**:

- mantiene el catálogo de proyectos;
- define qué proyectos se monitorean;
- recibe muestras del agente;
- valida y persiste métricas;
- realiza agregaciones y retención;
- muestra dashboards;
- calcula alertas;
- mantiene eventos y auditoría;
- entrega al agente su configuración activa.

La base de datos de `opzio_erp` es la **fuente canónica**. El agente no escribe directamente en la base de datos.

## 1.3 Responsabilidad de `opzio_observer_agent`

El proyecto nuevo será un servicio pequeño en Go cuya única responsabilidad es **recolectar y entregar métricas**.

El agente:

- lee NGINX;
- consulta PHP-FPM;
- lee `/proc`;
- calcula CPU/RAM de procesos;
- escanea almacenamiento;
- lee cgroups cuando estén habilitados;
- conserva offsets de logs;
- mantiene un spool temporal en caso de caída del ERP;
- envía lotes a `opzio_erp` por localhost;
- reporta su propio heartbeat.

El agente **no**:

- tiene dashboard;
- tiene usuarios;
- tiene base de datos de negocio;
- conoce reglas comerciales;
- envía notificaciones;
- modifica NGINX/PHP-FPM;
- escribe directamente en las tablas del ERP.

---

# 2. Por qué separar el agente del ERP

Esta separación resuelve varios problemas:

1. Laravel no necesita permisos amplios sobre `/proc`, `/sys/fs/cgroup` o logs de NGINX.
2. Los workers del ERP no se bloquean por escaneos de filesystem o parsing de logs.
3. El agente puede muestrear cada 10-15 segundos sin depender del Scheduler de Laravel.
4. Un deploy del ERP no interrumpe necesariamente la captura.
5. El agente puede reiniciarse independientemente.
6. El binario Go puede desplegarse sin instalar Go en producción.
7. El ERP mantiene el control absoluto de configuración, persistencia y visualización.
8. La seguridad queda mejor delimitada: agente de lectura + API localhost + ERP persistente.

---

# 3. Contrato entre los tres frentes

El contrato será:

```text
Servidor
  proporciona fuentes legibles
        │
        ▼
Agent
  recolecta + normaliza
        │
        ▼
POST /api/internal/observability/v1/ingest
        │
        ▼
opzio_erp
  valida + persiste + agrega + muestra
```

El agente obtendrá su configuración desde:

```text
GET /api/internal/observability/v1/config
```

Y reportará salud mediante:

```text
POST /api/internal/observability/v1/heartbeat
```

Los tres endpoints solo deben ser accesibles por loopback:

```text
127.0.0.1
```

Además se utilizará un token local independiente del login normal del ERP.

---

# PARTE A - COMANDOS Y CONFIGURACIÓN DIRECTA DEL SERVIDOR

# 4. Regla de trabajo del servidor

Esta parte se ejecuta mediante VS Code Remote SSH.

No debe quedar mezclada con migraciones Laravel ni con el repositorio del agente.

Mantener un registro de cada modificación en:

```text
opzio_erp/docs/observability/SERVER_CHANGELOG.md
```

Cada cambio debe guardar:

```text
fecha
servidor
archivo modificado
backup
comando de validación
resultado
rollback
```

---

# 5. S-001 - Auditoría inicial

Ejecutar antes de modificar cualquier configuración:

```bash
hostnamectl
cat /etc/os-release
uname -a

nginx -v
sudo nginx -T > /tmp/nginx-full-before-observability.txt

php -v
find /etc/php -path '*/fpm/pool.d/*.conf' -type f -print

systemctl list-units --type=service | grep -E 'nginx|php|supervisor|redis'
sudo supervisorctl status || true

find /var/www/html -mindepth 1 -maxdepth 1 -type d -printf '%f\n'

stat -fc %T /sys/fs/cgroup
mount | grep cgroup
cat /proc/cgroups

free -h
df -h
```

Guardar una versión sanitizada de los resultados en:

```text
docs/observability/server-baseline.md
```

**Criterio de aceptación:** se conoce el estado actual sin haber modificado producción.

---

# 6. S-002 - Usuario del agente

El agente no debe ejecutarse como `root`.

Crear usuario de sistema:

```bash
sudo useradd \
  --system \
  --no-create-home \
  --shell /usr/sbin/nologin \
  opzio-observer
```

Validar:

```bash
id opzio-observer
```

No otorgar `sudo` general al usuario.

---

# 7. S-003 - Directorios del agente

Crear estructura independiente de `/var/www/html`:

```bash
sudo install -d -o root -g root /opt/opzio-observer
sudo install -d -o root -g root /opt/opzio-observer/releases

sudo install -d -o root -g opzio-observer /etc/opzio-observer
sudo chmod 0750 /etc/opzio-observer

sudo install -d -o opzio-observer -g opzio-observer /var/lib/opzio-observer
sudo install -d -o opzio-observer -g opzio-observer /var/lib/opzio-observer/spool
sudo install -d -o opzio-observer -g opzio-observer /var/log/opzio-observer
```

Estructura final:

```text
/opt/opzio-observer/
├── current -> releases/<sha>
└── releases/

/etc/opzio-observer/
├── config.yaml
└── token

/var/lib/opzio-observer/
└── spool/

/var/log/opzio-observer/
```

**Importante:** no instalar Go en producción. GitHub Actions construirá el binario Linux.

---

# 8. S-004 - Dependencias mínimas del servidor

Solo instalar utilidades necesarias para administración:

```bash
sudo apt update
sudo apt install -y \
  curl \
  jq \
  acl \
  ca-certificates
```

No instalar Prometheus, Grafana ni una segunda base de datos para este MVP.

---

# 9. S-005 - Logs NGINX JSON

Crear directorio:

```bash
sudo install -d /var/log/nginx/opzio
```

Crear:

```text
/etc/nginx/conf.d/opzio_observability.conf
```

Contenido base:

```nginx
log_format opzio_observability escape=json
    '{'
      '"ts":"$msec",'
      '"host":"$host",'
      '"server_name":"$server_name",'
      '"method":"$request_method",'
      '"uri":"$uri",'
      '"status":$status,'
      '"request_length":$request_length,'
      '"bytes_sent":$bytes_sent,'
      '"request_time":$request_time,'
      '"upstream_response_time":"$upstream_response_time",'
      '"upstream_connect_time":"$upstream_connect_time"'
    '}';
```

No utilizar `$request_uri` si no se necesita query string.

En cada vhost monitoreado agregar un archivo distinto:

```nginx
access_log /var/log/nginx/opzio/iac_main.access.json opzio_observability;
```

Otro proyecto:

```nginx
access_log /var/log/nginx/opzio/clean_express.access.json opzio_observability;
```

Validar siempre:

```bash
sudo nginx -t
```

Solo después:

```bash
sudo systemctl reload nginx
```

**Rollback:** restaurar el vhost previo y volver a ejecutar `nginx -t`.

---

# 10. S-006 - Permisos de lectura NGINX

Primero inspeccionar:

```bash
ls -lah /var/log/nginx
getfacl /var/log/nginx
```

Preferencia: utilizar un grupo que ya tenga lectura de logs, por ejemplo `adm`, si la configuración actual de Debian lo permite.

Ejemplo:

```bash
sudo usermod -aG adm opzio-observer
```

Cerrar/reiniciar el servicio del agente después de cambios de grupo.

Si el esquema de permisos actual no usa `adm`, configurar ACL sobre la ruta específica en vez de ejecutar `chmod 777`.

Nunca utilizar:

```bash
chmod -R 777 /var/log/nginx
```

---

# 11. S-007 - PHP-FPM status por proyecto

Antes de cambiar pools, identificar la topología real.

Objetivo mínimo: cada proyecto monitoreado debe poder asociarse a un pool conocido.

Ejemplo conceptual dentro del pool:

```ini
pm.status_path = /__fpm_status/iac_main
ping.path = /__fpm_ping/iac_main
```

No exponer el status públicamente.

Crear un servidor NGINX exclusivamente localhost, por ejemplo:

```nginx
server {
    listen 127.0.0.1:9091;
    server_name localhost;

    access_log off;

    location = /__fpm_status/iac_main {
        include fastcgi_params;
        fastcgi_param SCRIPT_NAME /__fpm_status/iac_main;
        fastcgi_param SCRIPT_FILENAME /__fpm_status/iac_main;
        fastcgi_pass unix:/run/php/iac-main.sock;
    }
}
```

La ruta y socket deben adaptarse al pool real.

Validaciones:

```bash
sudo php-fpm8.2 -t 2>/dev/null || true
sudo nginx -t
curl -s http://127.0.0.1:9091/__fpm_status/iac_main
```

No cambiar todos los pools en un solo paso. Empezar con un proyecto piloto.

---

# 12. S-008 - Pools PHP-FPM dedicados

Esta tarea es importante, pero debe ser gradual.

Meta:

```text
iac_main          -> pool dedicado
clean_express     -> pool dedicado
opzio_erp         -> pool dedicado
...
```

Esto permite atribución mucho más confiable de procesos.

**Nota:** un pool separado mejora la atribución por PID, pero no crea automáticamente un cgroup separado. Para cgroup exacto se requiere separar también el conjunto de procesos/servicio, lo cual queda para una fase avanzada.

---

# 13. S-009 - Endpoint local del ERP

Crear un server block NGINX de loopback que apunte al ERP existente:

```nginx
server {
    listen 127.0.0.1:9080;
    server_name localhost;

    root /var/www/html/opzio_erp/current/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/opzio-erp.sock;
    }
}
```

**Adaptar** `root` y `fastcgi_pass` a la estructura real del CI/CD y FPM del ERP.

Validar:

```bash
sudo nginx -t
sudo systemctl reload nginx
curl -i http://127.0.0.1:9080/api/internal/observability/v1/health
```

No crear `listen 0.0.0.0:9080`.

---

# 14. S-010 - Token local

Generar una credencial independiente:

```bash
sudo sh -c 'openssl rand -hex 32 > /etc/opzio-observer/token'
sudo chown root:opzio-observer /etc/opzio-observer/token
sudo chmod 0640 /etc/opzio-observer/token
```

El mismo valor debe configurarse de manera segura para el ERP.

No guardar el token en Git.

---

# 15. S-011 - Configuración del agente

Crear:

```text
/etc/opzio-observer/config.yaml
```

Ejemplo:

```yaml
erp:
  base_url: http://127.0.0.1:9080
  token_file: /etc/opzio-observer/token

collector:
  fast_interval: 15s
  config_refresh_interval: 60s
  storage_interval: 10m

spool:
  path: /var/lib/opzio-observer/spool
  max_bytes: 104857600

logging:
  level: info
```

El listado de proyectos **no** vive aquí. El agente lo obtiene del ERP.

---

# 16. S-012 - systemd para el agente

Crear:

```text
/etc/systemd/system/opzio-observer.service
```

Base:

```ini
[Unit]
Description=OPZIO Observability Agent
After=network-online.target nginx.service
Wants=network-online.target

[Service]
Type=simple
User=opzio-observer
Group=opzio-observer
ExecStart=/opt/opzio-observer/current/opzio-observer --config /etc/opzio-observer/config.yaml
Restart=always
RestartSec=5
NoNewPrivileges=true
PrivateTmp=true
ProtectHome=true
ProtectSystem=full
ReadWritePaths=/var/lib/opzio-observer /var/log/opzio-observer

[Install]
WantedBy=multi-user.target
```

Después de instalar el primer binario:

```bash
sudo systemctl daemon-reload
sudo systemctl enable opzio-observer
sudo systemctl start opzio-observer
sudo systemctl status opzio-observer --no-pager
```

Logs:

```bash
journalctl -u opzio-observer -f
```

No agregar capabilities adicionales hasta demostrar que son necesarias.

---

# 17. S-013 - Lectura de `/proc` y filesystem

El agente empezará únicamente con métricas disponibles sin elevar privilegios.

Validar como usuario del agente:

```bash
sudo -u opzio-observer cat /proc/meminfo | head
sudo -u opzio-observer cat /proc/loadavg
sudo -u opzio-observer ls /var/www/html
```

Para PSS de procesos, no agregar `CAP_SYS_PTRACE` de forma preventiva.

Si `/proc/<pid>/smaps_rollup` no es legible, el MVP utiliza RSS y marca PSS como no disponible.

---

# 18. S-014 - Cgroup v2 avanzado

Solo iniciar después de estabilizar FPM pools.

Validar:

```bash
stat -fc %T /sys/fs/cgroup
```

Resultado esperado:

```text
cgroup2fs
```

Para obtener atribución verdaderamente exacta por cgroup, un simple pool FPM no basta si todos los workers pertenecen al mismo master/service.

La fase avanzada deberá evaluar **instancias PHP-FPM separadas por proyecto** o servicios separados para los procesos que se quieran contabilizar exactamente.

No ejecutar esta migración durante el MVP.

---

# 19. S-015 - Logrotate

Asegurar que los logs JSON roten y que el agente pueda continuar mediante inode/offset.

Verificar configuración existente:

```bash
cat /etc/logrotate.d/nginx
```

El agente debe manejar:

```text
rename
create
truncate
inode change
offset reset
```

No depender de que el archivo tenga siempre el mismo inode.

---

# PARTE B - CAMBIOS EN EL PROYECTO EXISTENTE `opzio_erp`

# 20. Regla de trabajo del ERP

El CI/CD actual de `opzio_erp` se conserva.

No crear un segundo pipeline para desplegar el mismo ERP.

Solo añadir al repositorio las piezas necesarias para observabilidad y verificar que el pipeline existente:

- ejecute migraciones;
- reinicie/reload workers si aplica;
- limpie/regenere caches de Laravel;
- ejecute tests antes de producción.

---

# 21. ERP-001 - Módulo de observabilidad

Crear:

```text
app/Domain/Observability/
├── Actions/
├── DTO/
├── Enums/
├── Http/
├── Models/
├── Queries/
├── Repositories/
├── Services/
└── Support/
```

El ERP ya no necesita collectors de Linux dentro de Laravel. Esa lógica vive en el agente.

---

# 22. ERP-002 - Modelo de datos

Tablas recomendadas:

```text
observability_hosts
observability_projects
observability_project_domains
observability_host_samples
observability_project_samples
observability_http_buckets
observability_storage_samples
observability_events
observability_agents
observability_rollups_hourly
observability_rollups_daily
```

La base de datos del ERP es la fuente de verdad.

No guardar una fila por request HTTP.

---

# 23. ERP-003 - Project Registry

`observability_projects` debe almacenar al menos:

```text
host_id
key
name
path
environment
enabled
php_version
fpm_pool
fpm_status_url
nginx_access_log
nginx_error_log
attribution_mode
metadata
```

Ejemplo:

```text
key: iac_main
path: /var/www/html/iac_app_main
fpm_pool: iac_main
fpm_status_url: http://127.0.0.1:9091/__fpm_status/iac_main
nginx_access_log: /var/log/nginx/opzio/iac_main.access.json
attribution_mode: pool
```

El ERP debe ser quien determine qué proyecto está habilitado.

---

# 24. ERP-004 - API interna: config

Crear:

```text
GET /api/internal/observability/v1/config
```

Respuesta conceptual:

```json
{
  "version": 17,
  "host": "opzio-1",
  "projects": [
    {
      "key": "iac_main",
      "path": "/var/www/html/iac_app_main",
      "nginx_access_log": "/var/log/nginx/opzio/iac_main.access.json",
      "fpm_pool": "iac_main",
      "fpm_status_url": "http://127.0.0.1:9091/__fpm_status/iac_main",
      "attribution_mode": "pool"
    }
  ]
}
```

El agente debe cachear la última configuración válida.

---

# 25. ERP-005 - API interna: ingest

Crear:

```text
POST /api/internal/observability/v1/ingest
```

El body debe contener lotes, no una request HTTP por cada métrica.

Ejemplo conceptual:

```json
{
  "agent_id": "opzio-1",
  "batch_id": "uuid",
  "captured_at": "2026-08-14T14:00:15Z",
  "host": {},
  "projects": [],
  "http_buckets": [],
  "storage": [],
  "events": []
}
```

Requisitos:

- validar schema;
- validar `agent_id`;
- validar token;
- idempotencia por `batch_id`;
- transacción corta;
- limitar tamaño de payload;
- devolver `202` o `204` cuando sea aceptado;
- no ejecutar rollups pesados dentro del request.

---

# 26. ERP-006 - API interna: heartbeat

Crear:

```text
POST /api/internal/observability/v1/heartbeat
```

Guardar:

```text
agent version
commit SHA
last_seen_at
config_version
spool_size
collection_errors
uptime
```

Esto permite distinguir:

```text
proyecto sin tráfico
```

de:

```text
agente caído
```

---

# 27. ERP-007 - Middleware de seguridad

Crear middleware dedicado.

Validaciones mínimas:

1. request llega por loopback;
2. header de token correcto;
3. payload dentro del tamaño permitido;
4. agente habilitado;
5. rate limit local razonable.

Ejemplo de header:

```text
X-Opzio-Observer-Token
```

No reutilizar tokens de usuarios del ERP.

---

# 28. ERP-008 - Persistencia

La ingestión debe convertir los DTO recibidos en las tablas correctas.

Principios:

- bulk insert;
- índices `(project_id, sampled_at)`;
- idempotencia;
- timestamps UTC;
- no ejecutar una query por métrica;
- almacenar `collector_version` cuando sea útil.

---

# 29. ERP-009 - Rollups

Laravel ejecutará las agregaciones, no el agente.

Ejemplo:

```text
raw 15 s       -> 7 días
HTTP 1 min     -> 30 días
hourly         -> 12 meses
daily          -> 24 meses
```

Jobs:

```text
BuildObservabilityHourlyRollups
BuildObservabilityDailyRollups
PruneObservabilityRawData
```

Estas tareas sí pertenecen al Scheduler/Queue del ERP.

---

# 30. ERP-010 - Dashboard global

Vista:

```text
SERVER OPZIO-1
CPU | RAM | Swap | Disk | Load

PROJECT
Name | Req/min | CPU | RAM | Storage | p95 | 5xx | FPM Queue | Health
```

Incluir siempre:

```text
last sample
attribution mode
agent health
```

---

# 31. ERP-011 - Dashboard de proyecto

Secciones:

```text
Overview
Traffic
PHP-FPM
Resources
Storage
Errors / Events
Configuration
```

Rangos:

```text
15 min
1 h
6 h
24 h
7 d
30 d
custom
```

Usar datos raw solo para rangos cortos.

---

# 32. ERP-012 - Alert evaluation

Las reglas pertenecen al ERP:

```text
FPM queue sostenida
max_children_reached aumenta
5xx > umbral
502/503/504 spike
p95 alto
CPU alta
RAM alta
disk > umbral
OOM
agent stale
collector stale
spool creciente
```

El agente solo reporta hechos.

---

# 33. ERP-013 - Eventos de deployment

El CI/CD actual del ERP puede opcionalmente ejecutar al terminar:

```bash
php artisan observability:deployment-record \
  --component=opzio_erp \
  --sha="$GITHUB_SHA" \
  --status=completed
```

Esto permite correlacionar cambios de rendimiento con deployments.

No es obligatorio para el MVP.

---

# 34. ERP-014 - Integración con CI/CD existente

No reemplazar el pipeline actual.

Agregar únicamente, si no existe:

```text
composer install
php artisan test
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan schedule:interrupt   # si la versión lo soporta y existen tareas sub-minuto
health check
```

La versión exacta de PHP y Laravel debe derivarse del proyecto actual, no de este documento.

---

# 35. ERP-015 - Documentación para el agente local

Dentro de `opzio_erp` crear:

```text
docs/observability/
├── PLAN.md
├── PROGRESS.md
├── DATA_CONTRACT.md
├── SERVER_CHANGELOG.md
└── adr/
```

El `PLAN.md` será una copia de este documento.

---

# PARTE C - PROYECTO NUEVO `opzio_observer_agent`

# 36. Lenguaje

El nuevo proyecto debe desarrollarse en **Go**.

Motivos:

- binario único;
- bajo consumo;
- buena concurrencia;
- proceso persistente adecuado para systemd;
- no depende de PHP-FPM;
- puede leer archivos y `/proc` eficientemente;
- se compila en GitHub Actions;
- no requiere Go instalado en el servidor.

---

# 37. A-001 - Crear repositorio

Nombre recomendado:

```text
opzio_observer_agent
```

Estructura:

```text
opzio_observer_agent/
├── cmd/
│   └── observer/
│       └── main.go
│
├── internal/
│   ├── config/
│   ├── api/
│   ├── collectors/
│   │   ├── host/
│   │   ├── process/
│   │   ├── nginx/
│   │   ├── fpm/
│   │   ├── filesystem/
│   │   └── cgroup/
│   ├── model/
│   ├── project/
│   ├── spool/
│   ├── state/
│   └── worker/
│
├── testdata/
│   ├── nginx/
│   ├── proc/
│   ├── fpm/
│   └── cgroup/
│
├── deploy/
│   └── install-release.sh
│
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── deploy-production.yml
│
├── AGENTS.md
├── go.mod
├── go.sum
└── README.md
```

---

# 38. A-002 - Ciclo principal

El proceso será conceptualmente:

```text
START
  │
  ├─ load config
  ├─ load offsets/state
  ├─ fetch ERP project registry
  │
  └─ LOOP
       ├─ fast collectors
       ├─ nginx incremental parser
       ├─ FPM status
       ├─ storage when due
       ├─ build batch
       ├─ POST batch to ERP
       ├─ if failure -> spool
       ├─ retry previous spool
       └─ heartbeat
```

---

# 39. A-003 - Collector interface

Contrato conceptual:

```go
type Collector interface {
    Name() string
    Collect(ctx context.Context, cfg ProjectConfig) ([]Metric, error)
}
```

Cada collector debe fallar de manera aislada.

Un fallo de storage no debe impedir capturar tráfico.

---

# 40. A-004 - Config client

El agente debe consultar periódicamente:

```text
GET /api/internal/observability/v1/config
```

Características:

- cache local de última configuración válida;
- `version` de configuración;
- timeout corto;
- retry con backoff;
- continuar recolectando con la última configuración si el ERP está temporalmente caído.

---

# 41. A-005 - Host collector

Leer:

```text
/proc/stat
/proc/meminfo
/proc/loadavg
/proc/net/dev
filesystem stat
```

Calcular deltas, no solo valores acumulados.

---

# 42. A-006 - Process collector

Resolver PIDs pertenecientes a cada proyecto mediante:

```text
FPM pool
command line
known workers
configured services
```

Métricas iniciales:

```text
CPU delta
RSS
process count
```

PSS será opcional.

Cada muestra debe incluir:

```text
attribution_mode=approximate|pool|cgroup
```

---

# 43. A-007 - NGINX collector

Leer incrementalmente cada archivo configurado.

Mantener estado:

```text
path
inode
offset
last_timestamp
```

Generar buckets de un minuto:

```text
requests_total
2xx
3xx
4xx
5xx
499
500
502
503
504
request_bytes
response_bytes
avg
p50
p95
p99
```

No enviar una entrada por request al ERP.

---

# 44. A-008 - FPM collector

Consultar URL local configurada por proyecto.

Ejemplo:

```text
http://127.0.0.1:9091/__fpm_status/iac_main?json
```

Si la versión de FPM disponible no entrega JSON, implementar parser del formato soportado.

Capturar:

```text
accepted conn
listen queue
max listen queue
idle processes
active processes
total processes
max active processes
max children reached
slow requests
```

---

# 45. A-009 - Storage collector

Frecuencia inicial:

```text
10 minutos
```

Métricas:

```text
total bytes
files
directories
storage/
vendor/
public/
node_modules/
logs/
other
scan duration
```

Protecciones:

```text
no seguir symlinks externos
timeout
cancelación por context
no dos scans simultáneos
```

---

# 46. A-010 - Cgroup collector

No bloquear el MVP por esta tarea.

Cuando exista aislamiento real:

```text
memory.current
cpu.stat
io.stat
memory.events
cpu.pressure
memory.pressure
io.pressure
```

Si no hay un cgroup confiable por proyecto, no inventar la atribución.

---

# 47. A-011 - Spool

Cuando el ERP no responda, el agente necesita un buffer temporal.

No utilizar una segunda base de datos canónica.

Formato sugerido:

```text
/var/lib/opzio-observer/spool/<timestamp>-<batch-id>.json
```

Características:

- escritura atómica;
- tamaño máximo;
- oldest-first replay;
- checksum opcional;
- eliminar solo después de ACK del ERP;
- evento cuando se alcanza el límite.

Valor inicial:

```text
100 MB
```

---

# 48. A-012 - Self health

El agente debe medir:

```text
uptime
version
commit SHA
config version
last successful ingest
spool bytes
spool batches
collector errors
collection durations
```

Enviar heartbeat cada 30-60 segundos.

---

# 49. A-013 - Logs del agente

Preferir logs estructurados a stdout/stderr para que systemd/journald los capture.

No escribir métricas completas ni tokens en logs.

Comandos operativos:

```bash
journalctl -u opzio-observer -n 200 --no-pager
journalctl -u opzio-observer -f
```

---

# 50. A-014 - Tests

Cada parser debe tener fixtures en `testdata`.

Casos mínimos:

```text
normal
malformed input
empty input
rotated log
truncated log
missing file
permission denied
FPM timeout
ERP timeout
spool replay
context cancellation
```

---

# 51. A-015 - CI del agente

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  pull_request:
  push:
    branches: [main]

permissions:
  contents: read

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-go@v5
        with:
          go-version-file: go.mod

      - name: Test
        run: go test ./...

      - name: Vet
        run: go vet ./...

      - name: Build Linux AMD64
        run: |
          CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
            go build -trimpath -ldflags="-s -w" \
            -o dist/opzio-observer ./cmd/observer

      - name: Checksum
        run: sha256sum dist/opzio-observer > dist/opzio-observer.sha256

      - uses: actions/upload-artifact@v4
        with:
          name: opzio-observer-linux-amd64
          path: dist/
```

Fijar versiones de Actions a SHA cuando se endurezca el pipeline de producción.

---

# 52. A-016 - CD del agente

El agente tendrá un pipeline independiente del ERP.

Reutilizar la misma estrategia de autenticación a GCP que ya utiliza el CI/CD de `opzio_erp`, siempre que sea apropiada.

El deploy hará:

```text
1. CI verde
2. build binario
3. upload a VM
4. crear /opt/opzio-observer/releases/<sha>
5. instalar binario
6. checksum
7. cambiar symlink current
8. systemctl restart opzio-observer
9. health check
10. conservar releases anteriores
```

Workflow conceptual:

```yaml
name: Deploy Production

on:
  push:
    branches: [main]

concurrency:
  group: opzio-observer-production
  cancel-in-progress: false

jobs:
  deploy:
    environment: production
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-go@v5
        with:
          go-version-file: go.mod

      - name: Test
        run: go test ./...

      - name: Build
        run: |
          mkdir -p dist
          CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
            go build -trimpath -ldflags="-s -w -X main.commit=${GITHUB_SHA}" \
            -o dist/opzio-observer ./cmd/observer

      # A partir de aquí usar el mismo mecanismo de autenticación
      # y conexión GCP que ya esté probado en opzio_erp.

      - name: Deploy
        run: |
          # upload dist/opzio-observer
          # ejecutar instalador remoto
          # systemctl restart opzio-observer
          # validar health
          echo "Integrar con el mecanismo GCP existente"
```

No duplicar secretos si ambos repositorios pueden compartir un GitHub Environment/Organization Secret con permisos adecuados.

---

# 53. A-017 - Script remoto de instalación

Instalar una vez en el servidor:

```text
/usr/local/sbin/opzio-observer-install-release
```

Responsabilidad:

```text
recibir SHA
recibir ruta temporal del binario
crear release
verificar checksum
chmod executable
cambiar symlink current
restart service
verificar status
limpiar releases antiguos
```

Ejemplo conceptual:

```bash
#!/usr/bin/env bash
set -euo pipefail

SHA="$1"
SOURCE="$2"
ROOT=/opt/opzio-observer
RELEASE="$ROOT/releases/$SHA"

install -d "$RELEASE"
install -m 0755 "$SOURCE" "$RELEASE/opzio-observer"

ln -sfn "$RELEASE" "$ROOT/current"

systemctl restart opzio-observer
systemctl is-active --quiet opzio-observer

ls -1dt "$ROOT"/releases/* | tail -n +6 | xargs -r rm -rf
```

El script debe ser propiedad de root y no editable por el usuario de deploy.

---

# 54. A-018 - Rollback del agente

Rollback no toca la base de datos.

```bash
sudo ln -sfn /opt/opzio-observer/releases/<sha-anterior> /opt/opzio-observer/current
sudo systemctl restart opzio-observer
sudo systemctl status opzio-observer --no-pager
```

Mantener al menos cinco releases.

---

# 55. Límites entre proyectos

## Servidor puede

```text
crear usuarios/directorios
configurar NGINX
configurar PHP-FPM
configurar permisos
instalar systemd unit
instalar release del agente
```

## Servidor no debe

```text
crear tablas del ERP
contener reglas de alertas
contener lógica de dashboard
almacenar históricos canónicos
```

## `opzio_erp` puede

```text
configurar proyectos
persistir métricas
crear históricos
mostrar dashboard
evaluar alertas
administrar retención
```

## `opzio_erp` no debe

```text
leer directamente todos los logs como www-data
requerir root
escanear /proc desde requests web
convertirse en daemon de sistema
```

## `opzio_observer_agent` puede

```text
recolectar
normalizar
agrupar
buffer temporal
entregar al ERP
```

## `opzio_observer_agent` no debe

```text
escribir DB del ERP
administrar usuarios
modificar NGINX
modificar PHP-FPM
hacer alertas de negocio
ser accesible desde Internet
```

---

# 56. Orden de ejecución global

Aunque el plan esté separado por repositorio/servidor, recomiendo ejecutarlo en este orden:

```text
FASE 0
S-001       auditoría

FASE 1
ERP-001     módulo
ERP-002     modelo de datos
ERP-003     registry
ERP-004     config API
ERP-005     ingest API
ERP-006     heartbeat
ERP-007     seguridad

FASE 2
A-001       repositorio Go
A-002       loop
A-003       interfaces
A-004       config client
A-011       spool
A-012       health
A-014       tests base

FASE 3
S-002       usuario
S-003       directorios
S-004       dependencias
S-009       endpoint local ERP
S-010       token
S-011       agent config

FASE 4
S-005       NGINX JSON
S-006       permisos logs
A-005       host collector
A-007       NGINX collector

FASE 5
S-007       FPM status piloto
S-008       pool dedicado piloto
A-006       process collector
A-008       FPM collector
A-009       storage collector

FASE 6
S-012       systemd
A-015       CI
A-016       CD
A-017       installer
A-018       rollback

FASE 7
ERP-008     persistencia optimizada
ERP-009     rollups
ERP-010     dashboard global
ERP-011     dashboard proyecto
ERP-012     alertas

FASE 8
S-014       cgroups avanzados
A-010       cgroup collector
```

---

# 57. MVP mínimo

El primer MVP no debe intentar resolver todo.

Debe entregar:

```text
Host
- CPU
- RAM
- load
- disk

Por proyecto
- requests/min
- 2xx/4xx/5xx
- 502/503/504
- bytes
- p50/p95/p99
- FPM active/idle/queue
- process count
- CPU aproximada/pool
- RAM RSS
- storage

Sistema
- agent heartbeat
- last sample
- spool size
```

No requiere todavía:

```text
cgroup exacto
eBPF
PSS obligatorio
Grafana
Prometheus
tracing distribuido
```

---

# 58. Sprint 1 - `opzio_erp`

Objetivo: dejar listo el contrato de datos.

- [ ] ERP-001 módulo.
- [ ] ERP-002 migraciones.
- [ ] ERP-003 registry.
- [ ] ERP-004 config endpoint.
- [ ] ERP-005 ingest endpoint.
- [ ] ERP-006 heartbeat.
- [ ] ERP-007 middleware.
- [ ] Tests feature/API.

**Salida:** el ERP puede recibir una muestra simulada y persistirla.

---

# 59. Sprint 2 - `opzio_observer_agent`

Objetivo: obtener datos reales sin tocar todavía FPM.

- [ ] A-001 repositorio.
- [ ] A-002 loop.
- [ ] A-004 config client.
- [ ] A-005 host collector.
- [ ] A-007 NGINX parser con fixtures.
- [ ] A-011 spool.
- [ ] A-012 heartbeat.
- [ ] A-014 tests.

**Salida:** ejecución local del agente contra fixtures y ERP local.

---

# 60. Sprint 3 - servidor piloto

Objetivo: conectar un solo proyecto real.

- [ ] S-002 a S-012.
- [ ] un vhost NGINX con JSON.
- [ ] permisos read-only.
- [ ] un pool/status FPM.
- [ ] systemd.
- [ ] ingest localhost.
- [ ] 24 horas de métricas.

**Salida:** un proyecto real visible dentro de `opzio_erp`.

---

# 61. Sprint 4 - dashboard y expansión

- [ ] ERP-008.
- [ ] ERP-009.
- [ ] ERP-010.
- [ ] ERP-011.
- [ ] A-006.
- [ ] A-008.
- [ ] A-009.
- [ ] agregar más proyectos gradualmente.

---

# 62. `AGENTS.md` para `opzio_erp`

```text
# Observability - ERP Agent Rules

1. Read docs/observability/PLAN.md and PROGRESS.md first.
2. Work only on ERP-* tasks in this repository.
3. Never modify /etc/nginx, /etc/php, systemd or server permissions from Laravel code.
4. The ERP database is canonical storage.
5. Do not add Linux collectors to Laravel.
6. Internal observability endpoints must remain loopback-only at infrastructure level.
7. Add tests for API contracts and persistence.
8. Keep ingestion idempotent by batch_id.
9. Use UTC timestamps.
10. Update PROGRESS.md after completing a task.
```

---

# 63. `AGENTS.md` para `opzio_observer_agent`

```text
# OPZIO Observer Agent Rules

1. Read PLAN.md and PROGRESS.md before coding.
2. Work only on A-* tasks in this repository.
3. The agent never writes directly to the ERP database.
4. The agent never modifies NGINX, PHP-FPM or systemd configuration.
5. Every parser requires testdata fixtures.
6. Every collector must support context cancellation and timeout.
7. Collector failures must be isolated.
8. Never log tokens or full sensitive requests.
9. Persist only transient spool/state locally.
10. Keep the Linux production binary CGO_ENABLED=0 unless a future ADR changes this.
11. Update PROGRESS.md after each completed task.
```

---

# 64. `PROGRESS.md` recomendado

En cada repositorio:

```text
# Progress

## Current
A-007 - NGINX incremental parser

## Done
- A-001
- A-002
- A-004

## Blocked
- A-008 waiting for S-007

## Last validation
Date:
Commit:
Tests:
Environment:

## Notes
...
```

En el ERP usar IDs `ERP-*`; en el agente, `A-*`.

Los cambios manuales del servidor se registran con `S-*` en `SERVER_CHANGELOG.md`.

---

# 65. Convención de branches

ERP:

```text
feat/erp-005-observability-ingest
feat/erp-010-observability-dashboard
```

Agente:

```text
feat/a-007-nginx-parser
feat/a-008-fpm-collector
fix/a-011-spool-replay
```

Servidor no tiene branch propio; sus cambios deben quedar documentados en el ERP o en un repositorio de infraestructura futuro.

---

# 66. Definition of Done del sistema

- [ ] El agente corre como usuario sin privilegios root.
- [ ] El agente no escucha en interfaces públicas.
- [ ] El ERP recibe datos solo mediante contrato versionado.
- [ ] La DB del ERP es la fuente canónica.
- [ ] El agente tiene spool limitado.
- [ ] NGINX utiliza logs JSON por proyecto.
- [ ] Existe al menos un pool FPM atribuible por proyecto piloto.
- [ ] Las métricas indican `attribution_mode`.
- [ ] No se presenta CPU/RAM aproximada como exacta.
- [ ] Los parsers sobreviven logrotate.
- [ ] Existe heartbeat del agente.
- [ ] Existe indicador de datos stale.
- [ ] Existe política de rollups y retención.
- [ ] El CI/CD del ERP continúa funcionando sin ser reemplazado.
- [ ] El agente tiene CI/CD independiente.
- [ ] El agente puede hacer rollback de binario.
- [ ] Los secretos no están en Git.
- [ ] Los endpoints internos son localhost-only.
- [ ] Existe documentación separada por `S-*`, `ERP-*` y `A-*`.

---

# 67. Primera acción recomendada

No comenzar todavía instalando el agente.

El primer bloque de trabajo debe ser:

```text
1. S-001: auditar servidor.
2. ERP-001 a ERP-007: definir el contrato de ingestión.
3. Crear `opzio_observer_agent` localmente.
4. Probar agente -> ERP completamente en local con fixtures.
5. Solo entonces ejecutar S-002 en adelante en producción.
```

Así el servidor de producción no se convierte en el entorno de desarrollo del agente.

---

# 68. Resumen final de responsabilidades

```text
SERVIDOR / VS CODE SSH
──────────────────────
Fuentes
Permisos
NGINX JSON
FPM status
Usuario agente
systemd
Config local
Token
Instalación del binario

OPZIO_ERP EXISTENTE
───────────────────
Project registry
API config
API ingest
API heartbeat
Base de datos
Rollups
Retención
Dashboards
Alertas
Usuarios/permisos
Auditoría
CI/CD actual

OPZIO_OBSERVER_AGENT NUEVO
──────────────────────────
Collectors
NGINX parser
FPM parser
/proc
Filesystem
cgroup futuro
Spool
Heartbeat
Batching
Retry
CI/CD propio
Binario Go
```

Esta separación debe mantenerse incluso si, en el futuro, se añaden más servidores. Cada servidor ejecutará su agente, pero todos podrán reportar al mismo `opzio_erp`.
