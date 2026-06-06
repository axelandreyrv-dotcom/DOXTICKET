# Flujos Principales — DoxTicket

## Proposito del documento
Describir los flujos end-to-end mas importantes de DoxTicket como aplicacion open source self-hosted.

## Flujo 1 — Instalacion con Docker Compose
1. Administrador descarga `docker-compose.yml` o clona el release estable.
2. Configura `.env` desde `.env.example`.
3. Ejecuta `docker compose up -d`.
4. Abre la URL local o dominio configurado.
5. Entra a `/setup`.

## Flujo 2 — Setup inicial
1. `/setup` pide idioma primero.
2. Valida PostgreSQL, Redis, storage, permisos, `APP_KEY`, `APP_DEBUG` y colas.
3. Crea superadmin.
4. Crea empresa inicial.
5. Crea una membresia `admin` del superadmin en la empresa inicial para que pueda usar `/app`.
6. Permite configurar SMTP global del sistema o saltarlo.
7. Pregunta si se activa telemetria anonima opcional.
8. Finaliza, bloquea `/setup` y redirige a `/login`.

## Flujo 3 — Login centralizado
1. Usuario va a `/login`.
2. Introduce correo y contrasena.
3. Backend valida credenciales.
4. Si es superadmin, puede ir a `/admin`.
5. Si tiene membresias de empresa, elige empresa activa antes de entrar a `/app`.
6. La sesion guarda la membresia activa; de ahi se resuelve `company_id`.

## Flujo 4 — Configuracion de correo
1. Admin de empresa entra a `/app/settings/correo`.
2. Configura una cuenta de soporte.
3. Sistema prueba recepcion y envio.
4. Si falla, muestra error accionable y queda visible en `/admin/health`.
5. Si funciona, la cuenta queda activa para ingest y envio.

Estado implementado actual: Settings permite guardar una cuenta IMAP/SMTP y ejecutar prueba manual de conexion desde `/app/settings/mail/test`; los errores se guardan sanitizados en la cuenta activa. La base OAuth permite iniciar autorizacion para Gmail/Microsoft 365 desde `/app/settings/mail/oauth/{provider}/redirect`, con `state` ligado a proveedor y empresa activa. El callback `/app/settings/mail/oauth/{provider}/callback` consume `state`, intercambia `code` por tokens y los guarda cifrados en la cuenta OAuth activa.

## Flujo 5 — Ticket por correo
1. Usuario externo envia correo a la cuenta de soporte.
2. Job de DoxTicket lee la bandeja.
3. Detecta loops/auto-respuestas.
4. Sanitiza HTML y bloquea imagenes externas.
5. Identifica thread por headers y marcador `[DT-123]`.
6. Si no hay match confiable, crea ticket nuevo con estado `Nuevo`.
7. Envia confirmacion automatica de recibido.
8. Adjuntos se procesan en storage privado.
9. Agente lo ve en el workspace de Tickets.

Estado implementado actual: la confirmacion automatica se envia con asunto `[DT-123] Recibimos tu solicitud` cuando la opcion de la cuenta esta activa. Si el SMTP del tenant falla, la ingesta no se revierte y queda evento interno `mail.auto_reply_failed`.

## Flujo 6 — Gestion diaria de tickets
1. Agente entra a Tickets.
2. Ve la lista activa y filtros para saber que requiere atencion.
3. Revisa todos los tickets activos.
4. Puede asignarse rapidamente desde la lista.
5. Abre el detalle para cambiar estado, prioridad, tipo, agente o responder.

## Flujo 7 — Respuesta al cliente
1. Agente abre `/app/tickets/{ticket}` y usa el bloque Responder.
2. La respuesta sale como agente desde la cuenta de soporte de la empresa.
3. Si no hay cuenta de correo activa o correo de solicitante, el sistema bloquea el envio y muestra error.
4. Se mantiene marcador `[DT-123]` en el asunto.
5. La respuesta queda guardada en el hilo como mensaje publico outbound.
6. El estado no cambia automaticamente a "En espera del cliente"; el agente decide.

Estado implementado actual: cuentas `imap_smtp` envian por SMTP; cuentas `gmail` y `microsoft365` envian por API OAuth. Si el proveedor falla, el error se sanitiza, queda en la cuenta y no se guarda mensaje outbound.

## Flujo 8 — Respuesta del cliente
1. Si el cliente responde a un ticket activo, el ticket queda `Abierto`.
2. Si responde a un ticket `Resuelto` o `Cerrado`, vuelve a `Abierto`.
3. Si responde a un ticket fusionado, el mensaje va al ticket principal.

## Flujo 9 — Ticket manual
1. Agente abre crear ticket.
2. Llena asunto, descripcion, tipo, solicitante opcional, prioridad, categoria, responsable y adjuntos.
3. Se crea ticket y mensaje inicial.
4. Es flujo secundario frente al correo.

## Flujo 10 — Fusion de tickets
1. Agente, supervisor o admin selecciona fusionar.
2. Busca ticket destino dentro de la misma empresa.
3. Elige principal/secundario.
4. Confirma la accion en el modal accesible del shell.
5. El secundario queda `Fusionado`.
6. Mensajes futuros del secundario se agregan al principal.
7. Se registra auditoria.

Estado implementado actual: el detalle de ticket permite indicar el ticket principal por clave visible `DT-123`; antes de enviar muestra confirmacion mediante el modal accesible global; el backend valida empresa activa, evita fusion contra si mismo y registra eventos en ambos tickets.

## Flujo 11 — Backups
1. Superadmin entra a `/admin`.
2. Ejecuta backup manual local desde el bloque Backups.
3. DoxTicket registra la ejecucion en `backup_runs` y guarda el artefacto en storage privado.
4. DoxTicket muestra estado del ultimo backup exitoso.
5. Desde `/admin/settings`, opcionalmente activa backup automatico local diario y define la hora local de ejecucion.
6. Antes de actualizar, el sistema exige backup reciente.

Estado implementado actual: `/admin/backups` permite generar backup local manual protegido para superadmins. `/admin/settings` permite configurar ventana segura, retencion local y backup automatico diario apagado por defecto. La retencion local se aplica diariamente y marca backups antiguos como `pruned`. Configuracion de destinos externos y restauracion automatizada quedan pendientes.

## Flujo 12 — Nueva version y rollback
1. `/admin` muestra version instalada.
2. DoxTicket consulta GitHub por release estable nueva.
3. Si hay update, muestra aviso y changelog.
4. Rollback esta visible, pero solo se habilita si existe version anterior/backup valido.
5. `/admin/rollback` ejecuta un preflight protegido para superadmins y dirige al procedimiento manual; v1 no restaura automaticamente.

## Flujo 13 — Telemetria opcional
1. Superadmin revisa el bloque Telemetria en `/admin`.
2. DoxTicket muestra si esta activa o apagada.
3. DoxTicket informa que no se envian nombres, correos, asuntos, cuerpos, adjuntos ni secretos.
4. Superadmin puede activar o desactivar el consentimiento local.

Estado implementado actual: `/setup` guarda el consentimiento inicial y `/admin/telemetry` permite cambiarlo despues. No existe envio remoto de reportes en este corte.

## Relacion con otros documentos
- `Requisitos Funcionales.md`
- `05 - Módulos/Tickets.md`
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `07 - Infraestructura/Deploy.md`
