# Modulo Superadmin — DoxTicket

## Proposito
Describir `/admin`, panel del administrador de la instalacion.

## Acceso
- Solo rol `superadmin`.
- En la base actual requiere usuario autenticado, activo y `is_superadmin=true`.
- 2FA opcional en v1.

## Pantallas

### Dashboard admin
- Version instalada.
- Aviso de nueva version estable.
- Estado general del sistema.
- Resumen de empresas, usuarios, tickets y jobs.

Estado implementado actual:
- `/admin` muestra version instalada desde `DOXTICKET_VERSION`, resumen de empresas, usuarios, tickets y cuentas de correo activas.
- `/admin` muestra el ultimo resultado local del chequeo de GitHub Releases si existe, incluyendo aviso de nueva version estable y enlace publico de release.
- `/admin` permite ejecutar un chequeo manual de actualizaciones desde una accion protegida para superadmins.
- `/admin` muestra el ultimo backup exitoso registrado, historial reciente de backups, permite ejecutar backup manual local y mantiene un boton rollback visible; queda deshabilitado si no hay backup/version valida.
- `/admin` muestra el estado de telemetria opcional, resume que datos no se envian y permite activar/desactivar desde una accion protegida.
- `/admin` enlaza a Empresas como seccion administrativa separada del shell de usuario.
- `/admin` enlaza a Usuarios como seccion administrativa separada del shell de usuario.
- `/admin` enlaza a Configuracion como seccion administrativa de instalacion separada del shell de usuario.
- `/admin` enlaza a Auditoria como seccion administrativa separada para revisar eventos globales.
- `/admin` y `/admin/health` usan la misma vista protegida por middleware `auth` + `superadmin`.

### Empresas
- Listar, crear, editar, desactivar, archivar y eliminar suavemente.
- Ver usuarios/tickets por empresa.

Estado implementado actual:
- `/admin/companies` lista empresas para superadmins con estado, slug, conteo de miembros, conteo de tickets y correo activo si existe.
- La vista usa consultas cross-tenant controladas desde el portal admin y no depende del selector normal de empresa.
- `/admin/companies/create` permite crear una empresa con nombre, slug unico, pais como texto libre, idioma por defecto y estado inicial.
- `/admin/companies/{company}/edit` permite editar datos base de la empresa sin cambiar usuarios, correo ni tickets.
- `POST /admin/companies/{company}/status` permite cambiar estado entre `active`, `disabled` y `archived`.
- `DELETE /admin/companies/{company}` elimina suavemente la empresa, limpia `last_active_company_id` de usuarios afectados y conserva datos para auditoria.
- Crear empresa, editar datos base, cambiar estado y eliminar suavemente registran eventos `admin.company.created`, `admin.company.updated`, `admin.company.status_changed` y `admin.company.deleted`.
- Los cambios de estado y eliminacion desde la UI muestran confirmacion accesible antes de enviar la accion.
- La gestion de empresas usa rutas protegidas por `auth` + `superadmin`; no depende de la empresa activa del usuario.
- Gestion avanzada de usuarios por empresa y restauracion/retencion quedan pendientes para fases futuras del portal admin.

### Usuarios superadmin
- Crear/editar/desactivar superadmins.
- Un superadmin es un usuario global con `is_superadmin=true`.
- Puede tener memberships normales para usar `/app`.

