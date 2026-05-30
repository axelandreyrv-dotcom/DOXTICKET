# Requisitos Funcionales — DoxTicket

## Proposito del documento
Listar las funcionalidades que DoxTicket debe ofrecer en v1, agrupadas por dominio.

## RF-1. Proyecto open source
- **RF-1.1** El README debe ser bilingue.
- **RF-1.2** El repositorio debe incluir `SECURITY.md`, `CONTRIBUTING.md` y `CODE_OF_CONDUCT.md`.
- **RF-1.3** Las releases deben publicarse con version estable.
- **RF-1.4** Las imagenes Docker deben publicarse versionadas.
- **RF-1.5** La app debe mostrar version instalada.
- **RF-1.6** Toda instalacion debe preservar `Powered by DoxTicket`.

## RF-2. Setup
- **RF-2.1** `/setup` pide idioma primero.
- **RF-2.2** `/setup` valida PostgreSQL, Redis, storage, permisos, `APP_KEY` y `APP_DEBUG`.
- **RF-2.3** `/setup` permite crear el superadmin.
- **RF-2.4** `/setup` crea una empresa inicial.
- **RF-2.5** `/setup` permite configurar SMTP global, pero se puede omitir.
- **RF-2.6** `/setup` ofrece telemetria opcional apagada por defecto.
- **RF-2.7** `/setup` se bloquea tras finalizar.

## RF-3. Autenticacion
- **RF-3.1** Login centralizado en `/login`.
- **RF-3.2** Recuperacion segura de contrasena.
- **RF-3.3** Verificacion de correo cuando aplique.
- **RF-3.4** 2FA opcional para todos los roles.
- **RF-3.5** Logout invalida la sesion actual.
- **RF-3.6** Si un usuario tiene varias membresias, debe elegir empresa antes de entrar a `/app`.
- **RF-3.7** La ultima empresa usada puede recordarse para sugerirla, pero el usuario debe poder cambiarla.

## RF-4. Empresas
- **RF-4.1** Superadmin puede crear y gestionar empresas.
- **RF-4.2** Una instalacion puede tener una o varias empresas.
- **RF-4.3** Cada empresa puede configurar datos, usuarios, categorias, plantillas, firma y correo.
- **RF-4.4** Toda lectura/escritura de datos de empresa se aisla por `company_id`.

## RF-5. Usuarios
- **RF-5.1** Admin de empresa puede invitar usuarios.
- **RF-5.2** Roles: admin, supervisor, agent.
- **RF-5.3** Superadmin vive en `/admin`.
- **RF-5.4** Desactivar usuario invalida sesiones.
- **RF-5.5** No puede quedar una empresa sin admin activo.
- **RF-5.6** Email de usuario unico globalmente en la instalacion.
- **RF-5.7** Invitar un email existente a otra empresa reutiliza la misma cuenta y crea una nueva membresia.
- **RF-5.8** Un usuario puede tener roles distintos por empresa.
- **RF-5.9** Desactivar acceso a una empresa desactiva solo esa membresia.

## RF-6. Tickets
- **RF-6.1** Crear ticket por correo entrante.
- **RF-6.2** Crear ticket manualmente desde la app.
- **RF-6.3** Listar todos los tickets activos con filtros.
- **RF-6.4** Accion rapida en lista: asignarse.
- **RF-6.5** Ver detalle con hilo, adjuntos, eventos y metadatos.
- **RF-6.6** Cambiar estado, prioridad, categoria y responsable.
- **RF-6.7** Responder al usuario externo por correo.
- **RF-6.8** Anadir notas internas.
- **RF-6.9** Adjuntar archivos con validacion MIME/tamano.
- **RF-6.10** Fusionar tickets dentro de la misma empresa.
- **RF-6.11** Reabrir automaticamente cuando el cliente responde a ticket resuelto/cerrado.
- **RF-6.12** Cerrar tickets solo despues de `resolved`.

## RF-7. Correo
- **RF-7.1** SMTP global del sistema.
- **RF-7.2** Una cuenta de soporte por empresa.
- **RF-7.3** IMAP/SMTP generico.
- **RF-7.4** Gmail y Microsoft 365 desde v1 segun roadmap.
- **RF-7.5** Confirmacion automatica de recibido.
- **RF-7.6** Marcador visible `[DT-123]` en asunto.
- **RF-7.7** Threading por headers + marcador visible.
- **RF-7.8** Sanitizar HTML entrante.
- **RF-7.9** Bloquear imagenes externas por privacidad con opcion de abrir.
- **RF-7.10** Detectar auto-respuestas, no-reply y loops.
- **RF-7.11** Priorizar evitar duplicados.

## RF-8. Dashboard
- **RF-8.1** Mostrar resumen del dia.
- **RF-8.2** Mostrar tickets nuevos y activos.
- **RF-8.3** Mostrar abiertos, en progreso, urgentes, criticos, sin asignar y vencidos por SLA.
- **RF-8.4** Dashboard de agente personal por defecto.
- **RF-8.5** Admin/supervisor pueden ver metricas globales y por agente.
- **RF-8.6** Mostrar alertas operativas y de sistema cuando aplique.
- **RF-8.7** Si correo no esta configurado, mostrar onboarding.
- **RF-8.8** Al cambiar la empresa activa, dashboard, tickets, KB y notificaciones cambian de contexto.

## RF-9. Admin del sistema
- **RF-9.1** `/admin` muestra salud de PostgreSQL, Redis, storage, colas, correo y backups.
- **RF-9.2** `/admin` muestra version instalada.
- **RF-9.3** `/admin` avisa si existe nueva version estable en GitHub.
- **RF-9.4** `/admin` permite configurar backups.
- **RF-9.5** `/admin` muestra boton de rollback.
- **RF-9.6** `/admin` muestra logs y auditoria.
- **RF-9.7** Superadmin puede ver todas las empresas desde `/admin` sin usar el selector normal de empresa.

## RF-10. Base de conocimiento
- **RF-10.1** Admin y supervisor pueden crear, editar, publicar, archivar y borrar articulos internos.
- **RF-10.2** Agente puede consultar articulos publicados de su empresa.
- **RF-10.3** Render Markdown seguro con sanitizacion.
- **RF-10.4** Toda lectura/escritura se aisla por `company_id`.

## RF-11. Idiomas
- **RF-11.1** Espanol por defecto.
- **RF-11.2** Ingles disponible.
- **RF-11.3** Toda cadena visible pasa por archivos de traduccion.

## Requisitos opcionales v1
- **RF-OP-1** Datos demo opcionales.
- **RF-OP-2** Listado de sesiones activas.
- **RF-OP-3** Exportacion CSV.

## Nota importante
No implementar billing, planes comerciales, trial ni suscripciones en v1.
