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

## Relacion
- `Tickets.md`
- `Usuarios.md`
