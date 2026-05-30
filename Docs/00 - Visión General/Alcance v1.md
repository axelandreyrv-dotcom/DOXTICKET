# Alcance v1 — DoxTicket

## Proposito del documento
Definir con precision que entra en la primera version usable de DoxTicket y que queda fuera.

## Incluido en v1

### Proyecto open source
- Licencia AGPLv3.
- Repositorio GitHub.
- README bilingue.
- `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`.
- GitHub Releases e imagenes Docker versionadas.
- Donaciones discretas: PayPal, GitHub Sponsors y Buy Me a Coffee.

### Instalacion
- Docker Compose como camino principal.
- Servicios en Docker: app, web server, PostgreSQL, Redis, workers y scheduler.
- Documentacion de Ubuntu manual.
- `/setup` como instalador inicial:
  - Idioma primero.
  - Validacion de PostgreSQL, Redis, storage, permisos, `APP_KEY`, `APP_DEBUG`.
  - Creacion de superadmin.
  - Creacion de empresa inicial.
  - Configuracion opcional del SMTP global.
  - Opcion explicita para activar telemetria anonima.
  - Bloqueo automatico al finalizar.
- Funcionamiento en LAN/intranet con IP local o dominio.

### App del cliente (`/app/*`)
- Dashboard operativo.
- Lista de todos los tickets activos con filtros.
- Detalle de ticket con hilo, adjuntos, eventos y metadatos.
- Respuesta a ticket por correo.
- Notas internas.
- Selector de empresa activa para usuarios con varias membresias.
- Busqueda global limitada a la empresa activa.
- Notificaciones in-app separadas por empresa.
- Configuracion de empresa, usuarios, categorias, correo, plantillas, firma y SLA.
- Base de conocimiento interna.

### Tickets
- Creacion por correo como canal principal.
- Creacion manual como canal secundario.
- Estados: Nuevo, Abierto, En progreso, En espera del cliente, En espera interna, Resuelto, Cerrado, Reabierto, Fusionado.
- Prioridad decidida por el agente.
- Asignacion manual.
- Accion rapida desde lista: asignarse.
- Fusion de tickets por agentes, supervisores y admins.
- Soft delete interno; vista de papelera puede quedar despues de la primera version usable.

### Correo
- SMTP global del sistema para invitaciones, resets y alertas.
- Una cuenta de soporte por empresa.
- IMAP/SMTP generico.
- Gmail y Microsoft 365 planificados desde v1.
- Confirmacion automatica de recibido.
- Marcador visible `[DT-123]` en asunto.
- Threading por headers y marcador visible.
- Sanitizacion de HTML.
- Bloqueo de imagenes externas por privacidad con opcion de abrirlas.
- Prevencion de loops.
- Adjuntos con validacion y almacenamiento privado.
- Prioridad: evitar duplicados.

### Superadmin (`/admin`)
- Gestion de empresas.
- Gestion de usuarios superadmin.
- Salud del sistema: PostgreSQL, Redis, storage, colas, correo, backups, version instalada.
- Aviso de nueva version estable consultando GitHub.
- Boton de rollback visible.
- Configuracion de backups desde admin.
- Logs generales y auditoria.

### Idiomas
- Espanol por defecto.
- Ingles disponible.

### Diseno
- Interfaz minimalista, clara, calmada y profesional.
- Modo claro como experiencia principal.
- Modo oscuro queda fuera del compromiso v1 salvo que no retrase la version usable.
- Branding DoxTicket y `Powered by DoxTicket` preservados.

## Fuera de v1
- Billing integrado.
- Planes comerciales dentro de la app.
- Trial de pago.
- DoxTicket Cloud.
- Demo publica online.
- Portal web para usuarios finales.
- Chat en vivo.
- Subdominios por empresa.
- Subtickets, tickets padre-hijo o division de tickets.
- Plugins/extensiones publicas.
- Marketplace de integraciones.
- Reportes avanzados personalizables.
- Importacion masiva desde competidores.

## Criterios para mover algo a v1
Una funcionalidad solo entra a v1 si:
1. Mejora instalacion, seguridad, correo entrante o gestion basica de tickets.
2. No retrasa el primer release usable de forma significativa.
3. No rompe el modelo multiempresa por `company_id`.
4. Tiene tests o una ruta clara de validacion.
