# AGENTS.md — Guia Obligatoria del Proyecto DoxTicket

> Este archivo es de lectura obligatoria para Codex, Claude Code y Cowork antes de realizar cualquier accion sobre este repositorio.
> No implementar nada sin haber revisado primero este archivo y la documentacion en `Docs/`.

---

## Descripcion del Proyecto

**DoxTicket** es un helpdesk IT **open source self-hosted**.

Permite que departamentos de TI instalen su propio sistema de tickets, conecten su correo de soporte y gestionen solicitudes con aislamiento multiempresa en una sola instalacion.

- **Modelo:** open source self-hosted.
- **Licencia:** AGPLv3.
- **Sitio oficial futuro:** `doxticket.com` como hub de proyecto, documentacion y releases.
- **Correo de seguridad:** `axelandreyrv@outlook.com`
- **Branding:** `Brand/DoxTicketSVG.svg`
- **Logo publico / favicon:** `public/brand/doxticket.svg`
- **Documentacion:** `Docs/`

---

## Stack Objetivo

| Capa | Tecnologia |
|---|---|
| Backend | Laravel 13.x (PHP 8.3+) |
| Base de datos | PostgreSQL |
| Cache / Colas | Redis |
| Frontend | Blade + Livewire + Tailwind CSS 4.1+ |
| Panel admin | Filament 5.x |
| Correo entrante/saliente | IMAP / SMTP, Gmail, Microsoft 365 |
| Extension PHP requerida | IMAP para correo entrante generico |
| Instalacion principal | Docker Compose |
| Instalacion alternativa | Ubuntu Server manual |
| Web server | Nginx o Caddy en Docker; Nginx + PHP-FPM en manual |
| Colas | Supervisor, systemd o workers Docker |

---

## Rutas Principales

