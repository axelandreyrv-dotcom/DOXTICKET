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
- HTTP delgado.
- Integraciones externas detras de adaptadores.
- Adjuntos en `storage/app/private`.
- No billing integrado en v1.
- `Powered by DoxTicket` en layouts.

## Relacion
- `Stack Técnico.md`
- `Convenciones de Código.md`
