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

La imagen `app` debe incluir la extension PHP IMAP para la ingesta de correo generico. En PHP 8.4 se compila desde PECL con SSL habilitado y conserva `c-client` como dependencia runtime. Tambien debe incluir `postgresql-client` para que los backups locales puedan ejecutar `pg_dump` dentro del contenedor.

Flujo:
1. Descargar release estable.
2. Copiar `.env.docker.example` a `.env.docker`.
3. Generar `APP_KEY` y ajustar variables minimas en `.env.docker`.
4. Ejecutar `docker compose --env-file .env.docker up -d --build`.
5. Ejecutar `docker compose --env-file .env.docker exec app php artisan migrate --force`.
6. Ejecutar `docker compose --env-file .env.docker exec app php artisan optimize`.
7. Abrir `/setup`.

Docker Compose usa `.env.docker` para no mezclar una configuracion local de desarrollo con la instalacion self-hosted. El archivo `.env.docker` no debe versionarse; solo se versiona `.env.docker.example`.

Los contenedores PHP (`app`, `worker` y `scheduler`) ejecutan el codigo copiado dentro de la imagen Docker. No deben montar el repositorio completo como volumen en QA o produccion, especialmente en Docker Desktop para Windows, porque Laravel se vuelve lento al leer autoload, rutas y vistas desde un bind mount. Solo se persisten `storage/` y `bootstrap/cache` como volumenes nombrados.

El contenedor `web` tambien se construye como imagen y sirve el mismo `public/` y `public/build` generado en el build. No debe montar `public/` desde el host en QA o produccion, porque puede quedar desincronizado con el manifest de Vite generado en la imagen. Despues de cambios de codigo PHP, dependencias o assets, reconstruir las imagenes con `docker compose --env-file .env.docker up -d --build` antes de probar.

Nginx usa el resolver interno de Docker (`127.0.0.11`) para resolver dinamicamente el upstream `app:9000`. Esto evita errores `502` despues de recrear el contenedor PHP, porque la IP interna de `app` puede cambiar en cada `docker compose up`.

El worker Docker debe escuchar al menos las colas `default,mail`. La ingesta de correo despacha `IngestMailboxJob` a la cola `mail`; si el worker no escucha esa cola, el scheduler parecera correr pero los correos entrantes no se convertiran en tickets.

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
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=

DOXTICKET_ATTACHMENT_MAX_BYTES=10485760
DOXTICKET_IMAP_VALIDATE_CERT=true
DOXTICKET_GITHUB_REPOSITORY=axelandreyrv-dotcom/DOXTICKET
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
`DOXTICKET_HTTP_PORT` define el puerto HTTP publicado por Docker; por ejemplo `8088` expone `http://127.0.0.1:8088`.
`MAIL_MAILER=log` sirve para QA local y escribe correos en logs. En produccion puede arrancarse asi durante la instalacion y luego configurar SMTP global real desde `/admin/settings`; los valores guardados en la interfaz tienen prioridad sobre `.env` y la contrasena queda cifrada en base de datos. Si se prefiere recuperacion manual o instalacion no interactiva, `MAIL_MAILER=smtp` con `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` sigue funcionando como fallback.
`DOXTICKET_ATTACHMENT_MAX_BYTES` controla el tamano maximo de adjuntos entrantes y manuales; el valor por defecto es 10 MB.
`DOXTICKET_IMAP_VALIDATE_CERT=true` mantiene validacion TLS de IMAP. Solo en QA local, si un antivirus/proxy corporativo intercepta TLS y rompe IMAP con un certificado local no confiable, puede usarse `false` temporalmente para agregar `/novalidate-cert`; no se recomienda en produccion.
Las variables OAuth solo son necesarias si se conectara Gmail o Microsoft 365; los secretos deben quedarse en `.env` y no en frontend, docs publicas ni repositorio.

## Setup
Despues de levantar la app, `/setup`:
- valida entorno,
- crea superadmin,
- crea empresa inicial,
- crea membership admin inicial,
- permite continuar con SMTP global pendiente para configurarlo despues desde `/admin/settings`,
- configura telemetria opt-in,
- bloquea setup.

## Actualizaciones
V1 solo avisa nueva version desde `/admin`.
El chequeo se ejecuta diariamente por scheduler y tambien puede dispararse manualmente desde el boton `Revisar actualizaciones` del panel superadmin.

Actualizacion manual recomendada:
1. Revisar release notes.
2. Verificar backup reciente.
3. Descargar nueva imagen/version o reconstruir desde el release estable.
4. Ejecutar migraciones.
5. Ejecutar `php artisan optimize` dentro del contenedor `app`.
6. Reiniciar servicios.
7. Revisar `/admin/health`.

## Rollback
Rollback visible en `/admin`.

En v1 el boton ejecuta un preflight protegido en `/admin/rollback`; no restaura automaticamente. Solo queda habilitado si:
- existe version anterior conocida,
- hay backup reciente,
- la migracion no fue destructiva o se restaura backup.

Si el preflight pasa, el superadmin debe seguir la guia manual de restauracion correspondiente a Docker Compose o Ubuntu.

## Smoke test
- `docker compose --env-file .env.docker ps` muestra `postgres` y `redis` saludables.
- `php -m` dentro del contenedor `app` incluye `imap`, `pdo_pgsql` y `redis`.
- `php artisan about` dentro del contenedor `app` muestra config, rutas y vistas cacheadas tras `php artisan optimize`.
- `/login` carga.
- `/setup` esta bloqueado tras instalar.
- SMTP global configurado desde `/admin/settings` si se enviaran invitaciones/resets reales.
- `/admin/health` sin errores criticos.
- Redis y PostgreSQL OK.
- Worker y scheduler OK.
- Worker escuchando `default,mail`.
- SMTP global probado u omitido.
- Ingesta de correo de prueba OK.
- `Revisar correo ahora` desde `/app/settings` procesa una bandeja de prueba; si funciona manualmente pero no automatico, revisar scheduler y worker `default,mail`.
- Extension PHP IMAP habilitada si se usa IMAP/SMTP generico.

## Relacion
- `Backups.md`
- `Ubuntu Server.md`
- `Nginx.md`
- `Redis.md`
- `PostgreSQL.md`