| Ruta | Descripcion |
|---|---|
| `/` | Entrada publica de la instalacion con estado de setup segun `system_settings.setup.completed`; si la tabla aun no existe, muestra instalador pendiente sin fallar |
| `/setup` | Instalador inicial; debe bloquearse tras finalizar |
| `/login` | Login centralizado |
| `/two-factor-challenge` | Reto publico de 2FA tras validar correo y contrasena |
| `/password/forgot` | Solicitud publica de enlace de restablecimiento con respuesta generica |
| `/password/reset/{token}` | Formulario publico para definir/restablecer contrasena con token |
| `/password/reset` | Actualizacion publica de contrasena via POST con token valido |
| `/logout` | Cierre de sesion autenticado |
| `/app/companies` | Selector de empresa activa |
| `/app/dashboard` | Ruta heredada; redirige a `/app/tickets` |
| `/app/activity` | Panel de actividad de la empresa activa |
| `/app/kb` | Base de conocimiento interna de la empresa activa |
| `/app/kb/create` | Creacion de articulo interno por admin o supervisor |
| `/app/kb/{article}/edit` | Edicion de articulo interno por admin o supervisor |
| `/app/kb/{article}` | Lectura de articulo interno por slug |
| `/app/kb/{article}/archive` | Archivado de articulo interno por admin o supervisor |
| `/app/kb/{article}` | Borrado soft delete de articulo interno por admin o supervisor via DELETE |
| `/app/tickets` | Workspace principal de trabajo y gestion de tickets |
| `/app/tickets/create` | Creacion manual de ticket |
| `/app/tickets/{ticket}` | Detalle de ticket dentro de la empresa activa por id interno o clave visible `DT-123` |
| `/app/tickets/{ticket}/assign-self` | Asignacion manual del ticket a la membresia activa |
| `/app/tickets/{ticket}/messages` | Alta de nota interna del ticket |
| `/app/tickets/{ticket}/replies` | Envio de respuesta publica por correo desde la cuenta activa del tenant, con adjuntos seguros opcionales |
| `/app/tickets/{ticket}/attachments` | Subida de adjunto privado al ticket dentro de la empresa activa |
| `/app/tickets/{ticket}/merge` | Fusion del ticket actual dentro de otro ticket de la misma empresa |
| `/app/tickets/{ticket}/status` | Cambio validado de estado del ticket |
| `/app/tickets/{ticket}/properties` | Cambio validado de estado, prioridad, tipo y agente |
| `/app/attachments/{uuid}/download` | Descarga protegida de adjunto privado por UUID dentro del tenant |
| `/app/settings` | Configuracion del tenant |
| `/app/settings/two-factor/start` | Preparacion protegida de 2FA personal con contrasena actual |
| `/app/settings/two-factor/confirm` | Confirmacion protegida de 2FA personal con codigo TOTP |
| `/app/settings/two-factor` | Desactivacion protegida de 2FA personal via DELETE |
| `/app/settings/mail` | Guardado de cuenta IMAP/SMTP del tenant |
| `/app/settings/mail/test` | Prueba manual IMAP/SMTP de la cuenta activa del tenant |
| `/app/settings/mail/oauth/{provider}/redirect` | Inicio protegido del flujo OAuth para Gmail o Microsoft 365 desde la empresa activa |
| `/app/settings/mail/oauth/{provider}/callback` | Callback OAuth protegido por `state`; intercambia `code` por tokens y los guarda cifrados en la cuenta activa |
| `/admin` | Panel superadmin de la instalacion |
| `/admin/audit` | Listado protegido de auditoria global para superadmins con filtros y metadatos sensibles redactados |
| `/admin/audit/export` | Exportacion CSV protegida de auditoria global para superadmins respetando filtros |
| `/admin/companies` | Listado protegido de empresas para superadmins con resumen operativo cross-tenant |
| `/admin/companies/create` | Creacion protegida de empresa para superadmins |
| `/admin/companies/{company}/edit` | Edicion protegida de empresa para superadmins |
| `/admin/companies/{company}` | Actualizacion protegida de datos base de empresa via PUT |
| `/admin/companies/{company}` | Eliminacion suave protegida de empresa via DELETE para superadmins |
| `/admin/companies/{company}/status` | Cambio protegido de estado de empresa via POST |
| `/admin/users` | Listado protegido de usuarios globales y membresias para superadmins |
| `/admin/users/invite` | Formulario protegido para registrar invitacion de usuario a empresa |
| `/admin/users/invite` | Registro protegido de invitacion, membership y envio de correo via POST |
| `/admin/users/{user}/status` | Activacion/desactivacion protegida de usuario global via POST |
| `/admin/users/{user}/password-reset` | Envio protegido de enlace para definir/restablecer contrasena via POST |
| `/admin/users/{user}` | Eliminacion suave protegida de usuario global via DELETE |
| `/admin/memberships/{membership}` | Actualizacion protegida de rol y estado de membership via PUT |
| `/admin/memberships/{membership}` | Eliminacion suave protegida de acceso a empresa via DELETE |
| `/admin/settings` | Configuracion protegida de instalacion para superadmins sin exponer secretos; permite guardar valores publicos no sensibles, politica basica de backups, backup automatico local y SMTP global cifrado via POST |
| `/admin/health` | Resumen protegido de salud de la instalacion para superadmins |
| `/admin/backups` | Ejecucion manual protegida de backup local para superadmins |
| `/admin/rollback` | Preflight protegido de rollback manual para superadmins, condicionado a backup valido |
| `/admin/telemetry` | Activacion/desactivacion protegida de telemetria opcional para superadmins |
| `/admin/updates/check` | Chequeo manual protegido de nueva version estable para superadmins |

---

## Decisiones de Arquitectura — FINALES

Estas decisiones estan tomadas. No proponer alternativas sin justificacion documentada.

### Open Source / Self-hosted
- DoxTicket es self-hosted primero.
- El repositorio se publicara en GitHub.
- Los usuarios deben instalar versiones publicadas mediante Releases e imagenes Docker versionadas, no commits aleatorios.
- El proyecto no promete DoxTicket Cloud en v1.
- Toda instalacion debe mantener **Powered by DoxTicket** en el footer.

### Licencia
- Licencia: **AGPLv3**.
- Cualquier cambio de licencia requiere decision documentada.

### Multiempresa
- Aislamiento por `company_id` en todas las tablas de datos de empresa.
- Los usuarios son identidades globales con email unico en la instalacion.
- La pertenencia de un usuario a una empresa se modela con `memberships`.
- El rol de empresa vive en `memberships.role`, no directamente en `users`.
- Un usuario puede pertenecer a varias empresas con roles distintos.
- Tras login, si el usuario tiene mas de una empresa, debe elegir empresa antes de entrar a `/app`.
- La empresa activa se resuelve desde la membresia seleccionada en sesion, no desde input del cliente.
- No se usan subdominios por empresa en v1.
- El tenant se resuelve por el usuario autenticado.
- Toda consulta a la base de datos que toque datos de empresa DEBE filtrar por `company_id`.
- Nunca aceptar `company_id` como input confiable del usuario.

