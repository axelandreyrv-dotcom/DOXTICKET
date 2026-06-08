# Arquitectura General — DoxTicket

## Proposito del documento
Dar una vista global de DoxTicket como aplicacion open source self-hosted.

## Vision a alto nivel

```
Usuario / LAN / Dominio
        |
        v
Web server (Docker: Caddy/Nginx | Manual: Nginx)
        |
        v
Laravel 13.x + Blade + Livewire + Filament 5.x
        |
        +-- PostgreSQL (datos)
        +-- Redis (cache, sesiones, colas)
        +-- Storage privado (adjuntos, backups locales)
        +-- Workers (mail, critical, default, low)
        |
        +-- IMAP/SMTP
        +-- Gmail / Microsoft 365
        +-- GitHub Releases (consulta de nueva version)
        +-- Telemetria opcional (si el admin la activa)
```

## Componentes

### Instalacion
- Docker Compose es el camino principal.
- Instalacion manual en Ubuntu queda documentada.
- DoxTicket debe funcionar con dominio o IP local.
- `/setup` prepara la instalacion y se bloquea tras finalizar.

### Frontend
- Entrada publica de instalacion con Blade.
- App del cliente bajo `/app/*` con Blade + Livewire.
- Panel superadmin bajo `/admin` con Filament 5.x.
- Tailwind CSS con modo claro como experiencia principal.

### Backend
- Laravel como framework principal.
- Capa modular por dominio: setup, companies, users, tickets, mail, admin, updates, backups, telemetry.
- Middleware de tenant para `/app/*`.
- Policies por modelo y rol.

### Datos
- PostgreSQL como unica base de datos oficial.
- Una sola base de datos, shared-schema, `company_id` en tablas multiempresa.
- Indices compuestos para filtros frecuentes.

### Cache y colas
- Redis para cache, sesiones y colas.
- Workers para correo, tareas criticas, jobs generales y mantenimiento.

### Storage
- Adjuntos en disco privado fuera de `public/`.
- S3 compatible opcional/futuro mediante driver de Laravel.
- Backups configurables desde `/admin`.

### Integraciones externas
- IMAP/SMTP generico.
- Gmail y Microsoft 365.
- GitHub Releases para comprobar versiones.

## Patrones clave

### Multiempresa self-hosted
- Cada instalacion puede tener una o varias empresas.
- Todas las tablas multiempresa tienen `company_id`.
- Los usuarios son identidades globales.
- La relacion usuario-empresa vive en `memberships`.
- El rol operativo viene de la membresia activa.
- El superadmin gestiona todas las empresas desde `/admin`.

### Setup seguro
- `/setup` valida dependencias y configuracion.
- Crea superadmin y empresa inicial.
- Crea una membresia `admin` inicial para el superadmin en la empresa inicial.
- Permite omitir SMTP global.
- Activa telemetria solo con consentimiento explicito.
- Se bloquea despues de completar.

### Trabajo en background
- Correo entrante/saliente, adjuntos, backups, SLA, notificaciones y cleanup viven en jobs.
- Los jobs deben ser idempotentes.
- La ingesta de correo usa lock por cuenta.

### Seguridad por defecto
- Validacion en servidor.
- CSRF en formularios.
- Adjuntos privados.
- Secretos en `.env`.
- Deteccion de configuracion insegura.

## Decisiones documentadas
- **Open source self-hosted primero**: v1 no incluye billing integrado ni Cloud.
- **PostgreSQL unico**: reduce complejidad y aprovecha JSONB/full-text/indices.
- **Docker Compose primero**: instalacion reproducible.
- **Multiempresa por `company_id`**: preserva flexibilidad para instalaciones con varias unidades.
- **Blade + Livewire**: interactividad suficiente sin SPA pesada.
- **Correo estable como prioridad**: evitar duplicados es mas importante que procesar automaticamente casos ambiguos.

## Relacion con otros documentos
- `Multiempresa Self-Hosted.md` — modelo multiempresa self-hosted.
- `Backend.md`
- `Frontend.md`
- `Colas y Jobs.md`
- `Integraciones.md`
- `06 - Desarrollo/Stack Técnico.md`
