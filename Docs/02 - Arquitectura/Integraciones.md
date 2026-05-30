# Integraciones — DoxTicket

## Proposito del documento
Listar integraciones externas de DoxTicket y sus consideraciones de seguridad.

## Integraciones v1
- SMTP global del sistema.
- SMTP + IMAP por empresa.
- Gmail via OAuth/API.
- Microsoft 365 via OAuth/API.
- GitHub Releases para aviso de nueva version.
- Donaciones via enlaces externos.
- Telemetria opcional anonima.
- S3 compatible opcional/futuro para adjuntos/backups.

## 1. SMTP global del sistema

### Proposito
Enviar invitaciones, recuperacion de contrasena, alertas del sistema y notificaciones administrativas.

### Seguridad
- Secretos en `.env`.
- Puede omitirse durante setup.
- Health check en `/admin`.

## 2. SMTP + IMAP por empresa

### Proposito
Permitir que cada empresa use su propia cuenta de soporte.

### Reglas v1
- Una cuenta por empresa.
- Credenciales cifradas en BD.
- Test de conexion antes de activar.
- Lock por cuenta durante ingesta.

## 3. Gmail
- OAuth 2.0.
- Tokens cifrados.
- Scopes minimos necesarios.
- Adaptador independiente y mockeable.

## 4. Microsoft 365
- OAuth 2.0 / Microsoft Graph.
- Tokens cifrados.
- Adaptador independiente y mockeable.

## 5. GitHub Releases

### Proposito
Consultar si hay una version estable nueva.

### Seguridad y privacidad
- No enviar nombres de empresas, correos, tickets ni secretos.
- Consultar releases publicas del repositorio oficial.
- Guardar resultado localmente para mostrarlo en `/admin`.

## 6. Donaciones
- PayPal.
- GitHub Sponsors.
- Buy Me a Coffee.

Solo enlaces externos. No almacenar pagos ni datos financieros.

## 7. Telemetria opcional
- Apagada por defecto.
- Activacion explicita en `/setup`.
- Datos permitidos: version, metodo de instalacion, sistema operativo/container, conteos aproximados y anonimos.
- Datos prohibidos: nombres, correos, asuntos, cuerpos, adjuntos, IPs publicas, secretos.

## 8. S3 compatible
- Opcional/futuro.
- Usar driver de Laravel.
- Adjuntos y backups deben seguir protegidos por policies o URLs firmadas.

## Fuera de v1
- Billing integrado.
- Portales o flujos de pago comercial.
- Webhooks de pago.
- Marketplace de integraciones.

## Relacion con otros documentos
- `04 - Seguridad/Gestión de Secretos.md`
- `05 - Módulos/Correo.md`
- `05 - Módulos/Superadmin.md`
- `02 - Arquitectura/Colas y Jobs.md`
