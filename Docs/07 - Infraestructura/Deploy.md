# Deploy / Instalacion — DoxTicket

## Proposito
Documentar instalacion y actualizacion de DoxTicket self-hosted.

## Camino principal: Docker Compose

Servicios esperados:
- `app`
- `web`
- `postgres`
- `redis`
- `worker`
- `scheduler`

Flujo:
1. Descargar release estable.
2. Copiar `.env.example` a `.env`.
3. Ajustar variables minimas.
4. Ejecutar `docker compose up -d`.
5. Abrir `/setup`.

## Camino alternativo: Ubuntu manual
- Instalar PHP-FPM, Composer, Node, Nginx, PostgreSQL, Redis.
- Clonar release estable.
- Configurar `.env`.
- Ejecutar migraciones.
- Configurar workers con Supervisor/systemd.

## Variables clave
```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.50
APP_KEY=

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=doxticket
DB_USERNAME=doxticket
DB_PASSWORD=

REDIS_HOST=redis
REDIS_PASSWORD=

MAIL_MAILER=smtp
```

`APP_URL` puede ser dominio o IP local.

## Setup
Despues de levantar la app, `/setup`:
- valida entorno,
- crea superadmin,
- crea empresa inicial,
- crea membership admin inicial,
- permite SMTP global opcional,
- configura telemetria opt-in,
- bloquea setup.

## Actualizaciones
V1 solo avisa nueva version desde `/admin`.

Actualizacion manual recomendada:
1. Revisar release notes.
2. Verificar backup reciente.
3. Descargar nueva imagen/version.
4. Ejecutar migraciones.
5. Reiniciar servicios.
6. Revisar `/admin/health`.

## Rollback
Rollback visible en `/admin`.

Solo funciona si:
- existe version anterior conocida,
- hay backup reciente,
- la migracion no fue destructiva o se restaura backup.

## Smoke test
- `/login` carga.
- `/setup` esta bloqueado tras instalar.
- `/admin/health` sin errores criticos.
- Redis y PostgreSQL OK.
- Worker y scheduler OK.
- SMTP global probado u omitido.
- Ingesta de correo de prueba OK.

## Relacion
- `Backups.md`
- `Ubuntu Server.md`
- `Nginx.md`
- `Redis.md`
- `PostgreSQL.md`
