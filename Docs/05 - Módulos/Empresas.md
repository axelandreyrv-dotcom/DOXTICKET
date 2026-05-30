# Modulo Empresas — DoxTicket

## Proposito
Describir empresas/sociedades/unidades dentro de una instalacion.

## Concepto
En self-hosted, una empresa no es un cliente comercial de DoxTicket. Es una organizacion interna dentro de la instalacion.

Puede representar:
- Una sociedad legal.
- Una sede.
- Un departamento.
- Un cliente administrado por un MSP.

## Empresa inicial
- Se crea en `/setup`.
- Si el admin no quiere pensar en multiempresa, puede usar un nombre simple como "Mi empresa".
- No cambia el nombre de la app.

## Estados
- `active`
- `disabled`
- `archived`

No hay estados de trial/pago en v1.

## Configuracion
- Nombre.
- Pais/telefono opcional.
- Logo opcional.
- Locale.
- Categorias.
- Plantillas.
- Firma.
- SLA.
- Correo de soporte.

## Reglas
- Toda empresa aisla sus datos por `company_id`.
- Superadmin puede crear/editar/desactivar empresas desde `/admin`.
- Admin de empresa gestiona su configuracion desde `/app/settings`.
- Los usuarios acceden a una empresa mediante memberships.
- La empresa activa en la app sale de la membership seleccionada.

## Auditoria
- `company.created`
- `company.updated`
- `company.disabled`
- `company.archived`
- `company.reactivated`

## Relacion con otros documentos
- `Usuarios.md`
- `Superadmin.md`
- `02 - Arquitectura/Multiempresa Self-Hosted.md`