### Instalacion
- Docker Compose es el camino principal.
- Docker Compose usa `.env.docker`, creado desde `.env.docker.example`, para separar la instalacion self-hosted del `.env` local de desarrollo. `.env.docker` no se versiona.
- En Docker Compose, los contenedores PHP (`app`, `worker`, `scheduler`) ejecutan el codigo copiado dentro de la imagen y no montan el repositorio completo en QA/produccion; solo persisten `storage/` y `bootstrap/cache` como volumenes nombrados para evitar latencia alta de Laravel en Docker Desktop para Windows.
- En Docker Compose, el contenedor `web` tambien se construye como imagen y sirve `public/` y `public/build` desde el mismo build para evitar assets desincronizados con Vite.
- Despues de cambios de codigo o assets en Docker, se debe reconstruir con `docker compose --env-file .env.docker up -d --build` y ejecutar `php artisan optimize` dentro del contenedor `app`.
- Tambien debe existir documentacion de instalacion manual en Ubuntu.
- `/setup` pide idioma primero, valida entorno, crea superadmin y una empresa inicial.
- Espanol es el idioma por defecto; los mensajes de validacion visibles deben usar el idioma activo y nombres de campos entendibles.
- `/setup` debe bloquearse automaticamente despues de terminar.
- DoxTicket debe funcionar en LAN/intranet con dominio o IP local.
- Las invitaciones de usuarios nuevos deben incluir un enlace con token para definir contrasena sin revelar credenciales.
- La solicitud publica de restablecimiento de contrasena debe responder de forma generica para no revelar si un correo existe.
- Los correos de restablecimiento de contrasena deben usar notificacion propia de DoxTicket en espanol, no la plantilla default del framework.
- 2FA es opcional en v1 y se activa por usuario desde `/app/settings`; el reto se exige despues de validar contrasena y antes de resolver empresa activa.

### Correo
- La estabilidad del correo entrante es la prioridad v1.
- Una cuenta de soporte por empresa en v1.
- Existe SMTP global del sistema para invitaciones, reset, alertas y correos internos.
- El SMTP global se puede configurar desde `/admin/settings`; la contrasena se guarda cifrada en `system_settings.mail.global.password`, nunca se renderiza de vuelta en la UI y dejar el campo vacio conserva el secreto existente.
- Los valores SMTP globales guardados en `system_settings.mail.global.*` tienen prioridad sobre `.env`; `.env` queda como fallback de instalacion temprana o recuperacion manual.
- El reset de contrasena usa SMTP global y notificacion `ResetPasswordNotification` con asunto en espanol y enlace al token.
- Se soporta IMAP/SMTP generico y se planifican Gmail/Microsoft 365 desde v1.
- Gmail/Microsoft 365 usan base OAuth con tokens cifrados; no mezclar tokens OAuth con contrasenas IMAP/SMTP.
- El inicio OAuth debe usar `state` aleatorio ligado a proveedor y empresa activa; callbacks no deben aceptar `company_id` confiable del cliente.
- El callback OAuth debe consumir `state` una sola vez, intercambiar `code` mediante cliente mockeable y guardar errores sanitizados en `last_error`.
- La renovacion OAuth debe ejecutarse en cola `mail`, refrescar solo cuentas OAuth activas, preservar el refresh token existente si el proveedor no entrega uno nuevo y no borrar tokens vigentes ante errores.
- Las respuestas salientes de cuentas Gmail/Microsoft 365 deben enviarse por API OAuth, no por SMTP, y los errores de API deben sanitizarse antes de mostrarse o guardarse.
- La ingesta de cuentas Gmail/Microsoft 365 debe usar API OAuth, normalizar hacia `InboundMailMessage` y reutilizar el mismo procesador tenant-safe de correo entrante.
- Los tickets por correo reciben confirmacion automatica si `auto_reply_enabled` esta activo; un fallo SMTP no debe revertir la ingesta y debe registrar evento interno.
- Las respuestas salen como agente y mantienen marcador visible `[DT-123]`.
- Las respuestas desde el detalle del ticket requieren una cuenta de correo activa de la empresa y correo de solicitante.
- Las respuestas desde el detalle pueden incluir adjuntos seguros; se envian con el correo, se guardan en storage privado asociados al mensaje outbound y se bloquean ejecutables/scripts o archivos sobre el limite configurado antes de enviar.
- La configuracion de correo permite probar IMAP/SMTP desde Settings; errores visibles deben sanitizar secretos.
- Los adjuntos entrantes por correo usan storage privado y bloquean ejecutables/scripts o archivos sobre el limite configurado sin romper la ingesta.
- Las imagenes externas de correos se bloquean por privacidad; sus URLs pueden conservarse como metadato para apertura manual, pero no deben renderizarse inline automaticamente.
- Se prioriza evitar duplicados sobre procesar casos ambiguos sin revision.

