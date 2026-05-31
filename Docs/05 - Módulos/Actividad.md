# Modulo Actividad — DoxTicket

## Proposito
Mostrar un historial operativo claro de lo que ocurre dentro de la empresa activa.

## Alcance v1
- Ruta: `/app/activity`.
- Vista cronologica de eventos recientes.
- Base inicial alimentada por `ticket_events`.
- Filtros por tipo: todo, creacion, estados, asignacion, notas y correo.
- Enlaces directos al ticket usando la clave visible `DT-123`.
- Paginacion para evitar listas pesadas.

## Reglas
- La actividad siempre se filtra por `company_id` desde la empresa activa.
- No se acepta `company_id` desde el cliente.
- Los eventos sin actor visible se muestran como `Sistema`.
- Si el ticket fue eliminado con soft delete, el evento puede seguir enlazando mientras el registro exista.
- En v1 no se muestran cuerpos de notas ni contenido de correos en esta vista para reducir exposicion de datos.

## Eventos iniciales
- `ticket.created_manual` — creacion manual.
- `ticket.created_from_mail` — creacion por correo.
- `ticket.opened` — primera apertura.
- `ticket.status_changed` — cambio de estado.
- `ticket.assigned_self` — asignacion propia.
- `ticket.note_added` — nota interna agregada.
- `ticket.mail_message_added` — mensaje de correo registrado.

## UX
- La pantalla responde a la pregunta: que paso en la operacion.
- No reemplaza al dashboard, que responde: que atender ahora.
- El diseno debe ser una lista sobria, escaneable y con acciones de navegacion claras.

## Estado implementado actual
- `/app/activity` muestra eventos de tickets de la empresa activa.
- La navegacion superior autenticada incluye `Actividad`.
- La vista enlaza cada evento al detalle del ticket cuando existe.
- Los tests cubren listado, aislamiento por tenant, filtros y navegacion.

## Relacion
- `Tickets.md`
- `Dashboard.md`
- `02 - Arquitectura/Frontend.md`
