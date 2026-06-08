# Testing — DoxTicket

## Proposito
Definir estrategia de pruebas.

## Herramientas
- Pest preferido.
- PHPUnit compatible.
- Factories Eloquent.
- Mockery.
- Playwright para QA visual local, screenshots y validacion de flujos UI.

## Cobertura obligatoria
- Setup.
- Auth.
- Tenant isolation.
- Policies.
- Actividad tenant-safe.
- Tickets.
- Detalle de tickets y bloqueo cross-tenant.
- Asignacion manual tenant-safe a la membresia activa.
- Notas internas sin aceptar `company_id` del cliente.
- Cambios de estado, incluyendo cierre solo despues de resuelto.
- Fusion.
- SLA automatico, filtro de vencidos en Tickets y evento de brecha idempotente.
- Correo entrante y threading.
- Evitar duplicados.
- SMTP outbound.
- Adjuntos.
- Health panel.
- Backups.
- Update check.
- Rollback disponible/no disponible.
- Auditoria admin y redaccion de secretos en metadatos.
- Telemetria opt-in.
- Donaciones configurables sin renderizar URLs inseguras.
- Base de conocimiento tenant-safe, permisos por rol y sanitizacion Markdown.
- Accesibilidad base del shell autenticado: navegacion etiquetada, pagina activa y portal admin fuera del shell de usuario.
- Mensajes flash del shell autenticado anunciables con `role="status"` y sin duplicados visibles.
- Errores inline de formularios asociados a campos con `aria-invalid`, `aria-describedby` y `role="alert"`.
- Localizacion de validaciones visibles en espanol por defecto.
- Confirmaciones en acciones sensibles/destructivas de UI.

## Estructura

```
tests/
  Feature/
    Setup/
    Auth/
    MultiTenant/
    Tickets/
    Mail/
    Admin/
    Backups/
    Updates/
  Unit/
    Mail/
    Sla/
    Security/
    Tenant/
```

## Tests clave
- Una membership de empresa A no puede acceder a recursos de B.
- Un usuario con membresias en A y B solo ve datos de la empresa activa.
- Un usuario puede tener roles distintos por empresa.
- Desactivar una membership solo quita acceso a esa empresa.
- Busqueda global solo busca en empresa activa.
- Notificaciones se separan por empresa.
- La actividad muestra solo eventos de la empresa activa.
- Un ticket de otra empresa no puede abrirse desde la empresa activa.
- Un agente puede asignarse un ticket y no puede forzar `assigned_to_membership_id` desde el cliente.
- Un agente no puede asignarse tickets de otra empresa.
- Agregar nota interna no puede mover datos a otra empresa aunque el cliente envie `company_id`.
- Un ticket no puede cerrarse sin pasar primero por `resolved`.
- Un ticket activo recibe `sla_due_at` por prioridad y el scheduler registra `ticket.sla_breached` una sola vez.
- `/setup` no funciona despues de completado.
- `APP_DEBUG=true` bloquea modo produccion seguro.
- Correo con `[DT-123]` se asocia al ticket correcto.
- Correo ambiguo no crea duplicado sin regla confiable.
- Imagen externa queda bloqueada.
- `Message-Id` duplicado no crea otro ticket ni mensaje.
- Headers de threading solo relacionan mensajes dentro de la misma empresa.
- Auto-respuestas por `Auto-Submitted` o `Precedence` se ignoran como loop.
- Job de ingesta avanza `last_uid` solo hacia adelante, limpia errores al exito y no procesa cuentas inactivas.
- Errores de ingesta se guardan sanitizados sin contrasenas ni tokens.
- Cuentas Gmail/Microsoft 365 deben leerse por API OAuth y normalizarse igual que IMAP antes del procesador.
- Adjuntos entrantes por API OAuth deben llegar al mismo pipeline privado/bloqueo que IMAP.
- Renovacion OAuth solo procesa cuentas activas Gmail/Microsoft 365 y no reemplaza tokens vigentes ante errores.
- Envio OAuth por API no debe guardar mensaje outbound si el proveedor falla y debe sanitizar tokens en `last_error`.
- Responder con adjuntos seguros debe enviar el correo, guardar adjuntos privados asociados al mensaje outbound y bloquear adjuntos peligrosos antes de enviar.
- Adjunto peligroso crea evento interno.
- Telemetria no envia datos si no se activo.
- Agentes solo leen articulos publicados de su empresa activa; admin/supervisor pueden crear articulos.

