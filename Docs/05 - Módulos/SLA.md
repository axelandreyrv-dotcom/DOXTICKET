# Módulo SLA — DoxTicket

## Propósito
Definir SLA por empresa y prioridad.

## Decisión v1
SLA está disponible para todas las instalaciones y empresas. No hay diferenciación por planes.

## Datos
- Prioridad.
- Tiempo de primera respuesta.
- Tiempo de resolución.
- Horario laboral opcional/futuro.

## Defaults sugeridos
| Prioridad | Primera respuesta | Resolución |
|---|---|---|
| Baja | 24 h | 7 días |
| Media | 8 h | 3 días |
| Alta | 4 h | 1 día |
| Urgente | 1 h | 8 h |

## UI
- Badge verde: saludable.
- Badge amarillo: pronto a vencer.
- Badge rojo: vencido.
- Tooltip con tiempo restante/vencido.

## Job
`ScheduleSlaCheckJob` cada 5 minutos.

## Estado implementado actual
- Los tickets activos (`new`, `open`, `pending`) reciben `sla_due_at` automaticamente al crearse segun defaults de resolucion por prioridad: baja 7 dias, media 3 dias, alta 1 dia y urgente 8 horas.
- `/app/tickets?sla=overdue` filtra tickets activos vencidos de la empresa activa y muestra un indicador discreto `SLA vencido`.
- `App\Jobs\Tickets\ScheduleSlaCheckJob` corre cada 5 minutos desde el scheduler y registra `ticket.sla_breached` una sola vez por ticket vencido.
- Configuracion editable de SLA por empresa y horario laboral quedan pendientes.

## Auditoría
- Cambios de SLA.
- `ticket.sla_breached` en eventos.

## Relación
- `Tickets.md`
