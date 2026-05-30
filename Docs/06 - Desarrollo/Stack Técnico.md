# Stack Tecnico — DoxTicket

## Proposito
Documentar stack tecnico recomendado.

## Backend
- PHP 8.3+.
- Laravel 13.x.
- PostgreSQL 16+.
- Redis 7+.
- Composer.

## Frontend
- Blade.
- Livewire.
- Alpine.js.
- Tailwind CSS 4.1+.
- Vite.
- Filament 5.x para `/admin`.

## Instalacion
- Docker Compose como camino principal.
- Ubuntu Server manual como alternativa.
- Caddy o Nginx en Docker.
- Nginx + PHP-FPM en instalacion manual.

## Correo
- SMTP global.
- IMAP/SMTP por empresa.
- Gmail API.
- Microsoft Graph.

## Operacion
- Workers Docker o Supervisor/systemd.
- Backups configurables.
- Health panel en `/admin`.
- GitHub Releases para aviso de version.

## Observabilidad
- Logs Laravel.
- `/admin/health`.
- Failed jobs.
- Telemetria opcional anonima.

## Por que este stack
| Decision | Justificacion |
|---|---|
| Laravel | Ecosistema maduro y productivo |
| Blade + Livewire | Interactividad sin SPA pesada |
| Filament | Admin potente rapido |
| PostgreSQL | JSONB, full-text, indices, robustez |
| Redis | Cache, sesiones, colas |
| Docker Compose | Instalacion self-hosted reproducible |

## Decisiones negativas
- No billing integrado en v1.
- No SPA React/Vue en v1.
- No MySQL/MariaDB en v1.
- No plugins hasta madurar.
- No Cloud prometido en v1.

## Herramientas
- Pest/PHPUnit.
- Laravel Pint.
- PHPStan/Psalm.
- ESLint/Prettier si aplica.
- Secret scanner.
- GitHub Actions.

## Versiones fijadas
- Laravel 13.x.
- Filament 5.x.
- Tailwind CSS 4.1+.
- PHP 8.3+.

## Relacion
- `02 - Arquitectura/Backend.md`
- `02 - Arquitectura/Frontend.md`
- `07 - Infraestructura/Deploy.md`
