# Modelo Entidad Relacion — DoxTicket

## Entidades principales

- **SystemSetting** — configuracion global de la instalacion.
- **Company** — empresa/sociedad/unidad dentro de una instalacion.
- **User** — identidad global de una persona.
- **Membership** — relacion usuario-empresa con rol.
- **MailAccount** — cuenta de soporte de una empresa.
- **Ticket** — solicitud de soporte.
- **TicketMessage** — mensaje publico o nota interna.
- **TicketEvent** — evento de timeline.
- **Attachment** — archivo adjunto privado.
- **Category** — categoria de ticket.
- **Template** — plantilla de respuesta.
- **Sla** — reglas de SLA por prioridad.
- **KbArticle** — articulo interno de base de conocimiento.
- **AuditLog** — auditoria.
- **Notification** — notificacion in-app.
- **BackupRun** — ejecucion de backup.
- **UpdateCheck** — consulta de nueva version.
- **TelemetryReport** — reporte anonimo opcional.

## Relaciones clave

```
Company 1 --- N Membership
User 1 --- N Membership
Company 1 --- N MailAccount
Company 1 --- N Ticket
Company 1 --- N Category
Company 1 --- N Template
Company 1 --- N Sla
Company 1 --- N KbArticle

Ticket 1 --- N TicketMessage
Ticket 1 --- N TicketEvent
Ticket 1 --- N Attachment
Ticket N --- 1 Category
Ticket N --- 1 Membership (assigned_to_membership_id)
Ticket N --- 1 MailAccount

TicketMessage 1 --- N Attachment
User 1 --- N AuditLog
Membership 1 --- N AuditLog
User 1 --- N Notification
Membership 1 --- N Notification
```

## Reglas multiempresa
- Todas las entidades de empresa llevan `company_id`.
- `users` no tiene `company_id`; la pertenencia vive en `memberships`.
- Superadmin se marca en `users.is_superadmin` y tambien puede tener memberships.
- `withoutTenant()` solo desde `/admin` y con auditoria.

## Fuera de modelo v1
No se modelan entidades comerciales de billing, suscripciones, pagos ni webhooks de pago.

## Relacion con otros documentos
- `Tablas.md`
- `Índices.md`
- `02 - Arquitectura/Multiempresa Self-Hosted.md`
