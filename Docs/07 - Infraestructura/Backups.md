# Backups — DoxTicket

## Proposito
Definir estrategia de backups.

## Que respaldar
- PostgreSQL.
- Adjuntos (`storage/app/private`).
- `.env`.
- Configuracion Docker/servidor.

## Enfoque v1
- Backups configurables desde `/admin`.
- Historial en `backup_runs`.
- Verificacion de backup reciente antes de actualizar.
- Restauracion documentada.

Estado implementado actual:
- Tabla `backup_runs` implementada.
- `/admin` muestra ultimo backup exitoso y rollback visible.
- `/admin` muestra historial reciente de backups con estado, destino, tamano y errores sanitizados, sin exponer rutas privadas de artefactos.
- `/admin/backups` ejecuta backup local manual para superadmins y registra el resultado en `backup_runs`.
- `/admin/settings` permite configurar cuantas horas cuentan como backup reciente, los dias de retencion local documentada y el backup automatico local mediante `system_settings.backups.*`.
- El backup automatico queda apagado por defecto; si se activa, `RunScheduledBackupJob` se evalua cada hora y ejecuta como maximo un backup `scheduled` al dia a la hora configurada.
- `RunBackupRetentionPruneJob` se ejecuta diariamente y aplica `backups.retention_days` sobre backups locales exitosos antiguos; elimina los artefactos privados y marca el registro como `pruned` sin disponibilidad de rollback.
- Los artefactos se guardan en el disco `private`, bajo `backups/{uuid}`, junto a un `manifest.json`.
- En SQLite de desarrollo/test se copia el archivo de base de datos; en PostgreSQL se usa `pg_dump` si esta disponible en el servidor.
- `/admin/health` marca warning si no existe backup exitoso dentro de la ventana configurada.
- Empaquetado de adjuntos, cifrado avanzado, destinos externos y restauracion automatizada quedan pendientes.

## Destinos
- Local.
- S3 compatible opcional.
- Destino custom documentado.

## Frecuencia recomendada
- DB diaria.
- Adjuntos diaria/incremental.
- Retencion configurable.

## Docker
Los backups deben operar sobre volumenes persistentes.
El contenedor que ejecute backups PostgreSQL necesita acceso a `pg_dump` y al volumen privado de storage.

## Ubuntu manual
Scripts de `pg_dump`, compresion y cifrado recomendados.

## Seguridad
- Backups pueden contener datos sensibles.
- Cifrar si salen del servidor.
- No guardar secretos en logs.

## Restauracion
1. Detener app.
2. Restaurar PostgreSQL.
3. Restaurar adjuntos.
4. Restaurar `.env`.
5. Ejecutar migraciones necesarias.
6. Revisar `/admin/health`.

## RTO/RPO recomendados
- RTO: <= 4 horas.
- RPO: <= 24 horas.

## Relacion
- `Deploy.md`
- `04 - Seguridad/Checklist Producción.md`
