# DoxTicket

**Helpdesk open source self-hosted para departamentos de TI.**

---

## Descripcion

DoxTicket es una plataforma de tickets IT open source para equipos que quieren mantener el control de sus datos, correo y operacion. Esta pensada para instalarse en infraestructura propia mediante Docker Compose o instalacion manual en Ubuntu.

El canal principal es el correo: DoxTicket recibe mensajes por IMAP/API, los convierte en tickets, permite asignarlos, responderlos por SMTP/API y medir el trabajo del equipo desde un dashboard claro.

---

## Objetivo

Construir un helpdesk IT open source, multiempresa, seguro y facil de autoalojar, con foco en correo entrante estable, privacidad, trazabilidad y una interfaz minimalista.

---

## Modelo del proyecto

- **Tipo:** open source self-hosted.
- **Licencia:** AGPLv3.
- **Publicacion:** GitHub Releases + imagenes Docker versionadas.
- **Base de datos oficial:** PostgreSQL.
- **Cache / colas:** Redis.
- **Framework:** Laravel 13.x sobre PHP 8.3+.
- **Frontend:** Blade + Livewire + Tailwind CSS 4.1+.
- **Panel admin:** Filament 5.x.
- **Instalacion principal:** Docker Compose.
- **Instalacion alternativa:** Ubuntu Server manual documentado.

---

## Rutas principales

| Ruta | Descripcion |
|---|---|
| `/` | Entrada publica de la instalacion |
| `/setup` | Instalador inicial, bloqueado tras finalizar |
| `/login` | Login centralizado |
| `/logout` | Cierre de sesion |
| `/app/companies` | Selector de empresa activa |
| `/app/dashboard` | Dashboard del tenant |
| `/app/tickets` | Gestion de tickets |
| `/app/tickets/create` | Creacion manual de ticket |
| `/app/tickets/{ticket}` | Detalle de ticket por id interno o clave visible `DT-123` |
| `/app/tickets/{ticket}/messages` | Agregar nota interna al ticket |
| `/app/tickets/{ticket}/status` | Cambiar estado del ticket |
| `/app/settings` | Configuracion del tenant |
| `/app/settings/mail` | Guardado de cuenta IMAP/SMTP del tenant |
| `/admin` | Panel superadmin de la instalacion |

---

## Funciones v1

- Multiempresa por `company_id`.
- Usuarios globales con email unico y membresias por empresa.
- Un usuario puede pertenecer a varias empresas con roles distintos.
- Una cuenta de soporte por empresa.
- Configuracion base de cuenta IMAP/SMTP por empresa.
- SMTP global del sistema para invitaciones, reset y alertas.
- IMAP/SMTP generico, Gmail y Microsoft 365 planificados desde v1.
- Confirmacion automatica de recibido.
- Marcador visible en asunto: `[DT-123]`.
- Dashboard operativo para saber que atender ahora.
- Tickets manuales como flujo secundario.
- Detalle de ticket con hilo, metadatos, eventos, notas internas y cambio de estado.
- Fusion de tickets.
- Adjuntos en almacenamiento privado.
- Modo claro como experiencia principal.
- Espanol por defecto, ingles disponible.
- Panel `/admin` con salud del sistema, backups, version instalada, aviso de nueva version y rollback.
- Telemetria opcional, apagada por defecto y activada explicitamente en `/setup`.
- Donaciones discretas: PayPal, GitHub Sponsors y Buy Me a Coffee.

---

## Fuera de v1

- Billing y suscripciones comerciales.
- DoxTicket Cloud u hosting oficial prometido.
- Portal de usuario final.
- Chat en vivo.
- Subtickets, tickets padre-hijo o division de tickets.
- Plugins/extensiones publicas.
- Marketplace de integraciones.

---

## Branding

- **Logo:** `Brand/DoxTicketSVG.svg`
- Toda instalacion debe mantener el texto **Powered by DoxTicket** en el footer.
- `doxticket.com` se proyecta como hub oficial del proyecto: documentacion, releases, seguridad, roadmap y donaciones.

---

## Seguridad

- Nunca incluir secretos, claves de API ni contrasenas en el repositorio.
- Usar `.env` para toda configuracion sensible.
- El instalador y el panel admin deben validar entorno, permisos, `APP_KEY`, `APP_DEBUG`, Redis, PostgreSQL, storage y colas.
- Ver `SECURITY.md`, `AGENTS.md` y `Docs/04 - Seguridad/` para reglas completas.

---

## Contribuciones

Las primeras contribuciones externas priorizadas son reportes y correcciones de bugs. El proyecto debe incluir guias claras (`CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`) antes del primer release publico.

---

## Nota legal

DoxTicket se publica bajo AGPLv3; ver `LICENSE`. No copiar codigo, diseno, textos, logos, iconos ni flujos de competidores. El nombre y logo DoxTicket pertenecen al proyecto y deben usarse segun las guias de marca.
