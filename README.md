# DoxTicket

**Helpdesk open source self-hosted para departamentos de TI.**

---

## Descripcion

DoxTicket es una plataforma de tickets IT open source para equipos que quieren mantener el control de sus datos, correo y operacion. Esta pensada para instalarse en infraestructura propia mediante Docker Compose o instalacion manual en Ubuntu.

El canal principal es el correo: DoxTicket recibe mensajes por IMAP/API, los convierte en tickets y permite asignarlos, responderlos por SMTP/API y atender el trabajo desde un inbox claro en Tickets.

---

## Objetivo

Construir un helpdesk IT open source, multiempresa, seguro y facil de autoalojar, con foco en correo entrante estable, privacidad, trazabilidad y una interfaz minimalista.

---

## Modelo del proyecto

- **Tipo:** open source self-hosted.
- **Licencia:** AGPLv3.
- **Publicacion:** GitHub Releases + imagenes Docker versionadas.
- **Base de datos oficial:** PostgreSQL.
- **Cache / colas:** Redis.
- **Framework:** Laravel 13.x sobre PHP 8.3+.
- **Frontend:** Blade + Livewire + Tailwind CSS 4.1+.
- **Panel admin:** Filament 5.x.
- **Instalacion principal:** Docker Compose.
- **Instalacion alternativa:** Ubuntu Server manual documentado.

Para Docker Compose se usa `.env.docker`, creado desde `.env.docker.example`, de modo que la instalacion self-hosted no herede por accidente un `.env` local de desarrollo. `.env.docker` debe permanecer privado.

Las imagenes Docker ejecutan el codigo y assets generados dentro del build: `app`, `worker` y `scheduler` no montan el repositorio completo, y `web` sirve `public/` desde su propia imagen. Despues de cambios de codigo o assets, reconstruir con `docker compose --env-file .env.docker up -d --build` y ejecutar `docker compose --env-file .env.docker exec app php artisan optimize`.

---

## Rutas principales