### Tickets
- Los tickets pueden crearse por correo o manualmente.
- `/app/tickets` es el workspace principal para saber que atender ahora; `/app/dashboard` no es una seccion visible y solo redirige por compatibilidad.
- El shell autenticado muestra solo Tickets y Actividad como navegacion operativa; Empresa, Configuracion y Admin no aparecen como accesos del usuario.
- El panel `/app/activity` muestra el historial operativo de eventos de tickets de la empresa activa.
- La lista de tickets enlaza a una pagina de detalle completa dentro de `/app/tickets/{ticket}`.
- La lista de tickets permite busqueda simple por clave visible, asunto o correo del solicitante dentro de la empresa activa.
- Abrir un ticket `new` por primera vez lo marca como `open` y registra auditoria interna.
- Los agentes pueden asignarse tickets manualmente; el servidor usa la membresia activa y nunca confia en `assigned_to_membership_id` enviado por el cliente para esa accion.
- El detalle del ticket usa un panel lateral de propiedades para editar estado, prioridad, tipo y agente dentro de la empresa activa.
- El detalle del ticket permite responder al solicitante por correo y guarda la respuesta como mensaje publico outbound.
- Los tickets activos calculan `sla_due_at` automaticamente desde defaults por prioridad y la lista permite filtrar vencidos con `sla=overdue`.
- La lista principal muestra y filtra vencidos por SLA solo de la empresa activa.
- `ScheduleSlaCheckJob` registra una sola vez el evento interno `ticket.sla_breached` para tickets activos vencidos.
- Estados visibles v1: `new`, `open`, `pending`, `resolved`, `closed`. `new` es automatico; el cierre manual solo se permite despues de `resolved`.
- Prioridades visibles v1: `low`, `medium`, `high`, `urgent`.
- Tipos visibles v1: `question`, `incident`, `problem`, `request`.
- Las notas internas se agregan desde el detalle y se guardan como `ticket_messages` con `visibility=internal`.
- Los adjuntos se guardan fuera de `public/` en disco `private`, se descargan por ruta autenticada y se filtran por la empresa activa.
- Los adjuntos entrantes por correo se asocian al mensaje recibido y siguen las mismas reglas de bloqueo.
- Los adjuntos salientes de respuestas se asocian al mensaje outbound y siguen las mismas reglas de bloqueo.
- El tamano maximo de adjuntos se configura con `DOXTICKET_ATTACHMENT_MAX_BYTES`.
- Fusión de tickets: SÍ. El ticket secundario queda `merged`, apunta a `merged_into_ticket_id` y respuestas futuras al secundario se agregan al principal.
- **Subtickets / ticket padre-hijo / división de tickets: NO** sin decision explicita futura.

### Base de conocimiento
- La base de conocimiento es interna por empresa y no publica articulos a usuarios finales en v1.
- Los articulos se guardan en `kb_articles` con `company_id`, slug unico por empresa, Markdown original y HTML cacheado sanitizado.
- Agentes solo leen articulos publicados de la empresa activa.
- Admin y supervisor pueden crear, editar, publicar, archivar y borrar articulos desde `/app/kb`.
- La busqueda v1 es simple por titulo y contenido Markdown dentro de la empresa activa.
- El render Markdown debe bloquear HTML inseguro; no renderizar scripts enviados por usuarios.

