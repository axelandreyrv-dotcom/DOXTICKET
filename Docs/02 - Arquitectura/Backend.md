# Backend — DoxTicket

## Proposito del documento
Describir la arquitectura interna del backend de DoxTicket.

## Stack
- PHP 8.3+.
- Laravel 13.x.
- PostgreSQL.
- Redis.
- Filament 5.x para `/admin`.
- Livewire para la app.

## Capas

### 1. HTTP
- `routes/web.php` — entrada publica, app, auth.
- `routes/setup.php` — instalador inicial.
- `routes/admin.php` — panel superadmin.

### 2. Validacion
- `FormRequest` por endpoint.
- Mensajes traducidos.
- Validacion de pertenencia a tenant desde servidor.

### 3. Dominio / servicios
Carpetas sugeridas:
- `Setup`
- `Companies`
- `Users`
- `Tickets`
- `Mail`
- `Sla`
- `Kb`
- `Admin`
- `Updates`
- `Backups`
- `Telemetry`

Los servicios no conocen HTTP.

### 4. Modelos Eloquent
- Trait `BelongsToCompany` en modelos multiempresa.
- Relaciones explicitas.
- Casts para enums, JSON y fechas.

### 5. Policies
- Una policy por modelo principal.
- Validan rol desde `memberships.role` y `company_id`.

### 6. Jobs y eventos
- Jobs para correo, adjuntos, backups, SLA, actualizaciones y mantenimiento.
- Eventos de dominio para tickets, auth, setup, correo y admin.

### 7. Integraciones
- `app/Integrations/Imap/`
- `app/Integrations/Smtp/`
- `app/Integrations/Gmail/`
- `app/Integrations/Microsoft365/`
- `app/Integrations/GitHub/`
- `app/Integrations/Telemetry/`

No incluir billing integrado en v1.

## Tenant resolution
- `EnsureTenantContext` corre tras `auth` en `/app/*`.
- El usuario debe tener una membresia activa en sesion.
- `Tenant::set($membership->company)` y `Tenant::setMembership($membership)` quedan scoped al request.
- Superadmin no usa tenant global en `/admin`; cualquier acceso cross-tenant se audita.
- Un superadmin puede tener memberships y usar `/app` como usuario normal de una empresa.

## Setup
- `/setup` debe tener una proteccion fuerte para no quedar accesible despues de instalar.
- Debe poder correr en LAN/intranet.
- Debe validar entorno antes de escribir datos.

## Manejo de errores
- Excepciones de dominio.
- 4xx con mensajes traducidos.
- 5xx con logs, sin secretos.
- Errores de health visibles en `/admin/health`.

## Configuracion
- `env()` solo en `config/*`.
- `.env.example` sin secretos reales.
- Variables para DB, Redis, SMTP global, storage, updates y telemetria.

## Tests obligatorios
- Setup.
- Auth.
- Tenant isolation.
- Tickets.
- Correo ingest/threading.
- Adjuntos.
- Admin health.
- Backups.
- Updates/rollback.

## Relacion con otros documentos
- `Frontend.md`
- `Colas y Jobs.md`
- `Integraciones.md`
- `06 - Desarrollo/Estructura del Proyecto.md`
- `06 - Desarrollo/Convenciones de Código.md`
