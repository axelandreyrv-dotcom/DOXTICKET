# Tablas — DoxTicket

## Proposito del documento
Definir las tablas principales de DoxTicket v1 open source self-hosted.

## Convenciones generales
- PK `id BIGSERIAL`.
- `uuid UUID UNIQUE` en entidades expuestas.
- `company_id BIGINT NOT NULL` en tablas multiempresa.
- Timestamps `created_at`, `updated_at`.
- `deleted_at` cuando aplique soft delete.
- PostgreSQL es la unica base de datos oficial.

---

## `system_settings`
- `id`
- `key` VARCHAR(120) UNIQUE NOT NULL
- `value` JSONB NULL
- `is_secret` BOOLEAN NOT NULL DEFAULT false
- `created_at`, `updated_at`

Uso: setup completado, idioma por defecto, telemetria, version, settings publicos de instalacion, update checks y backups.
Cuando `is_secret=true`, `value` guarda un string cifrado con Laravel encryption dentro de JSONB; la app lo descifra mediante `SystemSetting::get`.

Claves publicas no secretas actuales:
- `installation.public_url`
- `updates.github_repository`
- `updates.latest`
- `backups.recent_success_hours`
- `backups.retention_days`
- `backups.schedule_enabled`
- `backups.schedule_hour`
- `backups.last_scheduled_run_date`
- `telemetry.enabled`
- `mail.global.mailer`
- `mail.global.host`
- `mail.global.port`
- `mail.global.encryption`
- `mail.global.username`
- `mail.global.from_address`
- `mail.global.from_name`

Claves secretas actuales:
- `mail.global.password`

---

## `companies`
- `id`, `uuid`
- `name` VARCHAR(160) NOT NULL
- `slug` VARCHAR(80) UNIQUE NOT NULL
- `country` VARCHAR(120) NULL
- `phone` VARCHAR(40) NULL
- `status` VARCHAR(32) NOT NULL DEFAULT `active` — `active|disabled|archived`
- `logo_path` VARCHAR(255) NULL
- `locale_default` VARCHAR(8) NOT NULL DEFAULT `es`
- `storage_limit_bytes` BIGINT NULL
- `storage_used_bytes` BIGINT NOT NULL DEFAULT 0
- `created_at`, `updated_at`, `deleted_at`

---

## `users`
- `id`, `uuid`
- `name` VARCHAR(120) NOT NULL
- `email` VARCHAR(180) NOT NULL
- `password` VARCHAR(255) NOT NULL
- `is_superadmin` BOOLEAN NOT NULL DEFAULT false
- `email_verified_at` TIMESTAMPTZ NULL
- `two_factor_secret` TEXT NULL
- `two_factor_recovery_codes` TEXT NULL
- `two_factor_confirmed_at` TIMESTAMPTZ NULL
- `is_active` BOOLEAN NOT NULL DEFAULT true
- `locale` VARCHAR(8) NULL
- `timezone` VARCHAR(64) NULL
- `last_active_company_id` BIGINT NULL
- `last_login_at` TIMESTAMPTZ NULL
- `created_at`, `updated_at`, `deleted_at`
- UNIQUE `(email)`

---

## `memberships`
- `id`, `uuid`
- `company_id` BIGINT NOT NULL
- `user_id` BIGINT NOT NULL
- `role` VARCHAR(32) NOT NULL — `admin|supervisor|agent`
- `status` VARCHAR(32) NOT NULL DEFAULT `active` — `invited|active|disabled`
- `invited_by_user_id` BIGINT NULL
- `invited_at` TIMESTAMPTZ NULL
- `accepted_at` TIMESTAMPTZ NULL
- `last_selected_at` TIMESTAMPTZ NULL
- `preferences` JSONB NULL DEFAULT `{}`
- `created_at`, `updated_at`, `deleted_at`
- UNIQUE `(company_id, user_id)`

---

## `mail_accounts`
- `id`, `uuid`
- `company_id` NOT NULL
- `provider` VARCHAR(32) NOT NULL — `imap_smtp|gmail|microsoft365`
- `from_name` VARCHAR(120)
- `from_email` VARCHAR(180) NOT NULL
- `host_imap`, `port_imap`, `security_imap`
- `host_smtp`, `port_smtp`, `security_smtp`
- `username` VARCHAR(180) NULL
- `password_encrypted` TEXT NULL
- `oauth_access_token` TEXT NULL
- `oauth_refresh_token` TEXT NULL
- `oauth_provider_user_id` VARCHAR(255) NULL
- `oauth_expires_at` TIMESTAMPTZ NULL
- `oauth_scopes` JSONB NULL
- `oauth_connected_at` TIMESTAMPTZ NULL
- `folder_in` VARCHAR(120) DEFAULT `INBOX`
- `is_active` BOOLEAN NOT NULL DEFAULT true
- `last_uid` VARCHAR(120) NULL
- `last_sync_at` TIMESTAMPTZ NULL
- `last_error` TEXT NULL
- `created_at`, `updated_at`