### Billing
- Billing integrado, planes pagados, trial y suscripciones quedan fuera de v1.
- No implementar flujo de pago comercial en la app sin nueva decision documentada.

### Actualizaciones
- `/admin` muestra version instalada y aviso de nueva version estable consultando GitHub sin enviar datos sensibles.
- `/admin` y `/admin/health` requieren usuario autenticado, activo y `is_superadmin=true`.
- `/admin/companies` muestra empresas, estado, conteos de miembros/tickets y correo activo sin depender de la empresa activa del usuario.
- `/admin/companies` permite a superadmins crear empresas, editar datos base, cambiar estado entre `active`, `disabled` y `archived`, y eliminar suavemente empresas sin depender de la empresa activa.
- Eliminar una empresa desde `/admin/companies` usa soft delete, limpia `last_active_company_id` de usuarios afectados, olvida la membresia activa de la sesion del actor si corresponde y conserva datos para auditoria.
- `/admin/users` muestra usuarios globales, superadmins y membresias por empresa sin depender de la empresa activa; permite activar/desactivar usuarios globales sin permitir que un superadmin desactive su propia cuenta.
- `/admin/users` oculta membresias de empresas eliminadas y muestra roles como Administrador, Supervisor y Agente. Supervisor es el rol intermedio para coordinar trabajo y gestionar contenido interno sin ser administrador completo de la empresa.
- `/admin/users/{user}/password-reset` permite a superadmins enviar un enlace de definicion/restablecimiento de contrasena a usuarios existentes sin revelar contrasenas.
- Los usuarios pueden activar 2FA personal con TOTP desde `/app/settings`; el secreto y codigos de recuperacion se guardan cifrados.
- `/admin/users/{user}` permite eliminar suavemente usuarios globales y sus membresias, bloqueando la eliminacion de la propia cuenta y del ultimo superadmin activo.
- `/admin/users/invite` permite registrar invitaciones: reutiliza usuario global existente o crea uno nuevo, crea membership `invited`, evita duplicados por empresa y envia correo de invitacion por SMTP global sin revelar contrasenas.
- Si la invitacion crea un usuario nuevo, el correo incluye enlace `/password/reset/{token}` con token Laravel para definir contrasena; usuarios existentes reciben enlace normal a `/login`.
- Al definir/restablecer contrasena con token valido, las memberships `invited` del usuario se activan sin aceptar `company_id` desde el cliente.
- La aceptacion de invitacion debe guardar `accepted_at` y audit log `membership.accepted`.
- Si el envio de correo de invitacion falla, la invitacion queda registrada y el panel muestra un aviso generico sin exponer secretos SMTP.
- `/admin/memberships/{membership}` permite a superadmins cambiar rol `admin`, `supervisor` o `agent` y estado `active` o `disabled`, bloqueando dejar una empresa sin admin activo.
- `/admin/memberships/{membership}` permite eliminar suavemente un acceso a empresa, bloqueando dejar una empresa sin admin activo.
- `/admin/audit` permite a superadmins revisar eventos de `audit_logs` con empresa, actor, sujeto, accion y metadatos redactados para no exponer contrasenas, tokens ni secretos.
- `/admin/audit` permite busqueda libre por accion, empresa, actor o sujeto, y filtros por accion, empresa, actor y rango de fechas sin depender de la empresa activa del usuario.
- `/admin/audit/export` permite exportar CSV con los mismos filtros de auditoria y metadatos sanitizados, sin guardar artefactos en disco, y registra `admin.audit.exported`.
- La exportacion CSV de auditoria queda limitada a 5000 filas por solicitud en v1.
- Las acciones superadmin de crear/editar/cambiar estado/eliminar empresas, invitar usuarios, activar/desactivar usuarios, actualizar memberships, actualizar settings publicos, cambiar telemetria, ejecutar backups, solicitar rollback y revisar updates deben registrar audit logs.
- Los metadatos de audit logs deben sanitizarse antes de guardarse y antes de mostrarse si sus claves parecen contener contrasenas, tokens, secretos, cookies, autorizacion o credenciales.
- `/admin/settings` muestra URL publica, version, repositorio de releases, telemetria, politica basica de backups, backup automatico local y SMTP global sin exponer credenciales; los superadmins pueden guardar URL publica, repositorio de releases, ventana de backup reciente, dias de retencion local, activacion del backup automatico, hora diaria y SMTP global. Los secretos SMTP se guardan cifrados y los audit logs solo registran claves cambiadas.
- `/admin/health` muestra health base de `APP_KEY`, `APP_DEBUG`, setup bloqueado, PostgreSQL, cache/Redis, colas, scheduler, workers, storage, SMTP global, cuentas de correo y backups; los mensajes visibles no deben exponer secretos y la ventana de backup reciente se toma de `system_settings.backups.recent_success_hours`.
- Scheduler y workers se observan con heartbeats en cache; ausencia o antiguedad mayor a 10 minutos debe marcar warning.
- El chequeo de version usa GitHub Releases del repositorio efectivo: `system_settings.updates.github_repository` si fue guardado desde `/admin/settings`, o `DOXTICKET_GITHUB_REPOSITORY` como fallback; se ejecuta en cola/scheduler y guarda el resultado en `system_settings.updates.latest`.
- `/admin/updates/check` permite a superadmins ejecutar el mismo chequeo manualmente sin enviar datos sensibles.
- El panel admin debe mostrar solo version instalada, version estable disponible, enlace publico de release y errores sanitizados.
- `/admin` muestra el ultimo backup exitoso desde `backup_runs` y mantiene visible el boton rollback; la accion queda condicionada a `meta.rollback_available=true`.
- `/admin` muestra historial reciente de backups sin exponer rutas privadas de artefactos.
- `/admin/backups` permite a superadmins ejecutar un backup local manual; el artefacto queda en el disco privado y el resultado se registra en `backup_runs`.
- La restauracion de backups en v1 es manual: restaurar dump de base de datos, `storage/app/private` y `.env` antes de levantar la app. No existe importacion de backup desde UI en v1.
- El backup automatico local queda apagado por defecto; si se activa desde `/admin/settings`, `RunScheduledBackupJob` se evalua cada hora desde scheduler y ejecuta como maximo un backup `scheduled` por dia a la hora configurada.
- `RunBackupRetentionPruneJob` se ejecuta diariamente y aplica `system_settings.backups.retention_days` sobre backups locales exitosos; elimina artefactos privados antiguos y marca el registro `backup_runs.status=pruned` sin habilitar rollback.
- `/admin/rollback` permite a superadmins ejecutar un preflight protegido de rollback manual; en v1 no restaura automaticamente, solo confirma que existe backup valido y dirige a la guia manual.
- En v1 basta aviso de nueva version y guia/manual de actualizacion.
- El rollback debe tener boton visible en `/admin`, aunque solo funcione si existe una version anterior/backup valido.
- Antes de actualizar se debe verificar backup reciente.

