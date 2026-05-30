# Testing — DoxTicket

## Proposito
Definir estrategia de pruebas.

## Herramientas
- Pest preferido.
- PHPUnit compatible.
- Factories Eloquent.
- Mockery.
- Playwright para QA visual local, screenshots y validacion de flujos UI.

## Cobertura obligatoria
- Setup.
- Auth.
- Tenant isolation.
- Policies.
- Tickets.
- Fusion.
- Correo entrante y threading.
- Evitar duplicados.
- SMTP outbound.
- Adjuntos.
- Health panel.
- Backups.
- Update check.
- Rollback disponible/no disponible.
- Telemetria opt-in.

## Estructura

```
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
    Mail/
    Sla/
    Security/
    Tenant/
```

## Tests clave
- Una membership de empresa A no puede acceder a recursos de B.
- Un usuario con membresias en A y B solo ve datos de la empresa activa.
- Un usuario puede tener roles distintos por empresa.
- Desactivar una membership solo quita acceso a esa empresa.
- Busqueda global solo busca en empresa activa.
- Notificaciones se separan por empresa.
- `/setup` no funciona despues de completado.
- `APP_DEBUG=true` bloquea modo produccion seguro.
- Correo con `[DT-123]` se asocia al ticket correcto.
- Correo ambiguo no crea duplicado sin regla confiable.
- Imagen externa queda bloqueada.
- Adjunto peligroso crea evento interno.
- Telemetria no envia datos si no se activo.

## CI minimo
- Composer install.
- NPM install/build.
- Pint test.
- PHPStan/Psalm.
- Tests.
- Secret scan.

## QA visual
- Usar Playwright para revisar pantallas importantes en desktop y movil antes de cerrar cambios de UI.
- Guardar screenshots y snapshots locales en `output/playwright/`.
- `output/playwright/` y `.playwright-cli/` son artifacts locales y no deben versionarse.
- Revisar consola del navegador, overflow horizontal, presencia de `Powered by DoxTicket` y estados responsive.

## Relacion
- `04 - Seguridad/Checklist Producción.md`
- `02 - Arquitectura/Multiempresa Self-Hosted.md`
