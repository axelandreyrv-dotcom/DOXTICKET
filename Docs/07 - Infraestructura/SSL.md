# SSL — DoxTicket

## Proposito
Documentar HTTPS para instalaciones con dominio publico.

## Decision
- HTTPS es recomendado para produccion publica.
- LAN/intranet puede funcionar por HTTP local sin advertencia molesta.

## Dominio publico
Usar Let's Encrypt o el proxy elegido.

Ejemplo:
```bash
certbot --nginx -d ejemplo.com --email admin@ejemplo.com
```

## Docker
Si se usa Caddy, puede gestionar certificados automaticamente.

## Seguridad
- Cookies `Secure` solo cuando hay HTTPS.
- No forzar HSTS en entornos locales.
- HSTS solo si el administrador entiende el impacto.

## Relacion
- `Nginx.md`
- `Deploy.md`
