# Modulo Correo — DoxTicket

## Proposito
Describir recepcion, procesamiento y envio de correo.

## Prioridad v1
Correo entrante estable. Evitar duplicados es mas importante que procesar automaticamente casos ambiguos.

## Cuentas
- SMTP global del sistema para invitaciones, reset y alertas.
- Una cuenta de soporte por empresa.
- IMAP/SMTP generico.
- Gmail y Microsoft 365 desde v1 segun roadmap.

Estado implementado actual:
- El SMTP global puede configurarse desde `/admin/settings` para invitaciones, reset, alertas y correos internos del sistema.
- Los valores SMTP globales guardados en `system_settings.mail.global.*` tienen prioridad sobre `.env`; `.env` queda como fallback de instalacion temprana o recuperacion manual.
- La contrasena SMTP global se guarda cifrada en `system_settings.mail.global.password`, no se renderiza de vuelta en la UI y dejar el campo vacio conserva el secreto existente.
- Los correos de restablecimiento de contrasena salen por SMTP global mediante `App\Notifications\Auth\ResetPasswordNotification`, con asunto en espanol y enlace a `/password/reset/{token}`.
- La plantilla de reset conserva el tono sobrio de DoxTicket y no incluye contrasenas ni secretos.

## Configuracion por empresa
- Ruta UI implementada: `/app/settings`.
- Guardado implementado: `POST /app/settings/mail`.
- Prueba manual implementada: `POST /app/settings/mail/test`.
- Los errores inline del formulario de correo se anuncian con `role="alert"` y quedan asociados al campo con `aria-invalid` y `aria-describedby`.
- Los campos tecnicos del formulario de correo declaran metadatos de navegador explicitos: hosts, usuario, correo y carpeta desactivan autocompletado/correccion; puertos usan entrada numerica; la contrasena usa `autocomplete="new-password"` para evitar rellenos accidentales.
- El formulario configura IMAP y SMTP generico para la empresa activa.
- El servidor ignora cualquier `company_id` enviado por el cliente y usa el tenant de sesion.
- La contrasena se guarda cifrada en `mail_accounts.password_encrypted`.
- Al actualizar la cuenta, dejar la contrasena vacia conserva el secreto existente.
- En v1 se refuerza una sola cuenta de soporte por empresa.
- La confirmacion automatica queda configurable, activa por defecto y se envia al crear tickets por correo si la cuenta SMTP del tenant esta disponible.
- El boton `Probar Conexion` valida IMAP/SMTP de la cuenta activa y guarda errores sanitizados en `last_error`.

## Ingesta
1. Job con lock por `mail_account_id`.
2. Lee mensajes nuevos.
3. Detecta auto-respuestas y loops.
4. Sanitiza HTML.
5. Bloquea imagenes externas por privacidad.
6. Identifica thread por `Message-Id`, `In-Reply-To`, `References` y `[DT-123]`.
7. Crea o actualiza ticket.
8. Envia confirmacion automatica de recibido.

Estado implementado actual:
- `IngestMailboxJob` se despacha cada minuto para cuentas activas.
- El job delega lectura a `MailboxClient` y procesamiento a `InboundMailProcessor`.
- En exito actualiza `last_uid` solo hacia adelante, limpia `last_error` y marca `last_sync_at`.
- En error guarda `last_error` sanitizado y no expone contrasenas, tokens ni usuario.
- Si falla el procesamiento a mitad de lote, conserva el ultimo `last_uid` procesado con exito y deja los siguientes para reintento.
- `ImapMailboxClient` normaliza mensajes crudos de IMAP hacia `InboundMailMessage`.
- `NativeImapConnection` usa la extension PHP IMAP para leer mensajes nuevos por UID, respetando `mail_accounts.last_uid`.
- Para compatibilidad con servidores IMAP y PHP IMAP, `NativeImapConnection` solicita UIDs con criterio `ALL` y filtra en PHP los UIDs ya procesados en vez de usar criterios `UID n:*`.
- `NativeImapConnection` valida certificados TLS por defecto. `DOXTICKET_IMAP_VALIDATE_CERT=false` solo se permite como excepcion temporal de QA local cuando antivirus/proxy TLS rompen la cadena de certificados; no es una configuracion recomendada para produccion.
- `RoutingMailboxClient` enruta cuentas `imap_smtp` hacia IMAP y cuentas `gmail`/`microsoft365` hacia `OAuthMailboxClient`.
- `OAuthMailboxClient` lista mensajes de Inbox por API OAuth, los entrega en orden antiguo-a-reciente hasta `last_uid` y normaliza headers, remitente, asunto, cuerpo, fecha y adjuntos hacia `InboundMailMessage`.
- Los adjuntos encontrados en partes MIME se normalizan junto al mensaje y se entregan al procesador de correo.
- La confirmacion automatica de recibido se envia despues de guardar el ticket, para no perder la ingesta si el envio falla.

