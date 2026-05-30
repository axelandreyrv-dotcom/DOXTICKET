# Autenticacion — DoxTicket

## Proposito del documento
Definir login, recuperacion, sesiones, verificacion y 2FA.

## Identidad
- Login con email global.
- La empresa activa se elige despues de autenticar cuando hay varias membresias.
- Email unico globalmente en la instalacion.
- `users` no tiene `company_id`.
- El acceso a empresas vive en `memberships`.
- Superadmin se marca con `users.is_superadmin`.
- Un superadmin tambien puede tener memberships para usar `/app`.

## Hash de contrasenas
- bcrypt o argon2id.
- Minimo recomendado: 10 caracteres.
- Nunca almacenar ni loguear contrasenas en claro.

## Setup inicial
- `/setup` crea el primer superadmin.
- 2FA no es obligatorio en el primer login, pero debe estar disponible.
- Setup se bloquea tras finalizar.

## Login
- `/login`.
- Mensaje generico en fallo.
- Rate limit.
- Rotacion de session ID.
- `last_login_at`.
- Auditoria.
- Si el usuario tiene varias memberships activas, debe elegir empresa antes de entrar a `/app`.
- La sesion guarda `active_membership_id`.
- La busqueda, dashboard y notificaciones se limitan a la empresa activa.

## 2FA
- TOTP.
- Opcional para todos los roles en v1.
- Codigos de recuperacion cifrados.
- Puede activarse despues desde perfil.

## Recuperacion de contrasena
- Token de un solo uso.
- TTL maximo 60 minutos.
- Respuesta generica para evitar enumeracion.
- Invalida sesiones tras cambiar contrasena.

## Sesiones
- Redis recomendado.
- Cookies `HttpOnly`.
- `Secure` cuando la instalacion usa HTTPS.
- `SameSite=Lax`.

## Restricciones
- Empresa `disabled` o `archived`: sin acceso normal.
- Membership `disabled`: sin acceso a esa empresa, aunque el usuario siga activo en otras.
- Superadmin puede reactivar/gestionar desde `/admin`.

## Auditoria
- `auth.login_success`
- `auth.login_failed`
- `auth.logout`
- `auth.password_reset_requested`
- `auth.password_changed`
- `auth.two_factor_enabled`
- `auth.two_factor_disabled`

## Relacion con otros documentos
- `Rate Limiting.md`
- `Roles.md`
- `Modelo de Seguridad.md`
