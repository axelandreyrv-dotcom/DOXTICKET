# Modulo Tickets — DoxTicket

## Proposito
Describir el nucleo de tickets.

## Ambito
- Creacion por correo.
- Creacion manual secundaria.
- Lista de todos los tickets activos.
- Workspace principal de trabajo en `/app/tickets`.
- Detalle con hilo, eventos, adjuntos y metadatos.
- Asignacion manual.
- Estados, prioridades, tipos y categorias.
- Notas internas.
- Fusion de tickets.

## Estados
- `new` — nuevo, hasta que alguien lo abre.
- `open` — activo.
- `pending` — esperando informacion o accion externa; no se divide entre interno/cliente en v1.
- `resolved` — resuelto.
- `closed` — cerrado; solo despues de resuelto.
- `merged` — fusionado.
- `trashed` — papelera/soft delete.

## Reglas
- Al entrar por correo queda `new`.
- Al abrir por primera vez en `/app/tickets/{ticket}` pasa a `open`, registra `first_opened_at` y genera evento interno.
- Responder no cambia automaticamente a `pending`.
- Si cliente responde a `resolved`/`closed`, vuelve a `open` y registra evento de correo.
- Si cliente responde a ticket activo, queda `open`.
- Para cerrar debe pasar primero por `resolved`.

## Lista principal
- Vista principal: todos los tickets activos.
- Es la primera pantalla operativa tras login o seleccion de empresa.
- `/app/dashboard` redirige aqui como compatibilidad.
- Busqueda simple por clave visible, asunto o correo del solicitante.
- La busqueda es el unico control visible principal de la lista para mantener una vista rapida.
- Filtros implementados por backend/URL: estado, agente, prioridad, tipo, fuente y SLA vencido.
- El filtro de agente incluye cualquier agente, yo, sin asignar y agentes activos de la empresa.
- Cada fila muestra la clave visible y permite copiarla sin abrir el detalle.
- Accion rapida: asignarse.
- Otras acciones viven en detalle.

## Identificador
- Ticket visible: `DT-123`.
- Se usa en asunto: `[DT-123]`.
- Debe ser facil de buscar por llamada telefonica.
- En la lista y el detalle del ticket se puede copiar la clave visible con un boton accesible y estado anunciable.

## Prioridad
- La decide el agente.
- Valores: baja, media, alta, urgente.

## Tipo
- Clasifica el ticket sin depender del origen.
- Valores v1: pregunta, incidente, problema, solicitud.
- `source` conserva el origen real: correo, manual o sistema; es filtrable pero no editable desde el detalle en v1.

## Asignacion
- Manual.
- Agente puede asignarse.
- Admin/supervisor pueden asignar a otros.
- La asignacion se guarda contra `assigned_to_membership_id`, no solo contra usuario global.
- Esto evita ambiguedad cuando una persona pertenece a varias empresas.
- Ruta implementada para asignarse: `POST /app/tickets/{ticket}/assign-self`.
- Esta accion usa la membresia activa de la sesion y no acepta `assigned_to_membership_id` confiable desde el cliente.
- La asignacion genera evento interno `ticket.assigned_self`.
- Ruta implementada para propiedades: `PATCH /app/tickets/{ticket}/properties`.
- El panel lateral permite asignar a `Sin asignar` o a cualquier agente activo de la empresa seleccionada.
- La asignacion a otros agentes valida que la membresia pertenezca a la empresa activa y genera `ticket.assigned`.

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
- El detalle muestra marcador `DT-123`, boton para copiar la clave, asunto, solicitante, prioridad, estado, agente, categoria, hilo de mensajes y eventos.
- El hilo distingue visualmente correos entrantes, respuestas enviadas y notas internas con etiquetas claras, autor, correo cuando exista y fecha del mensaje.
- Las notas internas se agregan con `POST /app/tickets/{ticket}/messages`.
- Las respuestas publicas por correo se agregan con `POST /app/tickets/{ticket}/replies`.
- Las respuestas publicas pueden incluir adjuntos seguros opcionales; se envian junto al correo y se guardan asociados al mensaje outbound.
- Los adjuntos privados se agregan con `POST /app/tickets/{ticket}/attachments`.
- Los adjuntos privados se descargan con `GET /app/attachments/{uuid}/download`.
- Los adjuntos entrantes por correo se asocian al mensaje publico recibido y usan el mismo almacenamiento privado.
- Los adjuntos salientes de respuesta se asocian al mensaje publico outbound y usan el mismo almacenamiento privado.
- Las notas no aceptan `company_id` confiable desde el cliente; la empresa sale de la sesion activa.
- Las respuestas por correo tampoco aceptan `company_id`; usan la cuenta activa de la empresa seleccionada.
- Los adjuntos tampoco aceptan `company_id`; se guardan bajo la empresa activa y se descargan solo desde el tenant activo.
- Los cambios de estado se hacen con `PATCH /app/tickets/{ticket}/status`.
- Los cambios combinados de estado, prioridad, tipo y agente se hacen con `PATCH /app/tickets/{ticket}/properties`.
- Los errores de validacion del detalle se muestran junto al campo que los origina y se asocian con `aria-invalid`, `aria-describedby`, `role="alert"` y `aria-live="polite"`.
- `closed` solo se acepta si el ticket esta en `resolved`.
- El detalle muestra un panel lateral de propiedades editable con estado, prioridad, tipo y agente.
- La fuente se muestra como dato de auditoria y no se edita manualmente en v1.
- Los formularios de creacion manual y detalle declaran metadatos explicitos de navegador: `autocomplete="off"` en campos internos y `spellcheck="false"` en correos/claves visibles.

