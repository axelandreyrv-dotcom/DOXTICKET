# UI UX — DoxTicket

## Proposito
Definir principios de experiencia e interfaz.

## Direccion
DoxTicket debe sentirse como una herramienta de trabajo real: bonita, calmada, entendible y eficiente. No debe parecer pagina promocional generica ni interfaz generada por IA.

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
- Secciones principales: Tickets y Actividad.
- Tickets es la primera pantalla operativa y la marca DoxTicket enlaza a esa vista.
- Empresa y Configuracion no aparecen como secciones del shell de usuario; pertenecen al contexto administrativo o a flujos dedicados.
- El portal `/admin` no aparece en la navegacion del usuario; se mantiene separado para superadmins.
- La navegacion principal debe usar `aria-label` y marcar la seccion activa con `aria-current="page"`.
- Breadcrumbs en pantallas internas.
- En mobile, menu superior compacto.
- Contenido con densidad equilibrada para trabajo diario.

### Tickets
- Lista principal tipo inbox: todos los tickets activos.
- Reemplaza al dashboard como pantalla para decidir que atender ahora.
- Busqueda visible por clave `DT-123`, asunto o correo para ubicar tickets durante llamadas.
- La busqueda es el control principal visible; filtros avanzados no deben saturar la primera vista.
- Accion rapida: asignarse.
- Al abrir un ticket, usar pagina completa.
- Detalle con dos columnas en desktop:
  - Hilo y respuesta.
  - Panel lateral de propiedades con estado, prioridad, tipo, agente, fuente, categoria y fechas.
- Estado, prioridad, tipo y agente se editan en el panel lateral; fuente se muestra como dato informativo de auditoria.
- Mensajes como bloques de correo profesionales, con etiquetas visibles para correo entrante, respuesta enviada y nota interna.
- El bloque Responder permite adjuntar archivos seguros sin convertir el area de respuesta en un formulario pesado.
- `DT-123` siempre visible y facil de copiar/buscar desde lista y detalle, con boton de copiado y confirmacion anunciable.

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
- Las acciones sensibles usan `data-confirm` y se muestran mediante el modal accesible global del shell autenticado, no mediante `window.confirm`.
- Toasts discretos.
- Los mensajes de exito del shell autenticado se anuncian con `role="status"` y `aria-live="polite"` para lectores de pantalla.
- Las acciones de copiado en pantalla deben usar botones reales y anunciar el resultado con `role="status"` y `aria-live="polite"`.
- Los errores de campo deben quedar junto al input, con `role="alert"`, `aria-live="polite"` y el input asociado mediante `aria-invalid` y `aria-describedby`.
- En el detalle de ticket, las validaciones de responder, nota interna, adjunto, propiedades y fusion deben seguir ese mismo patron aunque compartan la misma pagina.
- Los formularios operativos de tickets deben declarar `autocomplete="off"` en campos internos; correos y claves visibles como `DT-123` deben desactivar spellcheck y usar `inputmode` apropiado.
- Los formularios de configuracion tecnica deben evitar sugerencias del navegador en hosts, usuarios, carpetas, correos operativos y secretos; las contrasenas de servicios se tratan como configuracion nueva con `autocomplete="new-password"`.
- Los campos de busqueda internos deben usar `type="search"`, `autocomplete="off"` y placeholders con elipsis cuando sugieren una entrada parcial.
- Empty states con CTA.
- Iconos minimalistas y consistentes.

## Entrada publica
Para self-hosted, la pantalla publica de una instalacion no debe vender un servicio alojado. Debe:
- Identificar DoxTicket.
- Permitir login.
- Mantener el instalador inicial usable con teclado, labels reales y errores asociados a campos.
- Asociar errores de login a sus campos y anunciarlos igual que en la app autenticada.
- Mostrar `Powered by DoxTicket`.
- En una web oficial futura, `doxticket.com` sera hub de docs/releases/donaciones.

## Accesibilidad
- WCAG AA.
- Foco visible.
- Labels.
- `aria-label` en botones de solo icono.
- `aria-current="page"` en navegacion activa.
- Cambios asíncronos y confirmaciones de acciones deben usar regiones anunciables sin duplicar mensajes visibles.
- Los formularios principales deben asociar cada error visible al control que lo origina.
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