| Ruta | Descripcion |
|---|---|
| `/` | Entrada publica de la instalacion con estado de setup segun `system_settings.setup.completed`; si la tabla aun no existe, muestra instalador pendiente sin fallar |
| `/setup` | Instalador inicial, bloqueado tras finalizar |
| `/login` | Login centralizado |
| `/two-factor-challenge` | Reto publico de 2FA tras validar correo y contrasena |
| `/password/forgot` | Solicitud publica de enlace de restablecimiento con respuesta generica |
| `/password/reset/{token}` | Formulario publico para definir/restablecer contrasena con token |
| `/password/reset` | Actualizacion publica de contrasena via POST |
| `/logout` | Cierre de sesion |
| `/app/companies` | Selector de empresa activa |
| `/app/dashboard` | Ruta heredada que redirige a `/app/tickets` |
| `/app/activity` | Historial operativo de la empresa activa |
| `/app/kb` | Base de conocimiento interna de la empresa activa |
| `/app/kb/create` | Creacion de articulo interno por admin o supervisor |
| `/app/kb/{article}/edit` | Edicion de articulo interno por admin o supervisor |
| `/app/kb/{article}` | Lectura de articulo interno por slug |
| `/app/tickets` | Workspace principal de trabajo y gestion de tickets |
| `/app/tickets/create` | Creacion manual de ticket |
| `/app/tickets/{ticket}` | Detalle de ticket por id interno o clave visible `DT-123` |
| `/app/tickets/{ticket}/assign-self` | Asignarse un ticket desde la empresa activa |
| `/app/tickets/{ticket}/messages` | Agregar nota interna al ticket |
| `/app/tickets/{ticket}/replies` | Enviar respuesta publica por correo al solicitante, con adjuntos seguros opcionales |
| `/app/tickets/{ticket}/attachments` | Subir adjunto privado al ticket |
| `/app/tickets/{ticket}/merge` | Fusionar el ticket actual dentro de otro ticket de la misma empresa |
| `/app/tickets/{ticket}/status` | Cambiar estado del ticket |
| `/app/tickets/{ticket}/properties` | Actualizar estado, prioridad, tipo y agente |
| `/app/attachments/{uuid}/download` | Descargar adjunto privado con autenticacion y tenant activo |
| `/app/settings` | Configuracion del tenant |
| `/app/settings/two-factor/start` | Preparacion protegida de 2FA personal con contrasena actual |
| `/app/settings/two-factor/confirm` | Confirmacion protegida de 2FA personal con codigo TOTP |
| `/app/settings/two-factor` | Desactivacion protegida de 2FA personal via DELETE |
| `/app/settings/mail` | Guardado de cuenta IMAP/SMTP del tenant |
| `/app/settings/mail/test` | Prueba manual IMAP/SMTP de la cuenta activa |
| `/app/settings/mail/oauth/{provider}/redirect` | Inicio OAuth para Gmail o Microsoft 365 |
| `/app/settings/mail/oauth/{provider}/callback` | Callback OAuth con validacion de `state` y guardado cifrado de tokens |
| `/admin` | Panel superadmin de la instalacion |
| `/admin/audit` | Listado protegido de auditoria global con filtros y metadatos sensibles redactados |
| `/admin/audit/export` | Exportacion CSV protegida de auditoria global respetando filtros |
| `/admin/companies` | Listado protegido de empresas con resumen operativo |
| `/admin/companies/create` | Creacion protegida de empresa por superadmin |
| `/admin/companies/{company}/edit` | Edicion protegida de empresa por superadmin |
| `/admin/companies/{company}` | Actualizacion protegida de empresa via PUT |
| `/admin/companies/{company}` | Eliminacion suave protegida de empresa via DELETE |
| `/admin/companies/{company}/status` | Cambio protegido de estado de empresa |
| `/admin/users` | Listado protegido de usuarios globales y membresias |
| `/admin/users/invite` | Formulario y registro protegido de invitacion a empresa con correo SMTP global |
| `/admin/users/{user}/status` | Activacion/desactivacion protegida de usuario global |
| `/admin/users/{user}/password-reset` | Envio protegido de enlace para definir/restablecer contrasena |
| `/admin/users/{user}` | Eliminacion suave protegida de usuario global |
| `/admin/memberships/{membership}` | Actualizacion protegida de rol y estado de membership |
| `/admin/memberships/{membership}` | Eliminacion suave protegida de acceso a empresa |
| `/admin/settings` | Configuracion protegida de instalacion sin exponer secretos; permite guardar URL publica, repositorio de releases, politica basica de backups y backup automatico local |
| `/admin/health` | Resumen protegido de salud de la instalacion |
| `/admin/backups` | Ejecucion manual protegida de backup local |
| `/admin/rollback` | Preflight protegido de rollback manual condicionado a backup valido |
| `/admin/telemetry` | Activacion/desactivacion protegida de telemetria opcional |
| `/admin/updates/check` | Chequeo manual protegido de nueva version estable |

---

## Funciones v1

