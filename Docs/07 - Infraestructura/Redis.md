# Redis — DoxTicket

## Proposito
Documentar Redis para cache, sesiones y colas.

## Version
- Redis 7+.

## Usos
| Uso | Driver |
|---|---|
| Cache | redis |
| Sesiones | redis |
| Colas | redis |

## Docker
Redis corre como servicio interno. No exponer al exterior.

## Ubuntu manual
```bash
apt install redis-server -y
```

Configurar:
```
bind 127.0.0.1 -::1
requirepass <password>
```

## Colas
- `critical`
- `mail`
- `default`
- `low`

## Health
`/admin/health` debe verificar:
- conexion,
- latencia basica,
- workers,
- failed jobs.

## Relacion
- `02 - Arquitectura/Colas y Jobs.md`
