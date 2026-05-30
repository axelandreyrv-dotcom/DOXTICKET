# UI UX — DoxTicket

## Proposito
Definir principios de experiencia e interfaz.

## Direccion
DoxTicket debe sentirse como una herramienta de trabajo real: bonita, calmada, entendible y eficiente. No debe parecer pagina promocional generica ni dashboard generado por IA.

Referencia de producto: Linear/Notion, pero con identidad propia de helpdesk IT self-hosted.

## Skills recomendadas para diseno
- `minimalist-ui` como direccion visual principal.
- `web-design-guidelines` para accesibilidad, claridad y buenas practicas.
- `impeccable` para polish final.
- `emil-design-eng` para microinteracciones sobrias.
- `vercel-react-best-practices` solo como referencia de performance, sin cambiar Blade/Livewire.

## Principios
- Funcionalidad primero.
- Una accion primaria clara por pantalla.
- Informacion densa pero respirable.
- Estados vacios utiles.
- Confirmaciones en acciones destructivas.
- Carga local con skeletons.
- Todo usable por teclado.

## App autenticada

### Layout
- Navegacion superior simple.
- Topbar compacta con logo, empresa activa, busqueda global, notificaciones y perfil.
- Secciones principales: Dashboard, Tickets, Base de conocimiento y Configuracion.
- Boton visible: Ver tickets.
- Link a `/admin` solo para superadmins.
- Breadcrumbs en pantallas internas.
- En mobile, menu superior compacto.
- Contenido con densidad equilibrada para trabajo diario.

### Dashboard
- Bonito y calmado.
- Completo, con cards claras y listas accionables.
- Resumen del dia arriba.
- Enfoque: que atender ahora.

### Tickets
- Lista principal tipo inbox: todos los tickets activos.
- Filtros visibles.
- Accion rapida: asignarse.
- Al abrir un ticket, usar pagina completa.
- Detalle con dos columnas en desktop:
  - Hilo y respuesta.
  - Metadatos, estado, prioridad, categoria, responsable, adjuntos.
- Mensajes como bloques de correo profesionales.
- `DT-123` siempre visible y facil de copiar/buscar.

### Admin
- Filament tematizado para sentirse parte de DoxTicket.
- Health, backups y updates deben ser muy claros.

## Componentes
- Botones con estados claros.
- Inputs con labels reales.
- Badges de estado/prioridad.
- Tablas densas pero legibles.
- Cards claras y pequenas; evitar tarjetas enormes.
- Modales para confirmaciones.
- Toasts discretos.
- Empty states con CTA.
- Iconos minimalistas y consistentes.

## Entrada publica
Para self-hosted, la pantalla publica de una instalacion no debe vender un servicio alojado. Debe:
- Identificar DoxTicket.
- Permitir login.
- Mostrar `Powered by DoxTicket`.
- En una web oficial futura, `doxticket.com` sera hub de docs/releases/donaciones.

## Accesibilidad
- WCAG AA.
- Foco visible.
- Labels.
- `aria-label` en botones de solo icono.
- No depender solo del color.

## Responsive
- 360px minimo.
- Tablas pueden transformarse en cards o scroll horizontal.
- Acciones principales accesibles en mobile.

## Motion
- Sutil.
- Solo transform/opacity.
- Respetar `prefers-reduced-motion`.
- Evitar animaciones decorativas.
