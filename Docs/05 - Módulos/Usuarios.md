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
6. Si la cuenta es nueva, el correo incluye enlace con token para definir contrasena.
7. Puede activar 2FA despues.

Estado implementado actual:
- `/admin/users/invite` registra invitaciones desde el portal superadmin.
- Si el correo no existe, se crea usuario global activo con contrasena aleatoria no revelada y locale `es`.
- Si el correo ya existe, se reutiliza la cuenta global sin cambiar su nombre.
- La membership queda en estado `invited`, con `invited_by_user_id` e `invited_at`.
- No se permite registrar dos memberships para la misma empresa y usuario.
- El correo de invitacion se envia por SMTP global como texto simple e incluye empresa, rol y enlace a `/login`, sin revelar contrasenas.
- Para usuarios nuevos, el correo incluye un enlace `/password/reset/{token}` con email precargado para definir la contrasena inicial.
- Para usuarios existentes, no se genera token nuevo y el correo solo orienta al login.
- Cuando un usuario define/restablece contrasena con token valido, sus memberships `invited` pasan a `active` para aceptar invitaciones pendientes.
- La aceptacion registra `accepted_at` y audit log `membership.accepted` con el contexto de usuario y membership.
- Si el envio SMTP falla, la invitacion se conserva y el panel muestra un aviso generico sin exponer credenciales.

## Reglas
- Email unico global.
- Un usuario puede pertenecer a varias empresas.
- El rol vive en `memberships.role`.
- Desactivar una membership quita acceso solo a esa empresa.
- Desactivar el usuario global invalida acceso a todas.
- El portal `/admin/users` permite a superadmins revisar usuarios globales, superadmins y membresias sin depender del tenant activo.
- El portal `/admin/users` permite activar/desactivar usuarios globales, pero bloquea que un superadmin desactive su propia cuenta.
- El portal `/admin/users` permite cambiar rol y estado de memberships existentes; solo acepta roles `admin`, `supervisor`, `agent` y estados `active`, `disabled`.
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
- Cambiar empresa cambia tickets, actividad, KB, busqueda y notificaciones.

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
