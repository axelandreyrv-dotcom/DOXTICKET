# Roles — DoxTicket

## Proposito del documento
Definir roles tecnicos y permisos.

## Roles
- `superadmin`: administra la instalacion desde `/admin`.
- `admin`: administra una empresa.
- `supervisor`: gestiona operacion de tickets y configuraciones no criticas.
- `agent`: atiende tickets.

## Reglas
- `users.is_superadmin` habilita `/admin`.
- `memberships.role` define el rol dentro de cada empresa.
- Un usuario puede tener roles distintos en empresas distintas.
- Superadmin puede tener memberships y usar `/app` como usuario normal de una empresa.

## Capacidades

### Superadmin
- Gestionar empresas.
- Ver usuarios globales y memberships.
- Activar/desactivar usuarios globales sin poder desactivar su propia cuenta.
- Cambiar roles y estado de memberships existentes sin dejar una empresa sin admin activo.
- Ver health.
- Configurar backups.
- Ver version/update.
- Ejecutar rollback si aplica.
- Gestionar superadmins.
- Ver auditoria.

### Admin de empresa
- Gestionar empresa.
- Invitar usuarios.
- Configurar correo.
- Gestionar categorias, plantillas, firma y SLA.
- Ver el workspace de Tickets de la empresa.

### Supervisor
- Gestionar tickets.
- Reasignar tickets.
- Gestionar categorias/plantillas si se habilita.
- Ver metricas de equipo.

### Agent
- Ver tickets de su empresa.
- Asignarse tickets.
- Responder.
- Crear tickets manuales.
- Fusionar tickets segun decision v1.
- Agregar notas internas.

## Policies
Cada policy valida:
- `company_id` del recurso.
- `membership_id` activo en sesion.
- `memberships.role`.

## Gates sugeridos
- `admin.system`
- `manage-company`
- `manage-users`
- `manage-mail`
- `manage-backups`
- `view-health`
- `merge-ticket`

## Relacion con otros documentos
- `01 - Producto/Roles y Permisos.md`
- `Modelo de Seguridad.md`