- Multiempresa por `company_id`.
- Usuarios globales con email unico y membresias por empresa.
- Un usuario puede pertenecer a varias empresas con roles distintos.
- Una cuenta de soporte por empresa.
- Configuracion base de cuenta IMAP/SMTP por empresa.
- Prueba manual IMAP/SMTP desde Settings con errores sanitizados.
- SMTP global del sistema para invitaciones, reset y alertas.
- Solicitud publica de restablecimiento de contrasena con respuesta generica para evitar enumeracion de usuarios y correo DoxTicket en espanol.
- 2FA opcional por usuario con TOTP, reto posterior al password, codigos de recuperacion cifrados y gestion desde `/app/settings`.
- IMAP/SMTP generico con transporte IMAP nativo, Gmail y Microsoft 365 planificados desde v1.
- Base OAuth para Gmail/Microsoft 365 con tokens cifrados, inicio tenant-safe, callback, renovacion automatica, lectura por API con adjuntos y envio de respuestas por API mediante adaptadores mockeables.
- Confirmacion automatica de recibido para tickets creados por correo, sin romper la ingesta si SMTP falla.
- Adjuntos entrantes por correo guardados en almacenamiento privado, con bloqueo de tipos peligrosos.
- Adjuntos seguros en respuestas salientes, enviados por SMTP/API y guardados en almacenamiento privado asociados al mensaje.
- Limite de adjuntos configurable por instalacion con `DOXTICKET_ATTACHMENT_MAX_BYTES`.
- Imagenes externas de correos bloqueadas por privacidad, con apertura manual desde el detalle del ticket.
- Marcador visible en asunto: `[DT-123]`.
- Tickets como workspace principal para saber que atender ahora.
- SLA base por prioridad con vencimiento automatico, filtro de vencidos en lista y evento interno por brecha.
- Base de conocimiento interna por empresa con articulos Markdown, busqueda simple, lectura para agentes y gestion de articulos por admin/supervisor.
- Navegacion autenticada compacta con estado activo accesible, centrada en Tickets y Actividad; Empresa, Configuracion y `/admin` quedan fuera del shell de usuario y se gestionan como contexto administrativo o flujos dedicados.
- Mensajes de exito del shell autenticado anunciables para lectores de pantalla y sin duplicados por pantalla.
- Errores inline en formularios principales asociados al campo que los origina.
- Confirmaciones en acciones sensibles de gestion interna.
- Panel de actividad para revisar que paso en tickets de la empresa activa.
- Tickets manuales como flujo secundario.
- Lista de tickets con busqueda principal por clave/asunto/correo y clave visible copiable; detalle con hilo, respuesta por correo con adjuntos seguros opcionales, adjuntos privados, panel lateral de propiedades, actividad, notas internas y cambio de estado.
- Estados simples: Nuevo, Abierto, Pendiente, Resuelto y Cerrado.
- Prioridades: Baja, Media, Alta y Urgente.
- Tipos de ticket: Pregunta, Incidente, Problema y Solicitud.
- Asignacion manual a agentes de la empresa activa; la lista mantiene accion rapida para asignarse.
- Fusion de tickets tenant-safe con redireccion de respuestas futuras al ticket principal.
- Adjuntos en almacenamiento privado.
- Modo claro como experiencia principal.
- Espanol por defecto, ingles disponible.
- Mensajes de validacion visibles en espanol por defecto con nombres de campos entendibles.
- Panel `/admin` protegido para superadmins con resumen de empresas, usuarios, tickets, cuentas de correo activas, version instalada, aviso cacheado de nueva version estable y health base.
- Listado `/admin/companies` para revisar tenants, estado, miembros, tickets y correo activo sin usar el selector normal de empresa; incluye creacion, edicion de datos base, cambio de estado `active`, `disabled` o `archived`, y eliminacion suave protegida.
- Listado `/admin/users` para revisar usuarios globales, superadmins y membresias por empresa, con envio de enlace de definicion/restablecimiento de contrasena, activacion/desactivacion global protegida, eliminacion suave de usuarios, edicion/eliminacion de accesos a empresa, roles visibles como Administrador, Supervisor y Agente, registro de invitaciones a empresa con correo SMTP global, enlace de definicion de contrasena para usuarios nuevos y bloqueo para no desactivar/eliminar la propia cuenta superadmin ni dejar una empresa sin admin activo.
- Listado `/admin/audit` para revisar eventos de auditoria globales con empresa, actor, accion, sujeto y metadatos redactados para no mostrar contrasenas, tokens ni secretos.
- Busqueda libre en `/admin/audit` por accion, empresa, actor o sujeto, con filtros por accion, empresa, actor y rango de fechas.
- Exportacion CSV protegida desde `/admin/audit/export`, respetando busqueda/filtros, metadatos sanitizados, limite v1 de 5000 filas y evento `admin.audit.exported`.
- Auditoria automatica de acciones superadmin criticas: empresas, usuarios, memberships, telemetria, backups, rollback y chequeos manuales de updates.
- Configuracion `/admin/settings` para revisar y guardar URL publica, repositorio de releases, politica basica de backups y backup automatico local como valores publicos no secretos, ademas de version, telemetria y SMTP global sin mostrar credenciales.
- Ruta `/admin/health` con checks de `APP_KEY`, `APP_DEBUG`, setup bloqueado, base de datos, cache, colas, scheduler, workers, storage, SMTP global, cuentas de correo y backups sin exponer secretos; la ventana de backup reciente se configura desde `/admin/settings`.
- Chequeo diario y manual desde `/admin` de GitHub Releases usando el repositorio guardado en `/admin/settings` o `DOXTICKET_GITHUB_REPOSITORY` como fallback, guardando solo estado local sin enviar datos de tenants.
- Historial base de backups en `backup_runs`, ultimo backup visible en `/admin`, historial reciente sin rutas privadas, ejecucion manual local desde `/admin/backups`, backup automatico local opcional desde scheduler, pruning diario de retencion local y boton rollback visible con preflight protegido en `/admin/rollback`, condicionado a backup valido.
- Telemetria opcional, apagada por defecto, activada explicitamente en `/setup` o `/admin`, con resumen visible de privacidad y sin envio de contenido sensible.

