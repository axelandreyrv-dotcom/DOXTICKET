# Modulo Superadmin — DoxTicket

## Proposito
Describir `/admin`, panel del administrador de la instalacion.

## Acceso
- Solo rol `superadmin`.
- 2FA opcional en v1.

## Pantallas

### Dashboard admin
- Version instalada.
- Aviso de nueva version estable.
- Estado general del sistema.
- Resumen de empresas, usuarios, tickets y jobs.

### Empresas
- Listar, crear, editar, desactivar, archivar.
- Ver usuarios/tickets por empresa.

### Usuarios superadmin
- Crear/editar/desactivar superadmins.
- Un superadmin es un usuario global con `is_superadmin=true`.
- Puede tener memberships normales para usar `/app`.

### Health
- PostgreSQL.
- Redis.
- Storage.
- Colas.
- Workers.
- Scheduler.
- SMTP global.
- Cuentas de correo por empresa.
- Backups.
- Setup bloqueado.
- `APP_DEBUG`.
- `APP_KEY`.

### Backups
- Configurar destino/frecuencia/retencion.
- Ver ultimo backup.
- Ejecutar backup manual.
- Ver historial `backup_runs`.

### Updates
- Mostrar version instalada.
- Consultar GitHub Releases.
- Mostrar ultima version estable.
- Mostrar changelog.
- Verificar backup reciente antes de actualizar.
- Mostrar boton rollback siempre; accion habilitada solo si aplica.

### Telemetria
- Ver si esta activa.
- Activar/desactivar.
- Mostrar exactamente que datos se enviarian.

### Donaciones
- Links discretos a PayPal, GitHub Sponsors y Buy Me a Coffee.

### Auditoria
- Busqueda por accion, empresa, usuario y fecha.

## Seguridad
- No mostrar secretos.
- Acciones criticas auditadas.
- Rollback/update/backups requieren confirmacion.
- Accesos cross-tenant en `/admin` no dependen del selector de empresa activa.

## Relacion con otros documentos
- `Empresas.md`
- `Usuarios.md`
- `04 - Seguridad/Checklist Producción.md`
