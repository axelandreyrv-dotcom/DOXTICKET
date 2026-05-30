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