Estado implementado actual:
- `/admin/users` lista usuarios globales, email, estado activo/inactivo, marca superadmin y membresias con empresa, rol y estado.
- `/admin/users` oculta membresias de empresas eliminadas para evitar accesos huerfanos como "Empresa no disponible".
- La vista usa consultas globales protegidas por `auth` + `superadmin` y no depende de la empresa activa.
- `/admin/users/invite` permite registrar una invitacion a empresa con nombre, correo, empresa y rol.
- Si el correo ya existe, se reutiliza el usuario global y se agrega una membership `invited`; si no existe, se crea usuario activo con contrasena aleatoria no revelada.
- El registro de invitacion evita duplicar membership para la misma empresa.
- El correo de invitacion se envia por SMTP global usando un mailable de texto, sin incluir contrasenas ni secretos.
- Las invitaciones de usuarios nuevos generan token de definicion de contrasena y enlace `/password/reset/{token}`; usuarios existentes reciben el acceso normal a `/login`.
- Si SMTP global falla, la invitacion queda registrada y el panel muestra un aviso generico para revisar la configuracion global.
- `POST /admin/users/{user}/status` permite activar/desactivar usuarios globales.
- `POST /admin/users/{user}/password-reset` permite enviar un enlace para definir/restablecer contrasena sin revelar credenciales; en local con `MAIL_MAILER=log`, el correo se escribe en `storage/logs/laravel.log`.
- `PUT /admin/memberships/{membership}` permite editar rol `admin`, `supervisor` o `agent` y estado `active` o `disabled` de una membership existente.
- `DELETE /admin/users/{user}` permite eliminar suavemente un usuario global y sus membresias, bloqueando la propia cuenta y el ultimo superadmin activo.
- `DELETE /admin/memberships/{membership}` permite eliminar suavemente un acceso a empresa.
- Los roles se muestran como Administrador, Supervisor y Agente. Supervisor es el rol intermedio para coordinar trabajo y gestionar contenido interno sin permisos completos de administrador de empresa.
- Invitar usuarios, enviar enlaces de contrasena, cambiar estado global de usuario, eliminar usuarios, actualizar memberships y eliminar memberships registran eventos `admin.user.invited`, `admin.user.password_reset_sent`, `admin.user.status_changed`, `admin.user.deleted`, `admin.membership.updated` y `admin.membership.deleted`.
- Un superadmin no puede desactivar ni eliminar su propia cuenta desde `/admin/users`.
- El portal bloquea cambios que dejarian una empresa sin al menos una membership `admin` activa.
- Los cambios de estado desde la UI muestran confirmacion accesible antes de enviar la accion.
- Gestion avanzada de superadmins y reasignacion de tickets quedan pendientes.

### Health
- PostgreSQL.
- Redis.
- Storage.
- Colas.
- Workers.
- Scheduler.
- SMTP global.
- Cuentas de correo por empresa.
- Backups.
- Setup bloqueado.
- `APP_DEBUG`.
- `APP_KEY`.

Estado implementado actual:
- `App\Services\Admin\SystemHealthChecker` reporta `APP_KEY`, `APP_DEBUG`, setup bloqueado, base de datos, cache, colas, scheduler, workers, storage, SMTP global, cuentas de correo activas y backups.
- Los errores de cuentas de correo se cuentan sin mostrar detalles que puedan contener secretos, correos o credenciales.
- Backups avisan si no existe backup exitoso reciente.
- Scheduler y workers usan heartbeats en cache; si no hay marca reciente, `/admin/health` muestra warning.
- SMTP global se valida como configuracion de produccion sin exponer host sensible, correos ni credenciales.

### Configuracion
- Revisar parametros de instalacion.
- Mostrar version instalada y repositorio de releases.
- Mostrar estado de telemetria y permitir activarla/desactivarla.
- Mostrar SMTP global sin credenciales.

Estado implementado actual:
- `/admin/settings` muestra URL publica, version instalada, repositorio de releases, estado de telemetria, mailer y remitente global.
- `POST /admin/settings` permite a superadmins guardar URL publica y repositorio de releases como valores publicos no secretos en `system_settings`.
- `POST /admin/settings` tambien permite guardar politica basica de backups: horas para considerar un backup como reciente y dias de retencion local documentada.
- `POST /admin/settings` permite activar/desactivar backup automatico local y definir la hora diaria; queda apagado por defecto.
- Los valores guardados desde `/admin/settings` tienen prioridad sobre `.env` para el repositorio de releases; `.env` queda como fallback.
- La actualizacion de settings publicos registra `admin.settings.updated` con las claves modificadas, sin guardar secretos.
- La vista no muestra tokens, contrasenas, hosts sensibles privados ni secretos `.env`.
- La accion de telemetria reutiliza `POST /admin/telemetry`.

### Backups
- Configurar destino externo y restauracion automatizada.
- Ver ultimo backup.
- Ejecutar backup manual.
- Ver historial `backup_runs`.

Estado implementado actual:
- Existe `backup_runs` para registrar ejecuciones de backup.
- `/admin` muestra el ultimo backup exitoso, destino, tamano y disponibilidad de rollback.
- `/admin` muestra un historial compacto de las ultimas ejecuciones de backup con estado, destino, tamano y error sanitizado cuando aplica; no muestra rutas privadas del artefacto.
- `/admin/backups` ejecuta un backup local manual protegido por `auth` + `superadmin`, guarda el artefacto en disco `private` y registra estado, tamano, error sanitizado y metadata en `backup_runs`.
- `App\Jobs\Admin\RunScheduledBackupJob` permite backup automatico local opcional: el scheduler lo evalua cada hora, respeta la hora configurada y no ejecuta mas de un backup `scheduled` por dia.
- `App\Jobs\Admin\RunBackupRetentionPruneJob` aplica diariamente la retencion local configurada; borra artefactos privados de backups antiguos y marca el registro como `pruned`.
- `/admin/rollback` ejecuta un preflight protegido por `auth` + `superadmin`; si no existe backup valido redirige con aviso, y si existe prepara el rollback manual sin restaurar automaticamente.
- La restauracion despues de reinstalar es manual en v1: detener la app, restaurar el dump de PostgreSQL o el archivo SQLite segun corresponda, restaurar `storage/app/private`, restaurar `.env`, ejecutar migraciones pendientes y revisar `/admin/health`.
- Backups manuales y preflights de rollback registran eventos `admin.backup.manual_run`, `admin.rollback.preflight_requested` o `admin.rollback.preflight_failed`.
- En SQLite de desarrollo/test se copia el archivo de base de datos; en PostgreSQL se usa `pg_dump` con salida privada local.
- `/admin/health` marca warning si no hay backup exitoso dentro de la ventana configurada en `/admin/settings`.
- Configuracion de destinos externos, cifrado avanzado y restauracion automatizada quedan pendientes.

