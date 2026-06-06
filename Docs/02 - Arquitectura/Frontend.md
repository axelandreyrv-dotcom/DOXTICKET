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
- Selector de empresa activo como flujo dedicado cuando el usuario pertenece a varias empresas.
- Busqueda global limitada a la empresa activa.
- Notificaciones in-app de la empresa activa.
- Avatar/iniciales y perfil en esquina superior derecha.
- Tickets como workspace principal.
- Panel de actividad.
- Lista de tickets activos.
- Detalle de ticket.
- La gestion de empresa y configuracion no ocupa navegacion diaria del usuario; se mueve al contexto administrativo.

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
- Top nav simple: Tickets y Actividad.
- La marca DoxTicket enlaza al workspace de Tickets.
- Empresa y Configuracion no se muestran como accesos del shell de usuario; el selector de empresa se conserva como flujo obligatorio tras login cuando aplica.
- `/admin` no se muestra en el shell de usuario; el superadmin entra al portal admin por URL directa o enlaces externos de administracion.
- Breadcrumbs en pantallas internas, por ejemplo `Tickets / DT-123`.
- En mobile: menu superior compacto.

La navegacion publica actual muestra solo `Login` en `/`; la home publica muestra el estado del instalador desde `system_settings.setup.completed`, no desde conteos de empresas. Si la tabla `system_settings` aun no existe durante una instalacion temprana o un test sin migraciones, la portada muestra instalador pendiente sin lanzar error 500. Las pantallas de login/setup no muestran botones duplicados en el encabezado. La navegacion autenticada incluye solo Tickets, Actividad y Salir como accion de sesion. `/app/dashboard` queda como ruta heredada que redirige a `/app/tickets`; `/app/kb` sigue existiendo como modulo interno pero no ocupa un acceso principal mientras se define el flujo de notas/recordatorios.

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
- Textos visibles en español con acentos correctos; los mensajes de validación no deben caer al inglés por falta de traducciones.

## Componentes
- `x-button`
- `x-input`
- `x-badge`
- `x-card`
- `x-modal`
- `x-dropdown`
- `x-empty-state`
- `x-health-status`

## Workspace principal
- `/app/tickets` es la primera pantalla operativa.
- Lista tipo inbox para saber que atender ahora.
- Busqueda por clave, asunto o correo.
- Busqueda visible como control principal; los filtros avanzados siguen soportados por backend/URL y pueden volver como panel dedicado si se necesita.

## Tickets
- Lista tipo inbox con busqueda visible.
- Filtros por estado, agente, prioridad, tipo, fuente y SLA siguen existiendo a nivel de query, pero no se muestran como controles principales para mantener la pantalla rapida.
- Filas enlazables hacia pagina completa de detalle.
- Accion rapida: asignarse desde la lista cuando el ticket no tiene agente.
- Detalle en pagina completa con hilo + panel lateral en desktop. La base implementada muestra mensajes, actividad, metadatos, respuesta al solicitante por correo con adjuntos seguros opcionales, nota interna, fusion de tickets y propiedades editables: estado, prioridad, tipo y agente.
- El origen del ticket se muestra como propiedad informativa y no editable.
- Marcador visible `DT-123`.

## Actividad
- Lista cronologica de eventos recientes.
- Filtros por tipo de actividad.
- Enlaces directos al detalle del ticket.
- No muestra cuerpos de correos ni notas en v1.

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
- Logo DoxTicket desde `public/brand/doxticket.svg` en shell publico, shell autenticado y admin.
- Favicon SVG declarado en las vistas principales.
- `Powered by DoxTicket` obligatorio.

## Relacion
- `08 - Diseño/UI UX.md`
- `08 - Diseño/Identidad Visual.md`
- `Backend.md`
