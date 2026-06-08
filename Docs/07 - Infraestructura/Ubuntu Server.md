# Ubuntu Server — DoxTicket

## Proposito
Documentar como subir DoxTicket a un VPS Ubuntu cuando no se use Docker Compose. Docker sigue siendo el camino principal; esta guia existe para instalaciones que prefieren Nginx + PHP-FPM + PostgreSQL + Redis directamente en el servidor.

## Sistema recomendado
- Ubuntu Server 24.04 LTS.
- Usuario Linux dedicado: `doxticket`.
- Dominio o IP LAN apuntando al VPS.

| Recurso | Minimo | Recomendado |
|---|---|---|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 4 GB | 8 GB |
| Disco | 40 GB SSD | 100 GB SSD |

## 1. Preparar el servidor
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql redis-server supervisor unzip git curl ca-certificates ufw fail2ban
sudo adduser --disabled-password --gecos "" doxticket
sudo mkdir -p /var/www/doxticket
sudo chown -R doxticket:www-data /var/www/doxticket
```

Hardening minimo:
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

Usar SSH con llave, deshabilitar login root por SSH y mantener PostgreSQL/Redis escuchando solo en localhost salvo necesidad documentada.

## 2. Instalar PHP, Composer y Node
DoxTicket requiere PHP 8.3+ con extensiones de Laravel, PostgreSQL, Redis e IMAP:

```bash
sudo apt install -y php-fpm php-cli php-pgsql php-redis php-imap php-mbstring php-xml php-curl php-zip php-intl php-bcmath php-gd
php -m | grep -E 'imap|pdo_pgsql|redis'
```

Instalar Composer:
```bash
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

Instalar Node.js LTS desde NodeSource o el metodo oficial de la organizacion. Verificar:
```bash
node -v
npm -v
```

## 3. Subir el codigo al VPS
Desde el VPS:
```bash
sudo -u doxticket git clone https://github.com/axelandreyrv-dotcom/DOXTICKET.git /var/www/doxticket
cd /var/www/doxticket
sudo -u doxticket git checkout main
```

Para actualizar despues:
```bash
cd /var/www/doxticket
sudo -u doxticket git pull --ff-only
```

No subir ni copiar `.env`, `.env.docker`, backups, logs, `storage/app/private`, `vendor` ni `node_modules` desde una maquina local. Esos archivos pertenecen a cada instalacion.

## 4. Configurar PostgreSQL
```bash
sudo -u postgres psql
```

Dentro de `psql`:
```sql
CREATE USER doxticket WITH PASSWORD 'cambia-esta-password';
CREATE DATABASE doxticket OWNER doxticket;
\q
```

Usar una contrasena unica y guardarla solo en `/var/www/doxticket/.env`.

## 5. Configurar `.env`
```bash
cd /var/www/doxticket
sudo -u doxticket cp .env.example .env
sudo -u doxticket php artisan key:generate
sudo chmod 600 .env
sudo chown doxticket:www-data .env
```

Variables minimas a revisar:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.example

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=doxticket
DB_USERNAME=doxticket
DB_PASSWORD=

REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=DoxTicket

DOXTICKET_GITHUB_REPOSITORY=axelandreyrv-dotcom/DOXTICKET
DOXTICKET_IMAP_VALIDATE_CERT=true
```

No usar `DOXTICKET_IMAP_VALIDATE_CERT=false` en produccion. Esa opcion existe solo para QA local cuando un antivirus o proxy rompe la cadena TLS de IMAP.

## 6. Instalar dependencias y compilar assets
```bash
cd /var/www/doxticket
sudo -u doxticket composer install --no-dev --optimize-autoloader
sudo -u doxticket npm ci
sudo -u doxticket npm run build
```

## 7. Permisos de runtime
```bash
sudo mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
sudo chown -R doxticket:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

## 8. Migraciones y cache
```bash
cd /var/www/doxticket
sudo -u doxticket php artisan migrate --force
sudo -u doxticket php artisan optimize
```

Abrir `APP_URL/setup` para completar el instalador inicial. Despues de terminar, `/setup` debe quedar bloqueado.

## 9. Nginx
Crear `/etc/nginx/sites-available/doxticket`:
```nginx
server {
    listen 80;
    server_name tu-dominio.example;
    root /var/www/doxticket/public;

    index index.php;
    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ ^/(vendor|storage|bootstrap/cache|\.git) {
        deny all;
    }
}
```

Activar:
```bash
sudo ln -s /etc/nginx/sites-available/doxticket /etc/nginx/sites-enabled/doxticket
sudo nginx -t
sudo systemctl reload nginx
```

Si el VPS tiene dominio publico, configurar HTTPS con Certbot o el proxy TLS que use la organizacion.

## 10. Workers y scheduler
Crear `/etc/supervisor/conf.d/doxticket-worker.conf`:
```ini
[program:doxticket-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/doxticket/artisan queue:work redis --queue=default,mail --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=doxticket
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/doxticket-worker.log
stopwaitsecs=3600
```

Crear `/etc/supervisor/conf.d/doxticket-scheduler.conf`:
```ini
[program:doxticket-scheduler]
command=php /var/www/doxticket/artisan schedule:work
autostart=true
autorestart=true
user=doxticket
redirect_stderr=true
stdout_logfile=/var/log/supervisor/doxticket-scheduler.log
```

Aplicar:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

El worker debe escuchar `default,mail`; si no escucha `mail`, la ingesta de correos no creara tickets.

## 11. Backups
Respaldar como minimo:
- Dump de PostgreSQL.
- `/var/www/doxticket/.env`.
- `/var/www/doxticket/storage/app/private`.

Ejemplo:
```bash
sudo -u postgres pg_dump doxticket > /var/backups/doxticket-$(date +%F).sql
sudo tar -czf /var/backups/doxticket-private-$(date +%F).tar.gz /var/www/doxticket/storage/app/private /var/www/doxticket/.env
```

Proteger `/var/backups` con permisos restrictivos y rotacion.

## 12. Actualizaciones
```bash
cd /var/www/doxticket
sudo -u doxticket git pull --ff-only
sudo -u doxticket composer install --no-dev --optimize-autoloader
sudo -u doxticket npm ci
sudo -u doxticket npm run build
sudo -u doxticket php artisan migrate --force
sudo -u doxticket php artisan optimize
sudo supervisorctl restart doxticket-worker:*
sudo supervisorctl restart doxticket-scheduler
sudo systemctl reload php8.3-fpm nginx
```

Antes de actualizar, confirmar backup reciente desde `/admin` o por procedimiento manual.

## 13. Checklist final
- `/login` carga.
- `/setup` esta bloqueado despues de instalar.
- `/admin/health` sin errores criticos.
- PostgreSQL OK.
- Redis OK.
- Storage escribible y no publico.
- Worker y scheduler activos.
- `php -m` muestra `imap`, `pdo_pgsql` y `redis`.
- SMTP global probado si se usaran invitaciones/reset reales.
- Cuenta IMAP/SMTP de empresa probada desde `/app/settings`.
- Backups funcionando.

## Relacion
- `Deploy.md`
- `Nginx.md`
- `PostgreSQL.md`
- `Redis.md`
- `Backups.md`
