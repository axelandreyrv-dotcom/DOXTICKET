# Modulo Base de Conocimiento — DoxTicket

## Proposito
Describir la base de conocimiento interna por empresa.

## Ambito
- Solo usuarios internos.
- No publica al usuario final en v1.
- Articulos Markdown.
- Disponible en todas las instalaciones.

## Funciones
- Crear, editar, publicar, archivar y borrar.
- Busqueda por titulo/contenido.
- Tags/categorias.
- Render Markdown seguro.

## Permisos
- Admin: todo.
- Supervisor: crear/editar/publicar/archivar.
- Agent: lectura.

## Seguridad
- Sanitizar HTML renderizado.
- Aislamiento por `company_id`.

## Estado implementado actual
- Rutas implementadas: `/app/kb`, `/app/kb/create`, `POST /app/kb`, `/app/kb/{article}`, `/app/kb/{article}/edit`, `PATCH /app/kb/{article}`, `PATCH /app/kb/{article}/archive` y `DELETE /app/kb/{article}`.
- Tabla implementada: `kb_articles` con slug unico por empresa, Markdown original, HTML cacheado, estado y fecha de publicacion.
- Los agentes pueden buscar y leer solo articulos publicados de la empresa activa.
- Admin y supervisor pueden crear, editar, publicar, archivar y borrar articulos internos.
- El render Markdown usa HTML inseguro bloqueado y enlaces inseguros deshabilitados.
- Archivar y borrar usan `data-confirm` con el modal accesible global antes de enviar la accion.
- El buscador usa `type="search"`, `autocomplete="off"` y placeholder con elipsis para indicar patron de entrada.
- Tags/categorias UI y permisos avanzados quedan para una fase posterior.

## Relacion
- `Tickets.md`
- `Usuarios.md`
