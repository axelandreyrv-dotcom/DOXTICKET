# Resumen del Proyecto — DoxTicket

## Proposito del documento
Describir que es DoxTicket, para quien existe, que problema resuelve y cual es la vision actual del proyecto.

## Que es DoxTicket
DoxTicket es un **helpdesk IT open source self-hosted** para departamentos de TI que quieren controlar sus datos, correo e infraestructura.

Cada instalacion puede manejar una o varias empresas internas mediante aislamiento por `company_id`. Los usuarios son identidades globales y acceden a empresas mediante `memberships`, por lo que una persona puede pertenecer a varias empresas con roles distintos. El canal principal es el correo: los mensajes entrantes se convierten en tickets, se asignan a agentes, se responden desde la app y se atienden desde un inbox operativo en Tickets.

## Marca
- Nombre comercial: **DoxTicket**
- Sitio oficial futuro: **doxticket.com** como hub del proyecto.
- Seguridad: **axelandreyrv@outlook.com**
- Carpeta de marca: `Brand/`
- Logo principal: `Brand/DoxTicketSVG.svg`
- Toda instalacion debe mantener **Powered by DoxTicket**.

## Problema que resuelve
Muchos departamentos de TI administran soporte desde bandejas compartidas de correo. Esto provoca tickets perdidos, falta de responsable, poca trazabilidad, dificultad para medir tiempos y dependencia de herramientas externas cerradas.

DoxTicket mantiene el correo como canal principal, pero le agrega estados, responsables, prioridades, historial, adjuntos, auditoria, workspace de Tickets y control self-hosted.

## Publico objetivo
- Departamentos de TI que quieren autoalojar su helpdesk.
- Empresas con requisitos de privacidad o control interno.
- Equipos que prefieren software open source y auditable.
- MSPs o areas internas que necesitan separar varias empresas/unidades en una instalacion.

## Pilares del producto
1. **Open source self-hosted** bajo licencia AGPLv3.
2. **Control y privacidad**: datos, correo y adjuntos bajo infraestructura del usuario.
3. **Multiempresa real** por `company_id`.
4. **Membresias por empresa** con selector claro de empresa activa.
5. **Correo entrante estable** como prioridad v1.
6. **Interfaz minimalista, clara y calmada**, orientada a saber que atender ahora.
7. **Seguridad por defecto**: tenant isolation, validacion, 2FA opcional, rate limiting, adjuntos privados y setup seguro.
8. **Operacion administrable**: Docker Compose, health panel, backups, version instalada, aviso de update y rollback.

## Alcance general v1
- Instalacion Docker Compose.
- Documentacion de instalacion manual en Ubuntu.
- Setup inicial en `/setup`.
- Login centralizado en `/login`.
- App del cliente bajo `/app/*`.
- Panel superadmin en `/admin`.
- Idiomas: espanol por defecto e ingles disponible.
- Correo: cuenta global SMTP del sistema + una cuenta de soporte por empresa.
- Tickets por correo, tickets manuales, workspace de Tickets, SLA, KB interna, adjuntos, fusion de tickets.
- Donaciones discretas: PayPal, GitHub Sponsors y Buy Me a Coffee.

## Fuera de v1
- Billing integrado, planes pagados, trial y suscripciones.
- DoxTicket Cloud u hosting oficial prometido.
- Portal de usuario final.
- Chat en vivo.
- Subtickets, tickets padre-hijo y division de tickets.
- Plugins publicos.

## Relacion con otros documentos
- `Objetivos.md` — metas del proyecto.
- `Alcance v1.md` — que entra y que queda fuera.
- `Roadmap.md` — fases planificadas.
- `01 - Producto/` — requisitos, roles y flujos.
- `02 - Arquitectura/` — diseno tecnico.
