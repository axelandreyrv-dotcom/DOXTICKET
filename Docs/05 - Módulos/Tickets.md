# Modulo Tickets — DoxTicket

## Proposito
Describir el nucleo de tickets.

## Ambito
- Creacion por correo.
- Creacion manual secundaria.
- Lista de todos los tickets activos.
- Detalle con hilo, eventos, adjuntos y metadatos.
- Asignacion manual.
- Estados, prioridades, categorias.
- Notas internas.
- Fusion de tickets.

## Estados
- `new` — nuevo, hasta que alguien lo abre.
- `open` — activo.
- `in_progress` — en trabajo.
- `waiting_customer` — esperando cliente, elegido manualmente.
- `waiting_internal` — esperando insumo interno.
- `resolved` — resuelto.
- `closed` — cerrado; solo despues de resuelto.
- `reopened` — cliente respondio tras resuelto/cerrado.
- `merged` — fusionado.
- `trashed` — papelera/soft delete.

## Reglas
- Al entrar por correo queda `new`.
- Al abrir por primera vez en `/app/tickets/{ticket}` pasa a `open`, registra `first_opened_at` y genera evento interno.
- Responder no cambia automaticamente a `waiting_customer`.
- Si cliente responde a `resolved`/`closed`, pasa a `reopened`.
- Si cliente responde a ticket activo, queda `open`.
- Para cerrar debe pasar primero por `resolved`.

## Lista principal
- Vista principal: todos los tickets activos.
- Filtros por estado, prioridad, responsable, categoria, fecha y texto.
- Accion rapida: asignarse.
- Otras acciones viven en detalle.

## Identificador
- Ticket visible: `DT-123`.
- Se usa en asunto: `[DT-123]`.
- Debe ser facil de buscar por llamada telefonica.

## Prioridad
- La decide el agente.
- Valores: baja, media, alta, urgente, critica.

## Asignacion
- Manual.
- Agente puede asignarse.
- Admin/supervisor pueden asignar a otros.
- La asignacion se guarda contra `assigned_to_membership_id`, no solo contra usuario global.
- Esto evita ambiguedad cuando una persona pertenece a varias empresas.
- Ruta implementada para asignarse: `POST /app/tickets/{ticket}/assign-self`.
- Esta accion usa la membresia activa de la sesion y no acepta `assigned_to_membership_id` confiable desde el cliente.
- La asignacion genera evento interno `ticket.assigned_self`.

## Creacion manual
- Secundaria frente al correo.
- Permite ticket interno o externo.
- No hay limites comerciales por plan en v1.
- Ruta implementada: `/app/tickets/create`.
- El formulario no envia `company_id`; el servidor usa la empresa activa de la sesion.
- La descripcion inicial se guarda como `ticket_messages` con `direction=internal` y `visibility=internal`.

## Detalle y notas internas
- Ruta implementada: `/app/tickets/{ticket}`.
- `{ticket}` puede ser el id interno o la clave visible `DT-123`; los enlaces de la UI usan la clave visible.
- La lista principal enlaza cada fila al detalle del ticket.
- El detalle muestra marcador `DT-123`, asunto, solicitante, prioridad, estado, agente, categoria, hilo de mensajes y eventos.
- Las notas internas se agregan con `POST /app/tickets/{ticket}/messages`.
- Las notas no aceptan `company_id` confiable desde el cliente; la empresa sale de la sesion activa.
- Los cambios de estado se hacen con `PATCH /app/tickets/{ticket}/status`.
- `closed` solo se acepta si el ticket esta en `resolved`.
- El detalle muestra `Asignarme` si el ticket no esta asignado a la membresia activa.

## Fusion
- Pueden fusionar agentes, supervisores y admins.
- Solo entre tickets de la misma empresa.
- Rapida, con confirmacion simple.
- El secundario queda `merged`.
- Respuestas futuras al secundario van al principal.
- Se audita.

## Papelera
- Soft delete interno en v1.
- Vista de papelera puede implementarse despues de la primera version usable.

## Detalle minimo
Debe mostrar:
- `DT-123`, asunto y estado.
- Solicitante.
- Responsable.
- Prioridad.
- Categoria.
- Hilo de mensajes.
- Notas internas.
- Adjuntos.
- Eventos.
- Acciones principales.

## Estado implementado actual
- `/app/tickets` muestra la lista de tickets activos de la empresa seleccionada, con filtro por estado y paginacion.
- `/app/tickets` muestra accion rapida `Asignarme` para tickets sin agente.
- `/app/tickets/create` permite crear tickets manuales con solicitante, correo, asunto, prioridad, categoria y agente.
- `/app/tickets/{ticket}` permite abrir el ticket en pagina completa, ver hilo/eventos/metadatos, agregar nota interna y cambiar estado.
- La clave visible se genera como `DT-<numero>` por empresa.
- La primera version de creacion manual registra evento interno `ticket.created_manual`.
- La primera apertura de un ticket `new` registra `ticket.opened`.
- La asignacion manual a la membresia activa registra `ticket.assigned_self`.
- Las notas internas registran `ticket.note_added`.
- Los cambios de estado registran `ticket.status_changed`.
- El aislamiento se aplica con scope de tenant y tests de regresion para evitar filtrar datos de otra empresa.
- Pendiente: asignar a otros agentes desde detalle, respuestas por correo saliente, adjuntos, fusion y pulido avanzado del hilo.

## Relacion con otros documentos
- `Correo.md`
- `Dashboard.md`
- `SLA.md`
- `04 - Seguridad/Seguridad de Archivos.md`
