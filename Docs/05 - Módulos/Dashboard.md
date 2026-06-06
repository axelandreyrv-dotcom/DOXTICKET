# Modulo Dashboard — DoxTicket

## Estado
Dashboard queda retirado como modulo visible de la app autenticada.

## Decision
`/app/tickets` es el workspace principal de DoxTicket. La lista tipo inbox concentra el trabajo diario: busqueda, filtros, tickets vencidos por SLA, asignacion rapida y entrada al detalle completo.

`/app/dashboard` se mantiene solo como ruta heredada para no romper enlaces antiguos, favoritos o redirects previos. Al acceder a esa ruta, la app redirige a `/app/tickets`.

## Motivo
Para un helpdesk IT self-hosted, la pantalla mas util no es un dashboard separado sino la cola de tickets que muestra que atender ahora. Quitar la seccion evita navegacion duplicada y mantiene la experiencia mas directa.

## Comportamiento esperado
- Login con una sola empresa activa redirige a `/app/tickets`.
- Seleccionar empresa activa redirige a `/app/tickets`.
- La marca DoxTicket en el shell autenticado enlaza a `/app/tickets`.
- La navegacion autenticada no muestra `Dashboard`.
- `/app/dashboard` redirige a `/app/tickets`.
- Los filtros de SLA vencido viven en `/app/tickets`.

## Relacion con otros documentos
- `Tickets.md`
- `SLA.md`
- `08 - Diseño/UI UX.md`
