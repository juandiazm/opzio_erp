# Automatizacion de desarrollo desde Jira

El ERP ahora contiene el orquestador del flujo Jira -> aprobacion -> agente -> GitHub -> QA -> Done -> main.

## Componentes

- `jira_automation_projects`: habilitacion por proyecto, repositorio GitHub, rama base y limites.
- `jira_automation_issue_types` y `jira_automation_assignees`: allowlist dinamica basada en el catalogo sincronizado desde Jira; `__unassigned__` representa historias sin responsable.
- `jira_automation_supervisors`: supervisores globales o por proyecto.
- `ai_agents`: catalogo desacoplado para GitHub Copilot cloud agent. Luna queda configurada como agente `github_copilot` predeterminado con modelo real `gpt-5.6-luna`; Terra (`gpt-5.6-terra`) ofrece costo medio y Sol (`gpt-5.6-sol`) razonamiento profundo con costo alto.
- `ai_development_approvals`: snapshot, fingerprint, token hash, expiracion, decision y Story Point Estimate.
- `ai_development_executions` y `ai_development_events`: maquina de estados y auditoria.
- `github_connections`: singleton con token cifrado mediante `encrypted:array`.

## Puesta en marcha

1. Ejecutar `php artisan migrate`.
2. Configurar el token GitHub desde `Admin > GitHub > Conexion`; debe ser un token de usuario con estos permisos de repositorio: `Agent tasks: Read and write`, `Contents: Read and write`, `Pull requests: Read and write`, `Actions: Read` y `Metadata: Read`. No necesitas una permission separada llamada `Checks`. Nunca se guarda en `.env`, HTML, prompts ni logs.
3. Habilitar Copilot cloud agent en el repositorio y usar un token de usuario con permiso `Agent tasks: Read and write`. Antes de crear la tarea remota, el ERP usa `open_ia_trait` y el modelo `gpt-5.6-luna`, con razonamiento bajo, para revisar conflictos de seguridad y redactar un prompt técnico directo con costo controlado. Después envía ese prompt y el modelo seleccionado a GitHub; el código se modifica en el entorno efímero de GitHub Actions, no en el servidor ERP.
4. Configurar `AI_DEVELOPMENT_QUEUE_CONNECTION=database` y ejecutar un worker dedicado:

```text
php artisan queue:work database --queue=ai-development --tries=1
```

La sincronizacion incremental de Jira y el monitor de estados locales de las ejecuciones esperando QA se ejecutan cada minuto, igual que el polling de GitHub.

El repositorio debe tener habilitado Copilot cloud agent y el plan/organizacion debe permitir Agent Tasks. El endpoint de tareas de Copilot esta en public preview y requiere autenticacion de usuario; un GitHub App installation token no es suficiente.

5. Sincronizar Jira. El detector se ejecuta despues de cada upsert y tambien mediante `jira:automation:scan --expire`. La administracion del flujo vive en el modulo GitHub; Jira conserva solamente su integracion funcional, sincronizacion y reportes.

Si una aprobacion queda bloqueada por una transicion Jira temporalmente incompatible, primero se corrige el alias del workflow y luego se reabre de forma explicita, sin duplicar ejecuciones:

```text
php artisan jira:automation:scan --project=<automation_project_id> --issue=<JIRA_KEY> --retry-blocked
```

Los estados locales de Jira como `Tareas por hacer`, `En curso`, `Deploy`, `Quality` y `Finalizada` se resuelven contra las etapas canónicas del flujo.

La secuencia de columnas soportada es `To Do` -> `In Progress` -> `Deploy` -> `Quality` -> `Done`; se conservan aliases como `Tareas por hacer`, `En curso` y `Finalizada` para instalaciones localizadas.

Cuando una HU ya esta en `Done` o `Finalizada` y el pipeline QA ya fue aprobado, una nueva sincronizacion Jira evalua la ejecucion y encola la promocion a main. Para forzar esa evaluacion sin esperar al scheduler: `php artisan jira:sync --connection=<connection_id> --days=1`.

