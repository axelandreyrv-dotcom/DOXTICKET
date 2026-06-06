# Donaciones y Sostenibilidad — DoxTicket

## Proposito del documento
Definir como se sostiene DoxTicket en su etapa open source sin convertir la app en un flujo comercial.

## Decision final v1
DoxTicket v1 no incluye planes comerciales, billing, trial ni suscripciones dentro de la aplicacion.

## Modelo actual
- Proyecto open source self-hosted.
- Licencia: AGPLv3.
- Todas las funciones de la version open source estan disponibles para cualquier instalacion.
- No hay limites comerciales por agentes, tickets o almacenamiento definidos por plan.
- Los limites tecnicos pueden configurarse por instalacion o por empresa para proteger recursos.

## Donaciones
Las donaciones deben mostrarse de forma discreta:
- Footer publico.
- Footer o seccion secundaria del panel `/admin`.
- README del repositorio.

Canales permitidos:
- PayPal.
- GitHub Sponsors.
- Buy Me a Coffee.

Estado implementado actual:
- `/admin` puede mostrar una seccion secundaria `Apoyar DoxTicket`.
- Cada canal es opcional y se configura por `.env` o por superadmin desde `/admin/settings` como valor publico no secreto.
- Si un enlace no esta configurado o no es `http`/`https`, no se muestra.

## Regla de UX
Las donaciones nunca deben bloquear funcionalidad, mostrar avisos agresivos ni parecer requisito para usar el producto.

## Producto comercial futuro
Mas adelante DoxTicket puede ofrecer:
- Hosting oficial.
- Soporte profesional.
- Instalacion administrada.
- Servicios enterprise.

No prometer DoxTicket Cloud en v1 ni en la entrada publica/documentacion inicial.

## Relacion con otros documentos
- `00 - Visión General/Resumen del Proyecto.md`
- `00 - Visión General/Alcance v1.md`
- `05 - Módulos/Superadmin.md`
