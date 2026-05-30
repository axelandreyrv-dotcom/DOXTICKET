# AGENTS.md — Guia Obligatoria del Proyecto DoxTicket

> Este archivo es de lectura obligatoria para Codex, Claude Code y Cowork antes de realizar cualquier accion sobre este repositorio.
> No implementar nada sin haber revisado primero este archivo y la documentacion en `Docs/`.

---

## Descripcion del Proyecto

**DoxTicket** es un helpdesk IT **open source self-hosted**.

Permite que departamentos de TI instalen su propio sistema de tickets, conecten su correo de soporte y gestionen solicitudes con aislamiento multiempresa en una sola instalacion.

- **Modelo:** open source self-hosted.
- **Licencia:** AGPLv3.
- **Sitio oficial futuro:** `doxticket.com` como hub de proyecto, documentacion, releases y donaciones.
- **Correo de seguridad:** `axelandreyrv@outlook.com`
- **Branding:** `Brand/DoxTicketSVG.svg`
- **Documentacion:** `Docs/`

---

## Stack Objetivo

| Capa | Tecnologia |
|---|---|
| Backend | Laravel 13.x (PHP 8.3+) |
| Base de datos | PostgreSQL |
| Cache / Colas | Redis |
| Frontend | Blade + Livewire + Tailwind CSS 4.1+ |
| Panel admin | Filament 5.x |
| Correo entrante/saliente | IMAP / SMTP, Gmail, Microsoft 365 |
| Instalacion principal | Docker Compose |
| Instalacion alternativa | Ubuntu Server manual |
| Web server | Nginx o Caddy en Docker; Nginx + PHP-FPM en manual |
| Colas | Supervisor, systemd o workers Docker |

---

## Rutas Principales

| Ruta | Descripcion |
|---|---|
| `/` | Entrada publica de la instalacion |
| `/setup` | Instalador inicial; debe bloquearse tras finalizar |
| `/login` | Login centralizado |
| `/logout` | Cierre de sesion autenticado |
| `/app/companies` | Selector de empresa activa |
| `/app/dashboard` | Panel principal del tenant |
| `/app/tickets` | Gestion de tickets |
| `/app/tickets/create` | Creacion manual de ticket |
| `/app/settings` | Configuracion del tenant |
| `/app/settings/mail` | Guardado de cuenta IMAP/SMTP del tenant |
| `/admin` | Panel superadmin de la instalacion |

---

## Decisiones de Arquitectura — FINALES

Estas decisiones estan tomadas. No proponer alternativas sin justificacion documentada.

### Open Source / Self-hosted
- DoxTicket es self-hosted primero.
- El repositorio se publicara en GitHub.
- Los usuarios deben instalar versiones publicadas mediante Releases e imagenes Docker versionadas, no commits aleatorios.
- El proyecto no promete DoxTicket Cloud en v1.
- Las donaciones son discretas: PayPal, GitHub Sponsors y Buy Me a Coffee.
- Toda instalacion debe mantener **Powered by DoxTicket** en el footer.

### Licencia
- Licencia: **AGPLv3**.
- Cualquier cambio de licencia requiere decision documentada.

### Multiempresa
- Aislamiento por `company_id` en todas las tablas de datos de empresa.
- Los usuarios son identidades globales con email unico en la instalacion.
- La pertenencia de un usuario a una empresa se modela con `memberships`.
- El rol de empresa vive en `memberships.role`, no directamente en `users`.
- Un usuario puede pertenecer a varias empresas con roles distintos.
- Tras login, si el usuario tiene mas de una empresa, debe elegir empresa antes de entrar a `/app`.
- La empresa activa se resuelve desde la membresia seleccionada en sesion, no desde input del cliente.
- No se usan subdominios por empresa en v1.
- El tenant se resuelve por el usuario autenticado.
- Toda consulta a la base de datos que toque datos de empresa DEBE filtrar por `company_id`.
- Nunca aceptar `company_id` como input confiable del usuario.

### Instalacion
- Docker Compose es el camino principal.
- Tambien debe existir documentacion de instalacion manual en Ubuntu.
- `/setup` pide idioma primero, valida entorno, crea superadmin y una empresa inicial.
- `/setup` debe bloquearse automaticamente despues de terminar.
- DoxTicket debe funcionar en LAN/intranet con dominio o IP local.

### Correo
- La estabilidad del correo entrante es la prioridad v1.
- Una cuenta de soporte por empresa en v1.
- Existe SMTP global del sistema para invitaciones, reset, alertas y correos internos.
- Se soporta IMAP/SMTP generico y se planifican Gmail/Microsoft 365 desde v1.
- Los tickets por correo reciben confirmacion automatica.
- Las respuestas salen como agente y mantienen marcador visible `[DT-123]`.
- Se prioriza evitar duplicados sobre procesar casos ambiguos sin revision.

### Tickets
- Los tickets pueden crearse por correo o manualmente.
- El dashboard y la lista principal se orientan a saber que atender ahora.
- Fusión de tickets: SÍ.
- **Subtickets / ticket padre-hijo / división de tickets: NO** sin decision explicita futura.

### Billing
- Billing integrado, planes pagados, trial y suscripciones quedan fuera de v1.
- No implementar flujo de pago comercial en la app sin nueva decision documentada.

### Actualizaciones
- `/admin` muestra version instalada y aviso de nueva version estable consultando GitHub sin enviar datos sensibles.
- En v1 basta aviso de nueva version y guia/manual de actualizacion.
- El rollback debe tener boton visible en `/admin`, aunque solo funcione si existe una version anterior/backup valido.
- Antes de actualizar se debe verificar backup reciente.