## Estado implementado actual
- `tests/Feature/Admin/AdminDashboardTest.php` cubre acceso superadmin, health, updates cacheados, chequeo manual protegido de actualizaciones, logo/favicons de marca y enlaces de donacion configurables sin renderizar URLs inseguras.
- `tests/Feature/Admin/AdminCompaniesTest.php` cubre acceso protegido a `/admin/companies`, resumen operativo cross-tenant, creacion de empresas con pais como texto libre, slug unico, edicion sin depender de empresa activa, cambio de estado permitido, eliminacion suave protegida con limpieza de `last_active_company_id`, confirmacion accesible y enlaces de gestion.
- `tests/Feature/Admin/AdminUsersTest.php` cubre acceso protegido a `/admin/users`, listado de usuarios globales con membresias, ocultamiento de membresias de empresas eliminadas, labels humanos de rol, envio de enlace de contrasena por superadmin, enlace desde dashboard, activacion/desactivacion de usuario, eliminacion suave de usuarios, bloqueo de autodesactivacion/autoeliminacion superadmin, edicion/eliminacion de memberships, bloqueo para no dejar una empresa sin admin activo, registro de invitaciones nuevas/existentes con correo SMTP global, enlace de definicion de contrasena solo para usuarios nuevos, bloqueo de duplicados y confirmacion accesible.
- `tests/Feature/Admin/AdminAuditTest.php` cubre acceso protegido a `/admin/audit`, listado de eventos globales, busqueda libre por accion/empresa/actor/sujeto, filtros por accion/empresa/actor/fecha, preservacion de valores del formulario, exportacion CSV protegida con filtros y metadatos sanitizados, auditoria de la propia exportacion, enlace desde dashboard y redaccion de metadatos sensibles.
- `tests/Feature/Admin/AdminActionAuditTest.php` cubre audit logs generados por acciones superadmin criticas: empresas incluyendo eliminacion suave, invitaciones, estado de usuarios, memberships, telemetria, backup manual, rollback y updates.
- `tests/Feature/Admin/AdminSettingsTest.php` cubre acceso protegido a `/admin/settings`, resumen seguro de configuracion de instalacion, guardado de settings publicos no secretos, politica de backups manual/automatico local, rechazo de URLs inseguras/repositorios invalidos/rangos invalidos, auditoria de cambios y enlace desde el dashboard admin.
- `tests/Feature/Admin/ScheduledBackupJobTest.php` cubre que el backup automatico local no corra si esta apagado, no corra fuera de la hora configurada, se ejecute cuando corresponde y no se repita dos veces el mismo dia.
- `tests/Feature/Admin/BackupRetentionPrunerTest.php` cubre que el pruning de retencion elimine artefactos locales antiguos, marque registros como `pruned` y preserve backups recientes, fallidos, en ejecucion o externos.
- `tests/Feature/Admin/BackupRetentionPruneJobTest.php` cubre que el job programado invoque el pruner de retencion local.
- `tests/Unit/Admin/SystemHealthCheckerTest.php` cubre checks de salud de instalacion, incluyendo que la ventana configurable de backup reciente afecte el estado de backups.
- `tests/Unit/Admin/GitHubReleaseUpdateCheckerTest.php` cubre uso del repositorio efectivo guardado en `system_settings` para consultar GitHub Releases, con fallback de configuracion.
- `tests/Feature/PublicNavigationTest.php` cubre navegacion publica sin Setup visible, estado publico del instalador basado en `setup.completed`, acciones no duplicadas en login y logo/favicons de marca en el shell publico. `tests/Feature/ExampleTest.php` mantiene la regresion basica de que `/` responde 200 aunque aun no existan tablas migradas.
- `tests/Feature/Auth/LoginTest.php` cubre login multiempresa, exclusion de memberships de empresas eliminadas, error generico de credenciales, localizacion en espanol y errores inline accesibles en `/login`.
- `tests/Feature/Auth/TwoFactorAuthenticationTest.php` cubre reto 2FA posterior al password, login con TOTP, consumo unico de codigos de recuperacion, activacion/desactivacion desde `/app/settings` y cifrado del secreto.
- `tests/Feature/Auth/PasswordResetTest.php` cubre enlace desde login, solicitud publica de reset con respuesta generica, envio de notificacion DoxTicket en espanol al usuario existente, no envio para correo desconocido, formulario publico de definicion/restablecimiento de contrasena con token, actualizacion segura de hash, activacion de memberships `invited`, `accepted_at` y audit log `membership.accepted`.
- `tests/Feature/Setup/InitialSetupTest.php` cubre creacion inicial, bloqueo posterior de `/setup` y errores inline accesibles del instalador.
- `tests/Feature/Activity/ActivityPanelTest.php` cubre listado de actividad, aislamiento por tenant, filtros y enlace en navegacion.
- `tests/Feature/Tickets/TicketDetailTest.php` cubre detalle tenant-safe, accion accesible para copiar la clave visible, notas internas, cambios de estado, etiquetas profesionales del hilo por direccion de mensaje, errores accesibles de formularios del detalle, metadatos de autocomplete/spellcheck y enlaces explicitos para imagenes externas bloqueadas.
- `tests/Feature/Tickets/TicketTenantTest.php` cubre aislamiento tenant-safe, creacion manual sin confiar en `company_id` y metadatos de autocomplete/spellcheck del formulario manual.
- `tests/Feature/Tickets/TicketFiltersTest.php` cubre busqueda por clave/asunto/correo, filtros backend por query string, buscador visible como control principal y accion accesible para copiar claves visibles desde `/app/tickets`.
- `tests/Feature/Tickets/TicketAssignmentTest.php` cubre asignacion propia, toma de ticket asignado a otro agente de la misma empresa, bloqueo cross-tenant y presencia de la accion en UI.
- `tests/Feature/Tickets/TicketMergeTest.php` cubre fusion tenant-safe, bloqueo cross-company, presencia del formulario de fusion en UI y confirmacion `data-confirm`.
- `tests/Feature/Tickets/TicketAttachmentTest.php` cubre subida privada de adjuntos, descarga tenant-safe, bloqueo de adjuntos peligrosos y limite configurable para subida manual.
- `tests/Feature/Tickets/TicketSlaTest.php` cubre defaults de SLA por prioridad y filtro `sla=overdue` en el workspace de Tickets.
- `tests/Feature/Tickets/DashboardTicketMetricsTest.php` cubre que `/app/dashboard` redirige a `/app/tickets` como ruta heredada.
- `tests/Feature/Tickets/ScheduleSlaCheckJobTest.php` cubre eventos `ticket.sla_breached` idempotentes para tickets activos vencidos.
- `tests/Feature/KnowledgeBase/KnowledgeBaseTest.php` cubre busqueda y lectura tenant-safe, metadatos del buscador, creacion/edicion/archivado/borrado por admin y bloqueo de gestion para agentes.
- `tests/Feature/KnowledgeBase/KnowledgeBaseTest.php` cubre mensajes `data-confirm` en archivar/borrar articulos y presencia del modal accesible global de confirmacion.
- `tests/Feature/Ui/AppShellAccessibilityTest.php` cubre `aria-label`, `aria-current`, navegacion compacta sin Dashboard/Base/Admin/Empresa/Configuracion en el shell, logo/favicons de marca, mensajes flash anunciables una sola vez, errores inline asociados a campos y validacion visible en espanol.
- `tests/Feature/Mail/MailAccountSettingsTest.php` cubre configuracion tenant-safe de cuenta IMAP/SMTP, secreto cifrado, prueba manual de conexion con errores sanitizados, errores inline accesibles y metadatos de navegador explicitos en settings.
- `tests/Feature/Mail/OAuthTokenStoreTest.php` cubre almacenamiento OAuth cifrado para Gmail/Microsoft 365, preservacion de refresh token y rechazo de cuentas `imap_smtp`.
- `tests/Feature/Mail/OAuthAuthorizationRedirectTest.php` cubre inicio OAuth tenant-safe, proveedor desconocido y credenciales faltantes.
- `tests/Feature/Mail/OAuthCallbackTest.php` cubre callback OAuth tenant-safe, consumo de `state`, guardado de tokens y errores sanitizados.
- `tests/Feature/Mail/OAuthTokenRefresherTest.php` cubre renovacion OAuth al vencer, omision cuando el token sigue fresco y errores sanitizados sin reemplazar tokens existentes.
- `tests/Feature/Mail/RefreshOAuthMailAccountsJobTest.php` cubre seleccion de cuentas activas Gmail/Microsoft 365 para renovacion en cola.
- `tests/Feature/Mail/OAuthStateStoreTest.php` cubre `state` OAuth ligado a proveedor/empresa y consumo unico.
- `tests/Unit/Mail/OAuthAuthorizationUrlFactoryTest.php` cubre URLs OAuth oficiales para Google y Microsoft 365 con scopes/configuracion esperada.
- `tests/Unit/Mail/OAuthHttpTokenClientTest.php` cubre intercambio HTTP de codigo por token, refresh token grant para Google/Microsoft 365 y propagacion de errores del proveedor.
- `tests/Feature/Mail/TicketReplyTest.php` cubre respuesta saliente desde el detalle, adjuntos seguros en respuestas, bloqueo de adjuntos peligrosos antes de enviar, bloqueo sin cuenta activa y bloqueo sin correo de solicitante.
- `tests/Feature/Mail/TicketReplyDeliveryTest.php` cubre que Gmail/Microsoft usen API OAuth para respuestas y que los errores de proveedor no guarden mensajes outbound ni filtren secretos.
- `tests/Unit/Mail/OAuthTicketReplyApiClientTest.php` cubre payloads HTTP de Gmail API `users.messages.send` y Microsoft Graph `sendMail`.
- `tests/Unit/Mail/OAuthMailboxClientTest.php` cubre lectura por Gmail API y Microsoft Graph, orden antiguo-a-reciente, normalizacion al DTO de ingesta y descarga de adjuntos de archivo.
- `tests/Unit/Mail/RoutingMailboxClientTest.php` cubre enrutamiento de cuentas IMAP/SMTP vs Gmail/Microsoft 365 hacia el cliente correcto.
- `tests/Feature/Mail/InboundMailProcessorTest.php` cubre creacion por correo, confirmacion automatica de recibido, fallo resiliente de auto-respuesta, sanitizacion, imagenes externas bloqueadas con URLs preservadas como metadato, correos sin cuerpo visible, adjuntos entrantes permitidos/bloqueados por tipo o tamano, deduplicacion por `Message-Id` y fingerprint interno, threading, loops y redireccion de respuestas a tickets fusionados.
- `tests/Feature/Mail/IngestMailboxJobTest.php` cubre el job de ingesta, avance monotono de UID, errores sanitizados, fallo a mitad de lote y cuentas inactivas.
- `tests/Unit/Mail/ImapMailboxClientTest.php` cubre normalizacion de headers, remitente, asunto MIME, cuerpos texto/HTML, adjuntos crudos de IMAP, nombres de adjuntos codificados y descarte de mensajes sin remitente valido.

