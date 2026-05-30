# Modelo de Seguridad — DoxTicket

## Proposito del documento
Definir principios y mecanismos transversales de seguridad para DoxTicket self-hosted.

## Principios
1. Seguridad por defecto.
2. Defensa en profundidad.
3. Aislamiento estricto por `company_id`.
4. Menos privilegios.
5. Sin secretos en logs, frontend o repositorio.
6. Adjuntos privados.
7. Setup seguro y bloqueado despues de instalar.
8. Trazabilidad con `audit_logs`.

## Modelo de amenazas

| Amenaza | Mitigacion |
|---|---|
| IDOR entre empresas | Tenant middleware + global scopes + policies + tests |
| Usuario con varias empresas opera en contexto equivocado | Selector de empresa claro + `active_membership_id` en sesion + auditoria |
| Setup expuesto despues de instalar | Bloqueo automatico + health check |
| Configuracion insegura | Bloquear `APP_DEBUG=true`, falta `APP_KEY`, permisos peligrosos |
| XSS por correos HTML | Sanitizacion allowlist + bloqueo de imagenes externas |
| Robo de adjuntos | Storage privado + rutas autorizadas |
| Duplicados o loops de correo | Headers + `[DT-123]` + locks + deteccion de auto-respuestas |
| Secretos filtrados | `.env` fuera de Git + scanners + filtros de logs |
| Telemetria sensible | Opt-in explicito + payload anonimo limitado |
| Update peligroso | Backup previo + versionado + rollback condicionado |

## Capas de seguridad

### Infraestructura
- Docker Compose con defaults seguros.
- Ubuntu manual con UFW/Fail2ban/SSH por clave.
- PostgreSQL y Redis no expuestos al exterior.
- HTTPS recomendado para dominio publico.
- LAN/intranet puede operar por HTTP local segun decision de instalacion.

### Aplicacion
- Middleware tenant.
- Policies por modelo.
- FormRequest.
- CSRF.
- Rate limiting.
- Sanitizacion HTML.
- Security headers cuando aplique.

Estado implementado actual: `InboundMailProcessor` limita deduplicacion/threading por `company_id`, redacta headers sensibles y bloquea loops basicos antes de crear tickets.

### Datos
- Passwords con bcrypt/argon2id.
- Credenciales SMTP/OAuth cifradas.
- Backups protegidos.
- Adjuntos fuera de `public/`.

### Setup
- `/setup` valida dependencias y permisos.
- Se bloquea tras finalizar.
- No debe poder recrear superadmin si ya existe instalacion completada.

### Auditoria
Registrar:
- Login, logout, fallos auth.
- Cambios de roles.
- Cambios de memberships.
- Configuracion de correo.
- Fusion/borrado de tickets.
- Acciones superadmin.
- Cambios de backup/update/telemetria.

## Reporte de vulnerabilidades
Reportar en privado a:

`axelandreyrv@outlook.com`

Ver `SECURITY.md`.

## OWASP
Cada release critica debe revisar:
- Access control.
- Crypto.
- Injection.
- Insecure design.
- Security misconfiguration.
- Vulnerable components.
- Auth failures.
- Software/data integrity.
- Logging/monitoring.
- SSRF.

## Relacion con otros documentos
- `Autenticación.md`
- `Roles.md`
- `Rate Limiting.md`
- `Gestión de Secretos.md`
- `Seguridad de Archivos.md`
- `Checklist Producción.md`
