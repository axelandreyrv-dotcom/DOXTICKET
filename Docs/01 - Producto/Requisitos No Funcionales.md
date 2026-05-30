# Requisitos No Funcionales — DoxTicket

## Proposito del documento
Definir atributos de calidad transversales: seguridad, rendimiento, disponibilidad, mantenibilidad, accesibilidad y operacion self-hosted.

## RNF-1. Seguridad
- **RNF-1.1** Aislamiento estricto por `company_id`.
- **RNF-1.2** Tenant activo derivado de una `membership` valida del usuario autenticado.
- **RNF-1.3** Password hashing con bcrypt o argon2id.
- **RNF-1.4** Rate limit en login, setup, reset, formularios y endpoints sensibles.
- **RNF-1.5** 2FA disponible para todos los roles.
- **RNF-1.6** Validacion backend en todos los endpoints.
- **RNF-1.7** Sanitizacion de HTML entrante.
- **RNF-1.8** Adjuntos fuera de `public/`.
- **RNF-1.9** Descargas protegidas por policy.
- **RNF-1.10** Bloqueo de produccion insegura: `APP_DEBUG=true`, falta `APP_KEY`, setup abierto, permisos peligrosos.

## RNF-2. Instalacion self-hosted
- **RNF-2.1** Docker Compose debe levantar una instalacion funcional.
- **RNF-2.2** Ubuntu manual debe estar documentado.
- **RNF-2.3** Debe funcionar con dominio o IP local en LAN/intranet.
- **RNF-2.4** `/setup` debe ser claro, seguro y bloqueable.

## RNF-3. Rendimiento
- **RNF-3.1** Operaciones comunes de tickets deben responder < 300 ms en instalaciones razonables.
- **RNF-3.2** Lista de tickets paginada y con indices.
- **RNF-3.3** Jobs de correo idempotentes y con locks por cuenta.
- **RNF-3.4** Evitar N+1 en dashboards y listados.

## RNF-4. Disponibilidad
- **RNF-4.1** Backups configurables desde `/admin`.
- **RNF-4.2** Restauracion documentada.
- **RNF-4.3** Logs persistidos y rotados.
- **RNF-4.4** Health panel en `/admin`.

## RNF-5. Actualizaciones
- **RNF-5.1** Version instalada visible.
- **RNF-5.2** Aviso de nueva version estable consultando GitHub.
- **RNF-5.3** No enviar datos sensibles al consultar actualizaciones.
- **RNF-5.4** Rollback visible, condicionado a version/backup disponible.
- **RNF-5.5** Verificar backup reciente antes de actualizar.

## RNF-6. Mantenibilidad
- **RNF-6.1** PSR-12 y Laravel Pint.
- **RNF-6.2** Tests para auth, tenant, setup, correo, tickets, adjuntos, backups, health y rollback.
- **RNF-6.3** Documentacion viva en `Docs/`.
- **RNF-6.4** Integraciones detras de adaptadores mockeables.

## RNF-7. Internacionalizacion
- **RNF-7.1** Espanol por defecto.
- **RNF-7.2** Ingles disponible.
- **RNF-7.3** Fechas y horas segun locale/timezone.

## RNF-8. Accesibilidad
- **RNF-8.1** Contraste WCAG AA.
- **RNF-8.2** Navegacion por teclado.
- **RNF-8.3** Labels en formularios.
- **RNF-8.4** Estados no dependen solo del color.

## RNF-9. Privacidad
- **RNF-9.1** Telemetria apagada por defecto.
- **RNF-9.2** Telemetria solo con consentimiento explicito en `/setup`.
- **RNF-9.3** No enviar contenido de tickets, asuntos, adjuntos, nombres, correos ni secretos.
- **RNF-9.4** Imagenes externas de correos bloqueadas por privacidad con opcion de abrir.

## RNF-10. Compatibilidad
- **RNF-10.1** Navegadores: ultimas dos versiones de Chrome, Firefox, Edge, Safari.
- **RNF-10.2** Resolucion minima: 360px.
- **RNF-10.3** Correos salientes compatibles con Gmail, Outlook web/desktop y Apple Mail.