## Fusion
- Pueden fusionar agentes, supervisores y admins.
- Solo entre tickets de la misma empresa.
- Rapida, con confirmacion simple.
- El secundario queda `merged`.
- Respuestas futuras al secundario van al principal.
- Se audita.

Estado implementado actual:
- Ruta implementada: `POST /app/tickets/{ticket}/merge`.
- El formulario vive en el detalle del ticket y pide la clave visible del ticket principal, por ejemplo `DT-123`.
- El formulario de fusion usa `data-confirm` con el modal accesible global antes de enviar la accion.
- El servidor busca el ticket principal dentro de la empresa activa y no acepta `company_id` desde el cliente.
- No se permite fusionar un ticket contra si mismo, contra otra empresa ni contra un ticket que ya esta fusionado.
- El ticket secundario guarda `status=merged`, `merged=true`, `merged_into_ticket_id`, `merged_at` y `merged_by_membership_id`.
- La fusion registra eventos `ticket.merged` en el secundario y `ticket.merge_received` en el principal.
- El procesador de correo redirige respuestas entrantes al ticket principal cuando el marcador o headers apuntan a un ticket fusionado.

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
- `/app/tickets` muestra la lista de tickets activos de la empresa seleccionada, con claves copiables, busqueda visible por clave/asunto/correo y paginacion.
- Los filtros por estado, agente, prioridad, tipo, fuente y SLA siguen disponibles por query string para vistas/enlaces internos, pero no aparecen como controles principales.
- `/app/tickets` reemplaza al dashboard como workspace principal para saber que atender ahora.
- `/app/tickets` muestra accion rapida `Asignarme` para tickets sin agente.
- `/app/tickets/create` permite crear tickets manuales con solicitante, correo, asunto, prioridad, tipo, categoria y agente.
- `/app/tickets/{ticket}` permite abrir el ticket en pagina completa, copiar la clave visible, ver hilo/actividad/metadatos, agregar nota interna y editar propiedades.
- `/app/tickets/{ticket}` permite responder al solicitante por correo si existe una cuenta activa del tenant y el ticket tiene correo de solicitante; la respuesta acepta adjuntos seguros opcionales.
- La clave visible se genera como `DT-<numero>` por empresa.
- La primera version de creacion manual registra evento interno `ticket.created_manual`.
- La primera apertura de un ticket `new` registra `ticket.opened`.
- La asignacion manual a la membresia activa registra `ticket.assigned_self`.
- La asignacion desde propiedades registra `ticket.assigned`.
- Los cambios de prioridad y tipo registran `ticket.priority_changed` y `ticket.type_changed`.
- Las notas internas registran `ticket.note_added`.
- Las respuestas por correo registran `ticket.reply_sent`, se muestran en el hilo como mensajes de correo y asocian sus adjuntos al mensaje outbound.
- La fusion registra `ticket.merged` y `ticket.merge_received`.
- Los adjuntos permitidos registran `ticket.attachment_added`; los tipos bloqueados registran `ticket.attachment_blocked`.
- Los cambios de estado registran `ticket.status_changed`.
- Los eventos de tickets alimentan el panel `/app/activity`.
- El aislamiento se aplica con scope de tenant y tests de regresion para evitar filtrar datos de otra empresa.
- El hilo ya muestra etiquetas profesionales por direccion del mensaje: correo entrante, respuesta enviada y nota interna.

## Relacion con otros documentos
- `Correo.md`
- `Dashboard.md`
- `SLA.md`
- `04 - Seguridad/Seguridad de Archivos.md`
