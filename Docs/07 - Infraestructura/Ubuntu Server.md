# Ubuntu Server — DoxTicket

## Proposito
Documentar instalacion manual en Ubuntu como alternativa a Docker.

## Sistema
- Ubuntu Server LTS.

## Requisitos recomendados
| Recurso | Minimo | Recomendado |
|---|---|---|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 4 GB | 8 GB |
| Disco | 40 GB SSD | 100 GB SSD |

## Servicios
- PHP-FPM 8.3+.
- Composer.
- Node.js 20+ para build.
- Nginx.
- PostgreSQL 16+.
- Redis 7+.
- Supervisor o systemd.

## Hardening
- SSH por clave.
- Root login deshabilitado.
- UFW.
- Fail2ban.
- PostgreSQL/Redis solo localhost.
- `.env` permisos 600.

## LAN/intranet
Puede operar con IP local. HTTPS es recomendado si hay dominio publico.

## Relacion
- `Deploy.md`
- `Nginx.md`
- `PostgreSQL.md`
- `Redis.md`
