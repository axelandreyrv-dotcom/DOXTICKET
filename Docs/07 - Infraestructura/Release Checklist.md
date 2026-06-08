# Release Checklist — DoxTicket v1

## Antes de publicar
- Confirmar que la rama no contiene `.env`, `.env.docker`, backups, logs, adjuntos ni secretos.
- Ejecutar `php artisan test`.
- Ejecutar `vendor/bin/pint --dirty`.
- Ejecutar `npm run build`.
- Ejecutar `git diff --check`.
- Levantar Docker con `.env.docker` usando `docker compose --env-file .env.docker up -d --build`.
- Ejecutar migraciones en PostgreSQL.
- Ejecutar `docker compose --env-file .env.docker exec app php artisan optimize`.
- Confirmar que los contenedores PHP usan el codigo de la imagen, que `web` sirve `public/build` desde su imagen y que solo se persisten `storage/` y `bootstrap/cache` como volumenes nombrados.
- Completar `/setup` en una base limpia.
- Entrar a `/app/tickets` con superadmin.
- Entrar a `/admin` y revisar health.

## QA manual minimo
- Crear empresa desde `/admin/companies`.
- Crear usuario desde `/admin/users/invite`.
- Enviar enlace de contrasena a un usuario.
- Activar 2FA en `/app/settings`.
- Crear ticket manual.
- Asignar ticket.
- Agregar nota interna.
- Subir adjunto permitido y bloquear adjunto peligroso.
- Cambiar estado hasta resuelto y cerrado.
- Ejecutar backup manual.
- Revisar auditoria.

## Correo real
- Configurar SMTP global.
- Configurar cuenta IMAP/SMTP de una empresa.
- Probar conexion.
- Enviar correo real al soporte.
- Confirmar que crea ticket.
- Responder desde ticket y verificar llegada al solicitante.

## Criterio de salida
- Sin errores 500.
- Sin 403 inesperado para usuarios validos.
- Sin secretos visibles en UI, logs de errores ni exports.
- Health sin fallos criticos.
- Warnings aceptados solo si estan documentados, por ejemplo SMTP no configurado durante QA local.
