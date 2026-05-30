# Nginx — DoxTicket

## Proposito
Documentar Nginx para instalacion manual o como reverse proxy externo.

## Escenarios
- Dominio publico con HTTPS.
- LAN/intranet con IP o hostname local.
- Reverse proxy frente a Docker.

## Reglas
- Root siempre `public/`.
- Bloquear `.env`, `.git`, `storage/`, `vendor/`, `bootstrap/cache`.
- Adjuntos nunca servidos directo.
- `autoindex off`.

## Ejemplo base
```nginx
server {
    listen 80;
    server_name _;

    root /var/www/doxticket/public;
    index index.php;

    client_max_body_size 35M;

    location ~ /\.(env|git|htaccess|htpasswd) {
        deny all;
        return 404;
    }

    location ~ ^/(vendor|storage|bootstrap/cache) {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## HTTPS
Recomendado para dominio publico. En LAN se permite HTTP normal segun decision del administrador.

## Headers
Aplicar headers de seguridad cuando no rompan entorno local:
- X-Frame-Options.
- X-Content-Type-Options.
- Referrer-Policy.
- CSP progresiva.

## Relacion
- `SSL.md`
- `Seguridad de Archivos.md`
