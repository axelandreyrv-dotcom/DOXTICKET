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
- El cliente `App\Services\Mail\ImapMailboxClient` es placeholder seguro; el adaptador IMAP real queda pendiente.

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

### CheckSystemHealthJob (`default`)
- Verifica PostgreSQL, Redis, storage, colas, correo y backups.
- Publica estado para `/admin/health`.

### BackupJob (`critical` o `default`)
- Ejecuta backups configurados desde admin.
- Registra resultado, tamano, destino y fecha.

### CheckForUpdatesJob (`default`)
- Consulta GitHub Releases.
- No envia datos sensibles.
- Guarda si existe nueva version estable.

### TelemetryReportJob (`low`)
- Solo si fue activada explicitamente.
- Envia datos anonimos permitidos.

### ScheduleSlaCheckJob (`default`)
- Calcula vencimientos SLA y eventos.

## Scheduler
- Ingest mailbox: cada 1 minuto por cuenta activa.
- SLA: cada 5 minutos.
- Health: cada 5 minutos.
- Updates: diario.
- Backups: segun configuracion admin.
- Limpieza: diaria.

## Idempotencia
- Correo entrante: clave por cuenta + UID/message-id.
- SMTP: clave por mensaje.
- Backups: identificador de ejecucion.
- Updates: version destino.

Estado implementado actual: el procesador deduplica por `Message-Id` dentro de `company_id`. La deduplicacion por UID de IMAP queda para el adaptador/job.
El job avanza `mail_accounts.last_uid` con el UID entregado por el cliente de buzon tras procesar cada mensaje.

## Observabilidad
- Logs con `company_id`, `ticket_id`, `mail_account_id`, `job_name`.
- Failed jobs visibles en `/admin/health`.
- `last_error` en `mail_accounts` guarda errores sanitizados para settings y health.

## Relacion con otros documentos
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `07 - Infraestructura/Redis.md`