Regla v1: una cuenta activa por empresa, reforzada con indice unico parcial por `company_id` cuando `is_active = true`.

Estado implementado actual: una cuenta de soporte por empresa se refuerza con `UNIQUE (company_id)`. Esto simplifica v1 y evita cuentas ambiguas mientras no exista flujo de multiples cuentas inactivas.

---

## `categories`
- `id`, `uuid`
- `company_id` NOT NULL
- `name` VARCHAR(120) NOT NULL
- `color` VARCHAR(16) NULL
- `is_active` BOOLEAN NOT NULL DEFAULT true
- `created_at`, `updated_at`
- UNIQUE `(company_id, name)`

---

## `tickets`
- `id`, `uuid`
- `company_id` NOT NULL
- `mail_account_id` BIGINT NULL
- `category_id` BIGINT NULL
- `assigned_to_membership_id` BIGINT NULL
- `created_by_membership_id` BIGINT NULL
- `requester_email` VARCHAR(180) NULL
- `requester_name` VARCHAR(180) NULL
- `subject` VARCHAR(255) NOT NULL DEFAULT `Sin Asunto`
- `public_number` BIGINT NOT NULL
- `public_key` VARCHAR(40) NOT NULL — ejemplo `DT-123`
- `status` VARCHAR(32) NOT NULL DEFAULT `new`
- `priority` VARCHAR(16) NOT NULL DEFAULT `medium`
- `ticket_type` VARCHAR(24) NOT NULL DEFAULT `request` — `question|incident|problem|request`
- `source` VARCHAR(16) NOT NULL — `email|manual|system`
- `external_thread_id` VARCHAR(255) NULL
- `first_opened_at` TIMESTAMPTZ NULL
- `first_response_at` TIMESTAMPTZ NULL
- `resolved_at` TIMESTAMPTZ NULL
- `closed_at` TIMESTAMPTZ NULL
- `merged` BOOLEAN NOT NULL DEFAULT false
- `merged_into_ticket_id` BIGINT NULL
- `merged_at` TIMESTAMPTZ NULL
- `merged_by_membership_id` BIGINT NULL
- `sla_due_at` TIMESTAMPTZ NULL
- `last_activity_at` TIMESTAMPTZ NOT NULL DEFAULT now()
- `created_at`, `updated_at`, `deleted_at`
- UNIQUE `(company_id, public_number)`
- UNIQUE `(company_id, public_key)`

Estados visibles v1: `new|open|pending|resolved|closed`. Estados internos futuros como `merged|trashed` pueden existir para fusion y papelera, pero no son seleccionables en el panel de propiedades.

---

## `ticket_messages`
- `id`, `uuid`
- `company_id` NOT NULL
- `ticket_id` NOT NULL
- `author_user_id` BIGINT NULL
- `author_membership_id` BIGINT NULL
- `author_email` VARCHAR(180) NULL
- `author_name` VARCHAR(180) NULL
- `visibility` VARCHAR(16) NOT NULL — `public|internal`
- `direction` VARCHAR(16) NOT NULL — `inbound|outbound|internal`
- `body_html` TEXT NULL
- `body_text` TEXT NULL
- `external_images_blocked` BOOLEAN NOT NULL DEFAULT false
- `external_image_urls` JSONB NULL
- `message_id_header` VARCHAR(255) NULL
- `in_reply_to_header` VARCHAR(255) NULL
- `references_header` TEXT NULL
- `headers_raw` JSONB NULL
- `delivered_at` TIMESTAMPTZ NULL
- `created_at`, `updated_at`, `deleted_at`

---

## `ticket_events`
- `id`
- `company_id`, `ticket_id`
- `actor_user_id` BIGINT NULL
- `actor_membership_id` BIGINT NULL
- `type` VARCHAR(64) NOT NULL
- `payload` JSONB NOT NULL
- `created_at`

---

## `attachments`
- `id`, `uuid`
- `company_id` NOT NULL
- `ticket_id` NOT NULL
- `ticket_message_id` BIGINT NULL
- `filename` VARCHAR(255) NOT NULL
- `mime_type` VARCHAR(120) NOT NULL
- `size_bytes` BIGINT NOT NULL
- `disk` VARCHAR(32) NOT NULL DEFAULT `private`
- `path` VARCHAR(512) NOT NULL
- `checksum_sha256` CHAR(64) NULL
- `blocked_reason` TEXT NULL
- `created_at`, `deleted_at`

