# Indices — DoxTicket

## Proposito del documento
Listar indices recomendados para PostgreSQL.

## Reglas generales
- Toda tabla multiempresa lleva indice por `company_id`.
- Filtros combinados usan indices compuestos con `company_id` primero.
- `uuid` y claves publicas tienen UNIQUE.
- Busquedas de texto pueden evolucionar a GIN/tsvector.

## `system_settings`
- `UNIQUE (key)`

## `companies`
- `UNIQUE (uuid)`
- `UNIQUE (slug)`
- `INDEX (status)`

## `users`
- `UNIQUE (uuid)`
- `UNIQUE (email)`
- `INDEX (is_superadmin)`
- `INDEX (is_active)`

## `memberships`
- `UNIQUE (uuid)`
- `UNIQUE (company_id, user_id)`
- `INDEX (user_id, status)`
- `INDEX (company_id, role)`
- `INDEX (company_id, status)`

## `mail_accounts`
- `INDEX (company_id, is_active)`
- `INDEX (provider)`
- `UNIQUE (company_id, from_email)`
- `UNIQUE (company_id) WHERE is_active = true`

## `categories`
- `UNIQUE (company_id, name)`
- `INDEX (company_id, is_active)`

## `tickets`
- `UNIQUE (uuid)`
- `UNIQUE (company_id, public_number)`
- `UNIQUE (company_id, public_key)`
- `INDEX (company_id, status, last_activity_at DESC)`
- `INDEX (company_id, assigned_to_membership_id, status)`
- `INDEX (company_id, priority, status)`
- `INDEX (company_id, category_id)`
- `INDEX (company_id, requester_email)`
- `INDEX (company_id, sla_due_at)`
- `INDEX (company_id, merged_into_ticket_id)`
- `INDEX (company_id, deleted_at)`
- `INDEX (external_thread_id)`
- Busqueda simple usa `public_key`, `requester_email` y `subject` con indices normales cuando aplique.
- Full-text opcional: `tsvector`/GIN sobre `subject` si la busqueda simple no alcanza.

## `ticket_messages`
- `INDEX (company_id, ticket_id, created_at)`
- `INDEX (author_membership_id, created_at)`
- `INDEX (message_id_header)`
- `INDEX (in_reply_to_header)`
- `INDEX (direction)`
- `INDEX (visibility)`
- GIN opcional sobre `body_text` para busqueda full-text en mensajes.

## `ticket_events`
- `INDEX (company_id, ticket_id, created_at)`
- `INDEX (actor_membership_id, created_at)`
- `INDEX (type)`

## `attachments`
- `INDEX (company_id, ticket_id)`
- `INDEX (ticket_message_id)`
- `INDEX (checksum_sha256)`

## Estado implementado actual
- Implementados indices de `categories`, `tickets`, `ticket_messages`, `ticket_events` y `attachments` necesarios para lista activa, dashboard y auditoria inicial.
- La lista de tickets usa `company_id`, `status` y `last_activity_at`.
- Las metricas por agente usan `company_id`, `assigned_to_membership_id` y `status`.
- La busqueda full-text queda pendiente para una fase posterior.

## `templates`
- `UNIQUE (company_id, name, locale)`
- `INDEX (company_id, locale)`

## `kb_articles`
- `UNIQUE (company_id, slug)`
- `INDEX (company_id, status, published_at)`
- `INDEX (company_id, category_id)`

## `slas`
- `UNIQUE (company_id, priority)`

## `audit_logs`
- `INDEX (company_id, created_at)`
- `INDEX (actor_user_id, created_at)`
- `INDEX (actor_membership_id, created_at)`
- `INDEX (action)`
- `INDEX (subject_type, subject_id)`

## `notifications`
- `INDEX (company_id, user_id, read_at)`
- `INDEX (company_id, membership_id, read_at)`
- `INDEX (user_id, created_at)`

## `backup_runs`
- `INDEX (status, created_at)`
- `INDEX (destination, created_at)`

## `update_checks`
- `INDEX (checked_at)`

## `telemetry_reports`
- `INDEX (sent_at)`
- `INDEX (created_at)`

## Anti-patrones
- Queries multiempresa sin `company_id`.
- Indices solapados sin razon.
- Ordenamientos frecuentes sin indice.
