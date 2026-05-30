# Seguridad de Archivos — DoxTicket

## Proposito del documento
Definir manejo seguro de adjuntos.

## Principios
1. Adjuntos fuera de `public/`.
2. Descarga solo por rutas protegidas.
3. Validar MIME real.
4. Limites configurables por instalacion/empresa.
5. Aislamiento por `company_id`.

## Almacenamiento
- Disco `private`.
- Ruta: `attachments/<company_id>/<ticket_id>/<uuid>`.
- Nombre sanitizado.
- S3 compatible opcional/futuro.

## Validacion
- Tamano maximo configurable.
- Bloquear ejecutables/scripts/accesos directos.
- Validar magic bytes.
- Doble extension sospechosa se bloquea o registra.

## Adjuntos por correo
- Mismas reglas.
- Si se bloquea un adjunto, solo se registra evento interno.
- No avisar automaticamente al cliente en v1.

## Descarga
- `GET /app/attachments/{uuid}/download`
- `auth` + tenant + policy.
- `Content-Disposition: attachment`.
- `X-Content-Type-Options: nosniff`.
- `Cache-Control: private, no-store`.

## Imagenes externas en correos
- Bloqueadas por privacidad.
- UI ofrece accion para abrir/cargar imagenes si el usuario decide.

## Borrado
- Soft delete.
- Limpieza fisica por job.
- Auditoria en eliminaciones criticas.

## Relacion con otros documentos
- `05 - Módulos/Tickets.md`
- `05 - Módulos/Correo.md`
- `07 - Infraestructura/Nginx.md`
