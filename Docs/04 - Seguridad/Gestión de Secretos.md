# Gestion de Secretos — DoxTicket

## Proposito del documento
Definir como se almacenan, despliegan y rotan secretos.

## Principios
1. Ningun secreto en el repositorio.
2. `.env` fuera de Git.
3. `.env.example` solo con placeholders.
4. Secretos por empresa cifrados en BD.
5. Logs sin secretos.

## Secretos de plataforma
- `APP_KEY`
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- SMTP global (`MAIL_*`)
- OAuth Gmail/Microsoft.
- S3 si se usa.
- Token/endpoint de telemetria si aplica.

## Secretos por empresa
- Password SMTP/IMAP.
- OAuth access/refresh tokens.

Todos cifrados con Laravel encryption.

Estado implementado actual: `mail_accounts.password_encrypted` usa cast cifrado de Eloquent; el formulario de settings nunca renderiza la contrasena guardada.

## Docker
- `.env` local del usuario.
- No versionar volumenes con datos reales.
- Documentar permisos recomendados.

## Ubuntu manual
- `.env` con permisos `600`.
- Owner del usuario de la app.
- Nginx bloquea `.env`.

## APP_KEY
Critico: si se pierde, no se pueden descifrar secretos en BD.

## Logs
No loguear campos:
- password
- token
- secret
- key
- authorization
- cookie

## Repositorio
- `.gitignore` incluye `.env`, `.env.*`, storage y caches.
- CI debe tener scanner de secretos.

## Rotacion
- SMTP global: cambiar `.env`, limpiar cache, probar health.
- Credenciales por empresa: actualizar desde settings, re-cifrar.
- APP_KEY: solo en compromiso, requiere plan especial de re-cifrado.

## Relacion con otros documentos
- `Modelo de Seguridad.md`
- `02 - Arquitectura/Integraciones.md`
- `07 - Infraestructura/Deploy.md`