## Estado implementado actual
- Implementadas en migraciones: `system_settings`, `companies`, `users` extendido, `memberships`, `mail_accounts`, `categories`, `tickets`, `ticket_messages`, `ticket_events` y `attachments`.
- Implementada en migraciones: `kb_articles` para base de conocimiento interna por empresa.
- La base de tickets usa scope de tenant por `company_id` en los modelos principales de empresa.
- La creacion manual de tickets no acepta `company_id` confiable desde input del usuario; el valor se deriva de la membresia activa en sesion.
- `mail_accounts` guarda credenciales por empresa cifradas y no expone secretos en vistas.
- `system_settings` guarda la contrasena SMTP global cifrada cuando se configura desde `/admin/settings`; los valores no secretos de `mail.global.*` permiten aplicar la configuracion SMTP sin editar `.env`.
- `mail_accounts` conserva metadatos OAuth para Gmail/Microsoft 365: tokens cifrados, scopes, usuario proveedor, expiracion y fecha de conexion.
- `users.two_factor_secret` y `users.two_factor_recovery_codes` se guardan cifrados mediante casts de Eloquent; `two_factor_confirmed_at` indica que el secreto ya fue validado por codigo TOTP.
- `tickets.ticket_type` clasifica el ticket por pregunta, incidente, problema o solicitud sin alterar el origen real guardado en `source`.
- `ticket_messages.external_image_urls` conserva las URLs externas bloqueadas para apertura manual desde el detalle del ticket, sin cargarlas automaticamente.
- `kb_articles` guarda Markdown original y HTML cacheado sanitizado; agentes solo consultan publicados y admin/supervisor pueden crear.

---

## `templates`
- `id`, `uuid`
- `company_id` NOT NULL
- `name` VARCHAR(120) NOT NULL
- `locale` VARCHAR(8) NOT NULL DEFAULT `es`
- `subject_template` VARCHAR(255)
- `body_template` TEXT NOT NULL
- `created_at`, `updated_at`
- UNIQUE `(company_id, name, locale)`

---

## `kb_articles`
- `id`, `uuid`
- `company_id` NOT NULL
- `category_id` BIGINT NULL
- `author_membership_id` BIGINT NULL
- `title` VARCHAR(180) NOT NULL
- `slug` VARCHAR(200) NOT NULL
- `body_markdown` TEXT NOT NULL
- `body_html_cached` TEXT NULL
- `tags` JSONB NULL DEFAULT `[]`
- `status` VARCHAR(32) NOT NULL DEFAULT `draft`
- `published_at` TIMESTAMPTZ NULL
- `created_at`, `updated_at`, `deleted_at`
- UNIQUE `(company_id, slug)`

---

## `slas`
- `id`
- `company_id` NOT NULL
- `name` VARCHAR(120) NOT NULL
- `priority` VARCHAR(16) NOT NULL
- `first_response_minutes` INTEGER NOT NULL
- `resolution_minutes` INTEGER NOT NULL
- `business_hours_only` BOOLEAN NOT NULL DEFAULT false
- `created_at`, `updated_at`
- UNIQUE `(company_id, priority)`

---

## `audit_logs`
- `id`
- `company_id` BIGINT NULL
- `actor_user_id` BIGINT NULL
- `actor_membership_id` BIGINT NULL
- `action` VARCHAR(120) NOT NULL
- `subject_type` VARCHAR(120) NULL
- `subject_id` BIGINT NULL
- `meta` JSONB NULL
- `ip` VARCHAR(45) NULL
- `user_agent` VARCHAR(255) NULL
- `created_at`

Estado implementado actual: tabla implementada en migraciones. La aceptacion de invitaciones por reset de contrasena registra `membership.accepted` con `company_id`, `actor_user_id`, `actor_membership_id`, subject membership y `meta.accepted_via=password_reset`.

---

## `notifications`
- `id`, `uuid`
- `company_id` BIGINT NULL
- `user_id` BIGINT NOT NULL
- `membership_id` BIGINT NULL
- `type` VARCHAR(120) NOT NULL
- `data` JSONB NOT NULL
- `read_at` TIMESTAMPTZ NULL
- `created_at`

---

## `backup_runs`
- `id`, `uuid`
- `status` VARCHAR(32) NOT NULL — `queued|running|succeeded|failed|pruned`
- `destination` VARCHAR(64) NOT NULL — `local|s3|custom`
- `started_at` TIMESTAMPTZ NULL
- `finished_at` TIMESTAMPTZ NULL
- `size_bytes` BIGINT NULL
- `error` TEXT NULL
- `meta` JSONB NULL
- `created_at`, `updated_at`

Estado implementado actual: tabla implementada en migraciones. `/admin` usa el ultimo registro `succeeded` para mostrar estado de backup y habilitar rollback cuando `meta.rollback_available=true`. El pruning de retencion local marca registros antiguos como `pruned`, elimina rutas privadas del `meta` y fuerza `meta.rollback_available=false`.

---

## `update_checks`
- `id`
- `current_version` VARCHAR(40) NOT NULL
- `latest_version` VARCHAR(40) NULL
- `checked_at` TIMESTAMPTZ NOT NULL
- `payload` JSONB NULL
- `created_at`

---

## `telemetry_reports`
- `id`
- `installation_id` UUID NOT NULL
- `payload` JSONB NOT NULL
- `sent_at` TIMESTAMPTZ NULL
- `created_at`

Solo existe/usa si la telemetria fue activada explicitamente.

---

## Tablas estandar Laravel
- `jobs`
- `failed_jobs`
- `password_reset_tokens`
- `sessions` si no se usa Redis
- `cache` opcional

## Fuera de v1
- Tablas comerciales de billing, suscripciones, pagos o webhooks de pago.
