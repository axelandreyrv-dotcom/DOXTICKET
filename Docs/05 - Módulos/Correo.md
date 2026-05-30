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

## Ingesta
1. Job con lock por `mail_account_id`.
2. Lee mensajes nuevos.
3. Detecta auto-respuestas y loops.
4. Sanitiza HTML.
5. Bloquea imagenes externas por privacidad.
6. Identifica thread por `Message-Id`, `In-Reply-To`, `References` y `[DT-123]`.
7. Crea o actualiza ticket.
8. Envia confirmacion automatica de recibido.

## Confirmacion automatica
- Activa por defecto.
- Se envia al crear ticket nuevo.
- Debe incluir marcador `[DT-123]`.
- Debe evitar loops con headers apropiados.

## Outbound
- Respuestas salen como agente desde la cuenta de soporte.
- Mantienen headers y marcador visible.
- Firma/plantilla se aplican segun configuracion.

## Asunto
- Si no hay asunto: `Sin Asunto`.
- Respuestas incluyen `[DT-123]`.

## HTML
- Se prefiere guardar version segura aunque se pierda formato.
- Allowlist de tags seguros.
- Scripts, iframes y objetos eliminados.
- Imagenes externas bloqueadas por defecto con opcion de abrir.

## Adjuntos
- Validacion MIME/tamano.
- Ejecutables/scripts bloqueados.
- Bloqueo registra solo evento interno.

## Errores
- Errores de cuenta visibles en settings y `/admin/health`.
- Logs claros sin secretos.

## Loops
- Deteccion por headers (`Auto-Submitted`, `Precedence`, etc.).
- Rate limit por remitente/cuenta.
- Pausar auto-respuestas cuando hay patron de loop.

## Gmail / Microsoft 365
- OAuth.
- Tokens cifrados.
- Adaptadores mockeables.

## Relacion con otros documentos
- `Tickets.md`
- `02 - Arquitectura/Colas y Jobs.md`
- `04 - Seguridad/Seguridad de Archivos.md`
