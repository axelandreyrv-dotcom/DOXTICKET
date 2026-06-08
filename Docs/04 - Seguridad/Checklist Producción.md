# Checklist Produccion / Self-Hosted — DoxTicket

## Proposito del documento
Checklist para instalaciones productivas o releases estables.

## Aplicacion
- [ ] `APP_KEY` existe.
- [ ] `APP_DEBUG=false` en produccion.
- [ ] `.env` no esta expuesto.
- [ ] `/setup` esta bloqueado tras instalar.
- [ ] Version instalada visible.
- [ ] Caches generadas cuando aplique.
- [ ] Migraciones aplicadas.

## Setup
- [ ] Idioma configurado.
- [ ] Superadmin creado.
- [ ] Empresa inicial creada.
- [ ] Membresia admin inicial creada para la empresa inicial.
- [ ] SMTP global probado o marcado como omitido.
- [ ] Telemetria apagada por defecto o activada con consentimiento explicito.

## Autenticacion
- [ ] Rate limit en login/setup/reset.
- [ ] Session ID rota al login.
- [ ] Recuperacion con token de un solo uso.
- [x] 2FA disponible.
- [ ] 2FA recomendado para superadmins.

## Multiempresa
- [ ] Middleware tenant activo.
- [ ] Tenant activo se deriva de `active_membership_id`.
- [ ] Trait `BelongsToCompany` en modelos multiempresa.
- [ ] Policies por modelo.
- [ ] Tests IDOR pasan.
- [ ] Tests de usuario multiempresa pasan.
- [ ] `withoutTenant()` solo en admin auditado.

## Correo
- [ ] SMTP global funciona o esta omitido conscientemente.
- [ ] Cuenta de soporte por empresa probada.
- [ ] Ingesta con lock por cuenta.
- [ ] Confirmacion automatica activa.
- [ ] Marcador `[DT-123]` activo.
- [ ] Sanitizacion HTML.
- [ ] Imagenes externas bloqueadas.
- [ ] Prevencion de loops.

## Adjuntos
- [ ] Storage privado.
- [ ] Validacion MIME/tamano.
- [ ] Ejecutables bloqueados.
- [ ] Descarga por policy.

## Admin / Operacion
- [x] `/admin` y `/admin/health` requieren superadmin autenticado y activo.
- [x] `/admin/health` muestra `APP_KEY`, `APP_DEBUG`, setup bloqueado, PostgreSQL, cache/Redis, storage, colas, scheduler, workers, SMTP global, cuentas de correo y backups sin exponer secretos.
- [x] Aviso de version nueva se consulta mediante GitHub Releases sin datos sensibles y se muestra desde cache local.
- [x] `/admin/health` muestra workers, scheduler y SMTP global.
- [ ] Backups configurados o decision documentada.
- [x] Ultimo backup visible.
- [x] Historial reciente de backups visible sin rutas privadas.
- [x] Backup manual local protegido por superadmin.
- [x] Aviso de version nueva funciona.
- [x] Rollback visible.

## Docker
- [ ] Volumenes persistentes configurados.
- [ ] PostgreSQL/Redis no expuestos innecesariamente.
- [ ] Variables sensibles fuera del repo.

## Ubuntu manual
- [ ] UFW configurado.
- [ ] SSH por clave.
- [ ] Redis/PostgreSQL solo localhost.
- [ ] Nginx bloquea `.env`, `.git`, `storage/`, `vendor/`.

## Tests y calidad
- [ ] Suite de tests pasa.
- [ ] Tests de 2FA pasan.
- [ ] Static analysis pasa.
- [ ] Build assets pasa.
- [ ] Secret scan pasa.

## Seguridad publica
- [ ] `SECURITY.md` existe.
- [ ] Reportes privados van a `axelandreyrv@outlook.com`.