### Telemetria
- Opcional y apagada por defecto.
- Solo se activa explicitamente en `/setup`.
- No enviar nombres, correos, contenido de tickets, asuntos, cuerpos, adjuntos ni secretos.

---

## Reglas de Seguridad — OBLIGATORIAS

### Secretos
- Prohibido incluir claves reales, tokens, contrasenas o secretos en frontend, vistas, JS compilado o repositorio.
- Toda clave debe vivir en `.env`.
- `.env` esta en `.gitignore`. Verificar siempre antes de publicar.

### Inputs
- Obligatorio validar todos los inputs con reglas de Laravel (`FormRequest` o `$request->validate()`).
- No confiar en datos del cliente. Validar en servidor siempre.

### Multitenancy
- Obligatorio proteger `company_id` en lectura, escritura y eliminacion.
- Usar policies y/o scopes globales de Eloquent para aplicar el filtro de tenant automaticamente.
- Usar `membership_id` en acciones de empresa cuando aplique para auditar el rol/contexto exacto.
- El superadmin opera cross-tenant desde `/admin` y con auditoria; tambien puede tener memberships para usar `/app` como usuario normal de una empresa.

### Autorizacion
- Usar Gates o Policies de Laravel para controlar acceso por rol dentro de cada empresa.
- El panel `/admin` solo es accesible para superadmins.

### Setup y Produccion
- Bloquear produccion si `APP_DEBUG=true`, falta `APP_KEY`, storage no es seguro, Redis/PostgreSQL no responden o `/setup` sigue habilitado tras instalar.
- Validar permisos de `.env`, `storage/` y `bootstrap/cache`.

---

## Reglas de Documentacion

- Cada cambio funcional, tecnico, visual, de infraestructura, seguridad, dependencias, rutas, schema, instalacion o comportamiento debe revisar y actualizar los `.md` necesarios en el mismo ciclo de trabajo.
- Antes de cerrar una tarea con cambios, verificar si corresponde actualizar `Docs/`, `README.md`, `AGENTS.md`, `SECURITY.md`, `CONTRIBUTING.md` o documentacion especifica del modulo tocado.
- No dejar la documentacion para despues si el cambio ya modifica el comportamiento esperado del proyecto.
- Cuando se tome una decision de arquitectura, producto, seguridad, licencia, instalacion o comunidad, actualizar `Docs/`.
- Si cambia el schema de base de datos, actualizar `Docs/03 - Base de Datos/`.
- Si cambia una ruta, comportamiento de tickets, correo, setup, actualizaciones o seguridad, actualizar este `AGENTS.md` y `README.md`.
- No dejar decisiones importantes solo en el chat o comentarios de codigo.

---

## Reglas de Uso de Skills

- Antes de crear cualquier archivo de codigo, revisar si ya existe documentacion relevante en `Docs/`.
- Antes de implementar una feature, confirmar que esta contemplada en la documentacion.
- Usar `superpowers:using-superpowers` como regla base cuando este disponible o sea solicitado en la sesion.
- Usar skills `gstack` cuando apliquen al trabajo: planificacion, especificacion, arquitectura, QA, revision, diseno, DX, investigacion, shipping o despliegue.
- Para planes grandes usar las rutas gstack correspondientes, por ejemplo `gstack-autoplan`, `gstack-spec`, `gstack-plan-eng-review`, `gstack-plan-design-review`, `gstack-plan-devex-review` y `gstack-review` segun corresponda.
- Para UI/frontend usar siempre skills de diseno antes de implementar: `minimalist-ui`, `web-design-guidelines`, `impeccable`, `emil-design-eng` y/o skills `gstack-design-*` cuando apliquen.
- Las skills de React/Vercel solo aplican como referencia de rendimiento si el stack real sigue siendo Blade + Livewire.
- El codigo debe mantenerse limpio, documentado, estructurado y preparado para escalar; no introducir abstracciones sin necesidad real.
- No instalar dependencias de Composer o NPM sin justificacion documentada.
- No modificar la estructura de carpetas sin actualizar este archivo.

---

## Reglas de Tests

- Obligatorio escribir tests para logica critica: autenticacion, aislamiento de tenant, setup, correo entrante, creacion/fusion de tickets, adjuntos, backups/rollback y health checks.
- Usar PHPUnit / Pest para backend.
- Los tests deben pasar antes de publicar una release estable.

---

## Prohibiciones Explicitas

- No implementar billing integrado en v1.
- No prometer DoxTicket Cloud en v1.
- No implementar subdominios por tenant en v1.
- No implementar subtickets, tickets padre-hijo ni division de tickets.
- No hardcodear secretos.
- No aceptar `company_id` como input confiable.
- No hacer deploy ni release sin revisar variables de entorno y checklist de seguridad.
- No copiar codigo o diseno de competidores.
- No quitar **Powered by DoxTicket** de instalaciones.
- No implementar sin revisar primero `Docs/` y este archivo.

---

## Estructura de Referencia

```
DoxTicket/
 AGENTS.md
 README.md
 LICENSE
 SECURITY.md
 CONTRIBUTING.md
 CODE_OF_CONDUCT.md
 Brand/
    DoxTicketSVG.svg
 Docs/
 app/Contracts/
 app/Jobs/
 .gitignore
```

---

*Ultima actualizacion: Mayo 2026.*