---

## Fuera de v1

- Billing y suscripciones comerciales.
- DoxTicket Cloud u hosting oficial prometido.
- Portal de usuario final.
- Chat en vivo.
- Subtickets, tickets padre-hijo o division de tickets.
- Plugins/extensiones publicas.
- Marketplace de integraciones.

---

## Branding

- **Logo:** `Brand/DoxTicketSVG.svg`
- **Logo publico / favicon SVG:** `public/brand/doxticket.svg`
- Toda instalacion debe mantener el texto **Powered by DoxTicket** en el footer.
- `doxticket.com` se proyecta como hub oficial del proyecto: documentacion, releases, seguridad y roadmap.

---

## Seguridad

- Nunca incluir secretos, claves de API ni contrasenas en el repositorio.
- Usar `.env` para toda configuracion sensible.
- En Docker, usar `.env.docker` para secretos y variables de la instalacion; solo `.env.docker.example` se versiona.
- El instalador y el panel admin deben validar entorno, permisos, `APP_KEY`, `APP_DEBUG`, Redis, PostgreSQL, storage y colas.
- Ver `SECURITY.md`, `AGENTS.md` y `Docs/04 - Seguridad/` para reglas completas.

---

## Instalacion rapida

### Docker Compose
```bash
cp .env.docker.example .env.docker
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php artisan key:generate
docker compose --env-file .env.docker exec app php artisan migrate --force
docker compose --env-file .env.docker exec app php artisan optimize
```

Abrir `http://127.0.0.1:8080/setup` o el `APP_URL` configurado.

### VPS Ubuntu
La guia completa esta en `Docs/07 - Infraestructura/Ubuntu Server.md`. Resumen:

1. Crear usuario Linux dedicado `doxticket`.
2. Instalar Nginx, PHP-FPM 8.3+, PostgreSQL, Redis, Supervisor, Composer, Node y `php-imap`.
3. Clonar `https://github.com/axelandreyrv-dotcom/DOXTICKET.git` en `/var/www/doxticket`.
4. Copiar `.env.example` a `.env`, generar `APP_KEY` y completar DB/Redis/SMTP.
5. Ejecutar `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, migraciones y `php artisan optimize`.
6. Configurar Nginx apuntando a `public/`.
7. Configurar Supervisor para `queue:work redis --queue=default,mail` y `schedule:work`.
8. Completar `/setup` y revisar `/admin/health`.

Nunca subir `.env`, `.env.docker`, backups, logs, sesiones, adjuntos privados, `vendor` ni `node_modules` al repositorio.

---

## Contribuciones

Las primeras contribuciones externas priorizadas son reportes y correcciones de bugs. El proyecto debe incluir guias claras (`CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`) antes del primer release publico.

---

## Nota legal

DoxTicket se publica bajo AGPLv3; ver `LICENSE`. No copiar codigo, diseno, textos, logos, iconos ni flujos de competidores. El nombre y logo DoxTicket pertenecen al proyecto y deben usarse segun las guias de marca.
