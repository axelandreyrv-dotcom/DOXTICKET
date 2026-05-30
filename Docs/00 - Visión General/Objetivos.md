# Objetivos — DoxTicket

## Proposito del documento
Definir que queremos lograr con DoxTicket como producto open source, proyecto tecnico y futuro producto comercial.

## Objetivos de producto
1. Construir un helpdesk open source self-hosted para departamentos de TI.
2. Hacer del correo entrante el canal principal de creacion de tickets, con threading estable y soporte de adjuntos.
3. Ofrecer una interfaz minimalista, rapida, bonita y entendible, sin apariencia generica de IA.
4. Mantener soporte multiempresa por `company_id` incluso en instalaciones self-hosted.
5. Permitir que un mismo usuario pertenezca a varias empresas mediante membresias y selector de empresa activa.
6. Soportar espanol por defecto e ingles desde el inicio.

## Objetivos de comunidad
1. Publicar el proyecto en GitHub con README bilingue, releases claras y guias de contribucion.
2. Aceptar primero contribuciones enfocadas en bugs, seguridad y estabilidad.
3. Mantener `SECURITY.md`, `CONTRIBUTING.md` y `CODE_OF_CONDUCT.md`.
4. Mantener `Powered by DoxTicket` en instalaciones para preservar atribucion del proyecto.

## Objetivos tecnicos
1. Docker Compose como instalacion principal y Ubuntu manual como alternativa documentada.
2. PostgreSQL como unica base de datos oficial.
3. Redis para cache, sesiones y colas.
4. Laravel 13.x + Blade + Livewire + Filament 5.x como stack principal.
5. Correo entrante estable con IMAP/SMTP, Gmail y Microsoft 365.
6. Setup seguro en `/setup`, bloqueado tras finalizar.
7. Panel `/admin` con salud del sistema, backups, version instalada, aviso de nueva version y rollback.

## Objetivos de calidad
1. **Seguridad**: aislamiento por tenant, validacion estricta, rate limiting, adjuntos privados, setup seguro y checklist de produccion.
2. **Disponibilidad**: backups configurables desde admin, logs rotados y plan de recuperacion.
3. **Rendimiento**: lista de tickets paginada, indices adecuados y jobs idempotentes.
4. **Mantenibilidad**: convenciones claras, tests de logica critica y documentacion viva.

## Metricas de exito
- Instalaciones activas (si el usuario activa telemetria opcional).
- Versiones instaladas y ritmo de actualizacion (solo telemetria anonima).
- Bugs reportados/resueltos.
- Tickets procesados por correo en instalaciones de prueba.
- Estabilidad de jobs de correo entrante.

## Objetivo comercial futuro
DoxTicket puede convertirse en producto comercial futuro mediante hosting oficial, soporte profesional, instalacion administrada o servicios empresariales. No se promete DoxTicket Cloud en v1.

## Lo que NO buscamos en v1
- Producto cerrado alojado.
- Billing integrado.
- Marketplace de plugins.
- Portal de usuario final.
- Chat en vivo.
- Aplicacion movil nativa.
