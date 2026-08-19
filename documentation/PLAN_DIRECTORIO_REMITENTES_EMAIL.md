# Directorio de remitentes de correo

## Política central

La política vive en `app/traits/mail_senders_trait.php` y se aplica desde `mail_trait`, `notifications_trait`, `mail_log_trait` y el worker de correos encolados.

| Dirección | Nombre visible | Uso principal |
|---|---|---|
| `info@opzio.co` | `OPZIO SAS - Información` | Información general, contacto web y fallback |
| `legal@opzio.co` | `OPZIO SAS - Legal` | Contratos y firma |
| `soporte@opzio.co` | `OPZIO SAS - Soporte` | Soporte, accesos, chats, backups y observabilidad |
| `contabilidad@opzio.co` | `OPZIO SAS - Contabilidad` | Pagos, cotizaciones, órdenes y facturación |
| `comunicaciones@opzio.co` | `OPZIO SAS - Comunicaciones` | News, publicaciones y reportes de marketing |

Todos los correos usan `info@opzio.co` como `Reply-To`, incluyendo flujos que se envían desde Legal, Contabilidad, Soporte o Comunicaciones.

## Resolución por propósito

El trait identifica el propósito mediante la vista del correo. Los envíos que no declaran un remitente reciben el remitente recomendado para esa vista; si no existe una regla, usan `info@opzio.co`. Un remitente explícito solo puede resolverse dentro del directorio aprobado.

Los correos directos y los que se crean en `mail_logs` guardan `_from` y `_reply_to` para que el worker conserve los mismos headers. Los logs antiguos usan sus columnas persistidas como fallback y pasan por la misma normalización.

## Flujos cubiertos

- Información y contacto web: Información.
- Soporte, chats, usuarios, contraseñas y backups: Soporte.
- Pagos, facturas, cotizaciones y órdenes: Contabilidad.
- Contratos: Legal.
- News, redes y marketing: Comunicaciones.
- Reportes mensuales de servidores: Soporte.
- Notificaciones manuales: remitente aprobado elegido por el usuario o Información por defecto.
