# Multiempresa Self-Hosted — DoxTicket

## Proposito del documento
Definir como DoxTicket soporta multiples empresas en una misma instalacion self-hosted.

## Modelo elegido
- Single database.
- Shared schema.
- Tenant por `company_id`.
- Usuarios globales + membresias por empresa.
- Sin subdominios por empresa en v1.
- Login centralizado.

## Por que mantener multiempresa en self-hosted
- Un departamento de TI puede separar sociedades, sedes, clientes internos o unidades.
- Un MSP puede administrar varios clientes en una instalacion propia.
- El superadmin de la instalacion mantiene control central.

## Componentes

### `companies`
- Representa una empresa/sociedad/unidad dentro de la instalacion.
- La empresa inicial se crea en `/setup`.

### `users`
- Identidad global.
- Email unico globalmente en la instalacion.
- Puede ser superadmin.
- Puede tener cero, una o muchas membresias de empresa.

### `memberships`
- Relaciona `users` con `companies`.
- Guarda rol por empresa: `admin|supervisor|agent`.
- Permite que el mismo usuario tenga roles distintos por empresa.
- Puede desactivarse sin desactivar la cuenta global del usuario.

### Tablas multiempresa
Toda tabla con datos de una empresa lleva `company_id`.

## Middleware de tenant
1. Tras autenticacion, si el usuario entra a `/app`, debe seleccionar una membresia activa.
2. La sesion guarda `active_membership_id`.
3. `EnsureTenantContext` carga esa membresia, valida que pertenece al usuario y que esta activa.
4. Se establece `Tenant::currentCompany()` y `Tenant::currentMembership()`.
5. Si es superadmin, no se aplica tenant global en `/admin`.
6. La app rechaza acceso a `/app` si no hay membresia activa valida.

## Global scope
- `BelongsToCompany` aplica scope por `company_id`.
- `withoutTenant()` solo se permite en codigo admin auditado.

## Policies
Cada modelo principal valida:
1. Recurso pertenece al `company_id` actual.
2. `memberships.role` permite la accion.

## Setup de empresa inicial
1. `/setup` pide nombre de empresa/sociedad inicial.
2. Si se deja vacio, puede usar "Mi empresa".
3. Crea categorias, plantillas y configuracion base.
4. Crea membresia `admin` para el superadmin inicial en esa empresa.

## Estados sugeridos de empresa
- `active`
- `disabled`
- `archived`

No usar estados comerciales de trial/pago en v1.

## Tests obligatorios
- Usuario de empresa A no puede ver recursos de empresa B.
- Usuario con membresias A y B solo ve datos de la empresa activa.
- Listados filtran por `company_id`.
- Superadmin puede ver cross-tenant desde `/admin`.
- `company_id` enviado por request no puede cambiar el tenant.
- Desactivar una membresia solo quita acceso a esa empresa.

## Relacion con otros documentos
- `04 - Seguridad/Modelo de Seguridad.md`
- `03 - Base de Datos/Tablas.md`
- `05 - Módulos/Empresas.md`
- `05 - Módulos/Superadmin.md`
