# Guia Operativa — DoxTicket

## Proposito
Guia corta para usar una instalacion DoxTicket v1 durante QA o uso interno temprano.

## Primer ingreso
1. Abrir la URL de la instalacion.
2. Si `/setup` esta pendiente, crear empresa inicial y superadmin.
3. Entrar por `/login`.
4. Si el usuario pertenece a varias empresas, elegir la empresa activa.
5. Trabajar desde `/app/tickets`.

## Tickets
- `Tickets` es la pantalla principal.
- Usar el buscador para clave visible, asunto o correo.
- Abrir el ticket desde la clave o asunto.
- Usar `Asignarme` para tomar un ticket.
- Cambiar estado, prioridad, tipo y agente desde el panel de propiedades.
- Responder al solicitante desde el bloque de respuesta.
- Subir adjuntos solo cuando sean necesarios; DoxTicket bloquea tipos peligrosos.
- Agregar notas internas para contexto del equipo.

## Seguridad personal
- Entrar a `/app/settings`.
- En `Verificacion 2FA`, escribir la contrasena actual y preparar 2FA.
- Registrar el secreto en una app autenticadora.
- Confirmar el codigo.
- Guardar los codigos de recuperacion fuera de DoxTicket.

## Admin
- `/admin` es solo para superadmins.
- Revisar health antes de pruebas fuertes.
- Configurar SMTP global para invitaciones y resets reales.
- Configurar correo de soporte por empresa desde `/app/settings`.
- Ejecutar backup antes de actualizar o tocar datos importantes.

## Correo
- Con `MAIL_MAILER=log`, los correos no salen; quedan en logs para QA.
- Para correo real, configurar SMTP global y la cuenta de soporte del tenant.
- Probar IMAP/SMTP desde Settings antes de depender de la ingesta.
