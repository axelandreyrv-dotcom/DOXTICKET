# Modulo Dashboard — DoxTicket

## Proposito
Definir el dashboard de `/app/dashboard`.

## Objetivo UX
Ayudar a saber que atender ahora mismo. Debe ser completo, bonito, calmado y accionable.

## Audiencia
- Agente: dashboard personal por defecto.
- Admin/Supervisor: dashboard global con opcion de metricas por agente.
- Si el usuario cambia empresa activa, el dashboard cambia completamente de contexto.

## Bloques

### Resumen del dia
- Tickets nuevos hoy.
- Tickets abiertos.
- Tickets en progreso.
- Tickets resueltos hoy.
- Tickets reabiertos hoy.

### Atencion inmediata
- Sin asignar.
- Urgentes.
- Criticos.
- Vencidos por SLA.
- Reabiertos.

### Lista accionable
- Tickets nuevos.
- Mis tickets.
- Tickets activos recientes.
- Boton principal: Ver tickets.

### Metricas admin
- Tickets por agente.
- Tiempo medio de primera respuesta.
- Tiempo medio de resolucion.
- Distribucion por prioridad/categoria.

### Sistema / onboarding
- Si correo no esta configurado, mostrar onboarding.
- Alertas relevantes de health/backups/correo.

### Notificaciones
- In-app desde v1.
- Separadas por empresa.
- El contador visible corresponde a la empresa activa.

## Visualizacion
- Cards claras.
- Listas accionables.
- Sin graficos pesados en primera version usable.
- Graficos pequenos pueden agregarse despues.
- La base actual muestra resumen con datos reales del tenant, acceso a tickets y una lista inbox de tickets activos recientes.

## Estado implementado actual
- `/app/dashboard` muestra metricas `Nuevos`, `Activos`, `Asignados` y `Resueltos`.
- `Activos` excluye `new`, `resolved`, `closed`, `merged` y `trashed`; representa trabajo ya abierto/en curso/en espera/reabierto.
- `Asignados` cuenta tickets activos asignados a la membresia activa.
- La lista inbox trae hasta seis tickets activos recientes de la empresa seleccionada.
- Todas las consultas dependen del tenant resuelto en sesion.

## Estilo
- Minimalista.
- Calmo.
- Sin ruido.
- Buen contraste.
- Responsive.

## Rendimiento
- Queries indexadas.
- Cache corto para metricas pesadas.
- Evitar N+1.

## Relacion con otros documentos
- `Tickets.md`
- `SLA.md`
- `08 - Diseño/UI UX.md`
