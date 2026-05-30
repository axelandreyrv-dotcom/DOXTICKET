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

## Flujo 5 — Ticket por correo
1. Usuario externo envia correo a la cuenta de soporte.
2. Job de DoxTicket lee la bandeja.
3. Detecta loops/auto-respuestas.
4. Sanitiza HTML y bloquea imagenes externas.
5. Identifica thread por headers y marcador `[DT-123]`.
6. Si no hay match confiable, crea ticket nuevo con estado `Nuevo`.
7. Envia confirmacion automatica de recibido.
8. Adjuntos se procesan en storage privado.
9. Agente lo ve en dashboard/lista.

## Flujo 6 — Gestion diaria de tickets
1. Agente entra al dashboard.
2. Ve resumen del dia y tickets que requieren atencion.
3. Entra a todos los tickets activos.
4. Puede asignarse rapidamente desde la lista.
5. Abre el detalle para cambiar estado, prioridad, categoria o responder.

## Flujo 7 — Respuesta al cliente
1. Agente redacta respuesta.
2. La respuesta sale como agente desde la cuenta de soporte de la empresa.
3. Se mantiene marcador `[DT-123]` y headers de threading.
4. El estado no cambia automaticamente a "En espera del cliente"; el agente decide.

## Flujo 8 — Respuesta del cliente
1. Si el cliente responde a un ticket activo, el ticket queda `Abierto`.
2. Si responde a un ticket `Resuelto` o `Cerrado`, pasa a `Reabierto`.
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
4. Confirma rapidamente.
5. El secundario queda `Fusionado`.
6. Mensajes futuros del secundario se agregan al principal.
7. Se registra auditoria.

## Flujo 11 — Backups
1. Superadmin entra a `/admin/backups`.
2. Configura destino, frecuencia y retencion.
3. DoxTicket muestra estado del ultimo backup.
4. Antes de actualizar, el sistema exige backup reciente.

## Flujo 12 — Nueva version y rollback
1. `/admin` muestra version instalada.
2. DoxTicket consulta GitHub por release estable nueva.
3. Si hay update, muestra aviso y changelog.
4. Rollback esta visible, pero solo funciona si existe version anterior/backup valido.

## Relacion con otros documentos
- `Requisitos Funcionales.md`
- `05 - Módulos/Tickets.md`
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `07 - Infraestructura/Deploy.md`
