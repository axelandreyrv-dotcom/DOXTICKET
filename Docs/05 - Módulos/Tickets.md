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
- Al abrir por primera vez puede pasar a `open` y registrar `first_opened_at`.
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

## Creacion manual
- Secundaria frente al correo.
- Permite ticket interno o externo.
- No hay limites comerciales por plan en v1.

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

## Relacion con otros documentos
- `Correo.md`
- `Dashboard.md`
- `SLA.md`
- `04 - Seguridad/Seguridad de Archivos.md`
