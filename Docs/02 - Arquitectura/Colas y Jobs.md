# Colas y Jobs — DoxTicket

## Proposito del documento
Describir como DoxTicket maneja trabajo en segundo plano.

## Driver
- Laravel Queue sobre Redis.
- En Docker: workers como servicios separados.
- En Ubuntu manual: Supervisor o systemd.

## Colas

| Cola | Proposito | Prioridad |
|---|---|---|
| `critical` | Setup finalization, auth sensible, actualizaciones, backups previos a update | Alta |
| `mail` | Ingesta IMAP/API, parseo, envio SMTP/API, adjuntos de correo | Alta |
| `default` | Notificaciones, SLA, telemetria opcional, tareas generales | Media |
| `low` | Limpiezas, archivado, metricas agregadas | Baja |

## Jobs principales

### IngestMailboxJob (`mail`)
- Lock por `mail_account_id`.
- Lee mensajes nuevos.
- Detecta auto-respuestas y loops.
- Sanitiza HTML.
- Bloquea imagenes externas por privacidad.
- Identifica thread por headers y `[DT-123]`.
- Prioriza evitar duplicados.
- Crea o actualiza tickets.

Estado implementado actual:
- Job implementado: `App\Jobs\Mail\IngestMailboxJob`.
- Contrato implementado: `App\Contracts\Mail\MailboxClient`.
- DTO de fetch implementado: `App\Support\Mail\FetchedMailMessage`.
- Scheduler implementado en `routes/console.php` para despachar cuentas activas cada minuto.
- El job usa `WithoutOverlapping` por `mail_account_id`.
- El job actualiza `last_uid`, `last_sync_at` y `last_error`.
- La logica de negocio posterior al parseo vive en `App\Services\Mail\InboundMailProcessor`.
- El cliente `App\Services\Mail\RoutingMailboxClient` decide si la cuenta se lee por IMAP o por API OAuth.
- El cliente `App\Services\Mail\ImapMailboxClient` normaliza mensajes del transporte IMAP.
- El transporte `App\Services\Mail\NativeImapConnection` usa la extension PHP IMAP, busca mensajes nuevos por UID y entrega cuerpos texto/HTML normalizados.
- El cliente `App\Services\Mail\OAuthMailboxClient` normaliza mensajes de Gmail API y Microsoft Graph hacia el mismo DTO `InboundMailMessage`.

### ProcessAttachmentsJob (`mail`)
- Valida MIME real y tamano.
- Bloquea ejecutables/scripts.
- Guarda en storage privado.
- Registra evento interno si un adjunto se bloquea.

### SendMailJob (`mail`)
- Envia confirmaciones y respuestas.
- Mantiene headers de threading.
- Incluye marcador visible `[DT-123]`.
- Envia respuestas como agente desde la cuenta de soporte.

### RefreshOAuthMailAccountsJob (`mail`)
- Recorre cuentas activas `gmail` y `microsoft365`.
- Renueva tokens vencidos o por vencer mediante `OAuthTokenRefresher`.
- Usa `WithoutOverlapping` global para evitar refrescos concurrentes.
- Preserva tokens existentes ante errores y guarda `last_error` sanitizado.

### CheckSystemHealthJob (`default`)
- Verifica PostgreSQL, Redis, storage, colas, correo y backups.
- Publica estado para `/admin/health`.

Estado implementado actual:
- `/admin/health` calcula checks en `App\Services\Admin\SystemHealthChecker`.
- `routes/console.php` escribe `doxticket:health:scheduler:last_run` cada minuto.
- `App\Jobs\Admin\RecordQueueHeartbeatJob` escribe `doxticket:health:workers:last_run` cuando un worker procesa la cola.
- Si scheduler o workers no actualizan heartbeat en 10 minutos, el health muestra warning.

### BackupJob (`critical` o `default`)
- Ejecuta backups configurados desde admin.
- Registra resultado, tamano, destino y fecha.

Estado implementado actual:
- Job implementado: `App\Jobs\Admin\RunScheduledBackupJob`.
- El scheduler lo evalua cada hora desde `routes/console.php`.
- Solo ejecuta si `system_settings.backups.schedule_enabled=true`, si la hora actual coincide con `system_settings.backups.schedule_hour` y si no se ha ejecutado ya en la fecha local guardada en `system_settings.backups.last_scheduled_run_date`.
- Ejecuta `LocalBackupRunner::run('scheduled')` y registra el resultado normal en `backup_runs`.
- Job implementado: `App\Jobs\Admin\RunBackupRetentionPruneJob`.
- El scheduler lo ejecuta diariamente a las 03:30 desde `routes/console.php`.
- Usa `App\Services\Admin\LocalBackupPruner` para borrar artefactos locales antiguos y marcar `backup_runs.status=pruned`.

### CheckForUpdatesJob (`default`)
- Consulta GitHub Releases.
- No envia datos sensibles.
- Guarda si existe nueva version estable.

Estado implementado actual:
- Job implementado: `App\Jobs\Admin\CheckForUpdatesJob`.
- Usa `App\Services\Admin\GitHubReleaseUpdateChecker`.
- Se ejecuta diariamente desde `routes/console.php`.
- Guarda el resultado en `system_settings.updates.latest` para que `/admin` no haga red en cada carga.

### TelemetryReportJob (`low`)
- Solo si fue activada explicitamente.
- Envia datos anonimos permitidos.

### ScheduleSlaCheckJob (`default`)
- Calcula vencimientos SLA y eventos.

Estado implementado actual:
- Job implementado: `App\Jobs\Tickets\ScheduleSlaCheckJob`.
- Se ejecuta cada 5 minutos desde `routes/console.php`.
- Recorre tickets activos vencidos en todas las empresas con `Ticket::withoutTenant()` y registra `ticket.sla_breached` una sola vez por ticket.
- El evento guarda `company_id`, `ticket_id`, prioridad y `sla_due_at`; no expone contenido del ticket.

## Scheduler
- Ingest mailbox: cada 1 minuto por cuenta activa.
- Health heartbeats: cada 1 minuto.
- OAuth mail token refresh: cada 5 minutos.
- SLA: cada 5 minutos mediante `ScheduleSlaCheckJob`.
- Health: cada 5 minutos.
- Updates: diario mediante `CheckForUpdatesJob`.
- Backups: cada hora se evalua `RunScheduledBackupJob`; el backup real corre solo si esta activado y toca la hora configurada.
- Retencion de backups: diaria mediante `RunBackupRetentionPruneJob`.
- Limpieza: diaria.

## Idempotencia
- Correo entrante: clave por cuenta + UID/message-id.
- SMTP: clave por mensaje.
- Backups: identificador de ejecucion.
- Updates: version destino.

Estado implementado actual: el procesador deduplica por `Message-Id` dentro de `company_id`. El job avanza `mail_accounts.last_uid` y el transporte IMAP consulta UIDs posteriores a ese valor.
El job avanza `mail_accounts.last_uid` con el UID entregado por el cliente de buzon tras procesar cada mensaje.

## Observabilidad
- Logs con `company_id`, `ticket_id`, `mail_account_id`, `job_name`.
- Failed jobs visibles en `/admin/health`.
- `last_error` en `mail_accounts` guarda errores sanitizados para settings y health.

## Relacion con otros documentos
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `07 - Infraestructura/Redis.md`
