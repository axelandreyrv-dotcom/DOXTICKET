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
- Detalle de tickets y bloqueo cross-tenant.
- Notas internas sin aceptar `company_id` del cliente.
- Cambios de estado, incluyendo cierre solo despues de resuelto.
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
- Un ticket de otra empresa no puede abrirse desde la empresa activa.
- Agregar nota interna no puede mover datos a otra empresa aunque el cliente envie `company_id`.
- Un ticket no puede cerrarse sin pasar primero por `resolved`.
- `/setup` no funciona despues de completado.
- `APP_DEBUG=true` bloquea modo produccion seguro.
- Correo con `[DT-123]` se asocia al ticket correcto.
- Correo ambiguo no crea duplicado sin regla confiable.
- Imagen externa queda bloqueada.
- `Message-Id` duplicado no crea otro ticket ni mensaje.
- Headers de threading solo relacionan mensajes dentro de la misma empresa.
- Auto-respuestas por `Auto-Submitted` o `Precedence` se ignoran como loop.
- Job de ingesta avanza `last_uid`, limpia errores al exito y no procesa cuentas inactivas.
- Errores de ingesta se guardan sanitizados sin contrasenas ni tokens.
- Adjunto peligroso crea evento interno.
- Telemetria no envia datos si no se activo.

## Estado implementado actual
- `tests/Feature/PublicNavigationTest.php` cubre navegacion publica sin Setup visible ni acciones duplicadas en login.
- `tests/Feature/Tickets/TicketDetailTest.php` cubre detalle tenant-safe, notas internas y cambios de estado.
- `tests/Feature/Mail/MailAccountSettingsTest.php` cubre configuracion tenant-safe de cuenta IMAP/SMTP y secreto cifrado.
- `tests/Feature/Mail/InboundMailProcessorTest.php` cubre creacion por correo, sanitizacion, imagenes externas, deduplicacion, threading y loops.
- `tests/Feature/Mail/IngestMailboxJobTest.php` cubre el job de ingesta, avance de UID, errores sanitizados y cuentas inactivas.

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
