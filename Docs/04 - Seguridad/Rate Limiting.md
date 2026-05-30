# Rate Limiting — DoxTicket

## Proposito del documento
Definir limites para mitigar abuso, brute force, spam y loops.

## Mecanismo
- Laravel RateLimiter.
- Redis.
- Identificacion por IP + usuario/email cuando aplique.

## Publicos

### `/login`
- 5 intentos por IP+email cada 60 segundos.
- 20 intentos por IP cada 5 minutos.

### `/password/forgot`
- 3 solicitudes por IP+email cada hora.
- Respuesta generica.

### `/setup`
- Solo accesible mientras no esta completado.
- Rate limit por IP.
- Bloqueo automatico tras instalar.

### Formularios publicos
- 5 envios por IP cada hora.

## Autenticados

### Tickets manuales
- Limite configurable por instalacion.
- Default razonable para evitar abuso accidental.

### Adjuntos
- Limite por usuario y por empresa.
- Validacion MIME/tamano siempre.

### Configuracion sensible
- Cambios de correo, backups, update/rollback y telemetria con rate limit y auditoria.

## Correo

### Ingesta
- 1 job concurrente por `mail_account_id`.

### SMTP
- Rate limit por cuenta.
- Rate limit por destinatario para evitar floods.

### Loops
- Si hay 3+ correos repetidos en < 60 segundos entre la misma pareja, pausar auto-respuestas y registrar evento.

## Relacion con otros documentos
- `Autenticación.md`
- `02 - Arquitectura/Colas y Jobs.md`
- `05 - Módulos/Correo.md`
