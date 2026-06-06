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

La imagen `app` debe incluir la extension PHP IMAP para la ingesta de correo generico. En PHP 8.4 se compila desde PECL con SSL habilitado y conserva `c-client` como dependencia runtime.

Flujo:
1. Descargar release estable.
2. Copiar `.env.example` a `.env`.
3. Ajustar variables minimas.
4. Ejecutar `docker compose up -d`.
5. Abrir `/setup`.

## Camino alternativo: Ubuntu manual
- Instalar PHP-FPM, Composer, Node, Nginx, PostgreSQL, Redis y `php-imap`.
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
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=

DOXTICKET_DONATION_PAYPAL_URL=
DOXTICKET_DONATION_GITHUB_SPONSORS_URL=
DOXTICKET_DONATION_BUY_ME_A_COFFEE_URL=
DOXTICKET_ATTACHMENT_MAX_BYTES=10485760
DOXTICKET_OAUTH_STATE_TTL_MINUTES=10
DOXTICKET_GMAIL_CLIENT_ID=
DOXTICKET_GMAIL_CLIENT_SECRET=
DOXTICKET_GMAIL_REDIRECT_URI="${APP_URL}/app/settings/mail/oauth/gmail/callback"
DOXTICKET_MICROSOFT_CLIENT_ID=
DOXTICKET_MICROSOFT_CLIENT_SECRET=
DOXTICKET_MICROSOFT_TENANT=organizations
DOXTICKET_MICROSOFT_REDIRECT_URI="${APP_URL}/app/settings/mail/oauth/microsoft365/callback"
```

`APP_URL` puede ser dominio o IP local.
Las variables `DOXTICKET_DONATION_*` son opcionales y solo aceptan URLs `http`/`https`; si se dejan vacias no aparece el panel de donaciones en `/admin`.
`DOXTICKET_ATTACHMENT_MAX_BYTES` controla el tamano maximo de adjuntos entrantes y manuales; el valor por defecto es 10 MB.
Las variables OAuth solo son necesarias si se conectara Gmail o Microsoft 365; los secretos deben quedarse en `.env` y no en frontend, docs publicas ni repositorio.

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
El chequeo se ejecuta diariamente por scheduler y tambien puede dispararse manualmente desde el boton `Revisar actualizaciones` del panel superadmin.

Actualizacion manual recomendada:
1. Revisar release notes.
2. Verificar backup reciente.
3. Descargar nueva imagen/version.
4. Ejecutar migraciones.
5. Reiniciar servicios.
6. Revisar `/admin/health`.

## Rollback
Rollback visible en `/admin`.

En v1 el boton ejecuta un preflight protegido en `/admin/rollback`; no restaura automaticamente. Solo queda habilitado si:
- existe version anterior conocida,
- hay backup reciente,
- la migracion no fue destructiva o se restaura backup.

Si el preflight pasa, el superadmin debe seguir la guia manual de restauracion correspondiente a Docker Compose o Ubuntu.

## Smoke test
- `/login` carga.
- `/setup` esta bloqueado tras instalar.
- `/admin/health` sin errores criticos.
- Redis y PostgreSQL OK.
- Worker y scheduler OK.
- SMTP global probado u omitido.
- Ingesta de correo de prueba OK.
- Extension PHP IMAP habilitada si se usa IMAP/SMTP generico.

## Relacion
- `Backups.md`
- `Ubuntu Server.md`
- `Nginx.md`
- `Redis.md`
- `PostgreSQL.md`