## Procesador normalizado
- Clase implementada: `App\Services\Mail\InboundMailProcessor`.
- Entrada implementada: `App\Support\Mail\InboundMailMessage`.
- Salida implementada: `App\Support\Mail\InboundMailResult`.
- El procesador no conecta IMAP directamente; recibe un mensaje normalizado desde `ImapMailboxClient`.
- Crea tickets `source=email` para mensajes nuevos.
- Anexa respuestas al ticket por marcador `[DT-123]` o por headers `In-Reply-To` / `References`.
- La busqueda por headers se limita siempre a `company_id` de la cuenta de correo.
- Deduplica por `Message-Id` dentro de la empresa.
- Si falta `Message-Id`, deduplica con un fingerprint interno del mensaje para priorizar evitar duplicados.
- Reabre tickets `resolved` o `closed` cuando llega respuesta del solicitante.
- Los headers sensibles (`authorization`, `cookie`, `token`, `secret`, `password`) se guardan redactados.
- Guarda adjuntos permitidos en el disco privado y los asocia al mensaje entrante.
- Bloquea adjuntos peligrosos sin perder la ingesta del correo y registra solo un evento interno.
- Las imagenes externas `http/https` se eliminan del HTML seguro, se guardan como `external_image_urls` y solo se ofrecen como enlaces explicitos en el detalle del ticket.

## Confirmacion automatica
- Activa por defecto.
- Se envia al crear ticket nuevo.
- Debe incluir marcador `[DT-123]`.
- Debe evitar loops con headers apropiados.

Estado implementado actual:
- El procesador registra `mail.ticket_created` y envia `TicketReceivedMail` al solicitante cuando `auto_reply_enabled` esta activo.
- El asunto de confirmacion usa `[$public_key] Recibimos tu solicitud`.
- La confirmacion sale por el mailer SMTP de la cuenta activa de la empresa.
- Si SMTP falla, el ticket y el mensaje entrante se conservan y se registra `mail.auto_reply_failed` como evento interno.

## Outbound
- Respuestas salen como agente desde la cuenta de soporte.
- Mantienen headers y marcador visible.
- Firma/plantilla se aplican segun configuracion.

Estado implementado actual:
- El detalle del ticket envia respuestas desde `POST /app/tickets/{ticket}/replies`.
- El envio usa la cuenta de correo activa de la empresa seleccionada y nunca acepta `company_id` desde el cliente.
- La respuesta requiere `requester_email`; si el ticket no tiene correo de solicitante se bloquea con error visible.
- La respuesta se envia con asunto `[DT-123] <asunto>` y `From/Reply-To` de la cuenta de soporte activa.
- La respuesta puede incluir adjuntos seguros opcionales; se bloquean tipos peligrosos o archivos sobre el limite antes de enviar.
- Las cuentas `imap_smtp` siguen saliendo por SMTP configurado por tenant.
- Las cuentas `gmail` salen por Gmail API `users.messages.send` con mensaje MIME `raw` y bearer token OAuth.
- Las cuentas `microsoft365` salen por Microsoft Graph `POST /me/sendMail` con JSON y bearer token OAuth.
- Si falla el envio SMTP/API, no se guarda mensaje outbound y `last_error` se actualiza con el error sanitizado.
- Tras envio exitoso se guarda `ticket_messages.visibility=public`, `direction=outbound`, se marca `delivered_at` y se registra `ticket.reply_sent`.
- Si la respuesta incluye adjuntos, se guardan en storage privado asociados al mensaje outbound despues de confirmar el envio.
- Si la empresa no tiene cuenta activa, no se crea mensaje outbound ni se intenta enviar correo.

## Asunto
- Si no hay asunto: `Sin Asunto`.
- Respuestas incluyen `[DT-123]`.

## HTML
- Se prefiere guardar version segura aunque se pierda formato.
- Allowlist de tags seguros.
- Scripts, iframes y objetos eliminados.
- Imagenes externas bloqueadas por defecto con opcion de abrir.

Estado implementado actual: el procesador elimina tags peligrosos basicos, remueve imagenes externas, bloquea enlaces `javascript:` y elimina handlers inline `on*`, marcando `external_images_blocked=true` cuando aplica. Las URLs externas bloqueadas se conservan como metadato para que el agente pueda abrirlas manualmente en otra pestana sin que DoxTicket las cargue automaticamente.
Si despues de sanitizar no queda cuerpo visible ni texto util, el mensaje se conserva con el texto `Este correo no incluía contenido visible.` para evitar hilos en blanco.

## Adjuntos
- Validacion MIME/tamano.
- Ejecutables/scripts bloqueados.
- Bloqueo registra solo evento interno.

