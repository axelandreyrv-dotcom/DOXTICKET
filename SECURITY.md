# Security Policy

## English

### Reporting a Vulnerability

Please report security vulnerabilities privately by email:

**axelandreyrv@outlook.com**

Do not open a public GitHub issue for vulnerabilities, exploit details, leaked secrets, authentication bypasses, tenant isolation issues, or any finding that could put a DoxTicket installation at risk.

When reporting, include as much of the following as possible:

- Affected version, branch, or commit.
- Installation method, such as Docker Compose or manual Ubuntu setup.
- Clear reproduction steps.
- Expected and actual behavior.
- Relevant logs, screenshots, or proof of concept details.
- Whether the issue affects authentication, authorization, tenant isolation, email ingestion, file uploads, backups, or admin access.

### Scope

Security-sensitive areas include:

- Authentication, password reset, and two-factor authentication.
- Tenant isolation and `company_id` enforcement.
- Superadmin access and impersonation.
- Incoming and outgoing email processing.
- File uploads, attachment downloads, and private storage.
- Setup, upgrades, rollback, backups, and environment validation.
- Secrets handling, `.env` exposure, and configuration leaks.

### Response

We will review reports as quickly as possible and may contact you for clarification. If the vulnerability is confirmed, we will prioritize a fix and publish release notes with appropriate detail after users have a safe upgrade path.

At this stage, DoxTicket does not operate a paid bug bounty program.

### Responsible Disclosure

Please give the project maintainers reasonable time to investigate and release a fix before disclosing the issue publicly.

---

## Espanol

### Reportar una Vulnerabilidad

Por favor reporta vulnerabilidades de seguridad de forma privada por correo:

**axelandreyrv@outlook.com**

No abras un issue publico en GitHub para vulnerabilidades, detalles de explotacion, secretos filtrados, saltos de autenticacion, fallos de aislamiento entre empresas o cualquier hallazgo que pueda poner en riesgo una instalacion de DoxTicket.

Al reportar, incluye todo lo que puedas:

- Version, rama o commit afectado.
- Metodo de instalacion, como Docker Compose o instalacion manual en Ubuntu.
- Pasos claros para reproducir el problema.
- Comportamiento esperado y comportamiento actual.
- Logs, capturas o detalles de prueba de concepto relevantes.
- Si el problema afecta autenticacion, autorizacion, aislamiento por tenant, correo entrante, subida de archivos, backups o acceso admin.

### Alcance

Las areas sensibles de seguridad incluyen:

- Autenticacion, recuperacion de contrasena y doble factor.
- Aislamiento por tenant y aplicacion de `company_id`.
- Acceso superadmin e impersonacion.
- Procesamiento de correo entrante y saliente.
- Subida de archivos, descarga de adjuntos y almacenamiento privado.
- Setup, actualizaciones, rollback, backups y validacion del entorno.
- Manejo de secretos, exposicion de `.env` y fugas de configuracion.

### Respuesta

Revisaremos los reportes lo mas pronto posible y podriamos contactarte para pedir aclaraciones. Si la vulnerabilidad se confirma, se priorizara una correccion y se publicaran notas de version con el detalle apropiado cuando exista una ruta segura de actualizacion para los usuarios.

En esta etapa, DoxTicket no opera un programa pagado de bug bounty.

### Divulgacion Responsable

Por favor da a los mantenedores del proyecto un tiempo razonable para investigar y publicar una correccion antes de divulgar el problema publicamente.
