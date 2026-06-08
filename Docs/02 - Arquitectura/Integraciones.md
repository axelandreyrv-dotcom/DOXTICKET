# Integraciones — DoxTicket

## Proposito del documento
Listar integraciones externas de DoxTicket y sus consideraciones de seguridad.

## Integraciones v1
- SMTP global del sistema.
- SMTP + IMAP por empresa.
- Gmail via OAuth/API.
- Microsoft 365 via OAuth/API.
- GitHub Releases para aviso de nueva version.
- Telemetria opcional anonima.
- S3 compatible opcional/futuro para adjuntos/backups.

## 1. SMTP global del sistema

### Proposito
Enviar invitaciones, recuperacion de contrasena, alertas del sistema y notificaciones administrativas.

### Seguridad
- Secretos en `.env`.
- Puede omitirse durante setup.
- Health check en `/admin`.

## 2. SMTP + IMAP por empresa

### Proposito
Permitir que cada empresa use su propia cuenta de soporte.

### Reglas v1
- Una cuenta por empresa.
- Credenciales cifradas en BD.
- Test de conexion antes de activar.
- Lock por cuenta durante ingesta.

## 3. Gmail
- OAuth 2.0.
- Tokens cifrados.
- Scopes minimos necesarios.
- Adaptador independiente y mockeable.

Estado implementado actual: existe base de almacenamiento OAuth para cuentas `provider=gmail`: tokens cifrados, scopes, identificador del usuario proveedor, expiracion y fecha de conexion. La URL de autorizacion usa el endpoint oficial de Google, `access_type=offline`, `prompt=consent`, scopes configurables y `state` de sesion ligado al tenant. El callback consume `state`, intercambia `code` por tokens mediante `OAuthHttpTokenClient` y guarda errores sanitizados en `last_error`. La renovacion automatica usa `grant_type=refresh_token` desde la cola `mail` y preserva el refresh token existente si Google no devuelve uno nuevo. La ingesta lista mensajes por `users.messages.list`, obtiene detalle con `users.messages.get`, descarga adjuntos con `users.messages.attachments.get` y normaliza al pipeline comun. Las respuestas de tickets salen por Gmail API `users.messages.send` con MIME `raw`.

## 4. Microsoft 365
- OAuth 2.0 / Microsoft Graph.
- Tokens cifrados.
- Adaptador independiente y mockeable.

Estado implementado actual: existe base de almacenamiento OAuth para cuentas `provider=microsoft365`: tokens cifrados, scopes, identificador del usuario proveedor, expiracion y fecha de conexion. La URL de autorizacion usa Microsoft identity platform v2.0, tenant configurable, `offline_access`, scopes Graph configurables y `state` de sesion ligado al tenant. El callback usa el endpoint token del tenant configurado, consume `state`, intercambia `code` por tokens mediante `OAuthHttpTokenClient` y guarda errores sanitizados en `last_error`. La renovacion automatica usa `grant_type=refresh_token` en el endpoint del tenant configurado y se limita a cuentas activas. La ingesta lee `mailFolders/Inbox/messages`, descarga `fileAttachment` desde `/me/messages/{id}/attachments` y normaliza al pipeline comun. Las respuestas de tickets salen por Microsoft Graph `POST /me/sendMail`.

## 5. GitHub Releases

### Proposito
Consultar si hay una version estable nueva.

### Seguridad y privacidad
- No enviar nombres de empresas, correos, tickets ni secretos.
- Consultar releases publicas del repositorio oficial.
- Guardar resultado localmente para mostrarlo en `/admin`.

Estado implementado actual:
- Repositorio configurable con `DOXTICKET_GITHUB_REPOSITORY` o desde `/admin/settings`; el valor guardado en `system_settings.updates.github_repository` tiene prioridad y `.env` queda como fallback.
- Cliente implementado en `App\Services\Admin\GitHubReleaseUpdateChecker`.
- Usa el endpoint publico `https://api.github.com/repos/{owner}/{repo}/releases/latest` con cabeceras oficiales de GitHub API.
- Guarda `checked_at`, version instalada, version estable detectada, enlace publico de release, nombre, extracto de changelog y error sanitizado en `system_settings.updates.latest`.
- Se puede ejecutar automaticamente desde scheduler/cola o manualmente con `POST /admin/updates/check`.
- No agrega query params ni payload con datos de la instalacion.

## 6. Telemetria opcional
- Apagada por defecto.
- Activacion explicita en `/setup`.
- Datos permitidos: version, metodo de instalacion, sistema operativo/container, conteos aproximados y anonimos.
- Datos prohibidos: nombres, correos, asuntos, cuerpos, adjuntos, IPs publicas, secretos.

## 7. S3 compatible
- Opcional/futuro.
- Usar driver de Laravel.
- Adjuntos y backups deben seguir protegidos por policies o URLs firmadas.

## Fuera de v1
- Billing integrado.
- Portales o flujos de pago comercial.
- Webhooks de pago.
- Marketplace de integraciones.

## Relacion con otros documentos
- `04 - Seguridad/Gestión de Secretos.md`
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `02 - Arquitectura/Colas y Jobs.md`