Estado implementado actual:
- Los adjuntos entrantes por IMAP se extraen desde partes MIME con nombre de archivo.
- Los adjuntos entrantes por Gmail API se descargan desde `users.messages.attachments.get` cuando una parte MIME trae `attachmentId`.
- Los adjuntos entrantes por Microsoft Graph se descargan desde `/me/messages/{id}/attachments` para `fileAttachment`.
- Los nombres de adjuntos con encoded-words MIME se decodifican antes de almacenarlos.
- Los adjuntos permitidos usan las mismas reglas y almacenamiento privado que los adjuntos subidos desde el detalle del ticket.
- Los adjuntos salientes de respuestas usan las mismas reglas, se envian por SMTP/API y se almacenan asociados al mensaje outbound.
- Los adjuntos bloqueados por tipo o tamano no se guardan en disco ni se notifican automaticamente al solicitante en v1; queda evento interno `ticket.attachment_blocked`.
- El limite de tamano se configura con `DOXTICKET_ATTACHMENT_MAX_BYTES` y aplica a adjuntos entrantes, respuestas salientes y subidas manuales.

## Errores
- Errores de cuenta visibles en settings y `/admin/health`.
- Logs claros sin secretos.

Estado implementado actual: `last_error` se muestra en `/app/settings`; el job de ingesta y la prueba manual de conexion lo actualizan con mensajes sanitizados.

## Requisito de runtime
- Docker instala la extension PHP IMAP en la imagen de aplicacion.
- En Ubuntu manual se debe instalar/habilitar `php-imap` para ingesta IMAP generica.
- Si la extension no existe, el cliente nativo falla de forma explicita y el job guarda error sanitizado en la cuenta.

## Loops
- Deteccion por headers (`Auto-Submitted`, `Precedence`, etc.).
- Rate limit por remitente/cuenta.
- Pausar auto-respuestas cuando hay patron de loop.

Estado implementado actual: se ignoran mensajes `Auto-Submitted` distintos de `no`, `Precedence: bulk|junk|list` y mensajes enviados desde la misma cuenta de soporte. Las confirmaciones automaticas usan `From/Reply-To` de la cuenta de soporte y marcador visible para conservar threading.

## Gmail / Microsoft 365
- OAuth.
- Tokens cifrados.
- Adaptadores mockeables.

Estado implementado actual:
- `mail_accounts` soporta proveedores `gmail` y `microsoft365` a nivel de modelo/schema.
- `OAuthTokenStore` guarda tokens de acceso/refresh cifrados, preserva el refresh token existente cuando el proveedor no entrega uno nuevo y limpia errores previos.
- `OAuthAuthorizationUrlFactory` genera URLs oficiales de autorizacion para Google y Microsoft 365 con scopes configurables.
- `OAuthStateStore` crea `state` aleatorio en sesion, ligado a proveedor y empresa activa, y lo consume una sola vez.
- `POST /app/settings/mail/oauth/{provider}/redirect` inicia el flujo OAuth desde el tenant activo y redirige al proveedor si las credenciales estan configuradas.
- `GET /app/settings/mail/oauth/{provider}/callback` valida y consume `state`, intercambia el `code` por tokens mediante `OAuthTokenClient` y guarda los tokens cifrados en la cuenta OAuth de la empresa activa.
- `OAuthHttpTokenClient` usa el endpoint token oficial de Google o Microsoft 365, respeta el tenant configurado para Microsoft y devuelve un `OAuthTokenSet` normalizado.
- `OAuthTokenRefresher` renueva tokens vencidos o por vencer en los proximos 5 minutos usando `grant_type=refresh_token`.
- `RefreshOAuthMailAccountsJob` corre en la cola `mail` y revisa solo cuentas activas `gmail` o `microsoft365` fuera del contexto tenant web.
- `OAuthTicketReplyApiClient` envia respuestas de tickets por Gmail API o Microsoft Graph cuando la cuenta activa usa OAuth.
- `OAuthMailboxClient` lee Inbox por Gmail API `users.messages.list/get` o Microsoft Graph `mailFolders/Inbox/messages`, descarga adjuntos de archivo y reutiliza el pipeline de ingesta existente.
- El almacenamiento OAuth rechaza cuentas `imap_smtp` para evitar mezclar contrasena IMAP/SMTP con tokens OAuth.
- Los errores del proveedor OAuth se guardan en `last_error` despues de sanitizar tokens, secretos y encabezados sensibles.
- Pendiente para Gmail/Microsoft 365: QA con buzones reales y optimizaciones incrementales especificas de cada proveedor.

## Relacion con otros documentos
- `Tickets.md`
- `02 - Arquitectura/Colas y Jobs.md`
- `04 - Seguridad/Seguridad de Archivos.md`
