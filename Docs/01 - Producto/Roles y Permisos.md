# Roles y Permisos — DoxTicket

## Proposito
Definir roles de DoxTicket self-hosted.

## Tipos

### Instalacion
- **Superadmin** — administra toda la instalacion desde `/admin`.
- Una cuenta superadmin tambien puede tener membresias normales en empresas para usar `/app`.

### Empresa
- **Administrador de empresa** — gestiona una empresa.
- **Supervisor** — coordina operacion de soporte.
- **Agente / Tecnico** — atiende tickets.

### Usuario final
- No tiene cuenta en v1.
- Interactua por correo.

## Empresa y configuracion

| Accion | Admin | Supervisor | Agente |
|---|---|---|---|
| Editar datos de empresa | Si | No | No |
| Invitar usuarios | Si | No | No |
| Cambiar roles | Si | No | No |
| Desactivar usuarios | Si | No | No |
| Configurar correo | Si | No | No |
| Gestionar categorias | Si | Si | No |
| Gestionar plantillas/firma | Si | Si | No |
| Gestionar SLA | Si | Si | No |

## Tickets

| Accion | Admin | Supervisor | Agente |
|---|---|---|---|
| Ver tickets de la empresa | Si | Si | Si |
| Crear ticket manual | Si | Si | Si |
| Asignarse ticket | Si | Si | Si |
| Asignar a otros | Si | Si | No |
| Responder | Si | Si | Si |
| Notas internas | Si | Si | Si |
| Cambiar prioridad/categoria | Si | Si | Si |
| Cambiar estado | Si | Si | Si |
| Fusionar tickets | Si | Si | Si |
| Eliminar ticket | Si | Si | No |

## Workspace de Tickets

| Accion | Admin | Supervisor | Agente |
|---|---|---|---|
| Ver inbox de tickets de la empresa | Si | Si | Si |
| Filtrar trabajo pendiente | Si | Si | Si |
| Ver metricas por agente | Si | Si | No |

## Superadmin

| Accion | Superadmin |
|---|---|
| Gestionar empresas | Si |
| Eliminar suavemente empresas | Si |
| Ver health | Si |
| Configurar backups | Si |
| Ver updates/version | Si |
| Ejecutar rollback si aplica | Si |
| Gestionar telemetria | Si |
| Ver auditoria | Si |

## Reglas transversales
1. Toda accion de empresa respeta `company_id`.
2. El rol de empresa sale de `memberships.role`.
3. Un usuario puede tener roles distintos en empresas distintas.
4. La busqueda global y las notificaciones se limitan a la empresa activa.
5. Acciones criticas se registran en auditoria con `company_id`, `user_id` y `membership_id` cuando aplique.
6. No hay permisos de billing en v1.

## Relacion
- `04 - Seguridad/Roles.md`
- `04 - Seguridad/Modelo de Seguridad.md`
