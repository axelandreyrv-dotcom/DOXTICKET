# Estructura del Proyecto — DoxTicket

## Proposito
Definir layout del repo.

## Layout recomendado

```
doxticket/
  app/
    Domain/
      Setup/
      Companies/
      Memberships/
      Users/
      Tickets/
      Mail/
      Sla/
      Kb/
      Admin/
      Backups/
      Updates/
      Telemetry/
    Http/
      Controllers/
      Middleware/
      Requests/
      Livewire/
    Integrations/
      Imap/
      Smtp/
      Gmail/
      Microsoft365/
      GitHub/
      Telemetry/
    Support/
      Tenant/
      Security/
      Installer/
    Filament/
  bootstrap/
  config/
  database/
    migrations/
    seeders/
    factories/
  lang/
    es/
    en/
  public/
  resources/
    views/
      components/
        layouts/
      setup/
      public/
      app/
      auth/
      emails/
      livewire/
    css/
    js/
  routes/
    web.php
    setup.php
    admin.php
    console.php
  storage/
    app/private/
  tests/
    Feature/
      Setup/
      Auth/
      MultiTenant/
      Tickets/
      Mail/
      Admin/
      Backups/
      Updates/
    Unit/
  Docs/
  Brand/
  SECURITY.md
  CONTRIBUTING.md
  CODE_OF_CONDUCT.md
  LICENSE
  README.md
  AGENTS.md
  .gitignore
  docker-compose.yml
  Dockerfile
  .env.example
```

## Reglas
- Dominio en `app/Domain`.
- La base actual usa modelos Eloquent estandar en `app/Models` hasta que el dominio necesite separar casos de uso mas complejos.
- Los modelos multiempresa reutilizan `app/Models/Concerns/BelongsToCompany.php` para scope y escritura de `company_id` desde el tenant activo.
- HTTP delgado.
- Integraciones externas detras de adaptadores.
- Adjuntos en `storage/app/private`.
- Layouts Blade reutilizables pueden vivir en `resources/views/components/layouts`.
- Bases SQLite locales de QA/desarrollo se ignoran y no se versionan.
- No billing integrado en v1.
- `Powered by DoxTicket` en layouts.

## Relacion
- `Stack Técnico.md`
- `Convenciones de Código.md`
