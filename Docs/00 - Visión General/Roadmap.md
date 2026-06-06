# Roadmap — DoxTicket

## Proposito del documento
Trazar las fases planificadas de DoxTicket como proyecto open source self-hosted.

## Fase 0 — Documentacion y fundamentos
- Actualizar documentacion al modelo open source self-hosted.
- Definir licencia AGPLv3.
- Definir alcance v1 sin billing ni Cloud.
- Preparar `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`.
- Definir identidad visual minimalista.

**Entregable:** documentacion coherente, accionable y lista para construir.

## Fase 1 — Esqueleto Laravel + Docker
- Crear app Laravel base.
- Configurar PostgreSQL, Redis, workers y scheduler.
- Crear Dockerfile y `docker-compose.yml`.
- Configurar web server en Docker.
- Preparar `.env.example` seguro.
- Configurar CI basico.

**Entregable:** DoxTicket levanta localmente con Docker Compose.

## Fase 2 — Setup, autenticacion y multiempresa
- `/setup` con idioma primero.
- Validaciones de entorno.
- Creacion de superadmin. **Base implementada.**
- Creacion de empresa inicial. **Base implementada.**
- Creacion de membresia admin inicial. **Base implementada.**
- Bloqueo de setup tras finalizar. **Base implementada.**
- Login centralizado. **Base implementada.**
- Selector de empresa para usuarios con multiples membresias. **Base implementada.**
- Middleware de tenant por `company_id`. **Base implementada.**
- 2FA opcional.

**Entregable:** una instalacion nueva queda operativa y segura.

## Fase 3 — Tickets nucleo
- Modelos `tickets`, `ticket_messages`, `ticket_events`, `attachments`, `categories`. **Base implementada.**
- Creacion manual de tickets. **Base implementada.**
- Lista de tickets activos con filtros por estado, agente, prioridad, tipo y fuente, mas paginacion. **Base implementada.**
- Tickets como workspace principal con filtros reales del tenant. **Base implementada.**
- Panel de actividad con historial operativo de tickets. **Base implementada.**
- Estados simples, prioridades, tipos, asignacion manual y categorias. **Base implementada con panel lateral de propiedades.**
- Notas internas. **Base implementada como primer mensaje interno en creacion manual y desde detalle.**
- Detalle de ticket con hilo, actividad, metadatos y propiedades editables. **Base implementada.**
- Resolucion/cierre manual. **Base implementada con regla: cerrar solo despues de resuelto.**
- Adjuntos privados. **Base implementada desde el detalle con descarga protegida y bloqueo de tipos peligrosos.**
- Respuestas por correo saliente. **Base implementada desde el detalle.**
- Fusion de tickets. **Base implementada con redireccion de respuestas futuras al principal.**

**Entregable:** agente puede crear, asignar, responder internamente y cerrar tickets manuales.

## Fase 4 — Correo estable
- SMTP global del sistema.
- Configuracion de una cuenta de soporte por empresa. **Base implementada para IMAP/SMTP generico con prueba manual desde Settings.**
- Ingesta IMAP. **Job, scheduler, normalizador y transporte IMAP nativo implementados; falta QA con buzones reales.**
- Envio SMTP. **Base implementada desde el detalle y confirmacion automatica.**
- Confirmacion automatica de recibido. **Base implementada al crear ticket por correo, con evento interno si falla SMTP.**
- Threading por headers + `[DT-123]`. **Base implementada en procesador normalizado.**
- Sanitizacion HTML. **Base implementada en procesador normalizado.**
- Adjuntos entrantes por correo. **Base implementada con storage privado y bloqueo de tipos peligrosos.**
- Bloqueo de imagenes externas con opcion de abrir. **Base implementada con URLs bloqueadas y apertura manual desde el detalle.**
- Prevencion de loops. **Base implementada por headers/remitente.**
- Tests extensivos contra duplicados. **Base implementada por `Message-Id`.**

**Entregable:** correo entrante y saliente estable en instalaciones reales.

## Fase 5 — Gmail y Microsoft 365
- OAuth con Google Workspace. **Base de almacenamiento seguro, inicio de autorizacion y callback/token exchange implementados.**
- OAuth con Microsoft 365. **Base de almacenamiento seguro, inicio de autorizacion y callback/token exchange implementados.**
- Renovacion de tokens. **Base automatica implementada con job en cola `mail`.**
- Adaptadores mockeables. **Base `OAuthTokenStore`, `OAuthTokenClient`, lectura con adjuntos y envio de respuestas por API implementada.**

**Entregable:** empresas conectan Gmail/Microsoft sin credenciales IMAP/SMTP manuales.

## Fase 6 — Admin, health, backups y releases
- Panel `/admin` completo. **Base implementada con dashboard, health, backups, updates, rollback, telemetria, donaciones y enlace a empresas.**
- Listado de empresas en `/admin/companies`. **Base implementada con estado, miembros, tickets, correo activo, creacion, edicion y cambio de estado.**
- Listado de usuarios en `/admin/users`. **Base implementada con usuarios globales, superadmins, membresias, activacion/desactivacion protegida, edicion de rol/estado de membership y registro de invitaciones con envio SMTP global.**
- Configuracion de instalacion en `/admin/settings`. **Base implementada sin exponer secretos.**
- Health checks internos. **Base implementada.**
- Configuracion de backups desde admin. **Base implementada con ventana de backup reciente, retencion local, pruning diario y backup automatico diario opcional apagado por defecto.**
- Version instalada visible. **Base implementada.**
- Aviso de nueva version estable desde GitHub. **Base implementada.**
- Boton de rollback visible. **Base implementada con preflight manual.**
- Telemetria opcional. **Base implementada como consentimiento local.**

**Entregable:** instalacion administrable por un responsable tecnico.

## Fase 7 — SLA y base de conocimiento
- SLA configurable para todas las instalaciones. **Base implementada con defaults por prioridad, metrica de vencidos, filtro y evento de brecha.**
- Alertas y metricas. **Base implementada para vencidos por SLA.**
- Base de conocimiento interna. **Base implementada con articulos Markdown, busqueda simple, gestion basica y permisos por rol.**

**Entregable:** equipos miden cumplimiento y documentan soluciones internas.

## Fase 8 — Pulido, accesibilidad e i18n
- Revision visual completa.
- Accesibilidad WCAG AA.
- Espanol e ingles completos.
- Documentacion de usuario y administrador.
- Primer release estable usable.

**Entregable:** version estable lista para uso real.

## Mas alla de v1
- Hosting oficial por evaluar, sin prometer DoxTicket Cloud en v1.
- Soporte profesional.
- Instalacion administrada.
- Plugins/extensiones.
- Portal de usuario final.
- Chat en vivo.
- Reportes avanzados.
- Integraciones Slack/Teams/Jira/WhatsApp.
