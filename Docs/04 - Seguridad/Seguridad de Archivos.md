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

Estado implementado actual:
- Disco `private` configurado en `config/filesystems.php` apuntando a `storage/app/private`.
- `.gitignore` excluye `storage/app/private/` para evitar publicar adjuntos, backups u otros datos privados de runtime.
- Subida desde detalle: `POST /app/tickets/{ticket}/attachments`.
- Adjuntos salientes desde respuesta: `POST /app/tickets/{ticket}/replies`.
- Ingesta desde correo entrante: adjuntos MIME normalizados por IMAP y guardados por el mismo servicio de adjuntos.
- El backend deriva `company_id` desde la empresa activa y no acepta `company_id` del cliente.
- La ruta fisica usa `attachments/<company_id>/<ticket_id>/<uuid>/<filename-sanitizado>`.
- Se guarda `checksum_sha256`, MIME reportado por Laravel, tamano, nombre sanitizado y relacion opcional con `ticket_message_id`.
- El limite por defecto es 10 MB y se configura con `DOXTICKET_ATTACHMENT_MAX_BYTES`.

## Validacion
- Tamano maximo configurable.
- Bloquear ejecutables/scripts/accesos directos.
- Validar magic bytes.
- Doble extension sospechosa se bloquea o registra.

Estado implementado actual: se valida archivo requerido cuando aplica y tamano maximo configurable; se bloquean extensiones ejecutables/scripts comunes (`exe`, `bat`, `cmd`, `ps1`, `sh`, `js`, `vbs`, `msi`, `lnk`, entre otras), MIME sospechoso basico y adjuntos entrantes/salientes que exceden el limite. La validacion avanzada por magic bytes queda pendiente.

## Adjuntos por correo
- Mismas reglas.
- Si se bloquea un adjunto, solo se registra evento interno.
- No avisar automaticamente al cliente en v1.

Estado implementado actual: los adjuntos permitidos se vinculan al `ticket_message` entrante; los bloqueados no se escriben en storage y generan `ticket.attachment_blocked` con nombre sanitizado, MIME y razon `blocked_file_type` o `file_too_large`.

## Adjuntos salientes
- Mismas reglas de tamano y bloqueo que los adjuntos entrantes.
- Si se bloquea un adjunto de respuesta, no se envia el correo ni se guarda mensaje outbound.
- Si el envio falla, no se guardan los adjuntos.

Estado implementado actual: los adjuntos permitidos en una respuesta se envian por SMTP/API OAuth, se escriben en disco privado despues del envio exitoso y se asocian al `ticket_message` outbound.

## Descarga
- `GET /app/attachments/{uuid}/download`
- `auth` + tenant + policy.
- `Content-Disposition: attachment`.
- `X-Content-Type-Options: nosniff`.
- `Cache-Control: private, no-store`.

Estado implementado actual: `GET /app/attachments/{uuid}/download` usa autenticacion y middleware tenant; busca el adjunto por UUID dentro de la empresa activa, devuelve `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff` y cache privada sin almacenamiento.

## Imagenes externas en correos
- Bloqueadas por privacidad.
- UI ofrece accion para abrir/cargar imagenes si el usuario decide.

Estado implementado actual: el procesador remueve las etiquetas `<img>` externas del HTML guardado, conserva hasta 20 URLs `http/https` en `ticket_messages.external_image_urls` y el detalle del ticket muestra enlaces `Abrir imagen` con `target="_blank"` y `rel="noopener noreferrer nofollow"`. DoxTicket no renderiza esas imagenes inline para evitar fugas de IP, cliente de navegador o confirmacion de lectura.

## Borrado
- Soft delete.
- Limpieza fisica por job.
- Auditoria en eliminaciones criticas.

## Relacion con otros documentos
- `05 - Módulos/Tickets.md`
- `05 - Módulos/Correo.md`
- `07 - Infraestructura/Nginx.md`