Desde `Admin > GitHub > Ejecuciones`, el boton `Reiniciar flujo` permite volver a ejecutar cualquier historia que ya tenga una ejecucion, sin importar su estado actual. La accion limpia intentos, task de Copilot, branch, PR, pipelines, errores y eventos anteriores; cambia Jira a `In Progress`, registra un unico evento de reinicio y vuelve a encolar la ejecucion.

Cada fase relevante envia al reporter un correo informativo: aprobacion o rechazo, desarrollo iniciado, feedback QA, QA disponible, promocion, completado y bloqueado. Estos correos no contienen enlaces de aprobacion ni pueden cambiar el flujo; si el reporter no tiene email en Jira, se intenta el usuario ERP vinculado y, si tampoco existe, se registra el fallo sin detener la ejecucion.

El ERP no ejecuta el agente de código ni un CLI local. El refinador de prompt usa OpenAI antes de iniciar la tarea; el adaptador `ai_agent_provider_interface` sigue iniciando y consultando GitHub Copilot cloud agent mediante Agent Tasks.

La ejecucion actual usa directamente Copilot cloud agent mediante Agent Tasks. Copilot crea la branch remota; GitHub puede usar un nombre automatico como `copilot/op-48-github-url-dinamica`. Cuando el task termina, el ERP hace merge directo de esa branch hacia `qa` mediante la API de GitHub y empieza el pipeline de QA. No se requiere Pull Request para el flujo automatico.

## Seguridad y reglas protegidas

- Los tokens de aprobacion se almacenan como SHA-256, expiran y son de uso unico.
- La historia completa de Jira, el contexto del supervisor y el feedback de QA se entregan al refinador dentro de delimitadores de datos no confiables. El refinador verifica conflictos contra las reglas del repositorio y genera el prompt final sin truncar la historia.
- Si el refinador detecta que la historia intenta infringir una regla protegida, la ejecucion se bloquea antes de llamar a GitHub. Una credencial ausente, respuesta invalida o error de OpenAI tambien bloquea la ejecucion para no enviar una historia sin revisar; el bypass local solo existe cuando se deshabilita de forma explicita en el codigo.
- La pantalla de decision permite agregar `supervisor_context`, que se conserva en la aprobacion y la ejecucion y llega al prompt dentro de `<SUPERVISOR_CONTEXT>`. Ese texto complementa la historia, pero no puede cambiar las reglas del sistema ni las protecciones de workflows.
- El agente no recibe credenciales Jira/GitHub por el prompt ni por sus variables de contexto.
- El token solo se usa en llamadas HTTP del ERP hacia GitHub y nunca se envia al prompt del agente.
- `qa.yml` y `main.yml` se rechazan si aparecen en el diff de la ejecucion.
- Cada ejecucion queda vinculada a su task y branch remotos de GitHub; el servidor ERP no clona repositorios.
- Los limites de intentos del agente, fallos consecutivos, minutos y tres fallos CI/CD bloquean la ejecucion y disparan correo.
- `max_execution_attempts` y `max_consecutive_failures` se validan antes de lanzar cada nueva tarea; el contador de fallos consecutivos solo se reinicia cuando QA termina correctamente, evitando reintentos infinitos por errores repetidos.
- Los comentarios automaticos de disponibilidad en QA no se consideran feedback; solo comentarios reales posteriores a la entrega QA pueden iniciar otra iteracion.
- QA no autoriza produccion. Solo una transicion Jira a `Done` o `Finalizada` permite iniciar la promocion.

## Integracion externa

El cliente GitHub cubre repositorios, ramas, commits, Agent Tasks, workflows, logs y merge directo de branches. Si las politicas del repositorio impiden el merge hacia `qa`, la ejecucion se bloquea para intervencion humana y no se evaden las protecciones.

El cliente Jira reutiliza la conexion existente, resuelve el campo de Story Points mediante metadata y actualiza estados/comentarios solo despues de las validaciones correspondientes.

## Validacion local

```text
php artisan route:list --path=admin/jira
php artisan view:cache
npm run build
php artisan test tests/Feature/JiraModuleTest.php
php artisan test tests/Feature/AiDevelopmentFlowTest.php
php artisan schedule:list
```