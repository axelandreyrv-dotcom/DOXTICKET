# Modulo SLA — DoxTicket

## Proposito
Definir SLA por empresa y prioridad.

## Decision v1
SLA esta disponible para todas las instalaciones y empresas. No hay diferenciacion por planes.

## Datos
- Prioridad.
- Tiempo de primera respuesta.
- Tiempo de resolucion.
- Horario laboral opcional/futuro.

## Defaults sugeridos
| Prioridad | Primera respuesta | Resolucion |
|---|---|---|
| Baja | 24 h | 7 dias |
| Media | 8 h | 3 dias |
| Alta | 4 h | 1 dia |
| Urgente | 1 h | 8 h |
| Critica | 30 min | 4 h |

## UI
- Badge verde: saludable.
- Badge amarillo: pronto a vencer.
- Badge rojo: vencido.
- Tooltip con tiempo restante/vencido.

## Job
`ScheduleSlaCheckJob` cada 5 minutos.

## Auditoria
- Cambios de SLA.
- `sla_breached` en eventos.

## Relacion
- `Dashboard.md`
- `Tickets.md`
