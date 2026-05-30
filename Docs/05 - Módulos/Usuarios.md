# Modulo Usuarios — DoxTicket

## Proposito
Describir usuarios internos y superadmins.

## Tipos
- Usuario global: identidad unica por email.
- Superadmin: permiso global en `users.is_superadmin`.
- Membership admin: administra una empresa.
- Membership supervisor: coordina soporte en una empresa.
- Membership agent: atiende tickets en una empresa.

No hay usuarios finales con cuenta en v1.

## Invitacion
1. Admin invita usuario.
2. Define nombre/correo/rol.
3. Si el email ya existe, se reutiliza la cuenta y se crea una nueva membership.
4. Si no existe, se crea usuario invitado y membership `invited`.
5. Se envia invitacion por SMTP global o sistema configurado.
6. Usuario define contrasena si aun no tenia cuenta.
7. Puede activar 2FA despues.

## Reglas
- Email unico global.
- Un usuario puede pertenecer a varias empresas.
- El rol vive en `memberships.role`.
- Desactivar una membership quita acceso solo a esa empresa.
- Desactivar el usuario global invalida acceso a todas.
- No dejar empresa sin admin activo.
- Desactivar usuario invalida sesiones.
- Tickets asignados apuntan a `assigned_to_membership_id`.
- Si se desactiva una membership, sus tickets pueden quedar sin asignar o reasignarse.

## Perfil
- Nombre.
- Idioma.
- Zona horaria.
- Cambio de contrasena.
- 2FA opcional.
- Preferencias globales.
- Preferencias por empresa en `memberships.preferences`.

## Selector de empresa
- Visible en la barra superior.
- Obligatorio cuando el usuario tiene mas de una membership activa.
- Cambiar empresa cambia dashboard, tickets, KB, busqueda y notificaciones.

## Auditoria
- `user.invited`
- `membership.created`
- `membership.disabled`
- `membership.role_changed`
- `user.role_changed`
- `user.deactivated`
- `user.reactivated`
- `user.password_changed`
- `user.two_factor_enabled`

## Relacion con otros documentos
- `04 - Seguridad/Autenticación.md`
- `04 - Seguridad/Roles.md`
