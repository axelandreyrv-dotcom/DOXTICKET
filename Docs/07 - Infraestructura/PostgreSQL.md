# PostgreSQL — DoxTicket

## Proposito
Documentar PostgreSQL como unica base de datos oficial.

## Version
- PostgreSQL 16+ recomendado.

## Docker
PostgreSQL corre como servicio interno con volumen persistente.

## Ubuntu manual
Instalar desde repositorio oficial PGDG cuando sea posible.

## Seguridad
- No exponer puerto 5432 al exterior.
- Password largo.
- `scram-sha-256`.
- Usuario sin superuser.

## Extensiones
```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
```

## Variables
```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=doxticket
DB_USERNAME=doxticket
DB_PASSWORD=
```

## Backups
Ver `Backups.md`.

## Relacion
- `03 - Base de Datos/Tablas.md`
- `03 - Base de Datos/Índices.md`