### Updates
- Mostrar version instalada.
- Consultar GitHub Releases.
- Mostrar ultima version estable.
- Mostrar changelog.
- Verificar backup reciente antes de actualizar.
- Mostrar boton rollback siempre; accion habilitada solo si aplica.

Estado implementado actual:
- `App\Services\Admin\GitHubReleaseUpdateChecker` consulta `GET /repos/{owner}/{repo}/releases/latest` del repositorio efectivo guardado en `system_settings.updates.github_repository` o, si no existe, el configurado en `DOXTICKET_GITHUB_REPOSITORY`.
- `App\Jobs\Admin\CheckForUpdatesJob` guarda el resultado en `system_settings.updates.latest`.
- El scheduler ejecuta el job diariamente.
- `POST /admin/updates/check` ejecuta el mismo chequeo manualmente para superadmins y redirige a `/admin` con mensaje de estado.
- El chequeo manual registra `admin.updates.checked` con resultado basico sin enviar ni guardar datos sensibles.
- El chequeo no envia nombres de empresas, correos, tickets, asuntos, cuerpos, adjuntos ni secretos.
- El panel muestra errores sanitizados y no realiza llamadas de red al cargar `/admin`.
- Changelog completo y restauracion automatizada quedan pendientes.
- El boton rollback ya es visible y solo se habilita si el ultimo backup exitoso indica `meta.rollback_available=true`; la accion disponible realiza preflight y remite al procedimiento manual.

### Telemetria
- Ver si esta activa.
- Activar/desactivar.
- Mostrar exactamente que datos se enviarian.

Estado implementado actual:
- `/admin` muestra `Telemetría` como `Activa` o `Apagada`.
- `POST /admin/telemetry` permite cambiar `system_settings.telemetry.enabled` solo a superadmins.
- El cambio de telemetria registra `admin.telemetry.updated` con valor anterior y nuevo.
- El panel indica que no se envian nombres, correos, asuntos, cuerpos, adjuntos ni secretos.
- Todavia no existe envio remoto de reportes; esta fase solo controla consentimiento local y transparencia.

### Auditoria
- Busqueda por accion, empresa, usuario y fecha.

Estado implementado actual:
- `/admin/audit` lista eventos de `audit_logs` para superadmins con fecha, accion, empresa, actor, sujeto y metadatos.
- `/admin/audit` permite busqueda libre por accion, empresa, actor o sujeto.
- `/admin/audit` filtra por accion, empresa, actor y rango de fechas mediante query string validado.
- `/admin/audit/export` exporta CSV protegido con los mismos filtros/busqueda y metadatos sanitizados sin guardar artefactos en disco.
- Cada exportacion registra `admin.audit.exported` con filtros usados, cantidad de filas y limite aplicado.
- En v1 la exportacion CSV se limita a 5000 filas por solicitud.
- La vista esta protegida por `auth` + `superadmin` y no depende de la empresa activa del usuario.
- `App\Services\Admin\AuditLogger` centraliza el registro de eventos superadmin con actor, sujeto, empresa inferida, IP, user agent y metadatos.
- Los metadatos se redactan antes de guardarse y antes de mostrarse cuando las claves parecen contener contrasenas, tokens, secretos, autorizacion, cookies o credenciales.
- Exportacion avanzada con programacion, entrega por correo o paginacion asincronica queda pendiente para una fase futura.

## Seguridad
- No mostrar secretos.
- Acciones criticas auditadas.
- Rollback/update/backups requieren confirmacion.
- Accesos cross-tenant en `/admin` no dependen del selector de empresa activa.

## Relacion con otros documentos
- `Empresas.md`
- `Usuarios.md`
- `04 - Seguridad/Checklist Producción.md`