### Telemetria
- Opcional y apagada por defecto.
- Solo se activa explicitamente en `/setup`.
- Tambien puede activarse/desactivarse desde `/admin` por superadmin.
- No enviar nombres, correos, contenido de tickets, asuntos, cuerpos, adjuntos ni secretos.

---

## Reglas de Seguridad — OBLIGATORIAS

### Secretos
- Prohibido incluir claves reales, tokens, contrasenas o secretos en frontend, vistas, JS compilado o repositorio.
- Toda clave debe vivir en `.env` o en almacenamiento cifrado de base de datos cuando la app ofrece un formulario administrativo para rotarla.
- En Docker, las claves de la instalacion viven en `.env.docker`; solo `.env.docker.example` puede versionarse.
- `.env` esta en `.gitignore`. Verificar siempre antes de publicar.

### Inputs
- Obligatorio validar todos los inputs con reglas de Laravel (`FormRequest` o `$request->validate()`).
- No confiar en datos del cliente. Validar en servidor siempre.

### Multitenancy
- Obligatorio proteger `company_id` en lectura, escritura y eliminacion.
- Usar policies y/o scopes globales de Eloquent para aplicar el filtro de tenant automaticamente.
- Usar `membership_id` en acciones de empresa cuando aplique para auditar el rol/contexto exacto.
- El superadmin opera cross-tenant desde `/admin` y con auditoria; tambien puede tener memberships para usar `/app` como usuario normal de una empresa.

### Autorizacion
- Usar Gates o Policies de Laravel para controlar acceso por rol dentro de cada empresa.
- El panel `/admin` solo es accesible para superadmins.

