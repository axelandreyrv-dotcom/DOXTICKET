# Convenciones de Codigo — DoxTicket

## Proposito
Mantener codigo consistente.

## PHP
- PSR-12.
- Laravel Pint.
- Tipos estrictos cuando aplique.
- Servicios con responsabilidades claras.

## JavaScript / Alpine
- `const`/`let`.
- Sin dependencias pesadas sin justificacion.

## CSS / Tailwind
- Tokens del sistema visual.
- Componentes Blade para patrones repetidos.
- Evitar apariencia generica.

## Naming
- Clases en ingles.
- Mensajes al usuario en `lang/es` y `lang/en`.
- Rutas kebab-case.
- Route names dot-notation.

## Seguridad
- No `DB::raw` con input.
- No `eval`.
- No `exec/system` con input.
- Escapar salida Blade.
- `{!! !!}` solo con HTML sanitizado.
- No billing integrado en v1.

## Comentarios
- Explicar el por que.
- Evitar comentarios obvios.

## Git
- Commits claros.
- Releases versionadas.
- `main` estable.

## Documentacion continua
- Cada cambio debe revisar si requiere actualizar documentacion.
- Los cambios de comportamiento deben actualizar los `.md` relacionados en el mismo ciclo de trabajo.
- Cambios de rutas, setup, seguridad, correo, tickets, actualizaciones o infraestructura deben reflejarse en `Docs/` y, cuando aplique, en `README.md` y `AGENTS.md`.
- Cambios de schema deben actualizar `Docs/03 - Base de Datos/`.
- No cerrar una tarea como terminada si la documentacion necesaria quedo pendiente.

## UI
- Minimalista, calmada, legible.
- Accesible.
- `Powered by DoxTicket` preservado.

## Relacion
- `Stack Técnico.md`
- `Testing.md`
- `04 - Seguridad/Modelo de Seguridad.md`
