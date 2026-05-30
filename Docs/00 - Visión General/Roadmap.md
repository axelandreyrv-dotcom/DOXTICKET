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
- Lista de tickets activos con filtro por estado y paginacion. **Base implementada.**
- Dashboard operativo con metricas reales del tenant. **Base implementada.**
- Estados, prioridades, asignacion manual y categorias. **Base parcial implementada.**
- Notas internas. **Base implementada como primer mensaje interno en creacion manual y desde detalle.**
- Detalle de ticket con hilo, eventos, metadatos y cambio de estado. **Base implementada.**
- Resolucion/cierre manual. **Base implementada con regla: cerrar solo despues de resuelto.**
- Adjuntos privados.
- Respuestas por correo saliente y fusion.

**Entregable:** agente puede crear, asignar, responder internamente y cerrar tickets manuales.

## Fase 4 — Correo estable
- SMTP global del sistema.
- Configuracion de una cuenta de soporte por empresa. **Base implementada para IMAP/SMTP generico.**
- Ingesta IMAP. **Job, scheduler y contrato de cliente implementados; adaptador IMAP real pendiente.**
- Envio SMTP.
- Confirmacion automatica de recibido.
- Threading por headers + `[DT-123]`. **Base implementada en procesador normalizado.**
- Sanitizacion HTML. **Base implementada en procesador normalizado.**
- Bloqueo de imagenes externas con opcion de abrir. **Base de bloqueo implementada; opcion de abrir pendiente.**
- Prevencion de loops. **Base implementada por headers/remitente.**
- Tests extensivos contra duplicados. **Base implementada por `Message-Id`.**

**Entregable:** correo entrante y saliente estable en instalaciones reales.

## Fase 5 — Gmail y Microsoft 365
- OAuth con Google Workspace.
- OAuth con Microsoft 365.
- Renovacion de tokens.
- Adaptadores mockeables.

**Entregable:** empresas conectan Gmail/Microsoft sin credenciales IMAP/SMTP manuales.

## Fase 6 — Admin, health, backups y releases
- Panel `/admin` completo.
- Health checks internos.
- Configuracion de backups desde admin.
- Version instalada visible.
- Aviso de nueva version estable desde GitHub.
- Boton de rollback visible.
- Telemetria opcional.

**Entregable:** instalacion administrable por un responsable tecnico.

## Fase 7 — SLA y base de conocimiento
- SLA configurable para todas las instalaciones.
- Alertas y metricas.
- Base de conocimiento interna.

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
