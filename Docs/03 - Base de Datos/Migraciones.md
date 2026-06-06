# Migraciones — DoxTicket

## Proposito del documento
Definir el flujo de migraciones de DoxTicket v1 self-hosted.

## Herramienta
- Laravel migrations.
- Driver oficial: PostgreSQL.

## Convenciones
- Nombre: `YYYY_MM_DD_HHMMSS_<verb>_<table_or_subject>.php`.
- Una migracion por cambio logico.
- Reversibles cuando sea razonable.
- Cambios destructivos requieren backup y nota en release.

## Orden recomendado inicial
1. `create_system_settings_table`
2. `create_companies_table`
3. `create_users_table`
4. `create_memberships_table`
5. `create_mail_accounts_table`
6. `create_categories_table`
7. `create_templates_table`
8. `create_slas_table`
9. `create_tickets_table`
10. `create_ticket_messages_table`
11. `create_ticket_events_table`
12. `create_attachments_table`
13. `create_kb_articles_table` **Base implementada.**
14. `create_audit_logs_table`
15. `create_notifications_table`
16. `create_backup_runs_table`
17. `create_update_checks_table`
18. `create_telemetry_reports_table`
19. Tablas Laravel: jobs, failed_jobs, password_reset_tokens, sessions/cache si aplica.
20. `audit_logs` para trazabilidad general de acciones sensibles fuera de tickets.

## Seeders
- `SystemSettingsSeeder`
- `DefaultCategoriesSeeder`
- `DefaultTemplatesSeeder`
- `DefaultSlaSeeder`
- `DemoDataSeeder` opcional y solo bajo decision explicita en setup/local.

No seedear configuracion comercial de billing en v1.

## Setup post-migracion
`/setup` crea:
- Superadmin.
- Empresa inicial.
- Membresia `admin` del superadmin en la empresa inicial.
- Configuracion base.

## Estado implementado
- Migraciones iniciales creadas para `system_settings`, `companies`, `users` extendido y `memberships`.
- `users` incluye `uuid`, `is_superadmin`, campos de 2FA opcional, estado activo, locale, zona horaria, ultima empresa activa y soft deletes.
- `memberships` incluye `uuid`, `company_id`, `user_id`, `role`, `status`, invitacion, aceptacion, ultima seleccion y preferencias JSON.
- Migracion inicial de tickets creada para `categories`, `tickets`, `ticket_messages`, `ticket_events` y `attachments`.
- `tickets` incluye claves visibles `DT-123`, estados/prioridades, asignacion por `assigned_to_membership_id`, origen, fusion futura, fechas operativas y soft deletes.
- Migracion incremental agregada para `tickets.ticket_type` con indice `(company_id, ticket_type, status)`.
- Migracion incremental agregada para `ticket_messages.external_image_urls`.
- `ticket_messages` conserva visibilidad, direccion, cuerpos texto/HTML, headers de correo, bloqueo de imagenes externas y URLs bloqueadas para apertura manual.
- `ticket_events` audita eventos internos por `actor_user_id` y `actor_membership_id`.
- `attachments` queda preparado para almacenamiento privado y bloqueo por privacidad/seguridad.
- Migracion inicial de `mail_accounts` creada para una cuenta IMAP/SMTP por empresa.
- `mail_accounts.password_encrypted` y futuros tokens OAuth se manejan cifrados por el modelo Eloquent.
- Migracion incremental agregada para metadatos OAuth de `mail_accounts`: `oauth_provider_user_id`, `oauth_scopes` y `oauth_connected_at`.
- Las bases locales SQLite de desarrollo (`database/*.sqlite`) deben quedar fuera de Git.

## Reglas de cambio
- Antes de update: verificar backup reciente.
- Migraciones destructivas deben estar marcadas como no rollback automatico.
- Indices grandes en produccion deben crearse con estrategia segura.

## Relacion con otros documentos
- `Tablas.md`
- `Índices.md`
- `07 - Infraestructura/Backups.md`
- `07 - Infraestructura/Deploy.md`