## CI minimo
- Composer install.
- NPM install/build.
- Pint test.
- PHPStan/Psalm.
- Tests.
- Secret scan.

## QA visual
- Usar Playwright para revisar pantallas importantes en desktop y movil antes de cerrar cambios de UI.
- Guardar screenshots y snapshots locales en `output/playwright/`.
- `output/playwright/` y `.playwright-cli/` son artifacts locales y no deben versionarse.
- Revisar consola del navegador, overflow horizontal, presencia de `Powered by DoxTicket` y estados responsive.

## QA Docker
- Crear `.env.docker` desde `.env.docker.example` y generar `APP_KEY`.
- Levantar con `docker compose --env-file .env.docker up -d --build`.
- Ejecutar migraciones con `docker compose --env-file .env.docker exec app php artisan migrate --force`.
- Ejecutar caches de runtime con `docker compose --env-file .env.docker exec app php artisan optimize`.
- Reconstruir con `--build` despues de cambios de codigo o assets; los contenedores PHP no montan el repo completo para evitar latencia alta en Docker Desktop para Windows y el contenedor `web` sirve los assets generados dentro de la imagen.
- Verificar que el contenedor `app` tenga `imap`, `pdo_pgsql` y `redis` en `php -m`.
- Completar `/setup`, entrar a `/login`, abrir `/app/tickets` y validar `/admin` con una cuenta superadmin.

## Relacion
- `04 - Seguridad/Checklist Producción.md`
- `02 - Arquitectura/Multiempresa Self-Hosted.md`