### Setup y Produccion
- Bloquear produccion si `APP_DEBUG=true`, falta `APP_KEY`, storage no es seguro, Redis/PostgreSQL no responden o `/setup` sigue habilitado tras instalar.
- Validar permisos de `.env`, `storage/` y `bootstrap/cache`.

---

## Reglas de Documentacion

- Cada cambio funcional, tecnico, visual, de infraestructura, seguridad, dependencias, rutas, schema, instalacion o comportamiento debe revisar y actualizar los `.md` necesarios en el mismo ciclo de trabajo.
- Antes de cerrar una tarea con cambios, verificar si corresponde actualizar `Docs/`, `README.md`, `AGENTS.md`, `SECURITY.md`, `CONTRIBUTING.md` o documentacion especifica del modulo tocado.
- No dejar la documentacion para despues si el cambio ya modifica el comportamiento esperado del proyecto.
- Cuando se tome una decision de arquitectura, producto, seguridad, licencia, instalacion o comunidad, actualizar `Docs/`.
- Si cambia el schema de base de datos, actualizar `Docs/03 - Base de Datos/`.
- Si cambia una ruta, comportamiento de tickets, correo, setup, actualizaciones o seguridad, actualizar este `AGENTS.md` y `README.md`.
- No dejar decisiones importantes solo en el chat o comentarios de codigo.

---

## Reglas de Uso de Skills

- Antes de crear cualquier archivo de codigo, revisar si ya existe documentacion relevante en `Docs/`.
- Antes de implementar una feature, confirmar que esta contemplada en la documentacion.
- Usar `superpowers:using-superpowers` como regla base cuando este disponible o sea solicitado en la sesion.
- Usar skills `gstack` cuando apliquen al trabajo: planificacion, especificacion, arquitectura, QA, revision, diseno, DX, investigacion, shipping o despliegue.
- Para planes grandes usar las rutas gstack correspondientes, por ejemplo `gstack-autoplan`, `gstack-spec`, `gstack-plan-eng-review`, `gstack-plan-design-review`, `gstack-plan-devex-review` y `gstack-review` segun corresponda.
- Para UI/frontend usar siempre skills de diseno antes de implementar: `minimalist-ui`, `web-design-guidelines`, `impeccable`, `emil-design-eng` y/o skills `gstack-design-*` cuando apliquen.
- En UI autenticada, los mensajes flash deben mostrarse una sola vez y anunciarse con `role="status"` y `aria-live="polite"`.
- Los errores inline de formularios deben usar `role="alert"` y estar asociados al input con `aria-invalid` y `aria-describedby`.
- Las skills de React/Vercel solo aplican como referencia de rendimiento si el stack real sigue siendo Blade + Livewire.
- El codigo debe mantenerse limpio, documentado, estructurado y preparado para escalar; no introducir abstracciones sin necesidad real.
- No instalar dependencias de Composer o NPM sin justificacion documentada.
- No modificar la estructura de carpetas sin actualizar este archivo.

---

## Reglas de Tests

- Obligatorio escribir tests para logica critica: autenticacion, aislamiento de tenant, setup, correo entrante, creacion/fusion de tickets, adjuntos, backups/rollback y health checks.
- Usar PHPUnit / Pest para backend.
- Los tests deben pasar antes de publicar una release estable.

---

## Prohibiciones Explicitas

- No implementar billing integrado en v1.
- No prometer DoxTicket Cloud en v1.
- No implementar subdominios por tenant en v1.
- No implementar subtickets, tickets padre-hijo ni division de tickets.
- No hardcodear secretos.
- No aceptar `company_id` como input confiable.
- No hacer deploy ni release sin revisar variables de entorno y checklist de seguridad.
- No copiar codigo o diseno de competidores.
- No quitar **Powered by DoxTicket** de instalaciones.
- No implementar sin revisar primero `Docs/` y este archivo.

---

## Estructura de Referencia

```
DoxTicket/
 AGENTS.md
 README.md
 LICENSE
 SECURITY.md
 CONTRIBUTING.md
 CODE_OF_CONDUCT.md
 Brand/
    DoxTicketSVG.svg
 Docs/
 app/Contracts/
 app/Jobs/
 .gitignore
```

---

*Ultima actualizacion: Mayo 2026.*
