# Frontend — DoxTicket

## Proposito
Describir la capa de presentacion de DoxTicket self-hosted.

## Stack
- Blade.
- Livewire.
- Alpine.js.
- Tailwind CSS 4.1+.
- Filament 5.x para `/admin`.
- Vite.

## Areas

### Setup (`/setup`)
- Idioma primero.
- Validaciones de entorno claras.
- Creacion de superadmin.
- Creacion de empresa inicial.
- SMTP global opcional.
- Telemetria opt-in.
- Finalizacion y bloqueo.

### Login
- Simple, claro, sin marketing pesado.
- Sin acciones duplicadas en el encabezado; el formulario contiene la accion principal.
- El acceso visual a `/setup` no se muestra en el encabezado publico.
- `Powered by DoxTicket`.

### App cliente (`/app/*`)
- Navegacion superior compacta.
- Selector discreto de empresa activa en topbar.
- Busqueda global limitada a la empresa activa.
- Notificaciones in-app de la empresa activa.
- Avatar/iniciales y perfil en esquina superior derecha.
- Dashboard operativo.
- Lista de tickets activos.
- Detalle de ticket.
- Settings de empresa/correo/usuarios/SLA/KB.

Estado implementado actual: `/app/settings` contiene la configuracion base de correo IMAP/SMTP del tenant.

### Admin (`/admin`)
- Filament tematizado.
- Empresas.
- Usuarios superadmin.
- Health.
- Backups.
- Updates/rollback.
- Telemetria.
- Auditoria.
- Donaciones discretas.

## Navegacion
- Top nav simple: Dashboard, Tickets, Base de conocimiento, Configuracion.
- Boton global: Ver tickets.
- Link a `/admin` visible solo para superadmins.
- Breadcrumbs en pantallas internas, por ejemplo `Tickets / DT-123`.
- En mobile: menu superior compacto.

La navegacion publica actual muestra solo `Login` en `/`; las pantallas de login/setup no muestran botones duplicados en el encabezado. La navegacion autenticada incluye Dashboard, Tickets, Empresa y Configuracion. Base de conocimiento se agregara cuando exista el modulo.

### Web oficial futura
`doxticket.com` sera hub del proyecto:
- Documentacion.
- Releases.
- Seguridad.
- Roadmap.
- Donaciones.
- Enlace a GitHub.

No prometer Cloud en v1.

## UX
- Minimalista.
- Calmada.
- Entendible.
- Profesional.
- Sin apariencia generica de IA.
- Una accion primaria clara.
- Estados vacios utiles.
- Confirmaciones en acciones destructivas.

## Componentes
- `x-button`
- `x-input`
- `x-badge`
- `x-card`
- `x-modal`
- `x-dropdown`
- `x-empty-state`
- `x-health-status`

## Dashboard
- Resumen del dia.
- Tickets que requieren atencion.
- Cards claras + listas.
- Sin graficos pesados en primera version usable.

## Tickets
- Lista tipo inbox con filtros.
- Filas enlazables hacia pagina completa de detalle.
- Accion rapida: asignarse desde la lista cuando el ticket no tiene agente.
- Detalle en pagina completa con hilo + panel lateral en desktop. La base implementada muestra mensajes, eventos, metadatos, asignacion propia, nota interna y cambio de estado.
- Marcador visible `DT-123`.

## Accesibilidad
- WCAG AA.
- Foco visible.
- Labels.
- ARIA en botones de icono.
- No depender solo del color.

## Performance
- Sin librerias JS pesadas extra.
- Livewire con estado minimo.
- Debounce en busquedas.
- Paginacion.
- Evitar N+1.

## Branding
- Logo DoxTicket.
- `Powered by DoxTicket` obligatorio.

## Relacion
- `08 - Diseño/UI UX.md`
- `08 - Diseño/Identidad Visual.md`
- `Backend.md`
